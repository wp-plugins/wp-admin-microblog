<?php
/*
Plugin Name: WP Admin Microblog
Plugin URI: http://mtrv.wordpress.com/microblog/
Description: Adds a microblog in your WordPress backend.
Version: 2.2.1
Author: Michael Winkler
Author URI: http://mtrv.wordpress.com/
Min WP Version: 3.3
Max WP Version: 3.4.1
*/

/*
   LICENCE
 
    Copyright 2010-2012  Michael Winkler

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
	
	
   You find the license text under:
   http://www.opensource.org/licenses/gpl-2.0.php

   LICENCE Information of included parts
   - document-new-6.png (Oxygen Icons 4.3.1 by http://www.oxygen-icons.org/) - Licence: LPGL
*/

// Define databases
global $wpdb;
$admin_blog_posts = $wpdb->prefix . 'admin_blog_posts';
$admin_blog_tags = $wpdb->prefix . 'admin_blog_tags';
$admin_blog_relations = $wpdb->prefix . 'admin_blog_relations';
$admin_blog_meta = $wpdb->prefix . 'admin_blog_meta';

// load microblog name
$wpam_blog_name = get_option('wp_admin_blog_name');
if ( $wpam_blog_name == false ) {
     $wpam_blog_name = 'Microblog';
}

// includes
require_once('wp_admin_microblog_screen.php');
require_once('wp_admin_microblog_settings.php');
require_once('wp_admin_microblog_messages.php');

// Define menu
function wpam_menu() {
   global $wpam_blog_name;
   global $wpam_admin_page;
   $wpam_admin_page = add_menu_page(__('Blog','wp_admin_blog'), $wpam_blog_name,'use_wp_admin_microblog', __FILE__, 'wpam_page', WP_PLUGIN_URL . '/wp-admin-microblog/images/logo.png');
   add_action("load-$wpam_admin_page", 'wpam_add_help_tab');
   add_submenu_page('wp-admin-microblog/wp-admin-microblog.php', __('Settings','wp_admin_blog'), __('Settings','wp_admin_blog'), 'administrator', 'wp-admin-microblog/settings.php', 'wpam_settings');
}

/** 
 * Secure variables
 * @param $var (STRING) the parameter you want to secure
 * @param $type (STRING) integer or string (default: string)
 * @return $var (STRING or INT) the secured parameter
*/
function wpam_sec_var($var, $type = 'string') {
   $var = htmlspecialchars($var);
   if ($type == 'integer') {
      settype($var, 'integer');
   }
   return $var;
}

/** 
 * Display media buttons
 * adapted from P2-Theme
*/
function wpam_media_buttons() {
   include_once ABSPATH . '/wp-admin/includes/media.php';
   ob_start();
   do_action( 'media_buttons' );
   return ob_get_clean();
}

/** 
 * Split the timestamp
 * @param $datum - timestamp
 * @return $split - ARRAY
 *
 * $split[0][0] => Year
 * $split[0][1] => Month 
 * $split[0][2] => Day
 * $split[0][3] => Hour 
 * $split[0][4] => Minute 
 * $split[0][5] => Second
*/ 
function wpam_datumsplit($datum) {
   $preg = '/[\d]{2,4}/'; 
   $split = array(); 
   preg_match_all($preg, $datum, $split); 
   return $split; 
}

