<?php
/*
Plugin Name: WP Admin Microblog
Plugin URI: http://www.mtrv.kilu.de/microblog/
Description: Adds a microblog in your WordPress backend.
Version: 0.5.2
Author: Michael Winkler
Author URI: http://www.mtrv.kilu.de/
Min WP Version: 2.8
Max WP Version: 2.9.2
*/

/*
   LICENCE
 
    Copyright 2010  Michael Winkler

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
*/

// Define databases
global $wpdb;
$admin_blog_posts = $wpdb->prefix . 'admin_blog_posts';
$admin_blog_tags = $wpdb->prefix . 'admin_blog_tags';
$admin_blog_relations = $wpdb->prefix . 'admin_blog_relations';

// Define menus
function wp_admin_blog_menu() {
	add_menu_page(__('Blog','wp_admin_blog'), __('Microblog','wp_admin_blog'),'use_wp_admin_microblog', __FILE__, 'wp_admin_blog_page', WP_PLUGIN_URL . '/wp-admin-microblog/logo.png');
}
function wp_admin_blog_add_menu_settings() {
	add_options_page(__('WP Admin Microblog Settings','wp_admin_blog'),'WP Admin Microblog','administrator','wp-admin-blog', 'wp_admin_blog_settings');
}

/* Add new message
 * @param $content - text of a message
 * @param $user - WordPress user ID
 * @param $tags - string with the tags
 */
