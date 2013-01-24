<?php

/**
 * Plugin Name: User Meta Manager
 * Plugin URI: http://websitedev.biz
 * Description: Add, edit, or delete user meta data with this handy plugin. Easily restrict access or insert user meta data into posts or pages.
 * Version: 2.0.7
 * Author: Jason Lau
 * Author URI: http://jasonlau.biz
 * Text Domain: user-meta-manager
 * Disclaimer: Use at your own risk. No warranty expressed or implied.
 * 
 * Always backup your database before making changes.
 * 
 * Copyright 2012 http://jasonlau.biz http://websitedev.biz
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 * 
 * See the GNU General Public License for more details.
 * http://www.gnu.org/licenses/gpl.html
 */

 if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    exit('Please don\'t access this file directly.');
}

define('UMM_VERSION', '2.0.7');
define("UMM_PATH", plugin_dir_path(__FILE__) . '/');
define("UMM_SLUG", "user-meta-manager");
define("UMM_AJAX", "admin-ajax.php?action=umm_switch_action&amp;sub_action=");
//error_reporting(E_ALL ^ E_NOTICE);
error_reporting(E_ALL);
include(UMM_PATH . 'includes/umm-table.php');
include(UMM_PATH . 'includes/umm-contextual-help.php');

function umm_add_custom_meta(){
    global $wpdb;
    $user_id = $_REQUEST['u'];
    $output = umm_fyi('<p>'.__('Insert a key and default value in the fields below.', UMM_SLUG).'</p>');
    $output .= '<form id="umm_update_user_meta_form" method="post">
    <strong>'.__('Key', UMM_SLUG).':</strong><br />
    <input name="meta_key" title="'.__('Letters, numbers, and underscores only', UMM_SLUG).'" type="text" value="" placeholder="'.__('Meta Key', UMM_SLUG).'" /><br />
    <strong>'.__('Default Value', UMM_SLUG).':</strong><br />
    <textarea rows="3" cols="40" name="meta_value"  placeholder=""></textarea>
    ';
    $output .= umm_profile_field_editor();
    $output .= '<br />
    <input id="umm_update_user_meta_submit" data-form="umm_update_user_meta_form" data-subpage="umm_update_user_meta" data-wait="'.__('Wait...', UMM_SLUG).'" class="button-primary" type="submit" value="'.__('Submit', UMM_SLUG).'" />
    <input name="all_users" type="hidden" value="true" /><input name="mode" type="hidden" value="add" /><input name="u" type="hidden" value="all" /><input name="return_page" type="hidden" value="' . UMM_AJAX . 'umm_add_custom_meta&u=0" />
    </form>  
    ';
    print $output;
    exit;
}

function umm_add_user_meta(){
    global $wpdb;
    $user_id = $_REQUEST['u'];
    $output = umm_button('home', null, "umm-back-button") . umm_subpage_title($user_id, __('Adding Meta Data For %s', UMM_SLUG));
    $output .= umm_fyi('<p>'.__('Insert a meta key and default value and press <em>Submit</em>.', UMM_SLUG).'</p>');
    $output .= '<form id="umm_update_user_meta_form" method="post">
    <strong>'.__('Key', UMM_SLUG).':</strong><br />
    <input name="meta_key" title="'.__('Letters, numbers, and underscores only', UMM_SLUG).'" type="text" value="" placeholder="'.__('Meta Key', UMM_SLUG).'" /><br />
    <strong>'.__('Value', UMM_SLUG).':</strong><br />
    <input name="meta_value" type="text" value="" size="40" placeholder="'.__('Default Value', UMM_SLUG).'" />';
    $output .= '<br />
    <input id="umm_update_user_meta_submit" data-form="umm_update_user_meta_form" data-subpage="umm_update_user_meta" data-wait="'.__('Wait...', UMM_SLUG).'" class="button-primary" type="submit" value="'.__('Submit', UMM_SLUG).'" />
    <input name="mode" type="hidden" value="add" /><input name="u" type="hidden" value="' . $user_id . '" /><input name="return_page" type="hidden" value="' . UMM_AJAX . 'umm_add_user_meta&u=' . $user_id . '" />
    </form>  
    ';   
    print $output;
    exit;
}

function umm_admin_init(){
    if(function_exists('load_plugin_textdomain'))
    load_plugin_textdomain( 'user-meta-manager', false, dirname(plugin_basename( __FILE__ )) . '/language/' ); 
}

function umm_admin_menu(){
  add_submenu_page('users.php', 'User Meta Manager', 'User Meta Manager', 'publish_pages', UMM_SLUG, 'umm_ui');
  add_action('admin_enqueue_scripts', 'umm_load_scripts');
}

