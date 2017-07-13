<?php
/*
Plugin Name: Elvis Media Manager
Plugin URI: http://www.elvisdam.com
Description: Insert images, video and text from the Elvis DAM system directly into your wordpress text editor.
Version: 1.0.0
Author: woodwing.com
Author URI: http://www.woodwing.com
License: MIT License
*/

define('ELVIS_PLUGIN_URL', plugin_dir_url( __FILE__ ));

class ElvisInsertMediaButton {

    static $elvis_api_url = 'http://';
    static $elvis_api_login = NULL;
    static $elvis_api_password = NULL;
    
    static $instance = NULL;
    
    static function get_instance() {
        if (self::$instance === NULL)
            new ElvisInsertMediaButton();
        return self::$instance;
    }
    
    public function __construct() {
        if (self::$instance === NULL)
            self::$instance = $this;
    }
    
	public function init(){
        if ( current_user_can('edit_posts') && current_user_can('edit_pages') && get_user_option('rich_editing') == 'true')
        {
            add_filter('tiny_mce_version', array(self::get_instance(), 'tiny_mce_version') );
            add_filter("mce_external_plugins", array(self::get_instance(), "mce_external_plugins"));
            add_filter('mce_buttons', array(self::get_instance(), 'mce_buttons'));
        }
		
		// Set default values
        if (get_option('elvis_api_url') === FALSE)
            update_option('elvis_api_url', 'http://');
        if (get_option('elvis_api_login') === FALSE)
            update_option('elvis_api_login', 'guest');
        if (get_option('elvis_api_password') === FALSE)
            update_option('elvis_api_password', 'guest');
			
        self::$elvis_api_url = get_option('elvis_api_url');
        self::$elvis_api_login = get_option('elvis_api_login');
        self::$elvis_api_password = get_option('elvis_api_password');
        
        if (is_admin)
        {
            add_action('admin_init', array(self::get_instance(), 'admin_init'));
            add_action('admin_menu', array(self::get_instance(), 'plugin_menu'));
			
			/* Add a settings page to the plugin menu */
			add_filter('plugin_action_links', array(self::get_instance(), 'plugin_settings_link'), 10, 2 );
			
			wp_enqueue_script("jquery");
        }

		add_shortcode('elvis_video', array(self::get_instance(), 'elvis_video_shortcode'));
	}
    
	public function elvis_video_shortcode($atts, $content="") {
		return "<video src=\"{$atts[src]}\" style=\"{$atts[style]}\" controls=\"controls\"><a href=\"{$atts[src]}\">{$content}</a></video>";
	}

	public function mce_buttons($buttons) {
		array_push($buttons, "separator", "elvisInsertMedia");
		return $buttons;
	}
    
	public function mce_external_plugins($plugin_array) {
		$plugin_array['elvis'] = plugins_url('elvis.js', __FILE__);
		return $plugin_array;
	}
    
	public function tiny_mce_version($version) {
		return ++$version;
	}
    
    public function plugin_menu() {
        add_options_page('Elvis DAM settings', 'Elvis DAM', 'manage_options', 'elvis_dam_options', array(self::get_instance(), 'plugin_options'));
    }
	
	public function plugin_settings_link($links, $file){
		static $this_plugin;
		if (!$this_plugin) $this_plugin = plugin_basename(__FILE__);
	
		if ($file == $this_plugin){
			$settings_link = '<a href="options-general.php?page=elvis_dam_options">' . __('Settings') . '</a>';
			$links = array_merge( array($settings_link), $links);
		}
		return $links;
	}
    
    public function admin_init()
    {
        add_action('admin_head', array(self::get_instance(), 'plugin_options_script'));
    }
    
    public function plugin_options_script()
    {
        ?>
        <script type="text/javascript">
            var elvisOptions = {
                serverUrl: '<?php echo str_replace("'", "\'", get_option('elvis_api_url')); ?>',
                publicUsername: '<?php echo str_replace("'", "\'", get_option('elvis_api_login')); ?>',
                publicUserpass: '<?php echo str_replace("'", "\'", get_option('elvis_api_password')); ?>'
            }
        </script>
        <?php
    }
    
