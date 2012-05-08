<?php

/**
 * Plugin Name: User Meta Manager
 * Plugin URI: http://websitedev.biz
 * Description: User Meta Manager allows administratiors to add, edit, or delete user meta data. User   Meta Manager also provides a shorttag for inserting user meta data into posts or pages. <strong>To display data for a particular user:</strong> <code>[usermeta key="meta key" user="user id"]</code> <strong>To display data for the current user:</strong> <code>[usermeta key="meta key"]</code> An additional shorttag is available for restricting user access based on a meta key and value or user ID. <strong>To restrict access based on meta key and value:</strong> <code>[useraccess key="meta key" value="meta value" message="You do not have permission to view this content."]Restricted content.[/useraccess]</code> Allowed users will have a matching meta value. <strong>To restrict access based on user ID:</strong> <code>[useraccess users="1 22 301" message="You do not have permission to view this content."]Restricted content.[/useraccess]</code> Allowed user IDs are listed in the users attribute.
 * Version: 1.0
 * Author: Jason Lau
 * Author URI: http://websitedev.biz
 * Disclaimer: Use at your own risk. No warranty expressed or implied.
 * Always backup your database before making changes.
 * Copyright 2012 http://websitedev.biz http://jasonlau.biz

 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
 
 if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    exit('Please don\'t access this file directly.');
}

define('UMM', null);
define('UMM_VERSION', '1.0');
define("UMM_PATH", ABSPATH . 'wp-content/plugins/user-meta-manager/');

if(!class_exists('WP_List_Table')):
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
endif;

//TODO:load_plugin_textdomain('user-meta-manager', '/wp-content/plugins/user-meta-manager/user-meta-manager.pot');

add_action('admin_menu', 'umm_admin_menu');
function umm_admin_menu(){
  add_submenu_page('users.php', 'User Meta Manager', 'User Meta Manager', 'publish_pages', 'user-meta-manager', 'umm_ui');
}

wp_enqueue_script('jquery');
wp_enqueue_script('scriptaculous');
wp_enqueue_script('scriptaculous-effects');
wp_enqueue_script('thickbox');
wp_enqueue_style('thickbox');

$umm_mode = (!isset($_REQUEST['umm_mode']) || $_REQUEST['umm_mode'] == '') ? '' : $_REQUEST['umm_mode'];

add_action('wp_ajax_editusermeta','editusermeta');
function editusermeta(){
    global $wpdb;
    $user_id = $_REQUEST['u'];
    $data = $wpdb->get_results("SELECT * FROM $wpdb->usermeta WHERE user_id = $user_id");
    $output = '<form id="updateusermeta_form" method="post">
    <table class="umm_edit_table">
    <thead>
    <tr>
      <th>Key</th>
      <th>Value</th>
    </tr>
  </thead>
    ';
    foreach($data as $d){
        $output .= "<tr><td>".$d->meta_key ."</td><td><input name=\"meta_key[]\" type=\"hidden\" value=\"". $d->meta_key ."\" /><input name=\"meta_value[]\" type=\"text\" value='". $d->meta_value ."' size=\"40\" /> </td></tr>";
    }
    $output .= '</table>
    <div class="updateusermeta-result hidden"></div>
    <input id="updateusermeta_submit" class="button-primary" type="submit" value="Update" />
    <input name="mode" type="hidden" value="edit" /><input name="u" type="hidden" value="' . $user_id . '" /><input name="return_page" type="hidden" value="admin-ajax.php?action=editusermeta&width=600&height=500&u=' . $user_id . '" />
    </form>  
    ';
    print $output;
    exit;
}

add_action('wp_ajax_addusermeta','addusermeta');
function addusermeta(){
    global $wpdb;
    $user_id = $_REQUEST['u'];
    $output = '<form id="updateusermeta_form" method="post">
    <table class="umm_add_table">
    <thead>
    <tr>
      <th>Key</th>
      <th>Default Value</th>
    </tr>
  </thead>
    <tr><td><input name="meta_key" type="text" value="" placeholder="Meta Key" /></td><td><input name="meta_value" type="text" value=\'\' size="40" placeholder="Meta Default Value" /> </td></tr>
    <tr><td>All Users</td><td><select name="all_users" size="1">
	<option value="false">No</option>
	<option value="true">Yes</option>
</select></td></tr>';
    $output .= '</table>
    <div class="updateusermeta-result hidden"></div>
    <input id="updateusermeta_submit" class="button-primary" type="submit" value="Update" />
    <input name="mode" type="hidden" value="add" /><input name="u" type="hidden" value="' . $user_id . '" /><input name="return_page" type="hidden" value="admin-ajax.php?action=addusermeta&width=600&height=500&u=' . $user_id . '" />
    </form>  
    ';
    print $output;
    exit;
}

add_action('wp_ajax_deleteusermeta','deleteusermeta');
function deleteusermeta(){
    global $wpdb;
    $user_id = $_REQUEST['u'];
    $data = $wpdb->get_results("SELECT * FROM $wpdb->usermeta WHERE user_id = $user_id");
    $output = '<form id="updateusermeta_form" method="post">
    <strong>Meta Key:</strong> <select name="meta_key" class="umm_meta_key_menu">
    <option value="">Select A Meta Key</option>
    ';
    foreach($data as $d){
        $output .= "<option value=\"".$d->meta_key ."\">".$d->meta_key ."</option>";
    }
    $output .= '</select><br />
    <strong>All Users:</strong> <select name="all_users" size="1">
	<option value="false">No</option>
	<option value="true">Yes</option>
</select><br />
    <div class="updateusermeta-result hidden"></div>
    <input id="updateusermeta_submit" class="button-primary" type="submit" value="Delete" />
    <input name="mode" type="hidden" value="delete" /><input name="u" type="hidden" value="' . $user_id . '" /><input name="return_page" type="hidden" value="admin-ajax.php?action=deleteusermeta&width=600&height=500&u=' . $user_id . '" />
    </form>  
    ';
    print $output;
    exit;
    exit;
}

add_action('wp_ajax_updateusermeta','updateusermeta');
function updateusermeta(){
    global $wpdb;
    $all_users = ($_POST['all_users'] == "true") ? true : false;
    $umm_data = get_option('user_meta_manager_data');
    switch($_POST['mode']){
        case "add":
        if($all_users){
            $data = $wpdb->get_results("SELECT * FROM $wpdb->users");
            foreach($data as $user){
                update_user_meta($user->ID, $_POST['meta_key'], $_POST['meta_value'], false);
            }
            $umm_data[$_POST['meta_key']] = $_POST['meta_value'];
            update_option('user_meta_manager_data', $umm_data);
        } else {
            update_user_meta($_POST['u'], $_POST['meta_key'], $_POST['meta_value'], false);
        }
        $output = 'Meta data successfully added.';
        break;
        
        case "edit":
        $x = 0;
        foreach($_POST['meta_key'] as $key){
            update_user_meta($_POST['u'], $key, trim(stripslashes($_POST['meta_value'][$x])));
            $x++;
        }
        $output = 'Meta data successfully updated.';
        break;
        
        case "delete":
        if($all_users){
            $data = $wpdb->get_results("SELECT * FROM $wpdb->users");
            foreach($data as $user){
                delete_user_meta($user->ID, $_POST['meta_key']);
            }
            
            unset($umm_data[$_POST['meta_key']]);
            $umm_data = array_values($umm_data);
            update_option('user_meta_manager_data', $umm_data);
        } else {
            delete_user_meta($_POST['u'], $_POST['meta_key']);
        }
        $output = 'Meta data successfully deleted.';
        break;
    }
    print $output;
    exit;
}
    
class UMM_UI extends WP_List_Table {
    
    function __construct(){
        global $status, $page;
        parent::__construct( array(
            'singular'  => __('user', 'user-meta-manager'),
            'plural'    => __('users', 'user-meta-manager'),
            'ajax'      => false
        ) );
        $this->title = "User Meta Manager";
        $this->slug = "user-meta-manager";
        $this->shortname = "umm_ui";
        $this->version = UMM_VERSION;
        
                 
    }
    
    function column_default($item, $column_name){
        switch($column_name){
            case 'ID':
            case 'user_login':
            case 'user_nicename':
            case 'display_name':           
            return $item->$column_name;
            default:
            return print_r($item,true);
        }
    }
    
    function column_user_login($item){
        $actions = array(
            'edit_meta_data' => sprintf('<a href="admin-ajax.php?action=editusermeta&width=600&height=500&u=%s" title="Edit User Meta" class="thickbox">' . __('Edit Meta Data', $this->slug) . '</a>',$item->ID),
            'add_user_meta' => sprintf('<a href="admin-ajax.php?action=addusermeta&width=600&height=500&u=%s" title="Add User Meta" class="thickbox">' . __('Add Meta Data', $this->slug) . '</a>',$item->ID),
            'delete_user_meta' => sprintf('<a href="admin-ajax.php?action=deleteusermeta&width=600&height=500&u=%s" title="Delete User Meta" class="thickbox">' . __('Delete Meta Data', $this->slug) . '</a>',$item->ID)
        );
        return sprintf('%1$s %2$s',
            $item->user_login,
            $this->row_actions($actions)
        );
    }
    
    function get_columns(){
        $columns = array(
            'ID'    => __('ID', 'user-meta-manager'),
            'user_login'     => __('User Login', $this->slug),            
            'user_nicename'  => __('Nice Name', $this->slug),
            'display_name' => __('Display Name', $this->slug)
        );
        return $columns;
    }
    
    function get_sortable_columns() {
        $sortable_columns = array(
            'ID'    => array('ID',false),
            'user_login'     => array('user_login',false),
            'user_nicename'  => array('user_nicename',false),
            'display_name' => array('display_name',false)
        );
        return $sortable_columns;
    }
    
    function get_bulk_actions() {
        $actions = array();
        return $actions;
    }
    
    function process_bulk_action(){
        global $wp_rewrite, $wpdb;
        if('edit_meta_data' === $this->current_action()):
            $output = "<div id=\"umm-status\" class=\"updated\">
            <input type=\"button\" class=\"umm-close-icon button-secondary\" title=\"Close\" value=\"x\" />"; 
            $output .= "</p></div>\n";
            define("UMM_STATUS", $output);       
        endif; // $this->current_action        
    }
    
    function close_icon(){
        echo '<input type="button" class="umm-close-icon button-secondary" title="' . __('Close', 'user-meta-manager') . '" value="x" />';
    }
    
    function prepare_items() {
        global $wpdb;
        
        $this->process_bulk_action();
        
        $per_page = (!$_REQUEST['per_page']) ? 10 : $_REQUEST['per_page'];
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        $orderby = (!$_REQUEST['orderby']) ? ' ORDER BY ID' : ' ORDER BY ' . $_REQUEST['orderby'];
        $order = (!$_REQUEST['order']) ? ' ASC' : ' ' . $_REQUEST['order'];
        $search = (!$_REQUEST['s']) ? "" : " WHERE " . $_REQUEST['umm_search_mode'] . " REGEXP '" . $_REQUEST['s'] . "'";        
        $query = "SELECT * FROM $wpdb->users " . $search . $orderby . $order;           
        $data = $wpdb->get_results($query);
        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
        $this->items = $data;
        $this->set_pagination_args(array('total_items' => $total_items, 'per_page'    => $per_page, 'total_pages' => ceil($total_items/$per_page)));
    }

  function display_module(){
    global $umm_mode;
     
    $per_page = (!$_REQUEST['per_page']) ? 10 : $_REQUEST['per_page'];
    $this->prepare_items();  
    ?>
    <style type="text/css">
    <!--
    
    #umm-left-panel{
        position: relative;
        margin: 0px 0px 0px 0px !important;
        border: 0px solid red;
    }
    
	.umm-error-field{
	   border: 1px solid red;
       background-color: #FFFF99;
	}
    
    .umm-close-icon, .umm-close-info-icon{
        float: right;
        margin: 5px 0px;
    }
    
    .umm-info{
        background: #ECECEC;
        border: 1px solid #CCC;
        padding: 0 10px;
        margin: 5px 0px;
        border-radius: 5px;
        -moz-border-radius: 5px;
        -webkit-border-radius: 5px;
    }
    
    .umm-hidden{
        display: none;
    }
    
    #umm-left-panel{
        z-index: 1;
        width:100%;
    }
    
    #umm-form, #umm-list-table-form{
        position: relative;
        margin: 0px 150px 0px 0px !important;
    }
    
    #umm-form{
        z-index: 2;
    }
    
    #umm-list-table-form{
        z-index: 1;
    }
    
    .wp-list-table{
        width:100%;
     }
     
     .column-ID{
        width:100px;
        min-width: 100px !important;
     }
     
     .wrap h3{
       margin: 20px 0px 0px 0px !important; 
     }
     
     div.actions{
       margin: -10px 0px 0px 0px !important; 
     }
     
     div#umm-search{
       float: right;
     }
     
     label{
        font-weight: bold;
    }
    
    .updateusermeta-result{
        background-color: lightYellow;
        border: 1px solid #E6DB55;
        padding: 4px 4px 4px 4px;
        margin: 10px 0px 10px 0px !important;
    }
    
    -->
    </style>
    <div class="wrap">
      <div id="icon-users" class="icon32"><br/></div>
        <h2><?php echo $this->title; ?></h2>
        <?php _e('Manage User Meta Data', 'user-meta-manager') ?><br />
        <div class="umm-info hidden"><br />
        <input type="button" class="umm-close-info-icon button-secondary" title="<?php _e('Close', 'user-meta-manager') ?>" value="x" />
            <p><?php _e('What is <em>User Meta</em>? <em>User Meta</em> is user-specific data which is stored in the <em>wp_usermeta</em> database table. This data is stored by WordPress and various and sundry plugins, and can consist of anything from profile information to membership levels.', $this->slug) ?></p>
            <p><?php _e('This plugin gives you the tools to manage the data which is stored for each user. Not only can you manage existing meta data, but you can also create new custom meta data for each user or for all users.', $this->slug) ?></p>
            <p><?php _e('Follow the steps below to manage user meta data.', $this->slug) ?></p>
            <ol start="1">
       <li><?php _e('Always backup your data before making changes to your website.', $this->slug) ?></li>     
	<li><?php _e('Locate from the list which User you want to work with and place your mouse on that item. Action links will appear as your mouse moves over each user.', $this->slug) ?>
    <ol>
    <li><?php _e('<strong>Edit Meta Data:</strong> Edit existing meta data for each member.', $this->slug) ?></li>
    <li><?php _e('<strong>Add Meta Data:</strong> Add new, custom meta data for each user, or for <em>All Users</em>. If the meta data is added to <em>All Users</em>, new registrations will automatically receive the meta key and default value.', $this->slug) ?></li>
    <li><?php _e('<strong>Delete Meta Data:</strong> Delete individual meta keys for a single user or for <em>All Users</em>. You can select which meta data to delete from the drop menu.', $this->slug) ?></li>
    </ol>
    </li>
    <li><?php _e('<strong>Shorttags:</strong><br /><br /><strong>Display data for a particular user:</strong> <br /><code>[usermeta key="meta key" user="user id"]</code> <br /><br /><strong>Display data for the current user:</strong> <br /><code>[usermeta key="meta key"]</code> <br /><br /><strong>Restrict access based on meta key and value:</strong> <br /><code>[useraccess key="meta key" value="meta value" message="You do not have permission to view this content."]Restricted content.[/useraccess]</code> <br />Allowed users will have a matching meta value. <br /><br /><strong>Restrict access based on user ID:</strong> <br /><code>[useraccess users="1 22 301" message="You do not have permission to view this content."]Restricted content.[/useraccess]</code> <br />Allowed user IDs are listed in the <em>users</em> attribute.', $this->slug) ?></li>
    <li>Premium Tech Support - $25.00/hr <form action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_s-xclick"><input type="hidden" name="hosted_button_id" value="RZ8KMAZYEDURL"><input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_paynow_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!"><img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1"></form>
</ol>
<br /> 
        </div>
        <?php
        if(defined("UMM_STATUS")) echo UMM_STATUS;
        ?>
        <div id="umm-left-panel" class="alignleft">
        
        <form id="umm-form" method="get"> 
        <input class="umm-mode" type="hidden" name="umm_mode" value="<?php echo $_REQUEST['umm_mode'] ?>" />      
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <input type="hidden" name="paged" value="<?php echo $_REQUEST['paged'] ?>" />
            <input type="hidden" id="per-page-hidden" name="per_page" value="<?php echo $per_page ?>" />
            <div id="umm-search"><?php $this->search_box(__('Search'), 'get') ?></div>
        </form>
        <form id="umm-list-table-form" method="post">        
            <?php $this->display() ?>
        </form>

<code><?php _e('Another <em><strong>Quality</strong></em> Work From', $this->slug) ?>  <a href="http://JasonLau.biz" target="_blank">JasonLau.biz</a> - &copy;Jason Lau</code> <code>[<?php _e($this->title . ' Version', $this->slug) ?>: <?php echo UMM_VERSION; ?>]</code>

</div>

<script type="text/javascript">
    jQuery.cookie = function(name, value, options) {
    if (typeof value != 'undefined') { // name and value given, set cookie
        options = options || {};
        if (value === null) {
            value = '';
            options.expires = -1;
        }
        var expires = '';
        if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
            var date;
            if (typeof options.expires == 'number') {
                date = new Date();
                date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
            } else {
                date = options.expires;
            }
            expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
        }
        // CAUTION: Needed to parenthesize options.path and options.domain
        // in the following expressions, otherwise they evaluate to undefined
        // in the packed version for some reason...
        var path = options.path ? '; path=' + (options.path) : '';
        var domain = options.domain ? '; domain=' + (options.domain) : '';
        var secure = options.secure ? '; secure' : '';
        document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
    } else { // only name given, get cookie
        var cookieValue = null;
        if (document.cookie && document.cookie != '') {
            var cookies = document.cookie.split(';');
            for (var i = 0; i < cookies.length; i++) {
                var cookie = jQuery.trim(cookies[i]);
                // Does this cookie string begin with the name we want?
                if (cookie.substring(0, name.length + 1) == (name + '=')) {
                    cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                    break;
                }
            }
        }
        return cookieValue;
    }
    };

    jQuery(function($){
        try{
            $("#umm-list-table-form input[type='checkbox']").prop('checked');
            }catch(e){
                alert('<?php _e('Error: User Meta Manager is not compatible with old versions of jQuery. Please update jQuery to the latest version from jquery.com. jquery.js is located in wp-includes/js/jquery/', $this->slug) ?>');
        }
        
        $("div.actions").first().prepend('<strong><?php _e('Items Per Page', $this->slug) ?>:</strong> <input type="text" id="per-page" size="4" value="<?php echo $per_page ?>" /><input class="umm-go button-secondary action" type="submit" value="<?php _e('Go', $this->slug) ?>" />').append('<input class="umm-help button-secondary hidden" type="button" value="?" title="Info" />');
        
        $("#get-search-input").after(' <select class="um-search-mode" name="umm_search_mode"><option value="ID"<?php if(!$_REQUEST['umm_search_mode'] || $_REQUEST['umm_search_mode'] == 'ID'): ?> selected="selected"<?php endif; ?>>ID</option><option value="user_login"<?php if($_REQUEST['umm_search_mode'] == 'user_login'): ?> selected="selected"<?php endif; ?>>User Login</option><option value="user_nicename"<?php if($_REQUEST['umm_search_mode'] == 'user_nicename'): ?> selected="selected"<?php endif; ?>>Nice Name</option><option value="display_name"<?php if($_REQUEST['umm_search_mode'] == 'display_name'): ?> selected="selected"<?php endif; ?>>Display Name</option></select>');
        
       $('.umm-help').css('border-color','#FFFF00');
       $(".umm-mode").each(function(){
	   $(this).bind('mouseup',function(){
           try{
            $("#umm-list-table-form input[type='checkbox']").prop('checked',false);
           $("#umm-form select").val('');
           }catch(e){}    	   
	       $(".umm-mode").val($(this).attr('rel'));
           $("#umm-form").submit();
	   });
       });
       $(".umm-go").each(function(){
       $(this).bind('mouseup',function(){
	       $("#per-page-hidden").val($("#per-page").val());
           $("#umm-form").submit();
	   }); 
       });
       
       $(".umm-help").bind('mouseup',function(){
        $(".umm-info").show('slow');
	    $(this).hide('slow');
        $.cookie('umminfo',1);
	   });
       
       if($.cookie('umminfo') == 1){
        $(".umm-info").show();
        $(".umm-help").hide();
       } else {
        $(".umm-info").hide();
        $(".umm-help").show();
       }
       
       $(".umm-close-info-icon").css('text-decoration','none').click(function(){
       $(this).parent().hide('slow');
       $(".umm-help").show('slow');
       $.cookie('umminfo',0);                  
    });
    
    $(".umm-close-icon").css('text-decoration','none').click(function(){
       $(this).parent().hide('slow');                  
    });
    
    $("div.actions:last").css({
        'margin': '0px 0px 0px 0px !important'
    });
    
    $("#updateusermeta_submit").live('click', function(event){
        event.preventDefault();
        var obj = $(this),
        original_value = $(this).val(),
        return_page = $("#updateusermeta_form input[name='return_page']").val();
        obj.prop('disabled',true).val('Wait... ');
        $.post('admin-ajax.php?action=updateusermeta&width=600&height=500', $("#updateusermeta_form").serialize(), function(data){
            $("#TB_ajaxContent").load(return_page, function(){
                new Effect.Highlight("TB_ajaxContent", { startcolor: '#ffff99',
endcolor: '#ffffff' });
            $('.updateusermeta-result').html(data).show('slow').delay(5000).hide('slow');
            });
        });
    });
    
    }); // jQuery  
</script>        
</div>
<?php
}
        
} // class UMM_UI

function umm_ui(){
    if(!current_user_can('edit_users')):
    echo "You do not have the appropriate permission to view this content.";
    else:
    $_UMM_UI = new UMM_UI();
    $_UMM_UI->display_module();
    endif;
}

add_shortcode('usermeta', 'umm_usermeta_shorttag');
function umm_usermeta_shorttag($atts, $content) {
    global $current_user;
    $key = $atts['key'];
    $user = ($atts['user']) ? $atts['user'] : $current_user->ID;
    if($key):
    $content = get_user_meta($user, $key, true);
    return $content; 
    endif;         
}

add_shortcode('useraccess', 'umm_useraccess_shorttag');
function umm_useraccess_shorttag($atts, $content) {
    global $current_user;
    $access = true;
    $key = $atts['key'];
    $value = $atts['value'];
    $users = ($atts['users']) ? explode(" ", $atts['users']) : false;
    $message = $atts['message'];
    
    if($key && $value){
      $v = get_user_meta($current_user->ID, $key, true);
      if($v != trim($value)){        
          $access = false;
      }  
    }
    
    if($users){
        if(!in_array($current_user->ID, $users)){
           $access = false; 
        }
    }
    
    if(!$access){
        if($message){
            $content = $message;
        } else {
            $content = "You do not have sufficient permissions to access this content.";
        }
    }
    
    return $content;         
}

add_action('user_register', 'umm_default_keys');
function umm_default_keys(){
    global $wpdb;
    $data = $wpdb->get_results("SELECT * FROM $wpdb->usermeta ORDER BY user_id DESC LIMIT 1");
    $umm_data = get_option('user_meta_manager_data');
    if($umm_data){
        foreach($umm_data as $key => $value){
            update_user_meta($data[0]->user_id, $key, $value, false);
        }
    }
}

register_activation_hook(__FILE__, 'umm_install');
function umm_install(){
   add_option('user_meta_manager_data', '');
}

register_deactivation_hook(__FILE__, 'umm_deactivate');
function umm_deactivate(){
    // Preserve data
    // delete_option('user_meta_manager_data');
}

?>