function add_message ($content, $user, $tags, $parent) {
	global $wpdb;
	global $admin_blog_posts;
	global $admin_blog_tags;
	global $admin_blog_relations;
	$content = htmlentities(utf8_decode($content));
	$content = nl2br($content);
	$sql = sprintf("INSERT INTO " . $admin_blog_posts . " (`post_parent`, `text`, `date`, `user`) VALUES('$parent', '$content', NOW(), '$user')", 
		mysql_real_escape_string( "$" . $admin_blog_posts . "_text"));
	$wpdb->query($sql);
	// Tags
	$array = explode(",",$tags);
	foreach($array as $element) {
		// Check if tag is in database
		$element = trim($element);
		if ($element != "" && $element != __('Tags (seperate with comma)', 'wp_admin_blog')) {
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
	// Send message
	wp_admin_blog_find_user($content);
}
/* Delete message
 * @param $delete - Post ID
 */
function del_message($delete) {
	global $wpdb;
	global $admin_blog_posts;
	global $admin_blog_relations;
	$wpdb->query( "DELETE FROM " . $admin_blog_posts . " WHERE post_ID = $delete" );
	$wpdb->query( "DELETE FROM " . $admin_blog_relations . " WHERE post_ID = $delete" );
}
/* Update message
 * @param $post_ID - Post ID
 * @param $text - text
 */
function update_message($post_ID, $text) {
	global $wpdb;
	global $admin_blog_posts;
	global $admin_blog_relations;
	$text = htmlentities(utf8_decode($text));
	$text = nl2br($text);
	$wpdb->query( sprintf("UPDATE " . $admin_blog_posts . " SET text = '$text' WHERE post_ID = '$post_ID'",
	mysql_real_escape_string( "$" . $admin_blog_posts . "_text") ));
}

/* Split the timestamp
 * @param $datum - timestamp
 * return $split
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

/* Replace URL strings with HTML URL strings
 * @param $text - string
 * return $text - string
*/ 
function wp_admin_blog_replace_url($text) {	
	if ( preg_match_all("((http://|https://|ftp://|mailto:)[^ ]+)", $text, $match) ) {
		$prefix = '#(^|[^"=]{1})(http://|ftp://|mailto:|news:)([^\s]+)([\s\n]|$)#sm';
		for ($x = 0; $x < count($match[0]); $x++) {
			$text = preg_replace($prefix, ' <a href="' . $match[0][$x] . '" target="_blank" title="' . $match[0][$x] . '">' . $match[0][$x] . '</a> ', $text); 
		}
	}
	return $text;
}
/* Handle bbcodes
 * @param $text - string
 * @param $mode - string --> replace (replace with HTML-Tag) or delete (Delete bbcode)
 * return $text - string
*/
function wp_admin_blog_replace_bbcode($text, $mode = 'replace') {
	if ($mode == 'replace') {
		$text = preg_replace("/\[b\](.*)\[\/b\]/Usi", "<strong>\\1</strong>", $text); 
		$text = preg_replace("/\[i\](.*)\[\/i\]/Usi", "<em>\\1</em>", $text); 
		$text = preg_replace("/\[u\](.*)\[\/u\]/Usi", "<u>\\1</u>", $text);
		$text = preg_replace("/\[s\](.*)\[\/s\]/Usi", "<s>\\1</s>", $text);
	}
	if ($mode == 'delete') {
		$text = preg_replace("/\[b\](.*)\[\/b\]/Usi", "\\1", $text); 
		$text = preg_replace("/\[i\](.*)\[\/i\]/Usi", "\\1", $text); 
		$text = preg_replace("/\[u\](.*)\[\/u\]/Usi", "\\1", $text);
		$text = preg_replace("/\[s\](.*)\[\/s\]/Usi", "\\1", $text);
	}
	return $text;
}

/* Find users in string an send mail
 * @param $text - string
*/ 
function wp_admin_blog_find_user($text) {
	global $wpdb;
	global $admin_blog_posts;
	$text = $text . ' ';
	$text = wp_admin_blog_replace_bbcode($text, 'delete');
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
/* Update who can use the plugin
 * @param $roles
 */
function wp_admin_blog_update_options ($roles) {
	global $wp_roles;
    $wp_roles->WP_Roles();

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
} 

/*
 * PAGES
*/ 
// Option Page
function wp_admin_blog_settings () {
$userrole = $_GET[userrole];
if ( isset($_GET[save]) ) {
	wp_admin_blog_update_options($userrole);
}
?>
<div class="wrap">
<h2><?php _e('WP Admin Microblog Settings','wp_admin_blog'); ?></h2>
<form name="form1" id="form1" method="get" action="<?php echo $PHP_SELF ?>">
<input name="page" type="hidden" value="wp-admin-blog" />
<table class="form-table">
	<tr>
    <th scope="row"><strong><?php _e('Access for','wp_admin_blog'); ?></strong><br /><em><?php _e('Select which userrole your users must have to use WP Admin Microblog.','wp_admin_blog'); ?></em></th>
	<td>
    <select name="userrole[]" id="userrole" multiple="multiple" style="height:80px;">
	<?php
    global $wp_roles;
    $wp_roles->WP_Roles();
    foreach ($wp_roles->role_names as $roledex => $rolename) 
    {
        $role = $wp_roles->get_role($roledex);
        $select = $role->has_cap('use_wp_admin_microblog') ? 'selected="selected"' : '';
        echo '<option value="'.$roledex.'" '.$select.'>'.$rolename.'</option>';
    }
    ?>
	</select>
	<small class="setting-description"><?php _e('use &lt;Ctrl&gt; key to select multiple roles','wp_admin_blog'); ?></small>
	</td>
    </tr>
</table>
<p class="submit">
<input type="submit" name="save" id="save" class="button-primary" value="<?php _e('Save Changes', 'wp_admin_blog') ?>" />
</p>
</form>
</div>
<?php
}
// Main-Page
function wp_admin_blog_page() {
	global $current_user;
	global $wpdb;
	global $admin_blog_posts;
	global $admin_blog_tags;
	global $admin_blog_relations;
	get_currentuserinfo();
	$user = $current_user->ID;
	$content = $_POST[content];
	$tags = htmlentities(utf8_decode($_POST[tags]));
	$author = htmlentities(utf8_decode($_GET[author]));
	$tag = htmlentities(utf8_decode($_GET[tag]));
	$delete = htmlentities(utf8_decode($_GET[delete]));
	$search = htmlentities(utf8_decode($_GET[search]));
	$text = $_GET[edit_text];
	$edit_message_ID = htmlentities(utf8_decode($_GET[message_ID]));
	$parent_ID = htmlentities(utf8_decode($_GET[parent_ID]));
	$rpl = htmlentities(utf8_decode($_GET[rpl]));
	$number_messages = 10;
	// Handles limits 
	if (isset($_GET[limit])) {
		$message_limit = (int)$_GET[limit];
		if ($message_limit < 0) {
			$message_limit = 0;
		}
	}
	else {
		$message_limit = 0;
	}
	// Handles actions
	if (isset($_POST[send])) {
		add_message($content, $user, $tags, 0);
		$content = "";
	}	
	if (isset($_GET[delete])) {
		del_message($delete);
	}
	if (isset($_GET[edit_message_submit])) {
		update_message($edit_message_ID, $text);
	}
	if (isset($_GET[reply_message_submit])) {
		add_message($text, $user, $tags, $parent_ID);
	}
	?>
    <div class="wrap" style="max-width:1200px; min-width:780px; width:96%; padding-top:10px;">
    
    <h2><?php _e('Microblog','wp_admin_blog');?><span class="tp_break">|</span> <small><a onclick="wp_admin_blog_showhide('hilfe_anzeigen')" style="cursor:pointer;"><?php _e('Help','wp_admin_blog'); ?></a></small></h2>
 <div id="hilfe_anzeigen">
    	<h3 class="teachpress_help"><?php _e('Help','wp_admin_blog'); ?></h3>
        <p class="hilfe_headline"><?php _e('E-mail notification','wp_admin_blog'); ?></p>
        <p class="hilfe_text"><?php _e('If you will send your message as an E-Mail to any user, so write @username (example: @admin)','wp_admin_blog'); ?></p>
        <p class="hilfe_headline"><?php _e('Text formatting','wp_admin_blog'); ?></p>
        <p class="hilfe_text"><?php _e('You can use simple bbcodes: [b]bold[/b], [i]italic[/i], [u]underline[/u] and [s]strikethrough[/s]. The using of HTML tags is not possible.','wp_admin_blog'); ?></p>
        <p class="hilfe_close"><strong><a onclick="wp_admin_blog_showhide('hilfe_anzeigen')" style="cursor:pointer;"><?php _e('close','wp_admin_blog'); ?></a></strong></p>
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
			if (isset($_GET[search]) && $_GET[search] != "") { ?>
            	<label for="suche_abbrechen" title="<?php _e('Delete the search from filter','wp_admin_blog'); ?>"><?php _e('Search', 'wp_admin_blog'); ?><a id="suche_abbrechen" href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;author=<?php echo $author; ?>&amp;search=&amp;tag=<?php echo $tag;?>" style="text-decoration:none; color:#FF9900;" title="<?php _e('Delete the search from filter','wp_admin_blog'); ?>"> X</a></label><?php 
			}
			else {
				_e('Search', 'wp_admin_blog');
			}?>
            </th>
        </tr>
        <tr>
        	<td>
            <input name="search" type="text"  value="<?php if (isset($_GET[search]) && $_GET[search] != "") { echo $search; } else { _e('Search word', 'wp_admin_blog'); }?>" onblur="if(this.value=='') this.value='<?php _e('Search word', 'wp_admin_blog'); ?>';" onfocus="if(this.value=='<?php _e('Search word', 'wp_admin_blog'); ?>') this.value='';"/>
            <input name="search_init" type="submit" value="<?php _e('Go', 'wp_admin_blog');?>"/>
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
				$limit = 50;
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
						// Formula: max. font size*(current number - min number)/ (max number - min number)
						$size = floor(($maxsize*($tagcloud['tagPeak']-$min)/($max-$min)));
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
    <form name="new_post" method="post" action="<?php echo $PHP_SELF ?>" id="new_post_form">
    <table class="widefat">
    <thead>
        <tr>
            <th><?php _e('Your Message', 'wp_admin_blog');?></th>
        </tr>
        <tr>
        	<td>
            <div id="postdiv" class="postarea">
            <textarea name="content" id="content" style="width:100%;" rows="4"></textarea>
            </div>
            <p><input name="tags" type="text" style="width:100%;" value="<?php _e('Tags (seperate with comma)', 'wp_admin_blog');?>" onblur="if(this.value=='') this.value='<?php _e('Tags (seperate with comma)', 'wp_admin_blog'); ?>';" onfocus="if(this.value=='<?php _e('Tags (seperate with comma)', 'wp_admin_blog'); ?>') this.value='';"></p>
            <p><input name="send" type="submit" class="button-primary" value="<?php _e('Send', 'wp_admin_blog'); ?>" onclick=""></p>
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
            <th colspan="2"><?php
			if ( $_GET[search] != '' || $_GET[author] != '' || $_GET[tag] != '' || $_GET[rpl] != '' ) {
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
			if (isset($_GET[search]) || isset($_GET[author]) || isset($_GET[tag])) {
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
			elseif( isset($_GET[rpl]) ) {
				$sql = "SELECT * FROM " . $admin_blog_posts . " WHERE post_parent = '$rpl' OR post_ID = '$rpl' ORDER BY post_ID DESC LIMIT $message_limit, $number_messages";
				$test_sql = "SELECT * FROM " . $admin_blog_posts . " WHERE post_parent = '$rpl' OR post_ID = '$rpl' ORDER BY post_ID DESC";
			}
			// Normal SQL
			else {
            	$sql = "SELECT * FROM " . $admin_blog_posts . " ORDER BY post_ID DESC LIMIT $message_limit, $number_messages";
				$test_sql = "SELECT * FROM " . $admin_blog_posts . " ORDER BY post_ID DESC";
			}
			// Find number of entries
			$test = $wpdb->query($test_sql);
			if ($test == 0) {
				echo '<tr><td>' . __('Sorry, no entries mached your criteria','wp_admin_blog') . '</td></tr>';
			}
			else {
				// Page Menu
				if ($test > $number_messages) {
					$num_pages = floor (($test / $number_messages) + 1);
					// previous page link
					if ($message_limit != 0) {
						$all_pages = $all_pages . '<a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;limit=' . ($message_limit - $number_messages) . '&amp;author=' . $author . '&amp;search=' . $search . '&amp;tag=' . $tag . '" title="' . __('previous page','wp_admin_blog') . '" class="page-numbers">&larr;</a> ';
					}	
					// page numbers
					$akt_seite = $message_limit + $number_messages;
					for($i=1; $i <= $num_pages; $i++) { 
						$s = $i * $number_messages;
						// First and last page
						if ( ($i == 1 && $s != $akt_seite ) || ($i == $num_pages && $s != $akt_seite ) ) {
							$all_pages = $all_pages . '<a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;limit=' . ( $s - $number_messages) . '&amp;author=' . $author . '&amp;search=' . $search . '&amp;tag=' . $tag . '" title="' . __('Page','wp_admin_blog') . ' ' . $i . '" class="page-numbers">' . $i . '</a> ';
						}
						// current page
						elseif ( $s == $akt_seite ) {
							$all_pages = $all_pages . '<span class="page-numbers current">' . $i . '</span> ';
						}
						else {
							// Placeholder before
							if ( $s == $akt_seite - (2 * $number_messages) && $num_pages > 4 ) {
								$all_pages = $all_pages . '... ';
							}
							// Normal page
							if ( $s >= $akt_seite - (2 * $number_messages) && $s <= $akt_seite + (2 * $number_messages) ) {
								$all_pages = $all_pages . '<a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;limit=' . ( ( $i * $number_messages ) - $number_messages) . '&amp;author=' . $author . '&amp;search=' . $search . '&amp;tag=' . $tag . '" title="' . __('Page','wp_admin_blog') . ' ' . $i . '" class="page-numbers">' . $i . '</a> ';
							}
							// Placeholder after
							if ( $s == $akt_seite + (2 * $number_messages) && $num_pages > 4 ) {
								$all_pages = $all_pages . '... ';
							}
						}
					}
					// next page link
					if ( ( $message_limit + $number_messages ) <= ($test)) { 
        				$all_pages = $all_pages . '<a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;limit=' . ($message_limit + $number_messages) . '&amp;author=' . $author . '&amp;search=' . $search . '&amp;tag=' . $tag . '" title="' . __('next page','wp_admin_blog') . '" class="page-numbers">&rarr;</a> ';
					}
					// print menu
					echo '<tr>';
					echo '<td colspan="2" style="text-align:center;" class="tablenav"><div class="tablenav-pages" style="float:none;">' . $all_pages . '</div></td>';
					echo '</tr>';
				}
				$message_date_old = '';
				// Entries
				$post = $wpdb->get_results($sql);
				$sql = "SELECT COUNT(post_parent) AS gesamt, post_parent FROM " . $admin_blog_posts . " GROUP BY post_parent";
				$replies = $wpdb->get_results($sql);
				foreach ($post as $post) {
					$user_info = get_userdata($post->user);
					$edit_button = '';
					$count_rep = 0;
					$rpl = 0;
					$str = "'";
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
					}
					else {
						$message_date = '' . $time[0][0]. '-' . $time[0][1] . '-' . $time[0][2] . '';
					}
					// Handles post parent
					if ($post->post_parent == '0') {
						$post->post_parent = $post->post_ID;
					}
					// Message Menu
					if ($count_rep != 0) {
						$edit_button = ' | <a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;rpl=' . $rpl . '" class="replies">' . $count_rep . ' ' . __('Replies','wp_admin_blog') . '</a>';
					}
					if ($post->user == $user) {
						$edit_button = $edit_button . ' | <a onclick="javascript:editMessage(' . $post->post_ID . ')" style="cursor:pointer;">' . __('Edit','wp_admin_blog') . '</a> | <a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&delete=' . $post->post_ID . '" title="' . __('Click to delete this message','wp_admin_blog') . '" style="color:#FF0000">' . __('Delete','wp_admin_blog') . '</a>';
					}
					$edit_button = $edit_button . ' | <a onclick="javascript:replyMessage(' . $post->post_ID . ',' . $str . '' . $post->post_parent . '' . $str . ')" style="cursor:pointer; color:#009900;">' . __('Reply','wp_admin_blog') . '</a>';
					// Message date headlines
					if ($message_date != $message_date_old) {
						echo '<tr><td colspan="2"><strong>' . $message_date . '</strong></td></tr>';
					}
					// print messages
					echo '<tr>';
					echo '<td style="padding:10px; width:60px;"><span title="' . $user_info->display_name . ' (' . $user_info->user_login . ')">' . get_avatar($user_info->ID, 45) . '</span></td>';
					echo '<td style="padding:10px;">';
					echo '<div id="message_' . $post->post_ID . '"><p style="color:#AAAAAA;">' . $time[0][3]. ':' . $time[0][4] . ' ' . __('by','wp_admin_blog') . ' ' . $user_info->display_name . '' . $edit_button . '</p>';
					echo '<p>' . $message_text . '</p></div>';
                    echo '<input name="message_text" id="message_text_' . $post->post_ID . '" type="hidden" value="' . $post->text . '" />';
					echo '</td>';
					echo '</tr>';
					$message_date_old = $message_date;
				}
				// Page Menu
				if ($test > $number_messages) {
					echo '<tr>';
					echo '<td colspan="2" style="text-align:center;" class="tablenav"><div class="tablenav-pages" style="float:none;">' . $all_pages . '</td>';
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
	if ( eregi('wp-admin-microblog', $_GET[page]) ) {
		wp_register_script('wp_admin_blog', WP_PLUGIN_URL . '/wp-admin-microblog/wp-admin-microblog.js');
		wp_register_style('wp_admin_blog_css', WP_PLUGIN_URL . '/wp-admin-microblog/wp-admin-microblog.css');
		wp_enqueue_style('wp_admin_blog_css');
		wp_enqueue_script('wp_admin_blog');
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
	$wp_roles->WP_Roles();
	$role = $wp_roles->get_role('administrator');
	if ( !$role->has_cap('use_wp_admin_microblog') ) {
		$wp_roles->add_cap('administrator', 'use_wp_admin_microblog');
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
}
// load language support
function wp_admin_blog_language_support() {
	load_plugin_textdomain('wp_admin_blog', false, 'wp-admin-microblog');
}
// Register WordPress hooks
register_activation_hook( __FILE__, 'wp_admin_blog_install');
add_action('init', 'wp_admin_blog_language_support');
add_action('admin_init','wp_admin_blog_header');
add_action('admin_menu','wp_admin_blog_menu');
add_action('admin_menu', 'wp_admin_blog_add_menu_settings');

?>