    public function plugin_options() {
        if (!current_user_can('manage_options'))  {
            wp_die( __('You do not have sufficient permissions to access this page.') );
        }
        
        $result = 0;
        if ($_POST['submit'])
        {
            foreach ($_POST as $key => $value)
            {
                if (strpos($key, 'param_') === 0)
                    $key = substr($key, 6);
                    
                update_option($key, $value);
            }
            //if ( ! isset($_POST['param_elvis_api_use_proxy']))
            //    update_option('elvis_api_use_proxy', FALSE);
            $result = 1;
        }
        
        ?>
        <div class="wrap">
            <div class="icon32" style="background: transparent url(<?php echo(plugin_dir_url( __FILE__ )) ?>img/elvis32.png) no-repeat;"><br/></div>
            <h2><?php _e('Elvis DAM settings'); ?></h2>
            <?php if ($result == 1) { ?>
            <div class="updated settings-error"><p><strong><?php _e('Settings saved.'); ?></strong></p></div>
            <?php } ?>
			<script language="javascript">
			function checkElvisServerUrl() {
				jQuery("#elvis_api_login_msg").html("");
				
				var serverUrl = jQuery("#elvis_api_url").val();
				if (serverUrl.length <= 7) {
					// just http://
					jQuery("#elvis_api_url_msg").html("Please enter Elvis serverUrl (can be found on the Elvis Client install page)");
				}
				else {
					jQuery("#elvis_api_url_msg").html("Validating...");
					
					// TODO .abort() any previous request...
					jQuery.ajax({
						url: serverUrl + "/services/login",
						data: {
							username: jQuery("#elvis_api_login").val(),
							password: jQuery("#elvis_api_password").val()
						},
						type: "POST",
						dataType: "json",
						success: function(data, textStatus, jqXHR) {
							if (data) {
								if (data.serverVersion) {
									jQuery("#elvis_api_url_msg").html("Elvis server version: " + data.serverVersion);
									
									if (data.loginSuccess) {
										jQuery("#elvis_api_login_msg").html("Authenticated successfully");
									} else if (data.loginFaultMessage) {
										jQuery("#elvis_api_login_msg").html(data.loginFaultMessage);
									} else {
										jQuery("#elvis_api_login_msg").html("Authentication attempt failed, cause: " + data.message);
									}
									return;
								} else if (data.loginSuccess != undefined) {
									jQuery("#elvis_api_url_msg").html("Unable to connect, unsupported server version");
									return;
								}
							}
							
							jQuery("#elvis_api_url_msg").html("Unable to connect");
						},
						error: function(jqXHR, textStatus, errorThrown) {
							jQuery("#elvis_api_url_msg").html("Unable to connect, cause: " + errorThrown);
							jQuery("#elvis_api_login_msg").html("");
						}
					});
				}
			}
			jQuery(function() {
				checkElvisServerUrl();
				jQuery("#elvis_api_url,#elvis_api_login,#elvis_api_password").focusout(checkElvisServerUrl);
			});
			</script>
            <div class="elvis-admin-form">
                <form method="post" action="">
					<h3>Instructions</h3>
					<ul>
						<li><a href="https://elvis.tenderapp.com/kb/third-party-integrations/wordpress-plugin" target="_blank">About this plugin</a> (including setup instructions)</li>
						<li><a href="http://www.elvisdam.com" target="_blank">About Elvis DAM</a></li>
					</ul>
					<h3>Settings</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="elvis_api_url"><?php _e('Elvis DAM server url:'); ?></label></th>
                            <td><input class="regular-text" type="text" id="elvis_api_url" value="<?php echo get_option('elvis_api_url'); ?>" name="param_elvis_api_url"/><span id="elvis_api_url_msg" style="margin-left:10px"></span></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="elvis_api_login"><?php _e('Public username:'); ?></label></th>
                            <td><input class="regular-text" type="text" id="elvis_api_login" value="<?php echo get_option('elvis_api_login'); ?>" name="param_elvis_api_login"/><span id="elvis_api_login_msg" style="margin-left:10px"></span></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="elvis_api_password"><?php _e('Public user password:'); ?></label></th>
                            <td><input class="regular-text" type="text" id="elvis_api_password" value="<?php echo get_option('elvis_api_password'); ?>" name="param_elvis_api_password"/></td>
                        </tr>
                    </table>
                    <p class="submit"><input class="button-primary" type="submit" name="submit" value="<?php _e('Save changes'); ?>" /></p>
                </form>
            </div>
        </div>
        <?php
    }
}
add_action('init', array('ElvisInsertMediaButton', 'init'));

?>