function umm_backup($backup_mode=false, $tofile=false, $print=true){
    global $wpdb, $current_user, $table_prefix;
    $backup_files = get_option('umm_backup_files');
    $mode = (!isset($_REQUEST['mode']) || empty($_REQUEST['mode'])) ? '' : $_REQUEST['mode'];
    $mode = (empty($backup_mode)) ? $mode : $backup_mode;
    $to_file = (!isset($_REQUEST['tofile']) || empty($_REQUEST['tofile'])) ? '' : $_REQUEST['tofile'];
    $tofile = (empty($tofile)) ? $to_file : $tofile;
    $backup_files = (!$backup_files || $backup_files == '') ? array() : $backup_files;
    $back_button = umm_button("umm_backup_page&u=1", null, "umm-back-button");
    switch($mode){
        case "sql":
        $data = $wpdb->get_results("SELECT * FROM " . $table_prefix . "umm_usermeta_backup");
        $budate = get_option('umm_backup_date');
        $sql = "DELETE FROM `" . $wpdb->usermeta . "`;\n";
        $sql .= "INSERT INTO `" . $wpdb->usermeta . "` (`umeta_id`, `user_id`, `meta_key`, `meta_value`) VALUES\n";      
        foreach($data as $d):
          $sql .= "(";
          foreach($d as $key => $value):
            if($key == 'umeta_id' || $key == 'user_id'):
              $sql .= $value . ", ";
            elseif($key == 'meta_value'):
              $sql .= "'" . addslashes($value) . "', ";
            else:
              $sql .= "'" . addslashes($value) . "', ";
            endif;              
          endforeach;
          $sql = trim($sql,", ");
          $sql .= "),\n";
        endforeach;
        $sql = trim($sql,",\n") . ";";
        $output = '<p class="umm-message">' . __("Below is the sql needed to restore the usermeta table.", UMM_SLUG) . "</p><strong>" . __("Backup from", UMM_SLUG) . " " . $budate . "</strong><br />\n<textarea onclick=\"this.focus();this.select();\" cols=\"65\" rows=\"15\">" . $sql . "</textarea>";
        break;
        
        case "php":
        $data = $wpdb->get_results("SELECT * FROM " . $table_prefix . "umm_usermeta_backup");
        $budate = get_option('umm_backup_date');
        $output = '<?php
';
        $output .= "require('" . ABSPATH . "wp-load.php');\n";
        $output .= 'if(!is_user_logged_in() OR !current_user_can(\'update_core\')) wp_die("' . __("Authorization required!", UMM_SLUG) . '");
global $wpdb;
if(isset($_REQUEST[\'umm_confirm_restore\'])):
';
        $output .= '$wpdb->query("DELETE FROM $wpdb->usermeta");' . "\n";
        foreach($data as $d):
          $output .= '$wpdb->query("INSERT INTO $wpdb->usermeta (umeta_id, user_id, meta_key, meta_value) VALUES(';
          foreach($d as $key => $value):
            if($key == 'umeta_id' || $key == 'user_id'):
              $output .= $value . ", ";
            elseif($key == 'meta_value'):
              $output .= "'" . addslashes($value) . "')";
            else:
              $output .= "'" . addslashes($value) . "', ";
            endif;              
          endforeach;
          $output = trim($output,", ");
          $output .= "\");\n";
        endforeach;
        $output .= "print('" . __("Restore complete.", UMM_SLUG) . "');\nelse:\nprint('<form action=\"#\" method=\"post\"><p>" . __("Are you sure you want to restore all user meta data to the backup version?", UMM_SLUG) . "</p><button type=\"submit\">" . __("Yes", UMM_SLUG) . "</button><input type=\"hidden\" name=\"umm_confirm_restore\" value=\"1\" /></form>');\nendif;\n?>";
        
        if($tofile == "yes"):
          $rs = umm_random_str(10);
          $temp_file = WP_PLUGIN_DIR . "/user-meta-manager/backups/" . "usermeta-backup-" . date("m.j.Y-") . ".php";
          $file = WP_PLUGIN_DIR . "/user-meta-manager/backups/" . "usermeta-backup-" . date("m.j.Y-") . date("g.i.a") . "-" . $current_user->ID . "-" . $_SERVER['REMOTE_ADDR'] . "-" . $rs . ".php";
          $link = WP_PLUGIN_URL . "/user-meta-manager/backups/" . "usermeta-backup-" . date("m.j.Y-") . date("g.i.a") . "-" . $current_user->ID . "-" . $_SERVER['REMOTE_ADDR'] . "-" . $rs . ".php";
          array_push($backup_files, $file);
          update_option('umm_backup_files', $backup_files);
          
          if($fp = @fopen($temp_file, "w+")):
            @chmod($temp_file, 0755);
            fwrite($fp, trim($output));
            fclose($fp);
            // Some servers need permissions set
            @chmod($temp_file, 0755);
            @rename($temp_file, $file);
            
            $output = '<p class="umm-message">' . __("Backup php file was successfully generated at ", UMM_SLUG) . ' <a href="' . $link . '" target="_blank">' . $link . '</a></p><p>' . __("Run the file in your browser to begin the restoration process.", UMM_SLUG) . '</p>' . "\n";
          else:
            $output = '<p class="umm-warning">' . __("Error: Backup php file could not be generated at ", UMM_SLUG) . ' ' . WP_PLUGIN_DIR . '/user-meta-manager/backups' . '</p><p>' . __("Please be sure the directory exists and is owner-writable.", UMM_SLUG) . '</p>' . "\n";
          endif;          
        else:
        $output = '<p class="umm-message">' . __("Below is the php needed to restore the usermeta table. Save this code as a php file to the root WordPress folder, then run it in your browser.", UMM_SLUG) . '</p><strong>' . __("Backup from", UMM_SLUG) . ' ' . $budate . '</strong><br />
        <textarea onclick="this.focus();this.select();" cols="65" rows="15">' . $output . '</textarea>' . "\n";
        endif;
        break;
        
        default:
        $wpdb->query("DROP TABLE IF EXISTS  " . $table_prefix . "umm_usermeta_backup");
        $wpdb->query("CREATE  TABLE  " . $table_prefix . "umm_usermeta_backup (umeta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT, user_id bigint(20) unsigned NOT NULL DEFAULT '0', meta_key varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL, meta_value longtext COLLATE utf8_unicode_ci, PRIMARY KEY (umeta_id), KEY user_id (user_id), KEY meta_key (meta_key))");
        $wpdb->query("INSERT INTO " . $table_prefix . "umm_usermeta_backup SELECT * FROM " . $wpdb->usermeta);
        update_option('umm_backup_date', date("M d, Y") . ' ' . date("g:i A"));
        $output = '<p class="umm-message">' . __("User meta data backup was successful.", UMM_SLUG) . "</p>";
        break;
    } 
    
    if($print):
      print '<div class="umm-backup-page-container">' . $back_button . $output . '</div>';
      exit;  
    endif;  
    
}

function umm_backup_page(){   
    global $wpdb;
    $budate = get_option('umm_backup_date');
    if($budate == "") $budate = __("No backup", UMM_SLUG);
    
    $output = umm_fyi('<p>'.__('Use the following links to backup and restore user meta data.', UMM_SLUG).'</p>');  
    $output .= '<div class="umm-backup-page-container">';
    $output .= '<ul><li><a href="#" data-subpage="' . UMM_AJAX . 'umm_backup&amp;u=1" title="'.__('Backup', UMM_SLUG).'" class="umm-subpage">'.__('Backup', UMM_SLUG).'</a> <strong>'.__('Last Backup:', UMM_SLUG). '</strong> ' . $budate . '</li>';  
    $output .= '<li><a href="#" data-subpage="' . UMM_AJAX . 'umm_restore_confirm&amp;u=1" title="'.__('Restore', UMM_SLUG).'" class="umm-subpage">'.__('Restore', UMM_SLUG).'</a></li>
    <li><a href="#" data-subpage="' . UMM_AJAX . 'umm_backup&amp;mode=sql&amp;u=1" title="'.__('Generate SQL', UMM_SLUG).'" class="umm-subpage">'.__('Generate SQL', UMM_SLUG).'</a></li>
    <li><a href="#" data-subpage="' . UMM_AJAX . 'umm_backup&amp;mode=php&amp;u=1" title="'.__('Generate PHP', UMM_SLUG).'" class="umm-subpage">'.__('Generate PHP', UMM_SLUG).'</a></li>
    <li><a href="#" data-subpage="' . UMM_AJAX . 'umm_backup&amp;mode=php&amp;tofile=yes&amp;u=1" title="'.__('Generate PHP Restoration File', UMM_SLUG).'" class="umm-subpage">'.__('Generate PHP Restoration File', UMM_SLUG).'</a></li>
    <li><a href="#" data-subpage="' . UMM_AJAX . 'umm_delete_backup_files" title="'.__('Delete All Backup Files', UMM_SLUG).'" class="umm-subpage">'.__('Delete All Backup Files', UMM_SLUG).'</a></li>
    </ul>';
    $output .= '</div>';
    print $output;
    exit;
}

function umm_button($go_to, $label=null, $css_class=null){
    $label = (!$label) ? __('<< Back', UMM_SLUG) : $label;
    $css_class = (!$css_class) ? 'button-secondary umm-button' : 'button-secondary umm-button ' . $css_class;
    switch($go_to){
        case 'home':
        $umm_button = '<button href="#" data-type="' . $go_to . '" title="' . $label . '" class="umm-homelink ' . $css_class . '">' . $label . '</button>';
        break;
        
        default:
        $umm_button = '<button href="#" data-type="subpage" data-subpage="' . UMM_AJAX . '' . $go_to . '" title="' . $label . '" class="' . $css_class . '">' . $label . '</button>';
    }
    return $umm_button;
}

function umm_column_exists($key){
   $used_columns = umm_get_columns();
   return array_key_exists($key, $used_columns);
}

function umm_deactivate(){
    global $wpdb, $table_prefix;    
    $umm_settings = get_option('umm_settings');
    if(empty($umm_settings)) $umm_settings = array('retain_data' => 'yes');
    if($umm_settings['retain_data'] == 'no'):
     // Delete all saved data
     $umm_data = get_option('user_meta_manager_data');
     if(empty($umm_data)) $umm_data = array();
     $umm_singles_data = get_option('umm_singles_data');
     if(empty($umm_singles_data)) $umm_singles_data = array();
     $data = $wpdb->get_results("SELECT * FROM " . $wpdb->users);
     foreach($data as $user):
        foreach($umm_data as $meta_key => $value):
           delete_user_meta($user->ID, $meta_key);
        endforeach;
        foreach($umm_singles_data as $meta_key):
           delete_user_meta($user->ID, $meta_key);
        endforeach;
     endforeach;
     delete_option('umm_singles_data');
     delete_option('user_meta_manager_data');
     delete_option('umm_users_columns');
     delete_option('umm_usermeta_columns');
     delete_option('umm_backup_date');
     delete_option('umm_backup_files');
     delete_option('umm_profile_fields');
     delete_option('umm_settings');
     $wpdb->query("DROP TABLE IF EXISTS " . $table_prefix . "umm_usermeta_backup");
    endif;   
}

function umm_delete_backup_files(){
    $back_button = umm_button("umm_backup_page&u=1", __('<< Back', UMM_SLUG), "umm-back-button");
    if(!empty($_REQUEST['umm_confirm_backups_delete'])):
    $backups_folder = WP_PLUGIN_DIR . "/user-meta-manager/backups";    
    chmod($backups_folder, 0755);
    $backup_files = get_option('umm_backup_files');
    
    if(is_array($backup_files) && !empty($backup_files)):
    foreach($backup_files as $backup_file):
      @unlink($backup_file);
    endforeach;
    endif;
    update_option('umm_backup_files', array());   
    $output = $back_button . '<p class="umm-message">' . __('All backup files successfully deleted.', UMM_SLUG) . '</p>';
    else:
    $output = $back_button . '<p class="umm-warning"><strong>' . __('Are you sure you want to delete all backup files?', UMM_SLUG) . '</strong><br /><a href="#" data-subpage="' . UMM_AJAX . 'umm_delete_backup_files&amp;umm_confirm_backups_delete=yes" class="umm-subpage">' . __('Yes', UMM_SLUG) . '</a> <a href="#" data-subpage="' . UMM_AJAX . 'umm_backup_page" class="umm-subpage">' . __('Cancel', UMM_SLUG) . '</a></p>';
    endif;
    print $output;
    exit;
    return;
}

function umm_default_keys(){
    global $wpdb;
    $data = umm_usermeta_data("ORDER BY user_id DESC LIMIT 1");
    $umm_data = get_option('user_meta_manager_data');
    if($umm_data):
        foreach($umm_data as $key => $value):
            update_user_meta($data[0]->user_id, $key, $value, false);
        endforeach;
    endif;
}

function umm_delete_custom_meta(){
    global $wpdb;
    $data = get_option('user_meta_manager_data');
    if(!empty($data)):    
    $delete_key = (!isset($_REQUEST['umm_edit_key']) || empty($_REQUEST['umm_edit_key'])) ? '' : $_REQUEST['umm_edit_key'];
    if($delete_key == ""):
    $output = umm_fyi('<p>'.__('Select from the menu a meta key to delete.').'</p>');  
    $output .= '<form id="umm_update_user_meta_form" method="post">
    <strong>'.__('Meta Key', UMM_SLUG).':</strong> <select id="umm_edit_key" name="umm_edit_key" class="umm_meta_key_menu">
    <option value="">'.__('Select A Meta Key', UMM_SLUG).'</option>
    ';

    if($data):
       foreach($data as $key => $value):
        $output .= '<option value="' . $key . '">' . $key . '</option>' . "\n";
       endforeach; 
    endif;   

    $output .= '</select> <input id="umm_update_user_meta_submit" data-form="umm_update_user_meta_form" data-subpage="umm_update_user_meta" data-wait="'.__('Wait...', UMM_SLUG).'" class="button-primary button-delete" type="submit" value="'.__('Submit', UMM_SLUG).'" /><input name="all_users" type="hidden" value="true" /><input name="mode" type="hidden" value="" /><input name="u" type="hidden" value="all" /><input name="return_page" type="hidden" value="' . UMM_AJAX . 'umm_delete_custom_meta&u=0" />
    </form>  
    ';
    else:
    $output = '<form id="umm_update_user_meta_form" method="post">
    <strong>'.__('Deleting', UMM_SLUG).':</strong> ' . $delete_key . '
    <p class="umm-warning">
    '.__('Are you sure you want to delete that item?', UMM_SLUG).'<br />
    <input id="umm_update_user_meta_submit" data-form="umm_update_user_meta_form" data-subpage="umm_update_user_meta" data-wait="'.__('Wait...', UMM_SLUG).'" class="button-primary button-delete" type="submit" value="'.__('Yes', UMM_SLUG).'" /> ';
    $output .= umm_button("umm_delete_custom_meta&u=0", __('Cancel', UMM_SLUG));
    $output .= '<input name="meta_key" type="hidden" value="' . $delete_key . '" />
    <input name="all_users" type="hidden" value="true" /><input name="mode" type="hidden" value="delete" /><input name="u" type="hidden" value="all" /><input name="return_page" type="hidden" value="' . UMM_AJAX . 'umm_delete_custom_meta&u=0" /></p>
    </form>';   
    endif;
    else: // !empty($data)
    $output = __('No custom meta to delete.', UMM_SLUG);
    endif; // !empty($data)
    print $output;
    exit;
}

function umm_delete_single_key($key){
    global $wpdb;
    $profile_fields = get_option('umm_profile_fields');
    $umm_data = get_option('user_meta_manager_data');    
    unset($profile_fields[$key]);
    unset($umm_data[$key]);    
    update_option('umm_profile_fields', $profile_fields);
    update_option('user_meta_manager_data', $umm_data);
    $data = $wpdb->get_results("SELECT * FROM " . $wpdb->users);
    foreach($data as $user):
      update_user_meta($user->ID, $meta_key, $meta_value, false);
    endforeach;
    $output = "<p>" . __("Meta data successfully deleted.", UMM_SLUG) . "</p>";
    print $output;
    exit;
}

function umm_delete_user_meta(){
    global $wpdb;
    $user_id = $_REQUEST['u'];
    $data = umm_usermeta_data("WHERE user_id = $user_id");
    $output = umm_button('home', null, "umm-back-button") . umm_subpage_title($user_id, __('Deleting Meta Data For %s', UMM_SLUG));
    
    $all_users = $_REQUEST['all_users'];
    $delete_key = (isset($_REQUEST['umm_edit_key']) && trim($_REQUEST['umm_edit_key']) != "" && trim($_REQUEST['umm_edit_key']) != "undefined") ? trim($_REQUEST['umm_edit_key']) : "";
    
    if($delete_key == ""):
    
    $output .= umm_fyi('<p>'.__('Select a <em>Meta Key</em> to delete, then press the <em>Submit</em> button. Select <em>All Users</em> to delete the selected item from all users.').'</p>', UMM_SLUG);
    $output .= '<form id="umm_update_user_meta_form" method="post">
    <strong>'.__('Meta Key', UMM_SLUG).':</strong> <select id="umm_edit_key" name="umm_edit_key" class="umm_meta_key_menu">
    <option value="">'.__('Select A Meta Key', UMM_SLUG).'</option>
    ';

    foreach($data as $d):
        $output .= '<option value="' . $d->meta_key . '">' . $d->meta_key . '</option>' . "\n";
    endforeach;

    $output .= '</select><br />
    <strong>'.__('All Users', UMM_SLUG).':</strong> <select name="all_users" size="1">
	<option value="false">'.__('No', UMM_SLUG).'</option>
	<option value="true">'.__('Yes', UMM_SLUG).'</option>
</select><br />
    <input id="umm_update_user_meta_submit" data-form="umm_update_user_meta_form" data-subpage="umm_update_user_meta" data-wait="'.__('Wait...', UMM_SLUG).'" class="button-primary button-delete" type="submit" value="'.__('Submit', UMM_SLUG).'" />
    <input name="mode" type="hidden" value="" /><input name="u" type="hidden" value="' . $user_id . '" /><input name="return_page" type="hidden" value="' . UMM_AJAX . 'umm_delete_user_meta&u=' . $user_id . '" />
    </form>  
    ';
    else:
    $output = '<form id="umm_update_user_meta_form" method="post">
    <strong>'.__('Deleting', UMM_SLUG).':</strong> ' . $delete_key . '
    <p class="umm-warning">
    '.__('Are you sure you want to delete that item?', UMM_SLUG).'<br />
    <input id="umm_update_user_meta_submit" data-form="umm_update_user_meta_form" data-subpage="umm_update_user_meta" data-wait="'.__('Wait...', UMM_SLUG).'" class="button-primary button-delete" type="submit" value="'.__('Yes', UMM_SLUG).'" /> ';
    $output .= umm_button("umm_delete_user_meta&u=" . $user_id, __('Cancel', UMM_SLUG));
    $output .= '<input name="meta_key" type="hidden" value="' . $delete_key . '" /><input name="all_users" type="hidden" value="' . $all_users . '" />
    <input name="all_users" type="hidden" value="true" /><input name="mode" type="hidden" value="delete" /><input name="u" type="hidden" value="' . $user_id . '" /><input name="return_page" type="hidden" value="' . UMM_AJAX . 'umm_delete_user_meta&u=' . $user_id . '" /></p>
    </form>';
    endif;
    print $output;
    exit;
}

function umm_edit_columns(){
    $columns = umm_get_columns();
    $output = umm_fyi('<p>'.__('Use the forms below to edit which table columns are displayed.', UMM_SLUG).'</p>');
    $output .= '<form id="umm_manage_columns_form" method="post">
    <h3>'.__('Display Columns', UMM_SLUG).'</h3>
    <table class="umm_edit_columns_table wp-list-table widefat fixed">
    <thead>
    <tr>
      <th></th>
      <th>'.__('Key', UMM_SLUG).'</th>
      <th>'.__('Label', UMM_SLUG).'</th>
    </tr>
  </thead>
  ';
  $x = 1;
  foreach($columns as $k => $v){
    $c = ($x%2) ? "" : "alternate";
    $cb = ($k != 'ID' && $k != 'user_login') ? '<input type="radio" value="'.$k.'|" name="umm_column_key" />' : '<input type="radio" value="'.$k.'|" name="umm_column_key" disabled="disabled" title="Required" />';
    $output .= '<tr class="' . $c . '"><td>' . $cb . '</td><td>' . $k . '</td><td>' . $v . '</td></tr>' . "\n";
    $x++;
  }
   $output .= '</table>
   <input id="umm_update_user_meta_submit" data-form="umm_manage_columns_form" data-subpage="umm_update_columns" data-wait="'.__('Wait...', UMM_SLUG).'" class="button-primary" type="submit" value="'.__('Remove Selected Column', UMM_SLUG).'" />
   <input name="mode" type="hidden" value="remove_columns" /><input name="return_page" type="hidden" value="' . UMM_AJAX . 'umm_edit_columns" />
   </form>
   <form id="umm_add_columns_form" method="post">
   <h3>'.__('Add A New Column', UMM_SLUG).'</h3>
   <strong>'.__('Key', UMM_SLUG).':</strong> <select name="umm_column_key">
   <option value="">'.__('Keys', UMM_SLUG).'</option>';
   $output .= umm_users_keys_menu(false, true); 
   $output .= umm_usermeta_keys_menu(false, true);
   $output .= '</select><br>
   <strong>'.__('Label', UMM_SLUG).':</strong> <input name="umm_column_label" type="text" value="" placeholder="'.__('Enter a label', UMM_SLUG).'" title="'.__('Enter a label which will appear in the top row of the results table.', UMM_SLUG).'" /><br />';   
   $output .= '<input id="umm_update_user_meta_submit" data-form="umm_add_columns_form" data-subpage="umm_update_columns" data-wait="'.__('Wait...', UMM_SLUG).'" class="button-primary" type="submit" value="'.__('Add Column', UMM_SLUG).'" />
    <input name="mode" type="hidden" value="add_columns" /><input name="return_page" type="hidden" value="' . UMM_AJAX . 'umm_edit_columns" />
    </form>  
    ';
    print $output;
    exit;
}

function umm_edit_custom_meta(){
    global $wpdb;
    $data = get_option('user_meta_manager_data');
    if(!$data):
       $output = __('No custom meta to edit.', UMM_SLUG); 
    else:
    $edit_key = (!isset($_REQUEST['umm_edit_key']) || empty($_REQUEST['umm_edit_key'])) ? '' : $_REQUEST['umm_edit_key'];
    if($edit_key == ""):
        $output = umm_fyi('<p>'.__('Select from the menu a meta key to edit.', UMM_SLUG).'</p>');
        $output .= '<form id="umm_update_user_meta_form" method="post">
        <strong>Edit Key:</strong> <select id="umm_edit_key" name="umm_edit_key" title="' . __('Select a meta key to edit.', UMM_SLUG) . '">
        <option value="">' . __('Select A Key To Edit', UMM_SLUG) . '</option>
        ';
        foreach($data as $key => $value):
            $output .= '<option value="'.$key.'">'.$key.'</option>
            ';
        endforeach;    
            $output .= '</select> 
    <input id="umm_edit_custom_meta_submit" data-form="umm_update_user_meta_form" data-subpage="umm_update_user_meta" data-wait="'.__('Wait...', UMM_SLUG).'" class="button-primary" type="submit" value="'.__('Submit', UMM_SLUG).'" />
    <input name="mode" type="hidden" value="" /><input name="u" type="hidden" value="all" /><input name="return_page" type="hidden" value="' . UMM_AJAX . 'umm_edit_custom_meta" /> 
    </form> 
    ';
        
    else:
    $profile_fields = get_option('umm_profile_fields');
    if(!$profile_fields) $profile_fields = array();
    $output = '<strong>' . __('Now Editing', UMM_SLUG) . ':</strong> <span class="umm-highlight">' . $_REQUEST['umm_edit_key'] . '</span>';
    $output .= umm_fyi('<p>'.__('Editing custom meta data here will edit the value for all new users. The value you set will become the default value for all users. New registrations will receive the custom meta key and default value.', UMM_SLUG).'</p>');
    $output .= '<form id="umm_update_user_meta_form" method="post">
    ';
    
    
    if(!$data):
       $output .= '<tr>
       <td colspan="2">' . __('No custom meta to display.', UMM_SLUG) . '</td>
       </tr>'; 
    else:
        foreach($data as $key => $value):
        if($key == $_REQUEST['umm_edit_key']):
            $output .= '<strong>' . __('Value', UMM_SLUG) . ':</strong><input name="meta_key" type="hidden" value="' . $key . '" /><br /><input name="meta_value" type="text" value="' . htmlspecialchars($value) . '" size="40" /><br />';
            endif; 
        endforeach;
    endif;
    $output .= '<strong>' . __('Update Value For All Current Users', UMM_SLUG) . ':</strong><br /><input type="checkbox" name="all_users" value="1" title="' . __('Check the box to update the value for all current users. Leave blank to update the value for future registrations only.', UMM_SLUG) . '" /> ' . __('Yes', UMM_SLUG) . ''; 
    $output .= umm_profile_field_editor($edit_key);
    $output .= '<input id="umm_update_user_meta_submit" data-form="umm_update_user_meta_form" data-subpage="umm_update_user_meta" data-wait="'.__('Wait...', UMM_SLUG).'" class="button-primary" type="submit" value="'.__('Update', UMM_SLUG).'" />
    <input name="mode" type="hidden" value="edit" /><input name="u" type="hidden" value="all" /><input name="return_page" type="hidden" value="' . UMM_AJAX . 'umm_edit_custom_meta" />
    </form>  
    ';
    endif; // edit_key
    endif; // !$data
    print $output;
    exit;
}

function umm_edit_user_meta(){  
    global $wpdb;
    $user_id = $_REQUEST['u'];
    $data = umm_usermeta_data("WHERE user_id = $user_id");
    sort($data);
    $umm_settings = get_option('umm_settings');
    $shortcut_editing = empty($umm_settings['shortcut_editing']) ? 'no' : $umm_settings['shortcut_editing'];
    $output = umm_button('home', null, "umm-back-button") . umm_subpage_title($user_id, __('Editing Meta Data For %s', UMM_SLUG));
    $output .= umm_fyi('<p>'.__('Editing an item here will only edit the item for the selected user and not for all users.<br /><a href="#" data-subpage="' . UMM_AJAX . 'umm_edit_custom_meta&u=1" data-nav_button="Edit Custom Meta" title="Edit Custom Meta" class="umm-subpage">Edit Custom Meta Data For All Users</a>', UMM_SLUG).'</p>');
    $edit_key = $_REQUEST['umm_edit_key'];
    if($edit_key == "" && $shortcut_editing == 'no'):
        $output .= '<form id="umm_update_user_meta_form" method="post">
        <strong>Edit Key:</strong> <select id="umm_edit_key" name="umm_edit_key" title="' . __('Select a meta key to edit.', UMM_SLUG) . '">
        <option value="">' . __('Select A Key To Edit', UMM_SLUG) . '</option>
        ';
        foreach($data as $d):
            $output .= '<option value="'.$d->meta_key.'">'.$d->meta_key.'</option>
            ';
        endforeach;    
            $output .= '</select> 
    <input id="umm_edit_custom_meta_submit" data-form="umm_update_user_meta_form" data-subpage="umm_update_user_meta" data-wait="'.__('Wait...', UMM_SLUG).'" class="button-primary" type="submit" value="'.__('Submit', UMM_SLUG).'" />
    <input name="mode" type="hidden" value="edit" /><input name="u" type="hidden" value="' . $user_id . '" /><input name="return_page" type="hidden" value="' . UMM_AJAX . 'umm_edit_user_meta&u=' . $user_id . '" />
    </form> 
    ';
        
    else:
    if($shortcut_editing == 'no'):
       $output .= '<strong>' . __('Now Editing', UMM_SLUG) . ':</strong> ' . $_REQUEST['umm_edit_key'];
    endif;
    
    $output .= '<form id="umm_update_user_meta_form" method="post">
    <table class="umm_edit_table wp-list-table widefat">
    <thead>
    <tr>
      <th>'.__('Key', UMM_SLUG).'</th>
      <th>'.__('Value', UMM_SLUG).'</th>
    </tr>
  </thead>
    ';
    $x = 1;
    foreach($data as $d):
    $class = ($x%2) ? ' class="alternate"' : '';
    if($d->meta_key == $edit_key || $shortcut_editing == 'yes'):
        $output .= '<tr' . $class . '><td width="25%">' . $d->meta_key . '</td><td><input name="meta_key[]" type="hidden" value="' . $d->meta_key . '" /><input name="meta_value[]" type="text" value="' . htmlspecialchars($d->meta_value) . '" size="40" /></td></tr>';
        $x++;
    endif;         
    endforeach;

    $output .= '</table>
    <input id="umm_update_user_meta_submit" data-form="umm_update_user_meta_form" data-subpage="umm_update_user_meta" data-wait="'.__('Wait...', UMM_SLUG).'" class="button-primary" type="submit" value="'.__('Update', UMM_SLUG).'" />
    <input name="mode" type="hidden" value="edit" /><input name="u" type="hidden" value="' . $user_id . '" /><input name="return_page" type="hidden" value="' . UMM_AJAX . 'umm_edit_user_meta&u=' . $user_id . '" />
    </form>  
    ';
    endif;
    print $output;
    exit;
}

function umm_fyi($message){
    return '<div class="umm-fyi">' . $message . '</div>';
}

function umm_get_columns(){
    $users_columns = (!get_option("umm_users_columns") ? array('ID' => __('ID', UMM_SLUG), 'user_login' => __('User Login', UMM_SLUG), 'user_registered' => __('Date Registered', UMM_SLUG)) : get_option("umm_users_columns"));
    $usermeta_columns = (!get_option("umm_usermeta_columns")) ? array() : get_option("umm_usermeta_columns");
    return array_merge($users_columns, $usermeta_columns);
}

function umm_install(){
    global $wpdb, $table_prefix;
    $wpdb->query("DROP TABLE IF EXISTS  " . $table_prefix . "umm_usermeta_backup");
    $wpdb->query("CREATE  TABLE  " . $table_prefix . "umm_usermeta_backup (umeta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT, user_id bigint(20) unsigned NOT NULL DEFAULT '0', meta_key varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL, meta_value longtext COLLATE utf8_unicode_ci, PRIMARY KEY (umeta_id), KEY user_id (user_id), KEY meta_key (meta_key))");
    $wpdb->query("INSERT INTO " . $table_prefix . "umm_usermeta_backup SELECT * FROM " . $wpdb->usermeta);   
    $bu_data = $wpdb->get_results("SELECT * FROM " . $table_prefix . "umm_usermeta_backup");
   update_option('umm_backup_date', date("M d, Y") . ' ' . date("g:i A"));
   add_option('umm_backup_files', array());
   umm_backup('php', 'yes', false);
   $umm_data = get_option('user_meta_manager_data');
   if(!empty($umm_data) && !is_array($umm_data)):
   // Backwards compatibility
   $new_array = array();
     $d = explode(",", $umm_data);
     foreach($d as $k):
       array_push($new_array, trim(stripslashes($k)));
     endforeach;
     update_option('user_meta_manager_data', $new_array);
   else:
     add_option('user_meta_manager_data', array());
   endif;
   add_option('umm_singles_data', array());     
   add_option('umm_profile_fields', array());
   add_option('umm_users_columns', array('ID' => __('ID', UMM_SLUG), 'user_login' => __('User Login', UMM_SLUG), 'user_registered' => __('Date Registered', UMM_SLUG)));
   add_option('umm_usermeta_columns', array());  
   add_option('umm_settings', array('retain_data' => 'yes', 'first_run' => 'yes', 'shortcut_editing' => 'no'));
    
}

function umm_key_exists($key=false){
    global $wpdb;
    $k = (empty($key)) ? $_REQUEST['meta_key'] : $key;
    $umm_data = get_option('user_meta_manager_data');
    $data = $wpdb->get_results("SELECT * FROM " . $wpdb->usermeta . " WHERE meta_key='" . $k . "'");
    if(!empty($data)):
       $output = '{"key_exists":true}';
       print $output;
    else:
       $output = '{"key_exists":false}';
       print $output;
    endif;
    exit;
}

function umm_load_scripts($hook) {
    if($hook && $hook == "users_page_user-meta-manager"):
       wp_enqueue_script('jquery');
       wp_register_script('umm_jquery_ui', plugins_url('/js/jquery-ui-1.9.0.min.js?version='.rand(100,1000), __FILE__));
       wp_enqueue_script('umm_jquery_ui');
       wp_register_style('umm_css', plugins_url('/css/user-meta-manager.css', __FILE__));
       wp_enqueue_style('umm_css');
       wp_register_script('umm_js', plugins_url('/js/user-meta-manager.js?version='.rand(100,1000), __FILE__));
       wp_enqueue_script('umm_js');
    endif;
}

function umm_profile_field_editor($umm_edit_key=null){
    $profile_fields = get_option('umm_profile_fields');
    $options_output = '';
    $select_option_row = '<tr class="umm-select-option-row">
	<td><input name="umm_profile_select_label[]" type="text" placeholder="'.__('Label', UMM_SLUG).'" value="" /></td>
	<td><input name="umm_profile_select_value[]" type="text" placeholder="'.__('Value', UMM_SLUG).'" value="" /></td>
	<td><button class="umm-add-row button-secondary umm-profile-editor umm-add-option">+</button> <button class="umm-remove-row button-secondary umm-profile-editor umm-remove-option">-</button></td>
</tr>
';
    
    if(!empty($umm_edit_key) && array_key_exists($umm_edit_key, $profile_fields)):
          $value = stripslashes(htmlspecialchars_decode($profile_fields[$umm_edit_key]['value']));
          $type = $profile_fields[$umm_edit_key]['type'];
          $label = stripslashes(htmlspecialchars_decode($profile_fields[$umm_edit_key]['label']));
          $class = $profile_fields[$umm_edit_key]['class'];
          $attrs = stripslashes(htmlspecialchars_decode($profile_fields[$umm_edit_key]['attrs']));
          $after = stripslashes(htmlspecialchars_decode($profile_fields[$umm_edit_key]['after']));
          $required = $profile_fields[$umm_edit_key]['required'];          
          $allow_tags = $profile_fields[$umm_edit_key]['allow_tags'];
          $add_to_profile = $profile_fields[$umm_edit_key]['add_to_profile'];
          $options = (!is_array($profile_fields[$umm_edit_key]['options'])) ? array() : $profile_fields[$umm_edit_key]['options'];
          
          $x = 1;          
          foreach($options as $option):
            $hide_button = ($x == 1) ? ' hidden' : '';
            if(!empty($option['label'])):          
            $options_output .= '<tr class="umm-select-option-row">
	<td><input name="umm_profile_select_label[]" type="text" placeholder="'.__('Label', UMM_SLUG).'" value="' . stripslashes($option['label']) . '" /></td>
	<td><input name="umm_profile_select_value[]" type="text" placeholder="'.__('Value', UMM_SLUG).'" value="' . stripslashes(htmlspecialchars_decode($option['value'])) . '" /></td>
	<td><button class="umm-add-row button-secondary umm-profile-editor umm-add-option">+</button> <button class="umm-remove-row button-secondary umm-profile-editor umm-remove-option' . $hide_button . '">-</button></td>
</tr>
';
          endif; //!empty($option['label'])
          $x++;
          endforeach;
          
          if(empty($options_output)):
            $options_output .= $select_option_row;
          endif;
                          
        else:
        $options_output .= $select_option_row;
    endif;
    $type = (!isset($type) || empty($type)) ? '' : $type;
    $label = (!isset($label) || empty($label)) ? '' : $label;
    $attrs = (!isset($attrs) || empty($attrs)) ? '' : $attrs;
    $after = (!isset($after) || empty($after)) ? '' : $after;
    $required = (!isset($required) || empty($required)) ? '' : $required;
    $add_to_profile = (!isset($add_to_profile) || empty($add_to_profile)) ? '' : $add_to_profile;
    $class = (!isset($class) || empty($class)) ? '' : $class;
    $output = '<div class="umm-profile-field-editor">
    <strong>'.__('Field <a title="W3Schools HTML5 Input Types Reference Page" href="http://www.w3schools.com/html/html5_form_input_types.asp" target="_blank">Type</a>', UMM_SLUG).' :</strong><br /><select class="umm-profile-field-type" size="1" name="umm_profile_field_type">
    <option value="" title="'.__('Do not add to user profile.', UMM_SLUG).'"';
    if($type == '') $output .= ' selected="selected"';
    $output .= '>'.__('None', UMM_SLUG).'</option>
	<option value="text"';
    if($type == 'text') $output .= ' selected="selected"';
    $output .= '>'.__('Text', UMM_SLUG).'</option>
	<option value="color"';
    if($type == 'color') $output .= ' selected="selected"';
    $output .= '>'.__('Color', UMM_SLUG).'</option>
    <option value="date"';
    if($type == 'date') $output .= ' selected="selected"';
    $output .= '>'.__('Date', UMM_SLUG).'</option>
    <option value="datetime"';
    if($type == 'datetime') $output .= ' selected="selected"';
    $output .= '>'.__('Date-Time', UMM_SLUG).'</option>
    <option value="datetime-local"';
    if($type == 'datetime-local') $output .= ' selected="selected"';
    $output .= '>'.__('Date-Time-Local', UMM_SLUG).'</option>
    <option value="email"';
    if($type == 'email') $output .= ' selected="selected"';
    $output .= '>'.__('Email', UMM_SLUG).'</option>
    <option value="month"';
    if($type == 'month') $output .= ' selected="selected"';
    $output .= '>'.__('Month', UMM_SLUG).'</option>
    <option value="number"';
    if($type == 'number') $output .= ' selected="selected"';
    $output .= '>'.__('Number', UMM_SLUG).'</option>
    <option value="range"';
    if($type == 'range') $output .= ' selected="selected"';
    $output .= '>'.__('Range', UMM_SLUG).'</option>
    <option value="search"';
    if($type == 'search') $output .= ' selected="selected"';
    $output .= '>'.__('Search', UMM_SLUG).'</option>
    <option value="tel"';
    if($type == 'tel') $output .= ' selected="selected"';
    $output .= '>'.__('Telephone', UMM_SLUG).'</option>
    <option value="time"';
    if($type == 'time') $output .= ' selected="selected"';
    $output .= '>'.__('Time', UMM_SLUG).'</option>
    <option value="url"';
    if($type == 'url') $output .= ' selected="selected"';
    $output .= '>'.__('URL', UMM_SLUG).'</option>
    <option value="week"';
    if($type == 'week') $output .= ' selected="selected"';
    $output .= '>'.__('Week', UMM_SLUG).'</option>
    <option value="textarea"';
    if($type == 'textarea') $output .= ' selected="selected"';
    $output .= '>'.__('Textarea', UMM_SLUG).'</option>
    <option value="checkbox"';
    if($type == 'checkbox') $output .= ' selected="selected"';
    $output .= '>'.__('Checkbox', UMM_SLUG).'</option>
    <option value="radio"';
    if($type == 'radio') $output .= ' selected="selected"';
    $output .= '>'.__('Radio Button Group', UMM_SLUG).'</option>
    <option value="select"';
    if($type == 'select') $output .= ' selected="selected"';
    $output .= '>'.__('Select Menu', UMM_SLUG).'</option>
    </select>';
    
    $hidden = (empty($type)) ? ' hidden' : '';
    
    $output .= '<div class="umm-input-options' . $hidden . ' umm-profile-field-options">
    <h3>'.__('Settings', UMM_SLUG).'</h3>
    <strong>'.__('Label', UMM_SLUG).':</strong><br />
    <textarea rows="3" cols="40" name="umm_profile_field_label"  placeholder="">' . $label . '</textarea>
    <br />
    <strong>'.__('Classes', UMM_SLUG).':</strong><br />
    <textarea rows="3" cols="40" name="umm_profile_field_class"  placeholder="">' . $class . '</textarea>
    <br />
    <strong>'.__('Additional Attributes', UMM_SLUG).':</strong><br />
    <textarea rows="3" cols="40" name="umm_profile_field_attrs" type="text" placeholder="'.__('Example', UMM_SLUG).': min=&quot;1&quot; max=&quot;5&quot; title=&quot;'.__('My Title', UMM_SLUG).'&quot; placeholder=&quot;'.__('My Text', UMM_SLUG).'&quot">' . $attrs . '</textarea>
    <br />
    <strong>'.__('HTML After', UMM_SLUG).':</strong><br />
    <textarea rows="3" cols="40" name="umm_profile_field_after" placeholder="">' . $after . '</textarea>
    <br />   
    <strong>'.__('Required', UMM_SLUG).':</strong> <select size="1" name="umm_profile_field_required">
	<option value="no"';
    if($required == 'no' || $required == '') $output .= ' selected="selected"';
    $output .= '>'.__('No', UMM_SLUG).'</option>
	<option value="yes"';
    if($required == 'yes') $output .= ' selected="selected"';
    $output .= '>'.__('Yes', UMM_SLUG).'</option>
    </select><br />
    <strong>'.__('Allow Tags', UMM_SLUG).':</strong> <select size="1" name="umm_allow_tags">
	<option value="no"';
    if($add_to_profile == 'no' || $add_to_profile == '') $output .= ' selected="selected"';
    $output .= '>'.__('No', UMM_SLUG).'</option>
    <option value="yes"';
    if($add_to_profile == 'yes') $output .= ' selected="selected"';
    $output .= '>'.__('Yes', UMM_SLUG).'</option> 	
    </select><br />
    <strong>'.__('Add To Profile', UMM_SLUG).':</strong> <select size="1" name="umm_add_to_profile">
	<option value="yes"';
    if($add_to_profile == 'yes' || $add_to_profile == '') $output .= ' selected="selected"';
    $output .= '>'.__('Yes', UMM_SLUG).'</option>
    <option value="no"';
    if($add_to_profile == 'no') $output .= ' selected="selected"';
    $output .= '>'.__('No', UMM_SLUG).'</option>	
    </select>
    </div>';
    
    $hidden = ($type == 'select' || $type == 'radio') ? '' : ' hidden';
    
    $output .= '
    <div class="umm-select-options' . $hidden . ' umm-profile-field-options">
    <h3>'.__('Options', UMM_SLUG).'</h3>
    <table class="umm-select-options-table">
<tr>
	<th>Label</th>
	<th>Value</th>
	<th></th>
</tr>
';
$output .= $options_output;
$output .= '</table>
<table class="umm-select-options-clone hidden">
 <tr class="umm-select-option-row">
	<td><input name="umm_profile_select_label[]" type="text" placeholder="'.__('Label', UMM_SLUG).'" value="" /></td>
	<td><input name="umm_profile_select_value[]" type="text" placeholder="'.__('Value', UMM_SLUG).'" value="" /></td>
	<td><button class="umm-add-row button-secondary umm-profile-editor">+</button> <button class="umm-remove-row button-secondary umm-profile-editor">-</button></td>
</tr>
</table>
    </div>
    </div>';   
    return $output;
}

function umm_random_str($number_of_digits = 1, $type = 3){
    // $type: 1 - numeric, 2 - letters, 3 - mixed, 4 - all ascii chars.
    $num = "";
    $r = 0;
    for($x = 0; $x < $number_of_digits; $x++):
        while(substr($num, strlen($num) - 1, strlen($num)) == $r):
            switch ($type) {
                case "1":
                    $r = rand(0, 9);
                    break;

                case "2":
                    $n = rand(0, 999);
                    if($n % 2):
                        $r = chr(rand(0, 25) + 65);
                    else:
                        $r = strtolower(chr(rand(0, 25) + 65));
                    endif;
                    break;

                case "3":
                    if(is_numeric(substr($num, strlen($num) - 1, strlen($num)))):
                        $n = rand(0, 999);
                        if($n % 2):
                            $r = chr(rand(0, 25) + 65);
                        else:
                            $r = strtolower(chr(rand(0, 25) + 65));
                        endif;
                    else:
                        $r = rand(0, 9);
                    endif;
                    break;
                    
                    case "4":
                    if(is_numeric(substr($num, strlen($num) - 1, strlen($num)))):
                        $n = rand(0, 999);
                        if($n % 2):
                            $r = chr(rand(33, 231));
                        else:
                            $r = strtolower(chr(rand(33, 231)));
                        endif;
                    else:
                        $r = rand(33, 231);
                    endif;                   
                    break;
            }
        endwhile;
        $num .= $r;
    endfor;
    return $num;
}

function umm_reset(){
    global $wpdb;
    $profile_fields = get_option('umm_profile_fields');
    $umm_data = get_option('user_meta_manager_data');       
    foreach($umm_data as $meta_key):
       $user_data = $wpdb->get_results("SELECT * FROM " . $wpdb->users);
       foreach($user_data as $user):
          delete_user_meta($user->ID, $meta_key);
       endforeach;
    endforeach;
    update_option('umm_profile_fields', array());
    update_option('user_meta_manager_data', array());
    $output = "<p>" . __("User Meta Manager data successfully reset.", UMM_SLUG) . "</p>";
    print $output;
    exit;
}

function umm_restore(){
    global $wpdb, $table_prefix;
    $wpdb->query("DELETE FROM " . $wpdb->usermeta);
    $wpdb->query("INSERT INTO " . $wpdb->usermeta . "  SELECT * FROM " . $table_prefix . "umm_usermeta_backup");
    $back_button = umm_button("umm_backup_page&u=1", __('<< Back', UMM_SLUG), "umm-back-button");
    $output = $back_button . '<p class="umm-message">' . __("User meta data successfully restored.", UMM_SLUG) . "</p>";
    print $output;
    exit;
}

function umm_restore_confirm(){
    $budate = get_option('umm_backup_date');
    if($budate == ""): 
      $output = __('No backup data to restore!', UMM_SLUG);
    else:
      $back_button = umm_button("umm_backup_page&u=1", __('<< Back', UMM_SLUG), "umm-back-button");
      $output = $back_button . '<p class="umm-warning"><strong>' . __('Restore all user meta data to the backup version?', UMM_SLUG) . '</strong><br /><a href="#" data-subpage="' . UMM_AJAX . 'umm_restore&u=1" title="' . __('Restore', UMM_SLUG) . '" class="umm-subpage">' . __('Yes', UMM_SLUG) . '</a> <a href="#" data-subpage="' . UMM_AJAX . 'umm_backup_page&u=1" title="' . __('Cancel', UMM_SLUG) . '" class="umm-subpage">' . __('Cancel', UMM_SLUG) . '</a></p>';
    endif;
    print $output;
    exit;
}

function umm_show_profile_fields($echo=true, $fields=false, $debug=false){
   global $current_user;
    $umm_data = get_option('user_meta_manager_data');
    $profile_fields = get_option('umm_profile_fields');
    $show_fields = ($fields) ?  explode(",", str_replace(", ", ",", $fields)) : false;
    if($debug) print_r($profile_fields);
    if(!empty($profile_fields)):
    $output = "";

    if($show_fields):
      $new_array = array();
      foreach($show_fields as $profile_field_name):
      if(isset($profile_fields[$profile_field_name]))
       $new_array[$profile_field_name] = $profile_fields[$profile_field_name];
      endforeach;
      $profile_fields = $new_array;   
    endif;

    foreach($profile_fields as $profile_field_name => $profile_field_settings):
    if((!$show_fields && $profile_field_settings['add_to_profile'] == 'yes') || ($show_fields && array_key_exists($profile_field_name, $umm_data))):
      $default_value = stripslashes(htmlspecialchars_decode($profile_field_settings['value']));
      $the_user = ((isset($_REQUEST['user_id']) && !empty($_REQUEST['user_id'])) && current_user_can('add_users')) ? $_REQUEST['user_id'] : $current_user->ID;
      $user_value = stripslashes(htmlspecialchars_decode(get_user_meta($the_user, $profile_field_name, true)));
      
      $value = (empty($user_value)) ? $default_value : $user_value;
      
      $output .= '<tr>
	<th><label for="' . $profile_field_name . '" class="' . str_replace(" ", "-", strtolower($profile_field_name)) . '">' . stripslashes(htmlspecialchars_decode($profile_field_settings['label'])) . '</label></th>
	<td>';
    switch($profile_field_settings['type']){
            case 'text':
            case 'color':
            case 'date':
            case 'datetime':
            case 'datetime-local':
            case 'email':
            case 'month':
            case 'number':
            case 'range':
            case 'search':
            case 'tel':
            case 'time':
            case 'url':
            case 'week':           
            $output .= '<input type="' . $profile_field_settings['type'] . '" name="' . $profile_field_name . '" value="' . $value . '" class="' . stripslashes(htmlspecialchars_decode($profile_field_settings['class'])) . '"';
            if($profile_field_settings['required'] == 'yes')
            $output .= ' required="required"';
            if(!empty($profile_field_settings['attrs']))
            $output .= ' ' . stripslashes(htmlspecialchars_decode($profile_field_settings['attrs']));
            $output .= " />";
            break;
            
            case 'textarea':
            $output .= '<textarea name="' . $profile_field_name . '" class="' . stripslashes(htmlspecialchars_decode($profile_field_settings['class'])) . '"';
            if($profile_field_settings['required'] == 'yes')
            $output .= ' required="required"';
            if(!empty($profile_field_settings['attrs']))
            $output .= ' ' . stripslashes(htmlspecialchars_decode($profile_field_settings['attrs']));
            $output .= '>' . $value . '</textarea>' . "\n";
            break;
            
            case 'checkbox':
 //TODO:Support for checkbox groups                      
            $output .= '<input type="checkbox" name="' . $profile_field_name;
            //if(count($profile_field_settings['options']) > 1) $output .= '[]';
            $output .= '" value="' . $value . '" class="' . stripslashes(htmlspecialchars_decode($profile_field_settings['class'])) . '"';
            if($profile_field_settings['required'] == 'yes')
              $output .= ' required="required"';
            if(!empty($value))
              $output .= ' checked="checked"';
            if(!empty($profile_field_settings['attrs']))
              $output .= ' ' . stripslashes(htmlspecialchars_decode($profile_field_settings['attrs']));
            $output .= ' />' . "\n";
            break; 
            
            case 'radio':
            foreach($profile_field_settings['options'] as $option => $option_settings):
              if(!empty($option_settings['label'])):
              $output .= '<input type="' . $profile_field_settings['type'] . '" name="' . $profile_field_name;
              
              $output .= '" value="' . $option_settings['value'] . '" class="' . stripslashes(htmlspecialchars_decode($profile_field_settings['class'])) . '"';
              if($profile_field_settings['required'] == 'yes')
              $output .= ' required="required"';
              if($option_settings['value'] == $value)
              $output .= ' checked="checked"';
              if(!empty($profile_field_settings['attrs']))
              $output .= ' ' . stripslashes(htmlspecialchars_decode($profile_field_settings['attrs']));
              $output .= ' /><label class="' . str_replace(" ", "-", strtolower($profile_field_name)) . '">' . $option_settings['label'] . '</label> ';
              endif;
            endforeach; 
            break;
            
            case 'select':
            $output .= '<select name="' . $profile_field_name . '" class="' . stripslashes(htmlspecialchars_decode($profile_field_settings['class'])) . '"';
            if($profile_field_settings['required'] == 'yes')
            $output .= ' required="required"';
            if(!empty($profile_field_settings['attrs']))
            $output .= ' ' . stripslashes(htmlspecialchars_decode($profile_field_settings['attrs']));
            $output .= ">\n";
            foreach($profile_field_settings['options'] as $option => $option_settings):
            if(!empty($option_settings['label'])):
            $output .= '<option value="' . stripslashes($option_settings['value']) . '"';
              if($option_settings['value'] == $value) $output .= ' selected="selected"';
            $output .= '>'.stripslashes($option_settings['label']).'</option>
            ';
            endif;
            endforeach; 
            $output .= "<select>\n";           
            break;
            
            default:
            $output .= '<input type="text" name="' . $profile_field_name . '" value="' . $value . '" class="' . stripslashes(htmlspecialchars_decode($profile_field_settings['class'])) . '"';
            if($profile_field_settings['required'] == 'yes')
            $output .= ' required="required"';
            if(!empty($profile_field_settings['attrs']))
            $output .= ' ' . stripslashes(htmlspecialchars_decode($profile_field_settings['attrs']));
            $output .= " />";
        }
    
    if(!empty($profile_field_settings['after'])) 
    $output .= stripslashes(htmlspecialchars_decode($profile_field_settings['after']));
    
    $output .= "</td>
</tr>";
    endif; // $show_fields
    endforeach; 
    endif; // !empty($profile_fields)
    
    if($output != ""):
    $output = '<table class="form-table">
  <tbody>
' . $output . '
  </tbody>
</table>
';
    if($echo):
    echo  $output;
    else:
    return $output;
    endif;
    endif;
    
}

function umm_sort($a, $b){
    $orderby = UMM_ORDERBY;
    $order = strtolower(UMM_ORDER);
    switch($order){        
        case "desc":
        if ( $a->$orderby > $b->$orderby ) return -1;
        if ( $a->$orderby < $b->$orderby ) return 1;
        return 0;
        break;
        
        default:
        if ( $a->$orderby < $b->$orderby ) return -1;
        if ( $a->$orderby > $b->$orderby ) return 1;
        return 0;
        break;
    }   
}

function umm_subpage_title($user_id, $text){
    $nickname = get_user_meta($user_id, 'nickname', true);
    $output = '<h3 class="umm-subpage-title">' . sprintf($text, '<a href="' . admin_url('user-edit.php?user_id=' . $user_id) . '" target="_blank"><em>' . $nickname .  '</em></a>') . '</h3>';
    return $output;
}

function umm_switch_action(){
    if(function_exists($_REQUEST['sub_action']))
       call_user_func($_REQUEST['sub_action']);
}

function umm_ui(){
    if(!current_user_can('edit_users')):
    _e('You do not have the appropriate permission to view this content.', UMM_SLUG);
    else:
    $_UMM_UI = new UMM_UI();
    $_UMM_UI->display_module();
    endif;
}

function umm_update_columns(){
    global $wpdb;
    $umm_column = @explode("|", $_REQUEST['umm_column_key']);
    $umm_column_key = $umm_column[0];
    switch($_REQUEST['mode']){
        case "add_columns":        
        $umm_table = $umm_column[1];
        $umm_column_label = $_REQUEST['umm_column_label'];
        if($umm_column_key == '' || $umm_column_label == ''):
          $output = __('Key and label are both required. Try again.', UMM_SLUG);
        else:
          if(umm_column_exists($umm_column_key)):
            $output = __('Column already exists.', UMM_SLUG);
          else:
            switch($umm_table){
                case "users":
                $users_columns = (!get_option("umm_users_columns") ? array('ID' => __('ID', UMM_SLUG), 'user_login' => __('User Login', UMM_SLUG), 'user_registered' => __('Date Registered', UMM_SLUG)) : get_option("umm_users_columns"));
                $users_columns[$umm_column_key] = $umm_column_label;
                update_option("umm_users_columns", $users_columns);
                break;
                
    
                case "usermeta":
                $usermeta_columns = (!get_option("umm_usermeta_columns")) ? array() : get_option("umm_usermeta_columns");
                $usermeta_columns[$umm_column_key] = $umm_column_label;
                update_option("umm_usermeta_columns", $usermeta_columns);
                break;
            }
            $output = __('Column successfully added.', UMM_SLUG);
          endif;           
        endif;
        break;
        
        case "remove_columns":
        if(empty($_REQUEST['umm_column_key'])):
          $output = __('No key was selected. Select a key to remove from the table.', UMM_SLUG);
        else:
        $users_columns = (!get_option("umm_users_columns") ? array('ID' => __('ID', UMM_SLUG), 'user_login' => __('User Login', UMM_SLUG), 'user_registered' => __('Date Registered', UMM_SLUG)) : get_option("umm_users_columns"));
        $usermeta_columns = (!get_option("umm_usermeta_columns")) ? array() : get_option("umm_usermeta_columns");
        if(array_key_exists($umm_column_key, $users_columns)):
            unset($users_columns[$umm_column_key]);
            update_option("umm_users_columns", $users_columns);
        elseif(array_key_exists($umm_column_key, $usermeta_columns)):
            unset($usermeta_columns[$umm_column_key]);
            update_option("umm_usermeta_columns", $usermeta_columns);         
        endif;
        $output = __('Column successfully removed.', UMM_SLUG);
        endif;
        break;
        
    }
    print $output;
    exit; 
}

function umm_update_profile_fields(){
    global $current_user;
    $saved_profile_fields = (!get_option('umm_profile_fields')) ? array() : get_option('umm_profile_fields');
    foreach($saved_profile_fields as $field_name => $field_settings):
      $posted_value = (isset($_REQUEST[$field_name])) ? trim($_REQUEST[$field_name]) : '';
      $field_value = ($posted_value == '') ? $field_settings['value'] : addslashes(htmlspecialchars($posted_value));
      $the_user = ((isset($_REQUEST['user_id']) && !empty($_REQUEST['user_id'])) && current_user_can('add_users')) ? $_REQUEST['user_id'] : $current_user->ID;
      update_user_meta($the_user, $field_name, $field_value);
    endforeach;
}

function umm_update_settings(){
    update_option('umm_settings', $_POST);
    $output = __('Settings successfully saved.', UMM_SLUG);
    print $output;
    exit; 
}

function umm_update_user_meta(){
    global $wpdb;
    $output = "";
    $mode = $_POST['mode'];
    $all_users = (!empty($_POST['all_users'])) ? true : false;
    $umm_data = get_option('user_meta_manager_data');
    $umm_singles_data = get_option('user_singles_data');
    $umm_singles_data = (empty($umm_singles_data) || !is_array($umm_singles_data)) ? array() : $umm_singles_data;
    $posted_value = !isset($_POST['meta_value']) ? '' : $_POST['meta_value'];
    $meta_key = (!empty($_POST['meta_key'])) ? $_POST['meta_key'] : '';
    $meta_value = (isset($_POST['umm_allow_tags']) && $_POST['umm_allow_tags'] == 'yes') ? $_POST['meta_value'] : wp_strip_all_tags($posted_value);
    $meta_key_exists = false;
    if(!empty($meta_key)):
    if(is_array($meta_key)):
        foreach($meta_key as $key):
           $data = umm_usermeta_data('WHERE meta_key="' . $key . '"');
           if(count($data) > 0):
              $meta_key_exists = true;
           endif;     
        endforeach;
    else:
      $data = umm_usermeta_data('WHERE meta_key="' . $meta_key . '"');
      if(count($data) > 0):
         $meta_key_exists = true;
      endif;
    endif;     
    if($meta_key_exists && $_POST['mode'] == 'add'):
    // meta_key already exists
    $output = '<span class="umm-error-message">' . __('Error: Meta key already existed. Choose a different name.', UMM_SLUG) . '</span>';
    else: 
    
    switch($mode){       
        case "add":
        case "edit":
        
        if($all_users):
           // Update default value and value for all users 
            $data = $wpdb->get_results("SELECT * FROM " . $wpdb->users);
            foreach($data as $user):
                update_user_meta($user->ID, $meta_key, maybe_unserialize(trim(stripslashes($meta_value))), false);
            endforeach;
            $umm_data[$meta_key] = $_POST['meta_value'];
            update_option('user_meta_manager_data', $umm_data);
        elseif(is_array($_POST['meta_key'])):
           // Update values for single user  
           $x = 0;
           foreach($_POST['meta_key']  as $meta_key):          
            if(empty($umm_singles_data)):
               $umm_singles_data = array($meta_key);
            else:
               if(!in_array($meta_key, $umm_singles_data)):
                  array_push($umm_singles_data, $meta_key);
               endif;
            endif;
            update_option('umm_singles_data', $umm_singles_data);
            update_user_meta($_POST['u'], $meta_key, maybe_unserialize(trim(stripslashes($_POST['meta_value'][$x]))), false);
            $x++;
           endforeach;
        else:
           // Update default value only
           $umm_data[$meta_key] = $_POST['meta_value'];
           update_option('user_meta_manager_data', $umm_data);
            
        endif;
        
        $saved_profile_fields = get_option('umm_profile_fields');
        
        if(empty($saved_profile_fields)) $saved_profile_fields = array(); 
              
        $options = array();
        if(!empty($_POST['umm_profile_field_type'])):        
          if(!empty($_POST['umm_profile_select_label']) && ($_POST['umm_profile_field_type'] == 'select' || $_POST['umm_profile_field_type'] == 'radio')):
           $x = 0;
          foreach($_POST['umm_profile_select_label'] as $option_label):
            if($option_label != ''):
               $options[$x] = array('label' => $option_label, 'value' => $_POST['umm_profile_select_value'][$x]);
               $x++;
            endif; 
          endforeach;
          else:
          $options = array();
          endif;
        
          $new_profile_field_data = array('value' => $meta_value,
                                          'type' => $_POST['umm_profile_field_type'],
                                          'label' => htmlspecialchars($_POST['umm_profile_field_label']),
                                          'class' => $_POST['umm_profile_field_class'],
                                          'attrs' => htmlspecialchars($_POST['umm_profile_field_attrs']),
                                          'after' => htmlspecialchars($_POST['umm_profile_field_after']) ,
                                          'required' => $_POST['umm_profile_field_required'],
                                          'allow_tags' => $_POST['umm_allow_tags'],
                                          'add_to_profile' => $_POST['umm_add_to_profile'],
                                          'options' => $options);                   
        endif;
        
        if(!empty($meta_key)):
        
        if($all_users):
         $umm_data[$meta_key] = $meta_value;        
         if((!array_key_exists($meta_key, $saved_profile_fields) || array_key_exists($meta_key, $saved_profile_fields)) && !empty($_POST['umm_profile_field_type'])):
           // add or update field
           $saved_profile_fields[$meta_key] = $new_profile_field_data;
           update_option('umm_profile_fields', $saved_profile_fields);
         elseif(array_key_exists($meta_key, $saved_profile_fields) && (!isset($_POST['umm_profile_field_type']) || empty($_POST['umm_profile_field_type']))):
           // remove field
           unset($saved_profile_fields[$meta_key]);
           update_option('umm_profile_fields', $saved_profile_fields);
         endif; // !array_key_exists                
         update_option('user_meta_manager_data', $umm_data);
         endif; // all_users
         
         switch($mode){
            case 'add':
            $output = __('Meta data successfully added.', UMM_SLUG);
            break;
            
            default:
            $output = __('Meta data successfully updated.', UMM_SLUG);
         }
                 
        else: // !$meta_key
        switch($mode){
            case 'add':
            $output = '<span class="umm-error-message">' . __('Error: No meta key entered.', UMM_SLUG) . '</span>';
            break;
            
            default:
            $output = '<span class="umm-error-message">' . __('Error: No meta key selected.', UMM_SLUG) . '</span>';
         }        
        endif;                    
        break;

        case "delete":
        if($_POST['meta_key']):
        $meta_key = $_POST['meta_key'];
        $saved_profile_fields = get_option('umm_profile_fields');
        if($all_users):
            $data = $wpdb->get_results("SELECT * FROM $wpdb->users");
            foreach($data as $user):
                delete_user_meta($user->ID, $meta_key);
            endforeach;
            unset($umm_data[$meta_key]);
            update_option('user_meta_manager_data', $umm_data);
            if(array_key_exists($meta_key, $saved_profile_fields)):
            // remove field
            unset($saved_profile_fields[$meta_key]);
            update_option('umm_profile_fields', $saved_profile_fields);
            // remove custom column
            $users_columns = (!get_option("umm_users_columns") ? array('ID' => __('ID', UMM_SLUG), 'user_login' => __('User Login', UMM_SLUG), 'user_registered' => __('Date Registered', UMM_SLUG)) : get_option("umm_users_columns"));
            $usermeta_columns = (!get_option("umm_usermeta_columns")) ? array() : get_option("umm_usermeta_columns");
            if(array_key_exists($meta_key, $users_columns)):
               unset($users_columns[$meta_key]);
               update_option("umm_users_columns", $users_columns);
            elseif(array_key_exists($meta_key, $usermeta_columns)):
               unset($usermeta_columns[$meta_key]);
               update_option("umm_usermeta_columns", $usermeta_columns);
            endif; // array_key_exists
         endif; // array_key_exists            
        else: // all_users
            delete_user_meta($_POST['u'], $_POST['meta_key']);
        endif;
        $output = __('Meta data successfully deleted.', UMM_SLUG);
        endif;
        break;
    }
    endif; // meta_key already exists 
    else: // if($meta_key) 
    if($mode) $output =  __('Meta Key is required!', UMM_SLUG);
    endif;
    print $output;
    exit;
}

function umm_useraccess_shortcode($atts, $content) {
    global $current_user;
    $access = true;
    $key = (!isset($atts['key'])) ? '' : $atts['key'];
    $value = (!isset($atts['value'])) ? '' : $atts['value'];
    $users = (isset($atts['users'])) ? explode(" ", $atts['users']) : false;
    $message = (!isset($atts['message'])) ? '' : $atts['message'];
    $json = (!isset($atts['json'])) ? false : $atts['json'];
    if($json):
    $access = false;
      $json = json_decode($json);
      foreach($json as $k => $v):
        if($k && $v):
          $meta_value = get_user_meta($current_user->ID, $k, true);
          if($meta_value == trim($v)):        
            $access = true;
          endif;  
        endif;
    endforeach; 
    elseif($key && $value):
        $meta_value = get_user_meta($current_user->ID, $key, true);
      if($meta_value != trim($value)):        
          $access = false;
      endif;
    endif;
    

    if($users):
        if(!in_array($current_user->ID, $users)):
           $access = false; 
        endif;
    endif;

    if(!$access):
        if($message):
            $content = $message;
        else:
            $content = __('You do not have sufficient permissions to access this content.', UMM_SLUG);
        endif;
    endif;    
    return $content;         
}

function umm_usermeta_data($criteria="ORDER BY umeta_id ASC"){
    global $wpdb;
    $data = $wpdb->get_results("SELECT * FROM $wpdb->usermeta " . $criteria);
    return $data;
}

function umm_usermeta_keys_menu($select=true,$optgroup=false,$include_used=false){
    global $wpdb;
    $used_columns = umm_get_columns();
    $output = '';
    if($select):
      $output .= '<select name="umm_usermeta_keys">' . "\n";
    endif;
    if($optgroup):
      $output .= '<optgroup label="wp_usermeta">' . "\n";
    endif;  
    $data = $wpdb->get_results("SELECT DISTINCT meta_key FROM " . $wpdb->usermeta);
    foreach($data as $d):
    if(!array_key_exists($d->meta_key, $used_columns) || (array_key_exists($d->meta_key, $used_columns) && $include_used)):
        $output .= '<option value="' . $d->meta_key . '|usermeta">' . $d->meta_key . '</option>' . "\n";         
    endif;
    endforeach;
    if($optgroup):
      $output .= '</optgroup>' . "\n";
    endif;
    $output .= '</select>' . "\n";
    return $output;    
}

function umm_usermeta_shortcode($atts, $content) {
    global $current_user;
    
    if(isset($atts['key']) && !empty($atts['key'])):
    $key = $atts['key'];
    $user = !empty($atts['user']) ? $atts['user'] : $current_user->ID;
    $content = get_user_meta($user, $key, true);
    endif;
    
    if(isset($atts['fields']) && $current_user->ID != 0 && !empty($atts['fields'])):   
    $umm_data = get_option('user_meta_manager_data');
    $class = (!empty($atts['class'])) ? $atts['class'] : 'umm-usermeta-update-form';
    $submit = (!empty($atts['submit'])) ? $atts['submit'] : __('Submit', UMM_SLUG);
    $success = (!empty($atts['success'])) ? $atts['success'] : __('Update successful!', UMM_SLUG);
    $error = (!empty($atts['error'])) ? $atts['error'] : __('Authorization required!', UMM_SLUG);
    $email_to = (!empty($atts['email_to'])) ? $atts['email_to'] : false;
    $email_from = (!empty($atts['email_from'])) ? $atts['email_from'] : __('do-not-reply', UMM_SLUG) . '@' . $_SERVER["HTTP_HOST"];
    $subject = (!empty($atts['subject'])) ? $atts['subject'] : false;
    $message = (!empty($atts['message'])) ? $atts['message'] : false;
    $vars = (!empty($atts['vars'])) ? explode('&', htmlspecialchars_decode($atts['vars'])) : array();
    $content = '<form action="#" method="post" class="' . $class .  '">';
    $show_fields = (!empty($atts['fields'])) ?  explode(",", str_replace(", ", ",", $atts['fields'])) : array();
    $umm_user = md5($_SERVER["REMOTE_ADDR"].$_SERVER["HTTP_USER_AGENT"]);
    
    if((isset($_POST['umm_update_usermeta']) && isset($_POST['umm_nonce'])) && $_POST['umm_forbots'] == ''):
    if(wp_verify_nonce($_POST['umm_nonce'], 'umm_wp_nonce') && $umm_user == $_POST['umm_update_usermeta']):
    
    $output = "";
      foreach($show_fields as $field => $field_name):
        if(isset($_POST[$field_name]) && array_key_exists($field_name, $umm_data)):
        $val = sprintf("%s", $_POST[$field_name]);
        $output .= $field_name . " = " . $val . "\n";
        update_user_meta($current_user->ID, $field_name, $val);       
        endif;
      endforeach;
      if($email_to):
        $email_message = sprintf($message, $output);
        mail($email_to, $subject, $email_message, "From: " . $email_from . "\n" . "X-Mailer: PHP/" . phpversion());
      endif;
      $content .= '<div class="umm-success-message">' . $success . '</div>' . "\n";
    else:
      $content .= '<div class="umm-error-message">' . $error . '</div>' . "\n";
    endif;
    endif;
    $umm_nonce = wp_nonce_field('umm_wp_nonce', 'umm_nonce');
    $content .= umm_show_profile_fields(false, $atts['fields']) . '
    <button type="submit">' . $submit .  '</button>' . "\n" . $umm_nonce . "\n";
 
    foreach($vars as $var):
      $v = split('=', $var);
      if(!empty($v[0]))
      $content .=  '<input type="hidden" name="' . $v[0] . '" value="' . $v[1] . '" />' . "\n";  
    endforeach;
    $content .=  '<input type="hidden" name="umm_update_usermeta" value="' . $umm_user . '" />
    <input type="hidden" name="umm_forbots" value="" /></form>' . "\n";
    endif;
    
    return $content; 
}

function umm_users_keys_menu($select=true, $optgroup=false, $include_used=false){
    global $wpdb;
    $used_columns = umm_get_columns();
    $output = '';
    if($select):
      $output .= '<select name="umm_users_keys">' . "\n";
    endif;
    if($optgroup):
      $output .= '<optgroup label="wp_users">' . "\n";
    endif;
    $data = $wpdb->get_results('SELECT * FROM ' . $wpdb->users . ' LIMIT 1');
    foreach($data as $k):
    $k = (array) $k;
    foreach($k as $kk => $vv):
        if(!array_key_exists($kk, $used_columns)):
        $output .= '<option value="' . $kk . '|users">' . $kk . '</option>' . "\n";
        endif;
    endforeach;                
    endforeach;
    if($optgroup):
      $output .= '</optgroup>' . "\n";
    endif;
    if($select):
      $output .= '</select>' . "\n";
    endif;
    return $output; 
}

add_action('admin_menu', 'umm_admin_menu');
add_action('admin_init', 'umm_admin_init');
add_action('user_register', 'umm_default_keys');
//TODO:Add fields to the top or to the bottom of the profile editor
//add_action('profile_personal_options', 'umm_show_profile_fields');
add_action('edit_user_profile', 'umm_show_profile_fields');
add_action('profile_update', 'umm_update_profile_fields');
add_action('show_user_profile', 'umm_show_profile_fields');

// All ajax admin-ajax calls pipe through umm_switch_action()
add_action('wp_ajax_umm_switch_action','umm_switch_action');

add_shortcode('usermeta', 'umm_usermeta_shortcode');
add_shortcode('useraccess', 'umm_useraccess_shortcode');

add_filter('contextual_help', 'umm_help', 10, 3);

register_activation_hook(__FILE__, 'umm_install');
register_deactivation_hook(__FILE__, 'umm_deactivate');

?>