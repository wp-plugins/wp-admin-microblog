<?php
/**
 * Add help tab to wp admin microblog main screen
 */
function wpam_add_help_tab () {
    $screen = get_current_screen();  
    $screen->add_help_tab( array(
        'id'        => 'wpam_help_tab',
        'title'     => __('Microblog','wp_admin_blog'),
        'content'   => '<p><strong>' . __('E-mail notification','wp_admin_blog') . '</strong> - ' . __('If you will send your message as an E-Mail to any user, so write @username (example: @admin)','wp_admin_blog') . '
                        <p><strong>' . __('Text formatting','wp_admin_blog') . '</strong> - ' . __('You can use simple bbcodes: [b]bold[/b], [i]italic[/i], [u]underline[/u], [s]strikethrough[/s], [red]red[/red], [blue]blue[/blue], [green]green[/green], [orange]orange[/orange]. Combinations like [red][s]text[/s][/red] are possible. The using of HTML tags is not possible.','wp_admin_blog') . '</p>
                        <p><strong>' . __('Tags') . '</strong> - ' . __('You can add tags directly to your message, if you use hashtags. Examples: #monday #2012','wp_admin_blog') . '</p>',
    ) );
} 

/**
 * get single message
 * @param string $post
 * @param array $tags
 * @param array $user_info
 * @param array $options
 * @param int $level
 * @return string 
 */
function wpam_get_message ($post, $tags, $user_info, $options, $level = 1) {
    $edit_button = '';
    $str = "'";
    $time = wpam_datumsplit($post->date);
    $message_text = wpam_message::prepare( $post->text, $tags );

    // Handles post parent
    if ($post->post_parent == '0') {
        $post->post_parent = $post->post_ID;
    }

    // sticky menu options
    $sticky_option = '';
    if ( current_user_can( 'use_wp_admin_microblog_sticky' ) && $level == 1 ) {
        if ( $post->is_sticky == 0 ) {
            $sticky_option = '<a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&add=' . $post->post_ID . '"" title="' . __('Sticky this message','wp_admin_blog') . '">' . __('Sticky','wp_admin_blog') . '</a> | ';
        }
        else {
            $sticky_option = '<a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&remove=' . $post->post_ID . '"" title="' . __('Unsticky this message','wp_admin_blog') . '">' . __('Unsticky','wp_admin_blog') . '</a> | ';
        }
    }

    // Show message edit options if the user is the author of the message or the blog admin
    if ( $post->user == $options['user'] || current_user_can('manage_options') ) {
        $edit_button = $edit_button . '<a onclick="javascript:wpam_editMessage(' . $post->post_ID . ')" style="cursor:pointer;" title="' . __('Edit this message','wp_admin_blog') . '">' . __('Edit','wp_admin_blog') . '</a> | ' . $sticky_option . '<a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&delete=' . $post->post_ID . '" title="' . __('Click to delete this message','wp_admin_blog') . '" style="color:#FF0000">' . __('Delete','wp_admin_blog') . '</a> | ';
    }
    $edit_button = $edit_button . '<a onclick="javascript:wpam_replyMessage(' . $post->post_ID . ',' . $post->post_parent . ',' . $str . '' . $options['auto_reply'] . '' . $str . ',' . $str . '' . $user_info->user_login . '' . $str . ')" style="cursor:pointer; color:#009900;" title="' . __('Write a reply','wp_admin_blog') . '">' . __('Reply','wp_admin_blog') . '</a>';

    // get human time difference
    $message_time = human_time_diff( mktime($time[0][3], $time[0][4], $time[0][5], $time[0][1], $time[0][2], $time[0][0] ), current_time('timestamp') ) . ' ' . __( 'ago', 'wp_admin_blog' );

    // handle date formats
    if ( __('en','wp_admin_blog') == 'de') {
        $message_date = '' . $time[0][2]. '.' . $time[0][1] . '.' . $time[0][0] . '';
    }
    else {
        $message_date = '' . $time[0][0]. '-' . $time[0][1] . '-' . $time[0][2] . '';
    }
    if ( date('d.m.Y') == $message_date || date('Y-m-d') == $message_date ) {
        $message_date = __('Today','wp_admin_blog');
    }     

    // print messages
    $r =  '<div id="wp_admin_blog_message_' . $post->post_ID . '" class="wpam-blog-message">
            <p style="color:#AAAAAA;"><span title="' . $message_date . '">' . $message_time . '</span> | ' . __('by','wp_admin_blog') . ' ' . $user_info->display_name . '</p>
            <p>' . $message_text . '</p>
            <div class="wpam-row-actions">' . $edit_button . '</div>
        </div>
        <input name="wp_admin_blog_message_text" id="wp_admin_blog_message_text_' . $post->post_ID . '" type="hidden" value="' . stripslashes($post->text) . '" />';
    return $r;
}

