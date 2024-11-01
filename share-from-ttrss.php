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
/*
    Plugin Name: Share from Tiny Tiny RSS
    Plugin URI: http://kjcoop.com/code/
    Description: Automatically create a Wordpress post of items you marked as "published" in Tiny Tiny RSS.
    Version: 0.9
    Text-Domain: share-from-ttrss
    Author: KJ Coop
    Author URI: http://kjcoop.com/
    License: GPL2
    License URI: https://www.gnu.org/licenses/gpl-2.0.txt
*/
// In creating this plugin, I referenced code in the following plugins:
// -Tiny Tiny Check, http://wordpress.org/plugins/tiny-tiny-check/
// -Scheduled Post Shift, http://www.dagondesign.com/articles/scheduled-post-shift-plugin-for-wordpress/

include_once('print_object.php');
include_once('class.curl.php');
include_once('class.ttrss_api.php');

$prefix = 'sf_ttrss_';

// Create the options we require
add_option($prefix.'base');
add_option($prefix.'user');
add_option($prefix.'password');

// Add a page to the options menu to get the TTRSS settings
add_action('admin_menu', $prefix.'option_page');

// Add a page to create posts
add_action('admin_menu', $prefix.'creation_page');

// Global variables we require
$sf_ttrss = sf_ttrss_connect();
$articles = null;

// Add a page to the menu where the user can set up options
function sf_ttrss_option_page() {
    global $prefix;

    if (function_exists('add_options_page')) {
        add_options_page('Share from Tiny Tiny RSS', 'Share from Tiny Tiny RSS', '2', __FILE__, $prefix.'options');
    }
}

// Add a page to the menu where the user can set up options
function sf_ttrss_creation_page() {
    global $prefix;
    if (function_exists('add_posts_page')) {
        add_posts_page('Create Post from Tiny Tiny RSS', 'Create Post from Tiny Tiny RSS', '2', __FILE__, $prefix.'create');
    }
}

function sf_ttrss_create() {
    global $prefix;

    // The user has not yet provided any credentials; nag them now.
    $base = get_option($prefix.'base');
    $user = get_option($prefix.'user');
    $password = get_option($prefix.'password');
    if (empty($base) || empty($user) || empty($password)) {
        ?><p>You must provide access to your Tiny Tiny RSS instance. You may do so <a href="options-general.php?page=share-from-ttrss/share-from-ttrss.php">here</a></p><?php
        return;
    }

    // If deal with posted values if they exist
    if (isset($_POST[$prefix.'create'])) {
        $unpublish = isset($_POST[$prefix.'unpublish']);
        $id = sf_ttrss_create_post($_POST[$prefix.'post_status'], $unpublish);
        if ($id === false) {
            ?><p>Could not create post. Did not unpublish stories.</p><?php
        } else {
            ?><div id="message" class="updated fade"><p><strong>Post created, <a href="<?php echo get_permalink($id); ?>" target="_blank">see it now.</a></strong></p></div><?php
        }
        return;
    }

    // We put the text together and if they like it, they can save it. We could
    // alternatively take them straight to the newly-created draft, but then we
    // don't know if they want to unpublish the newly-processed TTRSS stories.
    // Could use an option to always/never unpublish, but I think the it's
    // better to allow the user to make that decision on a per-post basis.
    ?>
    <h1>Share from Tiny Tiny RSS</h1>
    <p>Create a post based on the items you marked as "published" in Tiny Tiny RSS. Below is a rough preview of what it will look like.</p>
    <?php

    $base = get_option($prefix.'base');
    $user = get_option($prefix.'user');
    $password = get_option($prefix.'password');

    // todo: style blockquote
    ?>
    <blockquote><?php echo sf_ttrss_create_post('echo', false); ?></blockquote>
    <form action="<?php echo $_SERVER['SCRIPT_NAME'].'?page='.$_GET['page']; ?>" method="post">
        <p>
            <label for="<?php echo $prefix.'post_status'; ?>">Create post as:</label>
            <select name="<?php echo $prefix.'post_status'; ?>" id="<?php echo $prefix.'post_status'; ?>">
                <option value="draft">Draft</option>
                <option value="publish">Publish</option>
            </select>
        </p>
        <p>If you're happy with your post, you can publish it now. Alternatively, you can save it as a draft and tweak it. The draft will not be updated to reflect changes in your list of publish TTRSS posts.</p>
        <p>
            <input id="<?php echo $prefix.'unpublish'; ?>" name="<?php echo $prefix.'unpublish'; ?>" type="checkbox" value="1">
            <label for="<?php echo $prefix.'unpublish'; ?>">Mark articles unpublished in Tiny Tiny RSS once they have been added to a post.</label>
        </p>
        <div class="submit">
            <input type="submit" class="button button-primary"  name="<?php echo $prefix; ?>create" value="<?php _e('Create post'); ?>" />
        </div>
    </form>
    <?php
}

