<?php
/*
    Share from Tiny Tiny RSS - Automatically create a Wordpress post of items
    you marked as "published" in Tiny Tiny RSS

    Copyright (C) 2013 KJ Coop

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// See Tiny Tiny RSS's API documention at:
// http://tt-rss.org/redmine/projects/tt-rss/wiki/JsonApiReference

class TTRSS_API extends CURL {

    protected $user;
    protected $password;

    private $sid;

    // Constructor primarily just gets the necessary background information.
    public function __construct($base, $user, $password) {
        // Password can be an empty string, as long as it's a string.
        if ($base == '' || $user == '' || $password === null) {
            throw new Exception('Must provide a valid URL, user and password. You provided '.sf_ttrss_describe_problematic_variable($base).', '.sf_ttrss_describe_problematic_variable($user).' and '.sf_ttrss_describe_problematic_variable($password));
        }

        parent::__construct($base);
        $this->user = $user;
        $this->password = $password;
    }

    // Most API calls require you to log in and have an SID. This function
    // provides the SID. If you're not yet logged in, it will do so.
    private function get_sid() {
        if (isset($this->sid) && $this->sid != null) {
            return $this->sid;
        } else {
            return $this->login();
        }
    }

    // Checks if you're logged in. Assumes once you've logged in, you'll have an
    // SID.
    private function is_logged_in() {
        return $this->get_sid() !== false;
    }

    // Log you in and return the sid or false.
    public function login() {
        // Don't check if we're already logged in. User says log in again, log
        // in.
        $obj = parent::execute_curl(array('op' => 'login', 'user' => $this->user, 'password' => $this->password));

        if (isset($obj->content->session_id) && isset($obj->content->session_id)) {
            $this->sid = $obj->content->session_id;
            return $this->sid;
        } else {
            error_log('Could not log in to '.parent::get_url().' with user '.$this->user.' and password '.$this->password);
            // Do not throw error if we couldn't log in, because the end user
            // should see a nice message, not a runtime error.
            return false;
        }
    }

    // Calls any API function except login.
    private function execute_not_login($postfields) {
        $postfields['sid'] = $this->get_sid();
        return parent::execute_curl($postfields);
    }

    // Get headlines. Relies on ttrss' API call getHeadlines:
    // http://tt-rss.org/redmine/projects/tt-rss/wiki/JsonApiReference#getHeadlines
    public function get_published_articles() {
        // API returns additional metadata. Strip it off.
//        return $this->execute_not_login(array('op' => 'getHeadlines', 'feed_id' => '-2'));
        $articles = $this->execute_not_login(array('op' => 'getHeadlines', 'feed_id' => '-2'));

        if (isset($articles->content)) {
            return $articles->content;
        } else {
            return false;
        }
    }

    // Get special feeds. Relies on ttrss' API call getFeeds:
    // http://tt-rss.org/redmine/projects/tt-rss/wiki/JsonApiReference#getFeeds
    public function get_special_feeds($which) {
        return $this->execute_not_login(array('op' => 'getFeeds', 'cat_id' => '-1'));
    }

    public function logout() {
        return $this->execute_not_login(array('op' => 'logout'));
    }

    public function unpublish_articles($ids) {
        return $this->execute_not_login(array('op' => 'updateArticle', 'article_ids' => $ids, 'mode' => 0, 'field' => 1));
    }
}
?>