/** 
 * Main Page
 * @global type $current_user
 * @global class $wpdb
 * @global string $admin_blog_posts
 * @global string $admin_blog_tags
 * @global string $admin_blog_relations
 */
function wpam_page() {
   global $current_user;
   global $wpdb;
   global $admin_blog_posts;
   global $admin_blog_tags;
   global $admin_blog_relations;
   get_currentuserinfo();
   $user = $current_user->ID;
   
   // run the updater
   wpam_update();
   
   // edit post fields
   $text = isset( $_GET['wp_admin_blog_edit_text'] ) ? wpam_sec_var($_GET['wp_admin_blog_edit_text']) : '';
   $edit_message_ID = isset( $_GET['wp_admin_blog_message_ID'] ) ? (int) $_GET['wp_admin_blog_message_ID'] : 0;
   $parent_ID = isset( $_GET['wp_admin_blog_parent_ID'] ) ? (int) $_GET['wp_admin_blog_parent_ID'] : 0;
   $delete = isset( $_GET['delete'] ) ? (int) $_GET['delete'] : 0;
   $remove = isset( $_GET['remove'] ) ? (int) $_GET['remove'] : 0;
   $add = isset( $_GET['add'] ) ? (int) $_GET['add'] : 0;
   
   // filter
   $author = isset( $_GET['author'] ) ? wpam_sec_var($_GET['author']) : '';
   $tag = isset( $_GET['tag'] ) ? wpam_sec_var($_GET['tag']) : '';
   $search = isset( $_GET['search'] ) ? wpam_sec_var($_GET['search']) : '';
   $rpl = isset( $_GET['rpl'] ) ? (int) $_GET['rpl'] : 0;
 
   // load system settings
   $number_messages = 10;
   $auto_reply = false;
   $limit = 50;
   $blog_name = 'Microblog';
   
   $system = wpam_get_options('','system');
   foreach ($system as $system) {
       if ( $system['variable'] == 'number_messages' ) { $number_messages = $system['value']; }
       if ( $system['variable'] == 'auto_reply' ) { $auto_reply = $system['value']; }
       if ( $system['variable'] == 'blog_name' ) { $blog_name = $system['value']; }
       if ( $system['variable'] == 'number_tags' ) { $limit = $system['value']; }
   }
   
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
      // new_post fields
      $content = isset( $_POST['wpam_nm_text'] ) ? wpam_sec_var($_POST['wpam_nm_text']) : '';
      $headline = isset( $_POST['headline'] ) ? wpam_sec_var($_POST['headline']) : '';
      $is_sticky = isset( $_POST['is_sticky'] ) ? 1 : 0;
      
      // Add new message
      wpam_message::add_message($content, $user, 0, $is_sticky);
      if ( isset( $_POST['as_wp_post'] ) ) {
         wpam_message::add_as_wp_post($content, $headline, $user);
      }
      $content = "";
   }	
   if ( $delete != 0 ) {
      wpam_message::del_message($delete);
   }
   if ( $remove != 0 ) {
      wpam_message::update_sticky($remove, 0);  
   }
   if ( $add != 0 ) {
      wpam_message::update_sticky($add, 1);    
   }
   if (isset($_GET['wp_admin_blog_edit_message_submit'])) {
      wpam_message::update_message($edit_message_ID, $text);
   }
   if (isset($_GET['wp_admin_blog_reply_message_submit'])) {
      wpam_message::add_message($text, $user, $parent_ID, 0);
   }
   ?>
    <div class="wrap" style="max-width:1200px; min-width:780px;">
    <h2 style="padding-bottom: 10px;"><?php echo $blog_name;?></h2>
    <div style="width:31%; float:right; padding-right:1%;">
    <form name="blog_selections" method="get" action="admin.php">
    <input name="page" type="hidden" value="wp-admin-microblog/wp-admin-microblog.php" />
    <input name="author" type="hidden" value="<?php echo $author; ?>" />
    <input name="tag" type="hidden" value="<?php echo $tag; ?>" />
    <table class="widefat">
    <thead>
        <tr>
            <th><?php
              if ($search != "") { ?>
            	<label for="suche_abbrechen" title="<?php _e('Delete the search from filter','wp_admin_blog'); ?>">
                    <?php _e('Search', 'wp_admin_blog'); ?><a id="suche_abbrechen" href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;author=<?php echo $author; ?>&amp;search=&amp;tag=<?php echo $tag;?>" style="text-decoration:none; color:#FF9900;" title="<?php _e('Delete the search from filter','wp_admin_blog'); ?>"> X</a>
                </label><?php 
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
                          echo '<span style="font-size:' . $size . 'px;" class="wpam-tag"><a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;author=' . $author . '&amp;search=' . $search . '" title="' . __('Delete the tag from filter','wp_admin_blog') . '" style="color:#FF9900; text-decoration:underline;">' . $tagcloud['name'] . '</a></span> '; 
                       }
                       else{
                          echo '<span style="font-size:' . $size . 'px;" class="wpam-tag"><a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;author=' . $author . '&amp;search=' . $search . '&amp;tag=' . $tagcloud['tag_ID'] . '" title="' . __('Show related messages','wp_admin_blog') . '">' . $tagcloud['name'] . '</a></span> '; 
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
                 <ul class="wpam-user-list">
                 <?php
                 $sql = "SELECT DISTINCT user FROM " . $admin_blog_posts . "";
                 $users = $wpdb->get_results($sql);
                 foreach ($users as $users) {
                      $user_info = get_userdata($users->user);
                      $name = '' . $user_info->display_name . ' (' . $user_info->user_login . ')';
                      if ($author == $user_info->ID) {
                        echo '<li class="wpam-user-list-select"><a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;author=&amp;search=' . $search . '&amp;tag=' . $tag . '" title="' . __('Delete user as filter','wp_admin_blog') . '">';
                      }
                      else {
                        echo '<li><a href="admin.php?page=wp-admin-microblog/wp-admin-microblog.php&amp;author=' . $user_info->ID . '&amp;search=' . $search . '&amp;tag=' . $tag . '" title="' . $name . '">';
                      }
                      echo get_avatar($user_info->ID, 35);
                      echo '</a></li>';
                 }
                 ?>
                 </ul>
            </td>
         </tr>   
    </thead>
    </table>
    </form>
    </div>
    <div style="width:66%; float:left; padding-right:1%;">
    <form name="new_post" method="post" action="admin.php?page=wp-admin-microblog/wp-admin-microblog.php" id="new_post_form">
    <table class="widefat">
    <thead>
        <tr>
            <th><?php _e('Your Message', 'wp_admin_blog');?></th>
        </tr>
        <tr>
            <td>
            <div class="wpam_media_buttons" style="text-align:right;"><?php echo wpam_media_buttons(); ?></div>
            <div id="postdiv" class="postarea" style="display:block;">
            <textarea name="wpam_nm_text" id="wpam_nm_text" style="width:100%;" rows="4"></textarea>
            </div>
            <p style="text-align:right; float:right;"><input name="send" type="submit" class="button-primary" value="<?php _e('Send', 'wp_admin_blog'); ?>" /><p>
            <?php if ( current_user_can( 'use_wp_admin_microblog_bp' ) || current_user_can( 'use_wp_admin_microblog_sticky' ) ) { ?>
            <p style="float:left; padding: 5px;"><a onclick="javascript:wpam_showhide('wpam_message_options')" style="cursor:pointer; font-weight:bold;">+ <?php _e('Options', 'wp_admin_blog'); ?></a></p>
            <table style="width:100%; display: none; float:left;" id="wpam_message_options">
                <?php if ( current_user_can( 'use_wp_admin_microblog_sticky' ) ) { ?> 
            	<tr>
                     <td style="border-bottom-width:0px;"><input name="is_sticky" id="is_sticky" type="checkbox"/> <label for="is_sticky"><?php _e('Sticky this message','wp_admin_blog'); ?></label></td>
                </tr>
                <?php } ?>
                <?php if ( current_user_can( 'use_wp_admin_microblog_bp' ) ) { ?>
                <tr>
                     <td style="border-bottom-width:0px;">
                         <input name="as_wp_post" id="as_wp_post" type="checkbox" onclick="javascript:wpam_showhide('span_headline')" />
                         <label for="as_wp_post"><?php _e('as WordPress blog post', 'wp_admin_blog');?></label>
                         <span style="display:none;" id="span_headline">&rarr; <label for="headline"><?php _e('Title', 'wp_admin_blog');?> </label>
                             <input name="headline" id="headline" type="text" style="width:350px;" />
                         </span>
                     </td>
            	</tr>
                <?php } ?>
            </table>
            <?php } ?> 
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
         // Load tags
         $tags = $wpdb->get_results("SELECT `tag_id`, `name` FROM `$admin_blog_tags`", ARRAY_A);
         // build SQL requests
         if ( $search != '' || $author != '' || $tag != '' ) {
            $select = "SELECT DISTINCT p.post_ID, p.post_parent, p.text, p.date, p.user, p.is_sticky FROM " . $admin_blog_posts . " p
                          LEFT JOIN " . $admin_blog_relations . " r ON r.post_ID = p.post_ID
                          LEFT JOIN " . $admin_blog_tags . " t ON t.tag_ID = r.tag_ID";
            // is author and search?
            if ($author != '' && $search != '') {
               $where = "WHERE p.user = '$author' AND p.text LIKE '%$search%'";
            }
            // is search
            elseif ($author == '' && $search != '') {
               $where = "WHERE p.text LIKE '%$search%'";
            }
            // is author
            elseif ($author != '' && $search == '') {
               $where = "WHERE p.post_parent = '0' AND p.user = '$author'";
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
            $test_sql = "" . $select . " " . $where . "";				
         }
         // is replies?
         elseif( $rpl != '' ) {
            $sql = "SELECT * FROM " . $admin_blog_posts . " WHERE `post_ID` = '$rpl' ORDER BY `post_ID` DESC LIMIT $message_limit, $number_messages";
            $test_sql = "SELECT `post_ID` FROM " . $admin_blog_posts . " WHERE `post_ID` = '$rpl'";
         }
         // Normal SQL
         else {
            if ( $rpl == 0 ) {
               $sql = "SELECT * FROM " . $admin_blog_posts . " WHERE `post_parent` = '0' ORDER BY `is_sticky` DESC, `post_ID` DESC LIMIT $message_limit, $number_messages";
               $test_sql = "SELECT `post_ID` FROM " . $admin_blog_posts . " WHERE `post_parent` = '0'";  
            }
            else {
               $sql = "SELECT * FROM " . $admin_blog_posts . " ORDER BY `is_sticky` DESC, `post_ID` DESC LIMIT $message_limit, $number_messages";
               $test_sql = "SELECT `post_ID` FROM " . $admin_blog_posts . "";
            }
         }
         // Find number of entries
         $test = $wpdb->query($test_sql);
         if ($test == 0) {
            echo '<tr><td>' . __('Sorry, no entries mached your criteria','wp_admin_blog') . '</td></tr>';
         }
         else {
            // print menu
            echo '<tr>';
            echo '<td colspan="2" style="text-align:center;" class="tablenav"><div class="tablenav-pages" style="float:none;">' . wpam_page_menu($test, $number_messages, $curr_page, $message_limit, 'admin.php?page=wp-admin-microblog/wp-admin-microblog.php', 'search=' . $search . '&amp;author=' . $author . '&amp;tag=' . $tag . '') . '</div></td>';
            echo '</tr>';
            // Entries
            $post = $wpdb->get_results($sql);
            if ( $test > 0 && $search == '' ) {
                 $where = '';
                 foreach ($post as $ids) {
                      $where = $where . "`post_parent` = '" . $ids->post_ID . "' or";
                 }
                 $where = substr($where, 0, -3);
                 $sql = "SELECT * FROM " . $admin_blog_posts . " WHERE " . $where . " ORDER BY `post_ID` ASC";
                 $replies = $wpdb->get_results($sql);
            }
            
            
            foreach ($post as $post) {
               $user_info = get_userdata($post->user);
               
               // sticky post options
               // change background color for sticky posts
               $class = 'wpam_normal';
               if ( $post->is_sticky == 1  ) {
                    $class = 'wpam_sticky';
               }
               
               // print messages
               $options['auto_reply'] = $auto_reply;
               $options['user'] = $user;
               echo '<tr class="' . $class . '">';
               echo '<td style="padding:10px 0 10px 10px; width:40px;"><span title="' . $user_info->display_name . ' (' . $user_info->user_login . ')">' . get_avatar($user_info->ID, 40) . '</span></td>';
               echo '<td style="padding:10px;">';
               echo wpam_get_message($post, $tags, $user_info, $options);
               // print replies
               if ($search == '' && $tag == '') {
                    $r = '';
                    $str = "'";
                    (int) $count = 0;
                    (int) $reply_number = 0;
                    // count number of replies of this message
                    foreach ($replies as $counts) {
                         if ( $counts->post_parent == $post->post_ID ) {
                              $reply_number++;
                         }
                    }
                    // create replies
                    foreach ( $replies as $reply ) {
                         if ( $reply->post_parent == $post->post_ID ) {
                              $count++;
                              $user_info = get_userdata($reply->user);
                              if ( $reply_number >= 3 && $reply_number - $count >= 3 && $rpl == 0 ) {
                                   $style = 'style="display:none;"';
                              }
                              else {
                                   $style = '';
                              }
                              $r = $r . '<tr id="wpam-reply-' . $post->post_ID . '-' . $count . '" ' . $style . '>';
                              $r = $r . '<td style="padding:10px 0 10px 10px; width:40px;"><span title="' . $user_info->display_name . ' (' . $user_info->user_login . ')">' . get_avatar($user_info->ID, 40) . '</span></td>';
                              $r = $r . '<td>' . wpam_get_message($reply, $tags, $user_info, $options, 2) . '</td>';
                              $r = $r . '</tr>';
                         }
                    }
                    // show the number of replies text
                    if ( $count > 3 && $rpl == 0 ) {
                         echo '<table class="wpam-replies" id="wpam-reply-sum-' . $post->post_ID . '">';
                         echo '<tr><td style="padding:7px;"><a onclick="wpam_showAllReplies(' . $str . $post->post_ID . $str . ', ' . $str . $reply_number . $str . ')" style="cursor:pointer;" title="' . __('Show all replies','wp_admin_blog') . '">' . $count . ' ' . __('Replies','wp_admin_blog') . '</a></td></tr>';
                         echo '</table>';
                    }
                    // echo table of replies
                    echo '<table class="wpam-replies" id="wpam-replies-' . $post->post_ID . '">';
                    echo $r;
                    echo '</table>';
               }
               echo '</td>';
               echo '</tr>';
            }
            // Page Menu
            if ($test > $number_messages) {
               echo '<tr>';
               echo '<td colspan="2" style="text-align:center;" class="tablenav"><div class="tablenav-pages" style="float:none;">' . wpam_page_menu($test, $number_messages, $curr_page, $message_limit, 'admin.php?page=wp-admin-microblog/wp-admin-microblog.php', 'search=' . $search . '&amp;author=' . $author . '&amp;tag=' . $tag . '', 'bottom') . '</td>';
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
?>