function sf_ttrss_describe_problematic_variable($var) {
    return gettype($var).' '.$var;
}

// Connect to the TTRSS API
function sf_ttrss_connect() {
    global $prefix;

    $base = get_option($prefix.'base');
    $user = get_option($prefix.'user');
    $password = get_option($prefix.'password');

    if ($base != '' && $user != '' && $password != '') {
        return new TTRSS_API($base, $user, $password);
    } else {
        return false;
    }
}

function sf_ttrss_test_connection() {
    global $ttrss;

    if ($ttrss == null) {
        $ttrss = sf_ttrss_connect();
    }

    return method_exists($ttrss, 'login') && $ttrss->login();
}

function sf_ttrss_get_published_articles() {
    // Don't check if we already have the articles - user might want updated
    // list.
    global $ttrss, $articles;
    $articles = $ttrss->get_published_articles();
    return $articles;
}

function sf_ttrss_create_post($format = 'echo', $unpublish = false) {
    $possible_formats = array('echo', 'draft', 'publish');
    if (!in_array($format, $possible_formats)) {
        throw new Exception('Do not know how to format post as '.$format);
    }

    global $ttrss, $articles, $prefix;

    // The way this is currently written, ttrss will always be null right now,
    // but who knows what the future may hold?
    if ($ttrss == null) {
        $ttrss = sf_ttrss_connect();
    }

    if ($ttrss == false) {
//        throw new Exception('Could not connect to your Tiny Tiny RSS installation at '.get_option($prefix.'base');
        return false;
    }

    if ($articles == null) {
        $articles = $ttrss->get_published_articles();
    }

    $body = '';

    // todo: if it's a feed like Fark or Hacker News where there's only a link
    // to the story and comments, provide links to both?
    foreach ($articles as $article) {

        if ($article->feed_title == '') {
            $title_prefix = '';
        } else {
            $title_prefix = $article->feed_title.' - ';
        }

        if ($article->title == '') {
            $title = '[No title]';
        } else {
            $title = $article->title;
        }

        if ($article->link != '') {
            $body .= '<p>'.$title_prefix.'<a href="'.$article->link.'">'.$title."</a></p>\n\n";
        }
    }

    // If we've indicated a desire to echo the body text, return it now.
    if ($format == 'echo') {
        return $body;
    }

    // Otherwise we must be creating a post.
    $my_post = array(
        'post_title' => 'Reading list for '.date('m-d-y'),
        'post_content' => $body,
        'post_status' => $format,

    );

    if ($body != '' && $id = wp_insert_post($my_post)) {

        if ($unpublish) {
            sf_ttrss_unpublish_all();
        }

        $ttrss->logout();
        return $id;
    } else {
        return false;
    }
}

function sf_ttrss_unpublish_all() {
    global $ttrss, $articles;

    if ($ttrss InstanceOf TTRSS_API && is_array($articles)) {
        $ids = array();
        foreach ($articles as $article) {
            $ids[] = $article->id;
        }
        return $ttrss->unpublish_articles(implode(',', $ids));
    } else {
        return false;
    }
}

function sort_availability($array) {
    $flat = array();
    $fin = array();

    // Get PHP to do the actual sorting. To do so, flatten the array to just the
    // key we're sorting by.
    foreach ($array as $name => $details) {
        $flat[$name] = $details['interval'];
    }

    asort($flat, SORT_NUMERIC);

    foreach ($flat as $name => $duration) {
        $fin[$name] = $array[$name];
    }

    return $fin;
}

// If a TTRSS install is at example.org, the api is at example.org/api. This
// adds /api if necessary and deals with trailing spaces and all that.
function sf_ttrss_base_to_api($base) {
    $suffix = '/api';

    // Only add suffix if they've attempted to give us usable information
    if ($base == '') {
        return $base;
    }

    // remove the trailing slash
    $base = rtrim($base, '/');

    // Check if it ends in api
    $end = substr($base, 0-strlen($suffix));

    // If not, add it at the end.
    if ($end != $suffix) {
        $base .= '/api';
    }

    return trailingslashit(htmlspecialchars_decode($base));

}