/** 
 * WP Admin Microblog Page Menu (= teachPress Admin Page Menu)
 * @access public
 * @param $number_entries (Integer)	-> Number of all available entries
 * @param $entries_per_page (Integer)	-> Number of entries per page
 * @param $current_page (Integer)	-> current displayed page
 * @param $entry_limit (Integer) 	-> SQL entry limit
 * @param $page_link (String)		-> example: admin.php?page=wp-admin-microblog/wp-admin-microblog.php
 * @param $link_atrributes (String)	-> example: search=$search&amp;tag=$tag
 * @param $type - top or bottom, default: top
*/
function wpam_page_menu ($number_entries, $entries_per_page, $current_page, $entry_limit, $page_link = '', $link_attributes = '', $type = 'top') {
   // if number of entries > number of entries per page
   if ($number_entries > $entries_per_page) {
      $num_pages = floor (($number_entries / $entries_per_page));
      $mod = $number_entries % $entries_per_page;
      if ($mod != 0) {
         $num_pages = $num_pages + 1;
      }

      // first page / previous page
      if ($entry_limit != 0) {
         $back_links = '<a href="' . $page_link . '&amp;limit=1&amp;' . $link_attributes . '" title="' . __('first page','wp_admin_blog') . '" class="page-numbers">&laquo;</a> <a href="' . $page_link . '&amp;limit=' . ($current_page - 1) . '&amp;' . $link_attributes . '" title="' . __('previous page','wp_admin_blog') . '" class="page-numbers">&lsaquo;</a> ';
      }
      else {
         $back_links = '<a class="first-page disabled">&laquo;</a> <a class="prev-page disabled">&lsaquo;</a> ';
      }
      $page_input = ' <input name="limit" type="text" size="2" value="' .  $current_page . '" style="text-align:center;" /> ' . __('of','wp_admin_blog') . ' ' . $num_pages . ' ';

      // next page/ last page
      if ( ( $entry_limit + $entries_per_page ) <= ($number_entries)) { 
         $next_links = '<a href="' . $page_link . '&amp;limit=' . ($current_page + 1) . '&amp;' . $link_attributes . '" title="' . __('next page','wp_admin_blog') . '" class="page-numbers">&rsaquo;</a> <a href="' . $page_link . '&amp;limit=' . $num_pages . '&amp;' . $link_attributes . '" title="' . __('last page','wp_admin_blog') . '" class="page-numbers">&raquo;</a> ';
      }
      else {
         $next_links = '<a class="next-page disabled">&rsaquo;</a> <a class="last-page disabled">&raquo;</a> ';
      }

      // for displaying number of entries
      if ($entry_limit + $entries_per_page > $number_entries) {
         $anz2 = $number_entries;
      }
      else {
         $anz2 = $entry_limit + $entries_per_page;
      }

      // return
      if ($type == 'top') {
         return '<div class="tablenav-pages"><span class="displaying-num">' . ($entry_limit + 1) . ' - ' . $anz2 . ' ' . __('of','wp_admin_blog') . ' ' . $number_entries . ' ' . __('Entries','wp_admin_blog') . '</span> ' . $back_links . '' . $page_input . '' . $next_links . '</div>';
      }
      else {
         return '<div class="tablenav"><div class="tablenav-pages"><span class="displaying-num">' . ($entry_limit + 1) . ' - ' . $anz2 . ' ' . __('of','wp_admin_blog') . ' ' . $number_entries . ' ' . __('Entries','wp_admin_blog') . '</span> ' . $back_links . ' ' . $current_page . ' ' . __('of','wp_admin_blog') . ' ' . $num_pages . ' ' . $next_links . '</div></div>';
      }	
   }
}

/**
 * Get WPAM options
 * @global class $wpdb
 * @global string $admin_blog_meta
 * @param string $name
 * @param string $category
 * @return boolean 
 */
function wpam_get_options($name = '', $category = '') {
    global $wpdb;
    global $admin_blog_meta;
    if ( $category != '' ) {
        $row = $wpdb->get_results("SELECT * FROM `$admin_blog_meta` WHERE `category` = '$category'", ARRAY_A);
    }
    if ( $name != '' ) {
        $row = $wpdb->get_var("SELECT `value` FROM `$admin_blog_meta` WHERE `variable` = '$name'");
    }
    
    if ( $row == '' ) {
        return false;
    }
    else {
        return $row;
    }
}

/**
 * Dashboard Widget
 * @global type $current_user
 * @global type $wpdb
 * @global string $admin_blog_posts
 * @global string $admin_blog_tags
 * @global string $admin_blog_relations 
 */
