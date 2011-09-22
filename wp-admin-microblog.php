<?php
/*
Plugin Name: WP Admin Microblog
Plugin URI: http://mtrv.wordpress.com/microblog/
Description: Adds a microblog in your WordPress backend.
Version: 1.1.0
Author: Michael Winkler
Author URI: http://mtrv.wordpress.com/
Min WP Version: 3.0
Max WP Version: 3.2.1
*/

/*
   LICENCE
 
    Copyright 2010-2011  Michael Winkler

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

// load microblog name
$wpam_blog_name = get_option('wp_admin_blog_name');
if ( $wpam_blog_name == false ) {
     $wpam_blog_name = 'Microblog';
}

// Define menus
function wp_admin_blog_menu() {
   global $wpam_blog_name;
   add_menu_page(__('Blog','wp_admin_blog'), $wpam_blog_name,'use_wp_admin_microblog', __FILE__, 'wp_admin_blog_page', WP_PLUGIN_URL . '/wp-admin-microblog/images/logo.png');
}
function wp_admin_blog_add_menu_settings() {
   add_options_page(__('WP Admin Microblog Settings','wp_admin_blog'),'WP Admin Microblog','administrator','wp-admin-blog', 'wp_admin_blog_settings');
}

/** 
 * Secure variables
 * @param $var (STRING) the parameter you want to secure
 * @param $type (STRING) integer or string (default: string)
 * @return $var (STRING or INT) the secured parameter
*/
function wp_admin_blog_sec_var($var, $type = 'string') {
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
function wp_admin_blog_media_buttons() {
   include_once ABSPATH . '/wp-admin/includes/media.php';
   ob_start();
   do_action( 'media_buttons' );
   return ob_get_clean();
}

/** 
 * Add new message
 * @param $content (STRING) - text of a message
 * @param $user (INT) - WordPress user ID
 * @param $tags (STRING) - string with the tags
 */
function wp_admin_blog_add_message ($content, $user, $tags, $parent) {
   global $wpdb;
   global $admin_blog_posts;
   global $admin_blog_tags;
   global $admin_blog_relations;
   if ($content != '') {
      $content = nl2br($content);
      $post_time = current_time('mysql',0);
      $sql = sprintf("INSERT INTO " . $admin_blog_posts . " (`post_parent`, `text`, `date`, `user`) VALUES('$parent', '$content', '$post_time', '$user')", 
              mysql_real_escape_string( "$" . $admin_blog_posts . "_text"));
      $wpdb->query($sql);
      // Tags
      $array = explode(",",$tags);
      foreach($array as $element) {
         // Check if tag is in database
         $element = trim($element);
         if ($element != "" && $element != __('Tags (seperate by comma)', 'wp_admin_blog')) {
            $abfrage = "SELECT tag_ID FROM " . $admin_blog_tags . " WHERE name = '$element'";
            $check = $wpdb->query($abfrage);
            // if not, then insert tag
            if ($check == 0){
               $eintrag = sprintf("INSERT INTO " . $admin_blog_tags . " (`name`) VALUES('$element')", 
               mysql_real_escape_string( "$" . $admin_blog_tags . "_name") );
               $wpdb->query($eintrag);
               $row = $wpdb->get_results($abfrage);
            }
            else {
               $row = $wpdb->get_results($abfrage);
            }
            // Find post_ID and tag_ID and insert the relation
            foreach($row as $row) {
               $row2 = "SELECT post_ID FROM " . $admin_blog_posts . " WHERE text ='$content' AND user='$user'";
               $row2 = $wpdb->get_results($row2);
               foreach ($row2 as $row2) {
                  // check if the relation already exist
                  $test = "SELECT post_ID FROM " .$admin_blog_relations . " WHERE post_ID = '$row2->post_ID' AND tag_ID = '$row->tag_ID'";
                  $test = $wpdb->query($test);
                  // if not, then insert the relation
                  if ($test == 0) {
                     $eintrag2 = "INSERT INTO " .$admin_blog_relations . " (post_ID, tag_ID) VALUES ('$row2->post_ID', '$row->tag_ID')";
                     $wpdb->query($eintrag2);
                  }
               }
            }
         }
      }
   }
   // Send message
   wp_admin_blog_find_user($content, $user);
}

/**
 * Delete message
 * @global type $wpdb
 * @global $admin_blog_posts (STRING)
 * @global $admin_blog_relations (STRING)
 * @param $delete (INT) 
 */
function wp_admin_blog_del_message($delete) {
   global $wpdb;
   global $admin_blog_posts;
   global $admin_blog_relations;
   $wpdb->query( "DELETE FROM " . $admin_blog_posts . " WHERE post_ID = $delete" );
   $wpdb->query( "DELETE FROM " . $admin_blog_relations . " WHERE post_ID = $delete" );
}

/** 
 * Update message
 * @param $post_ID (INT) Post ID
 * @param $text (STRING)
 */
function wp_admin_blog_update_message($post_ID, $text) {
   global $wpdb;
   global $admin_blog_posts;
   global $admin_blog_relations;
   $text = nl2br($text);
   $wpdb->query( sprintf("UPDATE " . $admin_blog_posts . " SET text = '$text' WHERE post_ID = '$post_ID'",
   mysql_real_escape_string( "$" . $admin_blog_posts . "_text") ));
}

/** 
 * Add a message as WordPress blog post
 * @param $content (STRING) - the content of the message
 * @param $title (STRING) - the title of the message
 * @param $author (STRING) - the user id
 * @paran $tags (STRING) - a string with tags (seperate by comma)
*/ 
function wp_admin_blog_add_as_wp_post ($content, $title, $author, $tags) {
   if ($title == '') {
      $title = __('Short message','wp_admin_blog');
   }
   $content = str_replace('(file://)', 'http://', $content);
   $message = array();
   $message['post_title'] = $title;
   $message['post_content'] = $content;
   $message['post_status'] = 'publish';
   $message['post_author'] = $author;
   if ($tags != '') {
      $array = explode(",",$tags);
      foreach($array as $element) {
         $element = trim($element);
         $elements = $elements . $element . ', ';
      }
      $message['tags_input'] = substr($elements, 0, -2);
   }	
   wp_insert_post( $message );
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
function wp_admin_blog_datumsplit($datum) {
   $preg = '/[\d]{2,4}/'; 
   $split = array(); 
   preg_match_all($preg, $datum, $split); 
   return $split; 
}

/**
 * Replace URL strings with HTML URL strings
 * @param $text (STRING)
 * @return $text (STRING)
*/ 
function wp_admin_blog_replace_url($text) {
   // correct a problem when <br /> stands behind an url
   $text = str_replace("<br />"," <br />",$text);
   if ( preg_match_all("((http://|https://|ftp://|file://|mailto:|news:)[^ ]+)", $text, $match) ) {
      $prefix = '#(^|[^"=]{1})(http://|https://|ftp://|file://|mailto:|news:)([^\s]+)([\s\n]|$)#sm';
      for ($x = 0; $x < count($match[0]); $x++) {
         if ($match[1][$x] == 'file://') {
            $link = str_replace('file://', 'http://', $match[0][$x]);
            $text = str_replace($match[0][$x], ' <a href="' . $link . '" target="_blank" title="' . $link . '">' . basename($match[0][$x]) . '</a> ', $text);
         }
         else {
            $link_text = $match[0][$x];
            $length = strlen($link_text);
            $link_text = substr($link_text, 0 , 50);
            if ($length > 50) {
               $link_text = $link_text . '[...]';
            }
            $text = str_replace($match[0][$x], ' <a href="' . $match[0][$x] . '" target="_blank" title="' . $match[0][$x] . '">' . $link_text . '</a> ', $text);
         }
      }
   }
   return $text;
}

/** 
 * Handle bbcodes
 * @param $text (STRING)
 * @param $mode (STRING) --> replace (replace with HTML-Tag) or delete (Delete bbcode)
 * @return $text (STRING)
*/
function wp_admin_blog_replace_bbcode($text, $mode = 'replace') {
   if ($mode == 'replace') {
      $text = preg_replace("/\[b\](.*)\[\/b\]/Usi", "<strong>\\1</strong>", $text); 
      $text = preg_replace("/\[i\](.*)\[\/i\]/Usi", "<em>\\1</em>", $text); 
      $text = preg_replace("/\[u\](.*)\[\/u\]/Usi", "<u>\\1</u>", $text);
      $text = preg_replace("/\[s\](.*)\[\/s\]/Usi", "<s>\\1</s>", $text);
      $text = preg_replace("/\[red\](.*)\[\/red\]/Usi", '<span style="color:red;">\\1</span>', $text);
      $text = preg_replace("/\[blue\](.*)\[\/blue\]/Usi", '<span style="color:blue;">\\1</span>', $text);
      $text = preg_replace("/\[green\](.*)\[\/green\]/Usi", '<span style="color:green;">\\1</span>', $text);
      $text = preg_replace("/\[orange\](.*)\[\/orange\]/Usi", '<span style="color:orange;">\\1</span>', $text);
   }
   if ($mode == 'delete') {
      $text = preg_replace("/\[b\](.*)\[\/b\]/Usi", "\\1", $text); 
      $text = preg_replace("/\[i\](.*)\[\/i\]/Usi", "\\1", $text); 
      $text = preg_replace("/\[u\](.*)\[\/u\]/Usi", "\\1", $text);
      $text = preg_replace("/\[s\](.*)\[\/s\]/Usi", "\\1", $text);
      $text = preg_replace("/\[red\](.*)\[\/red\]/Usi", "\\1", $text);
      $text = preg_replace("/\[blue\](.*)\[\/blue\]/Usi", "\\1", $text);
      $text = preg_replace("/\[green\](.*)\[\/green\]/Usi", "\\1", $text);
      $text = preg_replace("/\[orange\](.*)\[\/orange\]/Usi", "\\1", $text);
   }
   return $text;
}

/**
 * Find users in string an send mail
 * @param $text (STRING)
 * @param $user (ARRAY)
 * @global $wpdb
 * @global $admin_blog_posts (STRING)
 */
function wp_admin_blog_find_user($text, $user) {
   global $wpdb;
   global $admin_blog_posts;

   $user = get_userdata($user);
   $text = $text . ' ';
   $text = wp_admin_blog_replace_bbcode($text, 'delete');
   $text = str_replace("<br />","",$text);
   $text = str_replace('(file://)', 'http://', $text);
   $text = __('Author:','wp_admin_blog') . ' ' . $user->display_name . chr(13) . chr(10) . chr(13) . chr(10) . $text;
   $text = $text . chr(13) . chr(10) . '________________________' . chr(13) . chr(10) . __('Login under the following address to create a reply:','wp_admin_blog') . ' ' . wp_login_url();

   $sql = "SELECT DISTINCT user FROM " . $admin_blog_posts . "";
   $users = $wpdb->get_results($sql);
   foreach ($users as $element) {
      $user_info = get_userdata($element->user);
      $the_user = "@" . $user_info->user_login . " ";
      $test = strpos($text, $the_user);
      if ( $test !== false ) {
         $headers = 'From: ' . get_bloginfo() . ' <' . get_bloginfo('admin_email') . '>' . "\r\n\\";
         $subject = get_bloginfo() . ': ' .__('New message in admin micoblog','wp_admin_blog');
         wp_mail( $user_info->user_email, $subject, $text, $headers );
      }	
   }
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
function wp_admin_blog_page_menu ($number_entries, $entries_per_page, $current_page, $entry_limit, $page_link = '', $link_attributes = '', $type = 'top') {
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
         return '<div class="tablenav-pages"><span class="displaying-num">' . ($entry_limit + 1) . ' - ' . $anz2 . ' ' . __('of','wp_admin_blog') . ' ' . $number_entries . ' ' . __('entries','wp_admin_blog') . '</span> ' . $back_links . '' . $page_input . '' . $next_links . '</div>';
      }
      else {
         return '<div class="tablenav"><div class="tablenav-pages"><span class="displaying-num">' . ($entry_limit + 1) . ' - ' . $anz2 . ' ' . __('of','wp_admin_blog') . ' ' . $number_entries . ' ' . __('entries','wp_admin_blog') . '</span> ' . $back_links . ' ' . $current_page . ' ' . __('of','wp_admin_blog') . ' ' . $num_pages . ' ' . $next_links . '</div></div>';
      }	
   }
}
/** 
 * Update who can use the plugin
 * @param $roles (ARRAY)
 * @param $name (STRING) - Name of the microblog
 * @param $widget (STRING) - Name of the WPAM dashboard widget 
 * @param $tags (INT) - number of tags in the tag cloud
 * @param $messages (INT) - number of messages per page
 * @param $reply - true or false
 * @param $upload - true or false
 * @param $blog_post (ARRAY)
 */
function wp_admin_blog_update_options ($roles, $name, $widget, $tags, $messages, $reply, $upload, $blog_post) {
   global $wp_roles;
   // Roles
   if ( empty($roles) || ! is_array($roles) ) { 
      $roles = array(); 
   }
   $who_can = $roles;
   $who_cannot = array_diff( array_keys($wp_roles->role_names), $roles);
   foreach ($who_can as $role) {
      $wp_roles->add_cap($role, 'use_wp_admin_microblog');
   }
   foreach ($who_cannot as $role) {
      $wp_roles->remove_cap($role, 'use_wp_admin_microblog');
   }
   // Roles for message as a blop post
   if ( empty($blog_post) || ! is_array($blog_post) ) { 
      $blog_post = array(); 
   }
   $who_can = $blog_post;
   $who_cannot = array_diff( array_keys($wp_roles->role_names), $blog_post);
   foreach ($who_can as $role) {
      $wp_roles->add_cap($role, 'use_wp_admin_microblog_bp');
   }
   foreach ($who_cannot as $role) {
      $wp_roles->remove_cap($role, 'use_wp_admin_microblog_bp');
   }
   // Name of the microblog
   if ( !get_option('wp_admin_blog_name') ) {
      add_option('wp_admin_blog_name', $name, '', 'no');
   }
   else {
      update_option('wp_admin_blog_name', $name );
   }
   // Name of the WPAM dashboard widget
   if ( !get_option('wp_admin_blog_name_widget') ) {
      add_option('wp_admin_blog_name_widget', $widget, '', 'no');
   }
   else {
      update_option('wp_admin_blog_name_widget', $widget );
   }
   // Number of tags
   if ( !get_option('wp_admin_blog_number_tags') ) {
      add_option('wp_admin_blog_number_tags', $tags, '', 'no');
   }
   else {
      update_option('wp_admin_blog_number_tags', $tags );
   }
   // Number of messages
   if ( !get_option('wp_admin_blog_number_messages') ) {
      add_option('wp_admin_blog_number_messages', $messages, '', 'no');
   }
   else {
      update_option('wp_admin_blog_number_messages', $messages );
   }
   // Auto reply
   if ( !get_option('wp_admin_blog_auto_reply') ) {
      add_option('wp_admin_blog_auto_reply', $reply, '', 'no');
   }
   else {
      update_option('wp_admin_blog_auto_reply', $reply );
   }
   // Media Upload
   if ( !get_option('wp_admin_blog_media_upload') ) {
      add_option('wp_admin_blog_media_upload', $upload, '', 'no');
   }
   else {
      update_option('wp_admin_blog_media_upload', $upload );
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
function wp_admin_blog_widget_function() {
   global $current_user;
   global $wpdb;
   global $admin_blog_posts;
   global $admin_blog_tags;
   global $admin_blog_relations;
   get_currentuserinfo();
   $user = $current_user->ID;
   $str = "'";
   
   // form fields
   if ( isset( $_POST['wpam_nm_text'] ) ) { $new['text'] = wp_admin_blog_sec_var($_POST['wpam_nm_text']); } 
   else { $new['text'] = ''; }
   
   if ( isset( $_POST['wpam_nm_tags'] ) ) { $new['tags'] = wp_admin_blog_sec_var($_POST['wpam_nm_tags']); } 
   else { $new['tags'] = ''; }
   
   if ( isset( $_POST['wpam_nm_headline'] ) ) { $new['headline'] = wp_admin_blog_sec_var($_POST['wpam_nm_headline']); } 
   else { $new['headline'] = ''; }
   
   if ( isset( $_POST['wp_admin_blog_edit_text'] ) ) { $text = wp_admin_blog_sec_var($_POST['wp_admin_blog_edit_text']); } 
   else { $text = ''; }
   
   // actions
   if (isset($_POST['wpam_nm_submit'])) {
      wp_admin_blog_add_message($new['text'], $user, $new['tags'], 0);
      // add as a blog post if it is wished
      if ( isset( $_POST['wpam_as_wp_post'] ) ) { 
           if ($_POST['wpam_as_wp_post'] == 'true') {
              wp_admin_blog_add_as_wp_post($new['text'], $new['headline'], $user, $new['tags']);
           }
      }
      $content = "";
   }
   if (isset($_POST['wp_admin_blog_edit_message_submit'])) {
      $edit_message_ID = wp_admin_blog_sec_var($_POST['wp_admin_blog_message_ID'], 'integer');
      wp_admin_blog_update_message($edit_message_ID, $text);
   }
   if (isset($_POST['wp_admin_blog_reply_message_submit'])) {
      $parent_ID = wp_admin_blog_sec_var($_POST['wp_admin_blog_parent_ID'], 'integer');
      wp_admin_blog_add_message($text, $user, '', $parent_ID);
   }
   if (isset($_GET['wp_admin_blog_delete'])) {
      $delete = wp_admin_blog_sec_var($_GET['wp_admin_blog_delete'], 'integer');
      wp_admin_blog_del_message($delete);
   }
   
   echo '<form method="post" name="wp_admin_blog_dashboard_widget" id="wp_admin_blog_dashboard_widget" action="' .  $_SERVER['REQUEST_URI'] . '">';

   echo '<div id="wpam_new_message" style="display:none;">';
   $test = get_option('wp_admin_blog_media_upload');
   if ( $test == 'true' ) {
      echo '<div class="wpam_media_buttons" style="text-align:right;">' .  wp_admin_blog_media_buttons() . '</div>';
   }
   echo '<textarea name="wpam_nm_text" id="wpam_nm_text" cols="70" rows="4" style="width:100%;"></textarea>';
   echo '<input name="wpam_nm_tags" id="wpam_nm_tags" type="text" style="width:100%;" value="' . __('Tags (seperate by comma)', 'wp_admin_blog') . '" onblur="if(this.value==' . $str . $str . ') this.value=' . $str . __('Tags (seperate by comma)', 'wp_admin_blog') . $str . ';" onfocus="if(this.value==' . $str . __('Tags (seperate by comma)', 'wp_admin_blog') . $str . ') this.value=' . $str . $str . ';" />';
   echo '<table style="width:100%; border-bottom:1px solid rgb(223 ,223,223); padding:10px;">';
   echo '<tr>';
   if ( current_user_can( 'use_wp_admin_microblog_bp' ) ) {
   echo '<td style="font-size:11px;"><input name="wpam_as_wp_post" id="wpam_as_wp_post" type="checkbox" value="true" onclick="javascript:wpam_showhide(' . $str . 'wpam_as_wp_post_title' . $str .')" /> <label for="wpam_as_wp_post">' . __('as WordPress blog post', 'wp_admin_blog') . '</label> <span style="display:none;" id="wpam_as_wp_post_title">&rarr; <label for="wpam_nm_headline">' . __('Title', 'wp_admin_blog') . ' </label><input name="wpam_nm_headline" id="wpam_nm_headline" type="text" style="width:80%;" /></span></td>';
	}	
    echo '<td style="text-align:right;"><input type="submit" name="wpam_nm_submit" id="wpam_nm_submit" class="button-primary" value="' . __('Send', 'wp_admin_blog') . '" /></td>';
    echo '</tr>';
    echo '</table>';
   echo '</div>';

   echo '<table border="0" cellpadding="0" cellspacing="0" width="100%">';
   $sql = "SELECT * FROM " . $admin_blog_posts . " ORDER BY post_ID DESC LIMIT 0, 5";
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
      $today = false;
      $time = wp_admin_blog_datumsplit($post->date);
      $message_text = wp_admin_blog_replace_url($post->text);
      $message_text = wp_admin_blog_replace_bbcode($message_text);
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
      // Handles post parent
      if ($post->post_parent == '0') {
              $post->post_parent = $post->post_ID;
      }
      // Message Menu
      if ($count_rep != 0) {
              $edit_button2 = ' | ' . $count_rep . ' ' . __('Replies','wp_admin_blog') . '';
      }
      if ($post->user == $user) {
              $edit_button = $edit_button . '<a onclick="javascript:wpam_editMessage(' . $post->post_ID . ')" style="cursor:pointer;" title="' . __('Edit this message','wp_admin_blog') . '">' . __('Edit','wp_admin_blog') . '</a> | <a href="index.php?wp_admin_blog_delete=' . $post->post_ID . '" title="' . __('Click to delete this message','wp_admin_blog') . '" style="color:#FF0000">' . __('Delete','wp_admin_blog') . '</a> | ';
      }
      $edit_button = $edit_button . '<a onclick="javascript:wpam_replyMessage(' . $post->post_ID . ',' . $str . '' . $post->post_parent . '' . $str . ')" style="cursor:pointer; color:#009900;" title="' . __('Write a reply','wp_admin_blog') . '">' . __('Reply','wp_admin_blog') . '</a>';
      $message_date = human_time_diff( mktime($time[0][3], $time[0][4], $time[0][5], $time[0][1], $time[0][2], $time[0][0] ), current_time('timestamp') ) . ' ' . __( 'ago', 'wp_admin_blog' );
      echo '<tr>';
      echo '<td style="border-bottom:1px solid rgb(223 ,223,223);">';
      echo '<div id="wp_admin_blog_message_' . $post->post_ID . '"><p style="color:#AAAAAA;">' . $message_date . ' | ' . __('by','wp_admin_blog') . ' ' . $user_info->display_name . '' . $edit_button2 . '</p>';
      echo '<p>' . $message_text . '</p>';
      echo '<div class="wam-row-actions" style="font-size:11px; padding: 0 0 10px 0; margin:0;">' . $edit_button . '</div></div>';
      echo '<input name="wp_admin_blog_message_text" id="wp_admin_blog_message_text_' . $post->post_ID . '" type="hidden" value="' . $post->text . '" />';
      echo '</td>';
      echo '</tr>';
   }
   echo '</table>';
   echo '</form>';
}

/**
 * Add dashboard widget
 */
function wp_admin_blog_add_widgets() {
     if ( current_user_can( 'use_wp_admin_microblog' ) ) {
          // load microblog name
          $name = get_option('wp_admin_blog_name_widget');
          if ( $name == false ) {
               $name = 'Microblog';
          }
          $str = "'";
          $title = '<a onclick="wpam_showhide(' . $str . 'wpam_new_message' . $str . ')" style="cursor:pointer; text-decoration:none; font-size:12px; font-weight:bold; color:#464646;" title="' . __('New Message','wp_admin_blog') . '">' . $name . ' <img src="' .  WP_PLUGIN_URL . '/wp-admin-microblog/images/document-new-6.png' . '" heigth="12" width="12" /></a>';
          wp_add_dashboard_widget('wp_admin_blog_dashboard_widget', '' . $title . '', 'wp_admin_blog_widget_function');
     }
}

/**
 * Option Page
 * @global type $wp_roles 
 */
function wp_admin_blog_settings () {
     if ( isset($_POST['save']) ) {
          $admin_tags = wp_admin_blog_sec_var($_POST['admin_tags'], 'integer');
          $admin_messages = wp_admin_blog_sec_var($_POST['admin_messages'], 'integer');
          $auto_reply = wp_admin_blog_sec_var($_POST['auto_reply']);
          $media_upload = wp_admin_blog_sec_var($_POST['media_upload']);
          $name_blog = wp_admin_blog_sec_var($_POST['name_blog']);
          $name_widget = wp_admin_blog_sec_var($_POST['name_widget']);
          $userrole = $_POST['userrole'];
          $blog_post = $_POST['blog_post'];
          wp_admin_blog_update_options($userrole, $name_blog, $name_widget, $admin_tags, $admin_messages, $auto_reply, $media_upload, $blog_post);
     }
     
     if ( !get_option('wp_admin_blog_name') ) { $name_blog = 'Microblog'; }
     else { $name_blog = get_option('wp_admin_blog_name'); }
     
     if ( !get_option('wp_admin_blog_name_widget') ) { $name_widget = 'Microblog'; }
     else { $name_widget = get_option('wp_admin_blog_name_widget'); }

     if ( !get_option('wp_admin_blog_number_tags') ) { $admin_tags = 50; }
     else { $admin_tags = get_option('wp_admin_blog_number_tags'); }

     if ( !get_option('wp_admin_blog_number_messages') ) { $admin_messages = 10; }
     else { $admin_messages = get_option('wp_admin_blog_number_messages'); }

     if ( !get_option('wp_admin_blog_auto_reply') ) { $auto_reply = 'false'; }
     else { $auto_reply = get_option('wp_admin_blog_auto_reply'); }

     if ( !get_option('wp_admin_blog_media_upload') ) { $media_upload = 'false'; }
     else { $media_upload = get_option('wp_admin_blog_media_upload'); }

     ?>
     <div class="wrap">
     <h2><?php _e('WP Admin Microblog Settings','wp_admin_blog'); ?></h2>
     <form name="form1" id="form1" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
     <input name="page" type="hidden" value="wp-admin-blog" />
     <table class="form-table">
        <tr>
             <th scope="row"><?php _e('Name of the Microblog','wp_admin_blog'); ?></th>
             <td><input name="name_blog" type="text" value="<?php echo $name_blog; ?>" /></td>
             <td><em><?php _e('Default: Microblog','wp_admin_blog'); ?></em></td>
        </tr>
        <tr>
             <th scope="row"><?php _e('Name of the dashboard widget','wp_admin_blog'); ?></th>
             <td><input name="name_widget" type="text" value="<?php echo $name_widget; ?>" /></td>
             <td><em><?php _e('Default: Microblog','wp_admin_blog'); ?></em></td>
        </tr>
        <tr>
             <th scope="row"><?php _e('Number of tags','wp_admin_blog'); ?></th>
             <td><input name="admin_tags" type="text" value="<?php echo $admin_tags; ?>" /></td>
             <td><em><?php _e('Default: 50','wp_admin_blog'); ?></em></td>
        </tr>
        <tr>
             <th scope="row"><?php _e('Number of messages per page','wp_admin_blog'); ?></th>
             <td><input name="admin_messages" type="text" value="<?php echo $admin_messages; ?>"/></td>
             <td><em><?php _e('Default: 10','wp_admin_blog'); ?></em></td>
        </tr>
        <tr>
        <th scope="row"><?php _e('Auto notification','wp_admin_blog'); ?></th>
             <td><select name="auto_reply">
                <?php
                if ($auto_reply == 'true') {
                echo '<option value="true" selected="selected">' . __('yes','wp_admin_blog') . '</option>';
                     echo '<option value="false">' . __('no','wp_admin_blog') . '</option>';
                }
                else {
                     echo '<option value="true">' . __('yes','wp_admin_blog') . '</option>';
                     echo '<option value="false" selected="selected">' . __('no','wp_admin_blog') . '</option>';
                } 
                ?>
             </select></td>
             <td><em><?php _e('Activate this option and the plugin insert in every reply the string for the auto e-mail notification','wp_admin_blog'); ?></em></td>
         </tr>
         <tr>
             <th scope="row"><?php _e('Media upload for the dashboard widget','wp_admin_blog'); ?></th>
             <td><select name="media_upload">
                <?php
                if ($media_upload == 'true') {
                    echo '<option value="true" selected="selected">' . __('yes','wp_admin_blog') . '</option>';
                    echo '<option value="false">' . __('no','wp_admin_blog') . '</option>';
                }
                else {
                    echo '<option value="true">' . __('yes','wp_admin_blog') . '</option>';
                    echo '<option value="false" selected="selected">' . __('no','wp_admin_blog') . '</option>';
                } 
                ?>
             </select></td>
             <td><em><?php _e('Activate this option to use the media upload for the WP Admin Microblog dashboard widget. If you use it, please notify, that the media upload will not work correctly for QuickPress.','wp_admin_blog'); ?></em></td>
         </tr>
             <tr>
         <th scope="row"><?php _e('Access for','wp_admin_blog'); ?></th>
             <td>
         <select name="userrole[]" id="userrole" multiple="multiple" style="height:80px;">
             <?php
              global $wp_roles;
              foreach ($wp_roles->role_names as $roledex => $rolename) {
                  $role = $wp_roles->get_role($roledex);
                  $select = $role->has_cap('use_wp_admin_microblog') ? 'selected="selected"' : '';
                  echo '<option value="'.$roledex.'" '.$select.'>'.$rolename.'</option>';
              }
              ?>
         </select>
         </td>
         <td><em><?php _e('Select which userroles have access to WP Admin Microblog.','wp_admin_blog'); ?><br /><?php _e('Use &lt;Ctrl&gt; key to select multiple roles.','wp_admin_blog'); ?></em></td>
         </tr>
         <tr>
         <th scope="row"><?php _e('"Message as a blog post"-function for','wp_admin_blog'); ?></th>
         <td>
         <select name="blog_post[]" id="blog_post" multiple="multiple" style="height:80px;">
             <?php
              foreach ($wp_roles->role_names as $roledex => $rolename) {
                  $role = $wp_roles->get_role($roledex);
                  $select = $role->has_cap('use_wp_admin_microblog_bp') ? 'selected="selected"' : '';
                  echo '<option value="'.$roledex.'" '.$select.'>'.$rolename.'</option>';
              }
              ?>
         </select>
         </td>
         <td><em><?php _e('Select which userroles can use the "Message as a blog post"-function.','wp_admin_blog'); ?><br /><?php _e('Use &lt;Ctrl&gt; key to select multiple roles.','wp_admin_blog'); ?></em></td>
         </tr>
     </table>
     <p class="submit">
     <input type="submit" name="save" id="save" class="button-primary" value="<?php _e('Save Changes', 'wp_admin_blog') ?>" />
     </p>
     </form>
     </div>
     <?php
}
/** 
 * Main Page
 * @global type $current_user
 * @global type $wpdb
 * @global string $admin_blog_posts
 * @global string $admin_blog_tags
 * @global string $admin_blog_relations
 * @global type $wpam_blog_name Main-Page
 */
function wp_admin_blog_page() {
   global $current_user;
   global $wpdb;
   global $admin_blog_posts;
   global $admin_blog_tags;
   global $admin_blog_relations;
   global $wpam_blog_name;
   get_currentuserinfo();
   $user = $current_user->ID;
   
   // new_post fields
   if ( isset( $_POST['wpam_nm_text'] ) ) { $content = wp_admin_blog_sec_var($_POST['wpam_nm_text']); }
   else { $content = ''; }
   
   if ( isset( $_POST['headline'] ) ) { $headline = wp_admin_blog_sec_var($_POST['headline']); }
   else { $headline = ''; }
   
   if ( isset( $_POST['tags'] ) ) { $tags = wp_admin_blog_sec_var($_POST['tags']); }
   else { $tags = ''; }
   
   if ( isset( $_POST['tas_wp_post'] ) ) { $as_wp_post = wp_admin_blog_sec_var($_POST['as_wp_post']); }
   else { $as_wp_post = ''; }
   
   // edit post fields
   if ( isset( $_GET['wp_admin_blog_edit_text'] ) ) { $text = wp_admin_blog_sec_var($_GET['wp_admin_blog_edit_text']); } 
   if ( isset( $_GET['wp_admin_blog_message_ID'] ) ) { $edit_message_ID = wp_admin_blog_sec_var($_GET['wp_admin_blog_message_ID'], 'integer'); } 
   if ( isset( $_GET['wp_admin_blog_parent_ID'] ) ) { $parent_ID = wp_admin_blog_sec_var($_GET['wp_admin_blog_parent_ID'], 'integer'); } 
   if ( isset( $_GET['delete'] ) ) { $delete = wp_admin_blog_sec_var($_GET['delete'], 'integer'); } 
   
   // filter
   if ( isset( $_GET['author'] ) ) { $author = wp_admin_blog_sec_var($_GET['author']); } 
   else { $author = ''; }
   
   if ( isset( $_GET['tag'] ) ) { $tag = wp_admin_blog_sec_var($_GET['tag']); } 
   else { $tag = ''; }
   
   if ( isset( $_GET['search'] ) ) { $search = wp_admin_blog_sec_var($_GET['search']); } 
   else { $search = ''; }
   
   if ( isset( $_GET['rpl'] ) ) { $rpl = wp_admin_blog_sec_var($_GET['rpl'], 'integer'); } 
   else { $rpl = ''; }
   
   
   // Number of messages
   if ( !get_option('wp_admin_blog_number_messages') ) { $number_messages = 10; }
   else { $number_messages = get_option('wp_admin_blog_number_messages'); }
   // Auto reply
   if ( !get_option('wp_admin_blog_auto_reply') ) { $auto_reply = 'false'; }
   else { $auto_reply = get_option('wp_admin_blog_auto_reply'); }
   // Handles limits 
   if (isset($_GET['limit'])) {
      $curr_page = (int)$_GET['limit'] ;
      if ( $curr_page <= 0 ) {
         $curr_page = 1;
      }
      $message_limit = ( $curr_page - 1 ) * $number_messages;
   }
   else {
      $message_limit = 0;
      $curr_page = 1;
   }
   // Handles actions
   if (isset($_POST['send'])) {
      wp_admin_blog_add_message($content, $user, $tags, 0);
      if ($as_wp_post == 'true') {
         wp_admin_blog_add_as_wp_post($content, $headline, $user, $tags);
      }
      $content = "";
   }	
   if (isset($_GET['delete'])) {
      wp_admin_blog_del_message($delete);
   }
   if (isset($_GET['wp_admin_blog_edit_message_submit'])) {
      wp_admin_blog_update_message($edit_message_ID, $text);
   }
   if (isset($_GET['wp_admin_blog_reply_message_submit'])) {
      wp_admin_blog_add_message($text, $user, $tags, $parent_ID);
   }
   ?>
    <div class="wrap" style="max-width:1200px; min-width:780px; width:96%; padding-top:10px;">
    <h2><?php echo $wpam_blog_name;?> <span class="tp_break">|</span> <small><a onclick="wpam_showhide('wam_hilfe_anzeigen')" style="cursor:pointer;"><?php _e('Help','wp_admin_blog'); ?></a></small></h2>
 <div id="wam_hilfe_anzeigen">
   <h3 class="wam_help"><?php _e('Help','wp_admin_blog'); ?></h3>
        <p class="wam_help_h"><?php _e('E-mail notification','wp_admin_blog'); ?></p>
        <p class="wam_help_t"><?php _e('If you will send your message as an E-Mail to any user, so write @username (example: @admin)','wp_admin_blog'); ?></p>
        <p class="wam_help_h"><?php _e('Text formatting','wp_admin_blog'); ?></p>
        <p class="wam_help_t"><?php _e('You can use simple bbcodes: [b]bold[/b], [i]italic[/i], [u]underline[/u], [s]strikethrough[/s], [red]red[/red], [blue]blue[/blue], [green]green[/green], [orange]orange[/orange]. Combinations like [red][s]text[/s][/red] are possible. The using of HTML tags is not possible.','wp_admin_blog'); ?></p>
        <p class="wam_help_c"><strong><a onclick="wpam_showhide('wam_hilfe_anzeigen')" style="cursor:pointer;"><?php _e('close','wp_admin_blog'); ?></a></strong></p>
    </div>
    <div style="width:31%; float:right; padding-right:1%;">
    <form name="blog_selections" method="get">
    <input name="page" type="hidden" value="wp-admin-microblog/wp-admin-microblog.php" />
    <input name="author" type="hidden" value="<?php echo $author; ?>" />
    <input name="tag" type="hidden" value="<?php echo $tag; ?>" />
    <table class="widefat">
    <thead>
        <tr>
            <th><?php
              if ($search != "") { ?>
            	<label for="suche_abbrechen" title="<?php _e('Delete the search from filter','wp_admin_blog'); ?>"><?php _e('Search', 'wp_admin_blog'); ?><a id="suche_abbrechen" href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;author=<?php echo $author; ?>&amp;search=&amp;tag=<?php echo $tag;?>" style="text-decoration:none; color:#FF9900;" title="<?php _e('Delete the search from filter','wp_admin_blog'); ?>"> X</a></label><?php 
              }
              else {
                 _e('Search', 'wp_admin_blog');
              }?>
            </th>
        </tr>
        <tr>
            <td>
            <input name="search" type="text"  value="<?php if ($search != "") { echo $search; } else { _e('Search word', 'wp_admin_blog'); }?>" onblur="if(this.value=='') this.value='<?php _e('Search word', 'wp_admin_blog'); ?>';" onfocus="if(this.value=='<?php _e('Search word', 'wp_admin_blog'); ?>') this.value='';"/>
            <input name="search_init" type="submit" class="button-secondary" value="<?php _e('Go', 'wp_admin_blog');?>"/>
            </td>
        </tr>    
    </thead>
    </table>
    <p style="margin:0px; font-size:2px;">&nbsp;</p>
    <table class="widefat">
    <thead>
        <tr>
            <th><?php _e('Tags', 'wp_admin_blog');?></th>
        </tr>
        <tr>
            <td><div style="padding:5px;">
             <?php
                 // number of tags
                 if ( !get_option('wp_admin_blog_number_tags') ) { $limit = 50; }
                 else { $limit = get_option('wp_admin_blog_number_tags'); }
                 // font sizes
                 $maxsize = 35;
                 $minsize = 11;
                 // Count number of tags
                 $sql = "SELECT anzahlTags FROM ( SELECT COUNT(*) AS anzahlTags FROM " . $admin_blog_relations . " GROUP BY " . $admin_blog_relations . ".`tag_ID` ORDER BY anzahlTags DESC ) as temp1 GROUP BY anzahlTags ORDER BY anzahlTags DESC";
                 // Count all tags and find max, min
                 $sql = "SELECT MAX(anzahlTags) AS max, min(anzahlTags) AS min, COUNT(anzahlTags) as gesamt FROM (".$sql.") AS temp";
                 $tagcloud_temp = $wpdb->get_row($sql, ARRAY_A);
                 $max = $tagcloud_temp['max'];
                 $min = $tagcloud_temp['min'];
                 $insgesamt = $tagcloud_temp['gesamt'];
                 // if there are tags in database
                 if ($insgesamt != 0) {
                         // compose tags and their numbers
                    $sql = "SELECT tagPeak, name, tag_ID FROM ( SELECT COUNT(b.tag_ID) as tagPeak, t.name AS name,  t.tag_ID as tag_ID FROM " . $admin_blog_relations . " b LEFT JOIN " . $admin_blog_tags . " t ON b.tag_ID = t.tag_ID GROUP BY b.tag_ID ORDER BY tagPeak DESC LIMIT " . $limit . " ) AS temp WHERE tagPeak>=".$min." ORDER BY name";
                    $temp = $wpdb->get_results($sql, ARRAY_A);
                    // create a cloud
                    foreach ($temp as $tagcloud) {
                       // compute font size
                       // offset for min
                       if ($min == 1) {
                          $min = 0;
                       }
                       $div = $max - $min;
                       if ($div == 0) {
                          $div = 1;
                       }
                       // Formula: max. font size*(current number - min number)/ (max number - min number)
                       $size = floor(($maxsize*($tagcloud['tagPeak']-$min)/($div)));
                       // offset for font size
                       if ($size < $minsize) {
                          $size = $minsize ;
                       }
                       // active tag
                       if ($tagcloud['tag_ID'] == $tag){
                          echo '<span style="font-size:' . $size . 'px;"><a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;author=' . $author . '&amp;search=' . $search . '" title="' . __('Delete the tag from filter','wp_admin_blog') . '" style="color:#FF9900; text-decoration:underline;">' . $tagcloud['name'] . '</a></span> '; 
                       }
                       else{
                          echo '<span style="font-size:' . $size . 'px;"><a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;author=' . $author . '&amp;search=' . $search . '&amp;tag=' . $tagcloud['tag_ID'] . '" title="' . __('Show related messages','wp_admin_blog') . '">' . $tagcloud['name'] . '</a></span> '; 
                       }
                    }
                 }
                 else {
                    _e('No tags available','wp_admin_blog');
                 }?>
             </div>     
            </td>
        </tr>
    </thead>
    </table>
    <p style="margin:0px; font-size:2px;">&nbsp;</p>
    <table class="widefat">
    <thead>
        <tr>
            <th><?php _e('User', 'wp_admin_blog');?></th>
        </tr>
        <tr>
            <td>
       	    <?php
            $sql = "SELECT DISTINCT user FROM " . $admin_blog_posts . "";
            $users = $wpdb->get_results($sql);
            foreach ($users as $users) {
                 $user_info = get_userdata($users->user);
                 $name = '' . $user_info->display_name . ' (' . $user_info->user_login . ')';
                 if ($author == $user_info->ID) {
                         echo '<a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;author=&amp;search=' . $search . '&amp;tag=' . $tag . '" title="' . __('Delete user as filter','wp_admin_blog') . '" style="padding:3px; border-bottom:2px solid #FF9900;">';
                 }
                 else {
                         echo '<a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;author=' . $user_info->ID . '&amp;search=' . $search . '&amp;tag=' . $tag . '" title="' . $name . '" style="padding:3px;">';
                 }
                 echo get_avatar($user_info->ID, 35);
                 echo '</a>';
            }
            ?>
            </td>
         </tr>   
    </thead>
    </table>
    </form>
    </div>
    <div style="width:66%; float:left; padding-right:1%;">
    <form name="new_post" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" id="new_post_form">
    <table class="widefat">
    <thead>
        <tr>
            <th><?php _e('Your Message', 'wp_admin_blog');?></th>
        </tr>
        <tr>
            <td>
            <div class="wpam_media_buttons" style="text-align:right;"><?php echo wp_admin_blog_media_buttons(); ?></div>
            <div id="postdiv" class="postarea" style="display:block;">
            <textarea name="wpam_nm_text" id="wpam_nm_text" style="width:100%;" rows="4"></textarea>
            <p><input name="tags" type="text" style="width:100%;" value="<?php _e('Tags (seperate by comma)', 'wp_admin_blog');?>" onblur="if(this.value=='') this.value='<?php _e('Tags (seperate by comma)', 'wp_admin_blog'); ?>';" onfocus="if(this.value=='<?php _e('Tags (seperate by comma)', 'wp_admin_blog'); ?>') this.value='';" /></p>
            </div>
            <table style="width:100%;">
            	<tr>
                	<?php if ( current_user_can( 'use_wp_admin_microblog_bp' ) ) { ?>
                	<td style="border-bottom-width:0px;"><input name="as_wp_post" id="as_wp_post" type="checkbox" value="true" onclick="javascript:wpam_showhide('span_headline')" /> <label for="as_wp_post"><?php _e('as WordPress blog post', 'wp_admin_blog');?></label> <span style="display:none;" id="span_headline">&rarr; <label for="headline"><?php _e('Title', 'wp_admin_blog');?> </label><input name="headline" id="headline" type="text" style="width:80%;" /></span></td>
                    <?php } ?>
            		<td style="text-align:right; border-bottom-width:0px;"><input name="send" type="submit" class="button-primary" value="<?php _e('Send', 'wp_admin_blog'); ?>" /></td>
            	</tr>
            </table>
           </td>
    	</tr>
    </thead>
    </table>
    </form>
    <p style="margin:0px; font-size:2px;">&nbsp;</p>
    <form name="all_messages" method="get">
    <input name="page" type="hidden" value="wp-admin-microblog/wp-admin-microblog.php" />
    <table class="widefat">
    <thead>
        <tr>
            <th colspan="2">
             <?php
              if ( $search != '' || $author != '' || $tag != '' || $rpl != '' ) {
                 echo '' . __('Search Results', 'wp_admin_blog') . ' | <a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php">' . __('Show all','wp_admin_blog') . '</a>';
              }
              else {
                 echo '' . __('Messages', 'wp_admin_blog') . '';
              }
               ?>
            </th>
        </tr>
         <?php
         // build SQL requests
         if ( $search != '' || $author != '' || $tag != '' ) {
            $select = "SELECT DISTINCT p.post_ID, p.post_parent, p.text, p.date, p.user FROM " . $admin_blog_posts . " p
                          LEFT JOIN " . $admin_blog_relations . " r ON r.post_ID = p.post_ID
                          LEFT JOIN " . $admin_blog_tags . " t ON t.tag_ID = r.tag_ID";
            // is author or user?
            if ($author != '' && $search != '') {
               $where = "WHERE p.user = '$author' AND p.text LIKE '%$search%'";
            }
            elseif ($author == '' && $search != '') {
               $where = "WHERE p.text LIKE '%$search%'";
            }
            elseif ($author != '' && $search == '') {
               $where = "WHERE p.user = '$author'";
            }
            else {
               $where = "";
            }
            // is tag?
            if ($tag != '') {
               if ($where != "") {
                  $where = $where . "AND t.tag_ID = $tag";
               }
               else {
                  $where = "WHERE t.tag_ID = '$tag'";
               }
            }	
            $sql = "" . $select . " " . $where . " ORDER BY p.post_ID DESC LIMIT $message_limit, $number_messages";
            $test_sql = "" . $select . " " . $where . " ORDER BY p.post_ID DESC";				
         }
         // is replies?
         elseif( $rpl != '' ) {
            $sql = "SELECT * FROM " . $admin_blog_posts . " WHERE post_parent = '$rpl' OR post_ID = '$rpl' ORDER BY post_ID DESC LIMIT $message_limit, $number_messages";
            $test_sql = "SELECT post_ID FROM " . $admin_blog_posts . " WHERE post_parent = '$rpl' OR post_ID = '$rpl' ORDER BY post_ID DESC";
         }
         // Normal SQL
         else {
            $sql = "SELECT * FROM " . $admin_blog_posts . " ORDER BY post_ID DESC LIMIT $message_limit, $number_messages";
            $test_sql = "SELECT post_ID FROM " . $admin_blog_posts . " ORDER BY post_ID DESC";
         }
         // Find number of entries
         $test = $wpdb->query($test_sql);
         if ($test == 0) {
            echo '<tr><td>' . __('Sorry, no entries mached your criteria','wp_admin_blog') . '</td></tr>';
         }
         else {
            // print menu
            echo '<tr>';
            echo '<td colspan="2" style="text-align:center;" class="tablenav"><div class="tablenav-pages" style="float:none;">' . wp_admin_blog_page_menu($test, $number_messages, $curr_page, $message_limit, 'admin.php?page=wp-admin-microblog/wp-admin-microblog.php', 'search=' . $search . '&amp;author=' . $author . '&amp;tag=' . $tag . '') . '</div></td>';
            echo '</tr>';
            $message_date_old = '';
            // Entries
            $post = $wpdb->get_results($sql);
            $sql = "SELECT COUNT(post_parent) AS gesamt, post_parent FROM " . $admin_blog_posts . " GROUP BY post_parent";
            $replies = $wpdb->get_results($sql);
            foreach ($post as $post) {
               $user_info = get_userdata($post->user);
               $edit_button = '';
               $edit_button2 = '';
               $count_rep = 0;
               $rpl = 0;
               $str = "'";
               $today = false;
               $time = wp_admin_blog_datumsplit($post->date);
               $message_text = wp_admin_blog_replace_url($post->text);
               $message_text = wp_admin_blog_replace_bbcode($message_text);
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
               // Handles german date format
               if ( __('en','wp_admin_blog') == 'de') {
                  $message_date = '' . $time[0][2]. '.' . $time[0][1] . '.' . $time[0][0] . '';
                  if ( date('d.m.Y') == $message_date ) {
                     $today = true;
                  }
               }
               else {
                  $message_date = '' . $time[0][0]. '-' . $time[0][1] . '-' . $time[0][2] . '';
                  if ( date('Y-m-d') == $message_date ) {
                     $today = true;
                  }
               }
               // Handles post parent
               if ($post->post_parent == '0') {
                  $post->post_parent = $post->post_ID;
               }
               // Message Menu
               if ($count_rep != 0) {
                  $edit_button2 = ' | <a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;rpl=' . $rpl . '" class="wam_replies">' . $count_rep . ' ' . __('Replies','wp_admin_blog') . '</a>';
               }
               if ($post->user == $user) {
                  $edit_button = $edit_button . '<a onclick="javascript:wpam_editMessage(' . $post->post_ID . ')" style="cursor:pointer;" title="' . __('Edit this message','wp_admin_blog') . '">' . __('Edit','wp_admin_blog') . '</a> | <a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&delete=' . $post->post_ID . '" title="' . __('Click to delete this message','wp_admin_blog') . '" style="color:#FF0000">' . __('Delete','wp_admin_blog') . '</a> | ';
               }
               $edit_button = $edit_button . '<a onclick="javascript:wpam_replyMessage(' . $post->post_ID . ',' . $post->post_parent . ',' . $str . '' . $auto_reply . '' . $str . ',' . $str . '' . $user_info->user_login . '' . $str . ')" style="cursor:pointer; color:#009900;" title="' . __('Write a reply','wp_admin_blog') . '">' . __('Reply','wp_admin_blog') . '</a>';
               // Message date headlines
               if ($message_date != $message_date_old) {
                  if ($today == true) {
                     echo '<tr><td colspan="2"><strong>' . __('Today','wp_admin_blog') . '</strong></td></tr>';
                  }
                  else {
                     echo '<tr><td colspan="2"><strong>' . $message_date . '</strong></td></tr>';
                  }
               }
               // print messages
               $message_time = human_time_diff( mktime($time[0][3], $time[0][4], $time[0][5], $time[0][1], $time[0][2], $time[0][0] ), current_time('timestamp') ) . ' ' . __( 'ago', 'wp_admin_blog' );
               echo '<tr>';
               echo '<td style="padding:10px; width:60px;">
                               <span title="' . $user_info->display_name . ' (' . $user_info->user_login . ')">' . get_avatar($user_info->ID, 50) . '</span></td>';
               echo '<td style="padding:10px;">
                       <div id="wp_admin_blog_message_' . $post->post_ID . '">
                          <p style="color:#AAAAAA;">' . $message_time . ' | ' . __('by','wp_admin_blog') . ' ' . $user_info->display_name . '' . $edit_button2 . '</p>
                          <p>' . $message_text . '</p>
                          <div class="wam-row-actions">' . $edit_button . '</div>
                       </div>
                       <input name="wp_admin_blog_message_text" id="wp_admin_blog_message_text_' . $post->post_ID . '" type="hidden" value="' . $post->text . '" />';
               echo '</td>';
               echo '</tr>';
               $message_date_old = $message_date;
            }
            // Page Menu
            if ($test > $number_messages) {
               echo '<tr>';
               echo '<td colspan="2" style="text-align:center;" class="tablenav"><div class="tablenav-pages" style="float:none;">' . wp_admin_blog_page_menu($test, $number_messages, $curr_page, $message_limit, 'admin.php?page=wp-admin-microblog/wp-admin-microblog.php', 'search=' . $search . '&amp;author=' . $author . '&amp;tag=' . $tag . '', 'bottom') . '</td>';
               echo '</tr>';
            }
         }
       ?>
    </thead>
    </table>
    </form>
    </div>
    </div>
	<?php
}
/*
 * Add scripts ans stylesheets
*/ 
function wp_admin_blog_header() {
   // Define $page
   if ( isset($_GET['page']) ) {
           $page = $_GET['page'];
   }
   else {
           $page = '';
   }
   // load scripts only, when it's wp_admin_blog page
   if ( eregi('wp-admin-microblog', $page) || eregi('wp-admin/index.php', $_SERVER['REQUEST_URI']) ) {
      wp_register_script('wp_admin_blog', WP_PLUGIN_URL . '/wp-admin-microblog/wp-admin-microblog.js');
      wp_register_style('wp_admin_blog_css', WP_PLUGIN_URL . '/wp-admin-microblog/wp-admin-microblog.css');
      wp_enqueue_style('wp_admin_blog_css');
      wp_enqueue_script('wp_admin_blog');
      wp_enqueue_script('media-upload');
      add_thickbox();
   }
   // load the hack for the normal WP Admin Microblog page
   if ( eregi('wp-admin-microblog', $page) ) {
      wp_register_script('wp_admin_blog_upload_hack', WP_PLUGIN_URL . '/wp-admin-microblog/media-upload-hack.js');
      wp_enqueue_script('wp_admin_blog_upload_hack');
   }
   // load the hack for the dashboard, when the user say yes
   $test = get_option('wp_admin_blog_media_upload');
   if (eregi('wp-admin/index.php', $_SERVER['REQUEST_URI']) && $test == 'true') {
      wp_register_script('wp_admin_blog_upload_hack', WP_PLUGIN_URL . '/wp-admin-microblog/media-upload-hack.js');
      wp_enqueue_script('wp_admin_blog_upload_hack');
   }
}
/*
 * Installer
*/
function wp_admin_blog_install () {
   global $wpdb;
   $admin_blog_posts = $wpdb->prefix . 'admin_blog_posts';
   $admin_blog_tags = $wpdb->prefix . 'admin_blog_tags';
   $admin_blog_relations = $wpdb->prefix . 'admin_blog_relations';

   // Add capabilities
   global $wp_roles;
   $role = $wp_roles->get_role('administrator');
   if ( !$role->has_cap('use_wp_admin_microblog') ) {
      $wp_roles->add_cap('administrator', 'use_wp_admin_microblog');
   }
   if ( !$role->has_cap('use_wp_admin_microblog_bp') ) {
           $wp_roles->add_cap('administrator', 'use_wp_admin_microblog_bp');
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
   // Post table
   $table_name = $admin_blog_posts;
   if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
      $sql = "CREATE TABLE " . $admin_blog_posts. " (
                        post_ID INT UNSIGNED AUTO_INCREMENT ,
                        post_parent INT ,
                        text LONGTEXT ,
                        date DATETIME ,
                        user INT ,
                        PRIMARY KEY (post_ID)
                  ) $charset_collate;";
      require_once(ABSPATH . 'wp-admin/upgrade-functions.php');		
      dbDelta($sql);
   }
   // Tag table
   $table_name = $admin_blog_tags;
   if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
      $sql = "CREATE TABLE " . $admin_blog_tags. " (
                        tag_ID INT UNSIGNED AUTO_INCREMENT ,
                        name VARCHAR (200) ,
                        PRIMARY KEY (tag_ID)
                    ) $charset_collate;";
      require_once(ABSPATH . 'wp-admin/upgrade-functions.php');			
      dbDelta($sql);
   }
   // Relation
   $table_name = $admin_blog_relations;
   if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
      $sql = "CREATE TABLE " . $admin_blog_relations. " (
                        rel_ID INT UNSIGNED AUTO_INCREMENT ,
                        post_ID INT ,
                        tag_ID INT ,
                        PRIMARY KEY (rel_ID)
                    ) $charset_collate;";
      require_once(ABSPATH . 'wp-admin/upgrade-functions.php');			
      dbDelta($sql);
   }
   if ( !get_option('wp_admin_blog_name') ) {
      add_option('wp_admin_blog_name', 'Microblog', '', 'no');
   }
   if ( !get_option('wp_admin_blog_name_widget') ) {
      add_option('wp_admin_blog_name_widget', 'Microblog', '', 'no');
   }
   if ( !get_option('wp_admin_blog_number_tags') ) {
      add_option('wp_admin_blog_number_tags', '50', '', 'no');
   }
   if ( !get_option('wp_admin_blog_number_messages') ) {
      add_option('wp_admin_blog_number_messages', '10', '', 'no');
   }
   if ( !get_option('wp_admin_blog_auto_reply') ) {
      add_option('wp_admin_blog_auto_reply', 'false', '', 'no');
   }
   if ( !get_option('wp_admin_blog_media_upload') ) {
      add_option('wp_admin_blog_media_upload', 'false', '', 'no');
   }
}
// load language support
function wp_admin_blog_language_support() {
   load_plugin_textdomain('wp_admin_blog', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
// Register WordPress hooks
register_activation_hook( __FILE__, 'wp_admin_blog_install');
add_action('init', 'wp_admin_blog_language_support');
add_action('admin_init','wp_admin_blog_header');
add_action('admin_menu','wp_admin_blog_menu');
add_action('admin_menu', 'wp_admin_blog_add_menu_settings');
add_action('wp_dashboard_setup', 'wp_admin_blog_add_widgets' );
?>