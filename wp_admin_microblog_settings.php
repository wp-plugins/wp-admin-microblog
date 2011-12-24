<?php
/**
 * Settings Page
 * @global type $wp_roles 
 */
function wp_admin_blog_settings () {
     // run the updater
     wp_admin_blog_update();
     
     if ( isset($_POST['save']) ) {
          $option['admin_tags'] = wp_admin_blog_sec_var($_POST['admin_tags'], 'integer');
          $option['admin_messages'] = wp_admin_blog_sec_var($_POST['admin_messages'], 'integer');
          $option['auto_reply'] = wp_admin_blog_sec_var($_POST['auto_reply']);
          $option['media_upload'] = wp_admin_blog_sec_var($_POST['media_upload']);
          $option['name_blog'] = wp_admin_blog_sec_var($_POST['name_blog']);
          $option['name_widget'] = wp_admin_blog_sec_var($_POST['name_widget']);
          $userrole = $_POST['userrole'];
          $blog_post = $_POST['blog_post'];
          $sticky = $_POST['sticky'];
          $option['sticky_for_dash'] = wp_admin_blog_sec_var($_POST['sticky_for_dash']);
          wp_admin_blog_update_options($option, $userrole, $blog_post, $sticky);
          echo '<div class="updated"><p>' . __('Settings are changed. Please note that access changes are visible, until you have reloaded this page a secont time.','wp_admin_blog') . '</p></div>';
     }
     
     $name_blog = !get_option('wp_admin_blog_name') ? 'Microblog' : get_option('wp_admin_blog_name');
     $name_widget = !get_option('wp_admin_blog_name_widget') ? 'Microblog' : get_option('wp_admin_blog_name_widget');
     $admin_tags = !get_option('wp_admin_blog_number_tags') ? 50 : get_option('wp_admin_blog_number_tags');
     $admin_messages = !get_option('wp_admin_blog_number_messages') ? 10 : get_option('wp_admin_blog_number_messages');
     $auto_reply = !get_option('wp_admin_blog_auto_reply') ? 'false' : get_option('wp_admin_blog_auto_reply');
     $media_upload = !get_option('wp_admin_blog_media_upload') ? 'false' : get_option('wp_admin_blog_media_upload');
     $sticky_for_dash = !get_option('wp_admin_blog_sticky_for_dash') ? 'false' : get_option('wp_admin_blog_sticky_for_dash');

     ?>
     <div class="wrap">
     <h2><?php _e('WP Admin Microblog Settings','wp_admin_blog'); ?></h2>
     <form name="form1" id="form1" method="post" action="admin.php?page=wp-admin-microblog/settings.php">
     <input name="page" type="hidden" value="wp-admin-blog" />
     <h3><?php _e('General options','wp_admin_blog'); ?></h3>
     <table class="form-table">
        <tr>
             <th scope="row"><?php _e('Name of the Microblog','wp_admin_blog'); ?></th>
             <td style="width: 180px;"><input name="name_blog" type="text" value="<?php echo $name_blog; ?>" /></td>
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
     </table>
     <h3><?php _e('Access options','wp_admin_blog'); ?></h3>
     <table class="form-table">
         <tr>
              <th scope="row"><?php _e('Access for','wp_admin_blog'); ?></th>
              <td style="width: 180px;">
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
              <td><em><?php _e('Select each user role which has access to WP Admin Microblog.','wp_admin_blog'); ?><br /><?php _e('Use &lt;Ctrl&gt; key to select more than one.','wp_admin_blog'); ?></em></td>
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
              <td><em><?php _e('Select each user role which can use the "Message as a blog post"-function.','wp_admin_blog'); ?><br /><?php _e('Use &lt;Ctrl&gt; key to select more than one.','wp_admin_blog'); ?></em></td>
         </tr>
     </table>
     <h3><?php _e('Sticky message options','wp_admin_blog'); ?></h3>
     <table class="form-table">
         <tr>
              <th scope="row"><?php _e('"Sticky messages"-function for','wp_admin_blog'); ?></th>
              <td style="width: 180px;">
                   <select name="sticky[]" id="sticky" multiple="multiple" style="height:80px;">
                        <?php
                        foreach ($wp_roles->role_names as $roledex => $rolename) {
                            $role = $wp_roles->get_role($roledex);
                            $select = $role->has_cap('use_wp_admin_microblog_sticky') ? 'selected="selected"' : '';
                            echo '<option value="'.$roledex.'" '.$select.'>'.$rolename.'</option>';
                        }
                        ?>
                   </select>
              </td>
              <td><em><?php _e('Select each user role which can add sticky messages.', 'wp_admin_blog'); ?><br /><?php _e('Use &lt;Ctrl&gt; key to select more than one.','wp_admin_blog'); ?></em></td>
         </tr> 
         <tr>
              <th scope="row"><?php _e('Sticky messages for the dashboard widget','wp_admin_blog'); ?></th>
              <td>
                   <select name="sticky_for_dash">
                     <?php
                     if ($sticky_for_dash == 'true') {
                         echo '<option value="true" selected="selected">' . __('yes','wp_admin_blog') . '</option>';
                         echo '<option value="false">' . __('no','wp_admin_blog') . '</option>';
                     }
                     else {
                         echo '<option value="true">' . __('yes','wp_admin_blog') . '</option>';
                         echo '<option value="false" selected="selected">' . __('no','wp_admin_blog') . '</option>';
                     } 
                     ?>
                   </select>
              </td>
              <td><em><?php _e('Select `yes` to display sticky messages in the dashboard widget.','wp_admin_blog'); ?></em></td>
         </tr>
     </table>
     <p class="submit">
     <input type="submit" name="save" id="save" class="button-primary" value="<?php _e('Save Changes', 'wp_admin_blog') ?>" />
     </p>
     </form>
     </div>
     <?php
}
?>