function sf_ttrss_options() {
    global $prefix;
    ?>
    <h1>Share from Tiny Tiny RSS</h1>
    <?php
    // They pressed the submit button
    if (isset($_POST['sf_ttrss_update'])) {

        // If they set a value for base, check if it ends in /api. If not, add
        // it
        if (isset($_POST[$prefix.'base'])) {
            $base = sf_ttrss_base_to_api($_POST[$prefix.'base']);
        } else {
            $base = '';
        }

        // Update the options
        // Originally used htmlspecialchars_decode, but the Wordpress codex
        // indicated it has a built-in function for sanitizing text fields, and
        // it will work better.
        update_option($prefix.'base', sanitize_text_field($base));
        update_option($prefix.'user', sanitize_text_field($_POST[$prefix.'user']));
        update_option($prefix.'password', sanitize_text_field($_POST[$prefix.'password']));

        // Test the connection. We could save some processing if we check that
        // all the credentials are non-empty, but I don't care that much.
        if (sf_ttrss_test_connection()) {
            $settings_worked = 'Updated your settings and successfully connected.';
        } else {
            $settings_worked = 'Updated your settings but could not connect using those credentials.';
        }

        // Print the results
        ?><div id="message" class="updated fade"><p><strong><?php echo $settings_worked; ?></strong></p></div><?php
    }

    // They're testing the connection
    if (isset($_GET['test_connection']) && $_GET['test_connection'] == 'true') {
        if (sf_ttrss_test_connection()) {
            ?><div id="message" class="updated fade"><p><strong>Your connection works.</strong></p></div><?php
        } else {
            ?><div id="message" class="updated fade"><p><strong>Your connection does not work.</strong></p></div><?php
        }
    }

    // They're creating a test post
    if (isset($_GET['test_post']) && $_GET['test_post'] == 'true') {
        if ($draft_id = sf_ttrss_create_post('draft', false)) {
            ?><div id="message" class="updated fade"><p><strong>Test post created, <a href="<?php echo get_permalink($draft_id); ?>" target="_blank">see it now.</a></strong></p></div><?php
        } else {
            ?><div id="message" class="updated fade"><p><strong>Could not create test post.</strong></p></div><?php
        }
    }

    // Create the form that allows them to do all the above
    $base = get_option($prefix.'base');
    $user = get_option($prefix.'user');
    $password = get_option($prefix.'password');
    ?>
    <p>Automatically create a Wordpress post of items you marked as "published" in Tiny Tiny RSS</p>
    <form action="<?php echo $_SERVER['SCRIPT_NAME'].'?page='.$_GET['page']; ?>" method="post">
        <p>
            <label for="<?php echo $prefix.'base'; ?>"><?php _e( 'URL to your Tiny Tiny RSS server:', 'share-from-ttrss' ); ?></label>
            <input id="<?php echo $prefix.'base'; ?>" name="<?php echo $prefix.'base'; ?>" type="text" value="<?php echo htmlspecialchars($base); ?>" />
        </p>
        <p>
            <label for="<?php echo $prefix.'user'; ?>"><?php _e( 'Username:', 'share-from-ttrss' ); ?></label>
            <input id="<?php echo $prefix.'user'; ?>" name="<?php echo $prefix.'user'; ?>" type="text" value="<?php echo htmlspecialchars($user); ?>" />
        </p>
        <p>
            <label for="<?php echo $prefix.'password'; ?>"><?php _e( 'Password:', 'share-from-ttrss' ); ?></label>
            <input id="<?php echo $prefix.'password'; ?>" name="<?php echo $prefix.'password'; ?>" type="password" value="<?php echo htmlspecialchars($password); ?>" />
        </p>
        <hr />

        <div class="submit">
            <input type="submit" class="button button-primary"  name="sf_ttrss_update" value="<?php _e('Update options'); ?>" />
        </div>
    </form>
    <?php if ($base != '' && $user != '' && $password != null) :?>
    <p><a href="<?php echo $_SERVER['SCRIPT_NAME'].'?page='.$_GET['page'].'&test_connection=true'; ?>">Test saved settings</a></p>
    <p><a href="<?php echo $_SERVER['SCRIPT_NAME'].'?page='.$_GET['page'].'&test_post=true'; ?>">Create a test post</a></p>
    <?php endif; ?>
    <?php
}
?>