function wpam_widget_function() {
   global $current_user;
   global $wpdb;
   global $admin_blog_posts;
   global $admin_blog_tags;
   get_currentuserinfo();
   $user = $current_user->ID;
   $str = "'";
   $text = isset( $_POST['wp_admin_blog_edit_text'] ) ? wpam_sec_var($_POST['wp_admin_blog_edit_text']) : '';
   $sticky_for_dash = wpam_get_options('sticky_for_dash');
   $media_upload = wpam_get_options('media_upload');
   // actions
   if ( isset($_POST['wpam_nm_submit']) ) {
      // form fields
      $new = array(
          'text' => wpam_sec_var($_POST['wpam_nm_text']),
          'headline' => wpam_sec_var($_POST['wpam_nm_headline'])
      );
      $is_sticky = isset ( $_POST['wpam_is_sticky'] ) ? 1 : 0;
      wpam_message::add_message($new['text'], $user, 0, $is_sticky);
      // add as a blog post if it is wished
      if ( isset( $_POST['wpam_as_wp_post'] ) ) { 
           if ($_POST['wpam_as_wp_post'] == 'true') {
              wpam_message::add_as_wp_post($new['text'], $new['headline'], $user);
           }
      }
      $content = "";
   }
   if (isset($_POST['wp_admin_blog_edit_message_submit'])) {
      $edit_message_ID = wpam_sec_var($_POST['wp_admin_blog_message_ID'], 'integer');
      wpam_message::update_message($edit_message_ID, $text);
   }
   if (isset($_POST['wp_admin_blog_reply_message_submit'])) {
      $parent_ID = wpam_sec_var($_POST['wp_admin_blog_parent_ID'], 'integer');
      wpam_message::add_message($text, $user, $parent_ID);
   }
   if (isset($_GET['wp_admin_blog_delete'])) {
      $delete = wpam_sec_var($_GET['wp_admin_blog_delete'], 'integer');
      wpam_message::del_message($delete);
   }
   if (isset($_GET['wp_admin_blog_remove'])) {
      $remove = wpam_sec_var($_GET['wp_admin_blog_remove'], 'integer');
      wpam_message::update_sticky($remove, 0);
   }
   if (isset($_GET['wp_admin_blog_add'])) {
      $add = wpam_sec_var($_GET['wp_admin_blog_add'], 'integer');
      wpam_mesage::update_sticky($add, 1);
   }
   
   echo '<form method="post" name="wp_admin_blog_dashboard_widget" id="wp_admin_blog_dashboard_widget" action="index.php">';
   echo '<div id="wpam_new_message" style="display:none;">';
   
   if ( $media_upload == 'true' ) {
      echo '<div class="wpam_media_buttons" style="text-align:right;">' .  wpam_media_buttons() . '</div>';
   }
   echo '<textarea name="wpam_nm_text" id="wpam_nm_text" cols="70" rows="4" style="width:100%;"></textarea>';
   echo '<table style="width:100%; border-bottom:1px solid rgb(223 ,223,223); padding:10px;">';
   echo '<tr>';
   // Add message options
   if ( current_user_can( 'use_wp_admin_microblog_bp' ) || current_user_can( 'use_wp_admin_microblog_sticky' ) ) {
     echo '<td style="vertical-align:top; padding-top:5px;"><a onclick="javascript:wpam_showhide(' . $str . 'wpam_message_options' . $str . ')" style="cursor:pointer; font-weight:bold;">+ ' .  __('Options', 'wp_admin_blog') . '</a>';
     echo '<table style="width:100%; display: none; float:left; padding:5px;" id="wpam_message_options">';
     if ( current_user_can( 'use_wp_admin_microblog_sticky' ) ) { 
          echo '<tr><td style="border-bottom-width:0px;"><input name="wpam_is_sticky" id="wpam_is_sticky" type="checkbox"/> <label for="wpam_is_sticky">' . __('Sticky this message','wp_admin_blog') . '</label></td></tr>';
     }
     if ( current_user_can( 'use_wp_admin_microblog_bp' ) ) { 
          echo '<tr><td style="border-bottom-width:0px;"><input name="wpam_as_wp_post" id="wpam_as_wp_post" type="checkbox" value="true" onclick="javascript:wpam_showhide(' . $str . 'wpam_as_wp_post_title' . $str .')" /> <label for="wpam_as_wp_post">' . __('as WordPress blog post', 'wp_admin_blog') . '</label> <span style="display:none;" id="wpam_as_wp_post_title">&rarr; <label for="wpam_nm_headline">' . __('Title', 'wp_admin_blog') . ' </label><input name="wpam_nm_headline" id="wpam_nm_headline" type="text" style="width:95%;" /></span></td></tr>';
     }
     echo '</table>';
   }
   // END
   echo '<td style="text-align:right; vertical-align:top;"><input type="submit" name="wpam_nm_submit" id="wpam_nm_submit" class="button-primary" value="' . __('Send', 'wp_admin_blog') . '" /></td>';
   echo '</tr>';
   echo '</table>';
   echo '</div>';
   // Load tags
   $tags = $wpdb->get_results("SELECT `tag_id`, `name` FROM `$admin_blog_tags`", ARRAY_A);
   // END
   echo '<table border="0" cellpadding="0" cellspacing="0" width="100%" class="wpam-dashboard">';
   if ( $sticky_for_dash == 'true' ) {
     $sql = "SELECT * FROM " . $admin_blog_posts . " ORDER BY is_sticky DESC, post_ID DESC LIMIT 0, 5";   
   }
   else {
     $sql = "SELECT * FROM " . $admin_blog_posts . " ORDER BY post_ID DESC LIMIT 0, 5";
   }
   $rows = $wpdb->get_results($sql);
   $sql = "SELECT COUNT(post_parent) AS gesamt, post_parent FROM " . $admin_blog_posts . " GROUP BY post_parent";
   $replies = $wpdb->get_results($sql);
   foreach ($rows as $post) {
      $user_info = get_userdata($post->user);
      $edit_button = '';
      $edit_button2 = '';
      $count_rep = 0;
      $rpl = 0;
      $str = "'";
      $time = wpam_datumsplit($post->date);
      $message_text = wpam_message::prepare($post->text, $tags);
      // Count Number of Replies
      foreach ($replies as $rep) {
         if ($rep->post_parent == $post->post_ID) {
            $count_rep = $rep->gesamt + 1;
            $rpl = $rep->post_parent;
         }

         if ($rep->post_parent == $post->post_parent && $post->post_parent != 0) {
            $count_rep = $rep->gesamt + 1;
            $rpl = $rep->post_parent;
         }
      }
      // sticky post options
      // change background color for sticky posts
      $class = 'wpam_normal';
      if ( $post->is_sticky == 1 && $sticky_for_dash == 'true' ) {
          $class = 'wpam_sticky';
      }
      $sticky_option = '';
      if ( current_user_can( 'use_wp_admin_microblog_sticky' ) && $sticky_for_dash == 'true' && $post->post_parent == 0 ) {
          if ( $post->is_sticky == 0 ) {
               $sticky_option = '<a href="index.php?wp_admin_blog_add=' . $post->post_ID . '"" title="' . __('Sticky this message','wp_admin_blog') . '">' . __('Sticky','wp_admin_blog') . '</a> | ';
          }
          else {
               $sticky_option = '<a href="index.php?wp_admin_blog_remove=' . $post->post_ID . '"" title="' . __('Unsticky this message','wp_admin_blog') . '">' . __('Unsticky','wp_admin_blog') . '</a> | ';
          }
      }
      // Handles post parent
      if ($post->post_parent == 0) {
          $post->post_parent = $post->post_ID;
      }
      // Message Menu
      if ($count_rep != 0) {
          $edit_button2 = ' | <a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;rpl=' . $post->post_parent . '" title="' . _n( 'One Reply', $count_rep . ' Replies', $count_rep, 'wp_admin_blog' ) . '">' . $count_rep . ' ' . __( 'Replies', 'wp_admin_blog' ) . '</a>';
      }
      // Show message edit options if the user is the author of the message or the blog admin
      if ( $post->user == $user || current_user_can('manage_options') ) {
          $edit_button = $edit_button . '<a onclick="javascript:wpam_editMessage(' . $post->post_ID . ')" style="cursor:pointer;" title="' . __('Edit this message','wp_admin_blog') . '">' . __('Edit','wp_admin_blog') . '</a> | ' . $sticky_option . '<a href="index.php?wp_admin_blog_delete=' . $post->post_ID . '" title="' . __('Click to delete this message','wp_admin_blog') . '" style="color:#FF0000">' . __('Delete','wp_admin_blog') . '</a> | ';
      }
      $edit_button = $edit_button . '<a onclick="javascript:wpam_replyMessage(' . $post->post_ID . ',' . $str . '' . $post->post_parent . '' . $str . ')" style="cursor:pointer; color:#009900;" title="' . __('Write a reply','wp_admin_blog') . '">' . __('Reply','wp_admin_blog') . '</a>';
      $message_date = human_time_diff( mktime($time[0][3], $time[0][4], $time[0][5], $time[0][1], $time[0][2], $time[0][0] ), current_time('timestamp') ) . ' ' . __( 'ago', 'wp_admin_blog' );
      echo '<tr class="' . $class . '">';
      echo '<td style="border-bottom:1px solid rgb(223,223,223); padding: 12px 0 0 5px;" valign="top" width="40"><span title="' . $user_info->display_name . ' (' . $user_info->user_login . ')">' . get_avatar($user_info->ID, 30) . '</span></td>';
      echo '<td style="border-bottom:1px solid rgb(223,223,223); padding: 0 5px 0 0;">';
      echo '<div id="wp_admin_blog_message_' . $post->post_ID . '"><p style="color:#AAAAAA;">' . $message_date . ' | ' . __('by','wp_admin_blog') . ' ' . $user_info->display_name . '' . $edit_button2 . '</p>';
      echo '<p>' . $message_text . '</p>';
      echo '<div class="wpam-row-actions" style="font-size:11px; padding: 0 0 10px 0; margin:0;">' . $edit_button . '</div></div>';
      echo '<input name="wp_admin_blog_message_text" id="wp_admin_blog_message_text_' . $post->post_ID . '" type="hidden" value="' . stripslashes($post->text) . '" />';
      echo '</td>';
      echo '</tr>';
   }
   echo '</table>';
   echo '</form>';
}

