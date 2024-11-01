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

// See the documention for PHP's cURL library:
// http://www.php.net/CURL
class CURL {

    protected $url;
    protected $postfields = array();

    public function __construct($url, $postfields = null) {
        if ($url != '') {
            $this->url = $url;
        }
        // We don't care about its datatype or if its empty, just toss it on.
        if ($postfields != null) {
            $this->postfields = $postfields;
        }
    }

    // If you're looking to create $_POST['user'] = $username, call this
    // function with $key = 'user', $value = $username.
    public function add_post_field($key, $value) {
        if (is_string($key) && $key !== '') {
            $this->postfields[$key] = $value;
            return true;
        } else {
            throw new Exception('Tried to add a post field with invalid key: '.sf_ttrss_describe_problematic_variable($key));
            return false;
        }
    }

    // Post fields declared here will overwrite its member variables.
    // todo: is this the desired behavior? If we're just going to clobber them
    // here, do we even want to give users the option of creating them
    // elsewhere?
    public function execute_curl($postfields = null) {
        if ($postfields != null) {
            $this->postfields = $postfields;
        }

        // Must have a URL
        if ($this->url == null || $this->url == '') {
            throw new Exception('Called CURL library without a valid URL');
            return false;
        }

        // create a new cURL resource
        $ch = curl_init();

        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        // Note that although the PHP documention indicates you can send an array,
        // ttrss requires everything to be json encoded.
        //print_object(json_encode($this->postfields), 'json args about to be sent via curl');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->postfields));

        // grab URL and pass it to the browser
        $stuff = curl_exec($ch);

        // close cURL resource, and free up system resources
        curl_close($ch);

        // Return result
        if ($stuff === false) {
            // Don't throw an exception - we want program execution to continue
            // even if there was a problem.
            return false;
        } else {
            return json_decode($stuff);
        }
    }

    // This is mostly for testing, but since testing is never really over, I'm
    // leaving it in place.
    protected function get_url() {
        return $this->url;
    }
}
?>