/**
 * Add dashboard widget
 */
function wpam_add_widgets() {
    if ( current_user_can( 'use_wp_admin_microblog' ) ) {
        // load microblog name
        $name = wpam_get_options('blog_name_widget', '');
        if ( $name == false || $name == '' ) {
            $name = 'Microblog';
        }
        $str = "'";
        $title = '<a onclick="wpam_showhide(' . $str . 'wpam_new_message' . $str . ')" style="cursor:pointer; text-decoration:none; font-size:12px; font-weight:bold; color:#464646;" title="' . __('New Message','wp_admin_blog') . '">' . $name . ' <img src="' .  WP_PLUGIN_URL . '/wp-admin-microblog/images/document-new-6.png' . '" heigth="12" width="12" /></a>';
        wp_add_dashboard_widget('wpam_dashboard_widget', '' . $title . '', 'wpam_widget_function');
    }
}

/*
 * Add scripts ans stylesheets
*/ 
function wpam_header() {
   // Define $page
   if ( isset($_GET['page']) ) {
           $page = $_GET['page'];
   }
   else {
           $page = '';
   }
   // load scripts only, when it's wp_admin_blog page
   if ( eregi('wp-admin-microblog', $page) || eregi('wp-admin/index.php', $_SERVER['PHP_SELF']) ) {
      wp_register_script('wp_admin_blog', WP_PLUGIN_URL . '/wp-admin-microblog/wp-admin-microblog.js');
      wp_register_style('wp_admin_blog_css', WP_PLUGIN_URL . '/wp-admin-microblog/wp-admin-microblog.css');
      wp_enqueue_style('wp_admin_blog_css');
      wp_enqueue_script('wp_admin_blog');
      wp_enqueue_script('media-upload');
      add_thickbox();
   }
   // load the hack for the normal WP Admin Microblog page
   if ( eregi('wp-admin-microblog', $page) ) {
      wp_register_script('wpam_upload_hack', WP_PLUGIN_URL . '/wp-admin-microblog/media-upload-hack.js');
      wp_enqueue_script('wpam_upload_hack');
   }
   // load the hack for the dashboard, when the user say yes
   $test = get_option('wp_admin_blog_media_upload');
   if (eregi('wp-admin/index.php', $_SERVER['REQUEST_URI']) && $test == 'true') {
      wp_register_script('wpam_upload_hack', WP_PLUGIN_URL . '/wp-admin-microblog/media-upload-hack.js');
      wp_enqueue_script('wpam_upload_hack');
   }
}

/**
 * Updater
 * 
 * @global string $admin_blog_posts
 * @global class $wpdb
 * @since 1.2.0
 */
function wpam_update() {
   global $admin_blog_posts;
   global $admin_blog_meta;
   global $wpdb;
   $version = get_option('wp_admin_blog_version');
   // Update to database version 1.2.0
   if ( $version == false ) {
      add_option('wp_admin_blog_version', '1.2.0', '', 'no');
      // Add is_sticky column
      $sql = "SHOW COLUMNS FROM " . $admin_blog_posts . " LIKE 'is_sticky'";
      $test = $wpdb->query($sql);
      if ($test == '0') { 
          $wpdb->query("ALTER TABLE " . $admin_blog_posts . " ADD `is_sticky` INT NULL AFTER `user`");
      }
   }
   // Update to database version 2.1.0
   if ( $version == false || $version == '1.2.0' ) {
      // Add meta table 
      if($wpdb->get_var("SHOW TABLES LIKE '$admin_blog_meta'") != $admin_blog_meta) {
        $sql = "CREATE TABLE " . $admin_blog_meta . " (
                            `meta_ID` INT UNSIGNED AUTO_INCREMENT ,
                            `variable` VARCHAR (200) ,
                            `value` LONGTEXT ,
                            `category` VARCHAR (200) ,
                            PRIMARY KEY (meta_ID)
                        ) $charset_collate;";
        $wpdb->query($sql);
        $wpdb->query("INSERT INTO " . $admin_blog_meta . " (variable, value, category) VALUES ('blog_name', 'Microblog', 'system')");
        $wpdb->query("INSERT INTO " . $admin_blog_meta . " (variable, value, category) VALUES ('blog_name_widget', 'Microblog', 'system')");
        $wpdb->query("INSERT INTO " . $admin_blog_meta . " (variable, value, category) VALUES ('number_tags', '50', 'system')");
        $wpdb->query("INSERT INTO " . $admin_blog_meta . " (variable, value, category) VALUES ('number_messages', '10', 'system')");
        $wpdb->query("INSERT INTO " . $admin_blog_meta . " (variable, value, category) VALUES ('auto_reply', 'false', 'system')");
        $wpdb->query("INSERT INTO " . $admin_blog_meta . " (variable, value, category) VALUES ('media_upload', 'false', 'system')");
        $wpdb->query("INSERT INTO " . $admin_blog_meta . " (variable, value, category) VALUES ('sticky_for_dash', 'false', 'system')");
        $wpdb->query("INSERT INTO " . $admin_blog_meta . " (variable, value, category) VALUES ('auto_notifications', 'false', 'system')");
     }
     // Delete old settings
     delete_option('wp_admin_blog_name');
     delete_option('wp_admin_blog_name_widget');
     delete_option('wp_admin_number_tags');
     delete_option('wp_admin_number_messages');
     delete_option('wp_admin_auto_reply');
     delete_option('wp_admin_media_upload');
     update_option('wp_admin_blog_version', '2.1.0');
   }
}

/**
 * WPAM plugin activation
 * @param boolean $network_wide
 * @since 2.2.0
 */
function wpam_activation ( $network_wide ) {
    global $wpdb;
    // it's a network activation
    if ( $network_wide ) {
        $old_blog = $wpdb->blogid;
        // Get all blog ids
        $blogids = $wpdb->get_col($wpdb->prepare("SELECT `blog_id` FROM $wpdb->blogs"));
        foreach ($blogids as $blog_id) {
            switch_to_blog($blog_id);
            wpam_install();
        }
        switch_to_blog($old_blog);
        return;
    } 
    // it's a normal activation
    else {
        wpam_install();
    }
}

/**
 * Installer
 * @global class $wpdb
 * @global type $wp_roles 
 */
function wpam_install () {
   global $wpdb;

   // Add capabilities
   global $wp_roles;
   $role = $wp_roles->get_role('administrator');
   if ( !$role->has_cap('use_wp_admin_microblog') ) {
      $wp_roles->add_cap('administrator', 'use_wp_admin_microblog');
   }
   if ( !$role->has_cap('use_wp_admin_microblog_bp') ) {
      $wp_roles->add_cap('administrator', 'use_wp_admin_microblog_bp');
   }
   if ( !$role->has_cap('use_wp_admin_microblog_sticky') ) {
      $wp_roles->add_cap('administrator', 'use_wp_admin_microblog_sticky');
   }

   // charset & collate like WordPress
   $charset_collate = '';
   if ( version_compare(mysql_get_server_info(), '4.1.0', '>=') ) {
      if ( ! empty($wpdb->charset) ) {
         $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
      }	
      if ( ! empty($wpdb->collate) ) {
         $charset_collate .= " COLLATE $wpdb->collate";
      }	
   }
   require_once(ABSPATH . 'wp-admin/includes/upgrade.php');	
   // Post table
   $table_name = $wpdb->prefix . 'admin_blog_posts';
   if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
      $sql = "CREATE TABLE " . $wpdb->prefix . "admin_blog_posts (
                        `post_ID` INT UNSIGNED AUTO_INCREMENT ,
                        `post_parent` INT ,
                        `text` LONGTEXT ,
                        `date` DATETIME ,
                        `user` INT ,
                        `is_sticky` INT ,
                        PRIMARY KEY (post_ID)
                  ) $charset_collate;";
      
      dbDelta($sql);
   }
   // Tag table
   $table_name = $wpdb->prefix . 'admin_blog_tags';
   if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
      $sql = "CREATE TABLE " . $wpdb->prefix . "admin_blog_tags (
                        `tag_ID` INT UNSIGNED AUTO_INCREMENT ,
                        `name` VARCHAR (200) ,
                        PRIMARY KEY (tag_ID)
                    ) $charset_collate;";			
      dbDelta($sql);
   }
   // Relation
   $table_name = $wpdb->prefix . 'admin_blog_relations';
   if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
      $sql = "CREATE TABLE " . $wpdb->prefix . "admin_blog_relations (
                        `rel_ID` INT UNSIGNED AUTO_INCREMENT ,
                        `post_ID` INT ,
                        `tag_ID` INT ,
                        PRIMARY KEY (rel_ID)
                    ) $charset_collate;";		
      dbDelta($sql);
   }
   // Meta
   $table_name = $wpdb->prefix . 'admin_blog_meta';
   if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
      $sql = "CREATE TABLE " . $wpdb->prefix . "admin_blog_meta (
                        `meta_ID` INT UNSIGNED AUTO_INCREMENT ,
                        `variable` VARCHAR (200) ,
                        `value` LONGTEXT ,
                        `category` VARCHAR (200) ,
                        PRIMARY KEY (meta_ID)
                    ) $charset_collate;";		
      dbDelta($sql);
      $wpdb->query("INSERT INTO " . $wpdb->prefix . "admin_blog_meta (variable, value, category) VALUES ('blog_name', 'Microblog', 'system')");
      $wpdb->query("INSERT INTO " . $wpdb->prefix . "admin_blog_meta (variable, value, category) VALUES ('blog_name_widget', 'Microblog', 'system')");
      $wpdb->query("INSERT INTO " . $wpdb->prefix . "admin_blog_meta (variable, value, category) VALUES ('number_tags', '50', 'system')");
      $wpdb->query("INSERT INTO " . $wpdb->prefix . "admin_blog_meta (variable, value, category) VALUES ('number_messages', '10', 'system')");
      $wpdb->query("INSERT INTO " . $wpdb->prefix . "admin_blog_meta (variable, value, category) VALUES ('auto_reply', 'false', 'system')");
      $wpdb->query("INSERT INTO " . $wpdb->prefix . "admin_blog_meta (variable, value, category) VALUES ('media_upload', 'false', 'system')");
      $wpdb->query("INSERT INTO " . $wpdb->prefix . "admin_blog_meta (variable, value, category) VALUES ('sticky_for_dash', '', 'system')");
      $wpdb->query("INSERT INTO " . $wpdb->prefix . "admin_blog_meta (variable, value, category) VALUES ('auto_notifications', '', 'system')");
   }
   if ( !get_option('wp_admin_blog_version') ) {
      add_option('wp_admin_blog_version', '2.1.0', '', 'no');
   }
}

/**
 * Uninstalling
 * @global class $wpdb 
 */
function wpam_uninstall() {
    global $wpdb;
    $wpdb->query("DROP TABLE " . $wpdb->prefix . "admin_blog_posts, " . $wpdb->prefix . "admin_blog_tags, " . $wpdb->prefix . "admin_blog_relations, " . $wpdb->prefix . "admin_blog_meta");
    delete_option('wp_admin_blog_version');
}

// load language support
function wpam_language_support() {
    load_plugin_textdomain('wp_admin_blog', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

// Register WordPress hooks
register_activation_hook( __FILE__, 'wpam_activation');
add_action('init', 'wpam_language_support');
add_action('admin_init','wpam_header');
add_action('admin_menu','wpam_menu');
add_action('wp_dashboard_setup', 'wpam_add_widgets' );
?>