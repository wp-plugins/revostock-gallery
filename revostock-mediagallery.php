<?php
/*
 * Plugin Name: Revostock Media Gallery Plugin
 * Plugin URI: http://www.revostock.com/wordpress
 * Description: Display clips from Revostock on your site
 * Text Domain: revostock_mediagallery
 * Version: 0.9.10
 * Author: Revostock
 * Author URI: http://www.revostock.com/
 * License: GPLv2
 * 
 *  Copyright 2011  Revostock
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License, version 2, as 
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

if ( !class_exists( 'Revostock' ) ) {
	class Revostock {
		function on_load() {
			add_action( 'init', array( __CLASS__, 'init' ) );
			register_activation_hook( __FILE__, array( __CLASS__, 'activate' ) );
			register_deactivation_hook( __FILE__, array( __CLASS__, 'deactivate' ) );
			register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
			
			add_action( 'admin_init', array( __CLASS__, 'add_settings_api_components' ) );
			add_action( 'admin_menu', array( __CLASS__, 'add_settings_pages' ) );
			
			add_action('template_redirect', array(__CLASS__, 'add_revostock_mediagallery_scripts_styles'));
			add_action('wp_ajax_revostock_mediagallery_fetch_items', array(__CLASS__, 'ajax_revostock_mediagallery_fetch_items'));
			add_action('wp_ajax_nopriv_revostock_mediagallery_fetch_items', array(__CLASS__, 'ajax_revostock_mediagallery_fetch_items'));
		}
		
		function init() {
			add_shortcode( 'revostock-gallery', array( __CLASS__, 'shortcode_revostock' ) );
			
			load_plugin_textdomain( 'revostock_mediagallery', false, basename(dirname( __FILE__ ) ).'/languages' );
			
			wp_register_style( 'revostock_mediagallery_stylesheet', plugins_url( 'styles/revostock-mediagallery-content.css', __FILE__ ) );  // TODO if we're enabling selectable stylesheets, this needs to be handled in a function
			wp_register_script( 'revostock_mediagallery_fetch_items_caller', plugins_url( 'scripts/revostock-mediagallery-content.js', __FILE__ ), array( 'jquery' ) );
			wp_register_script( 'imagetrail_script', plugins_url( 'scripts/imagetrail.js', __FILE__ ) );
		}
		
		function activate() {
			// Create settings record in options table
			$credentials = array(
				'username' => '',
				'password' => '',
			);
			$options = array(
				'_credentials' => $credentials,
				'_defaults' => self::get_original_defaults(),
			);
			add_option( 'revostock_mediagallery_settings', $options );
		}
		
		function deactivate() {
			delete_option( 'revostock_mediagallery_settings' ); // FIXME remove from deactivation
		}
		
		function uninstall() {
			delete_option( 'revostock_mediagallery_settings' );
		}
		
		function get_original_defaults(){
			return array(
				'scope' => 'all', // || Values: all, producer
				'asset_id' => '', // specific asset
				'mediabox_id' => '',  // specific mediabox
				'producer_id' => '', // specific producer
				'search' => '', // Holds search terms
				'content_type' => 'all', // Values: all, video, audio, aftereffects, motion
				'group' => '', // Values: newest, most_downloaded, editors_choice
				'limit_media' => '', // Values: 1-40 // uses rpp= API arg
				'columns' => '', // Values: 0-10 // 0 indicates dynamic, non-columned output
				'asset_display' => 'all', // Values: all, description, thumbnail, content_type, TODO asset_specifics
				'css_prefix' => '',
				'css_color_scheme' => 'grey',
			);
		}
		
		function get_shortcode_defaults(){
			$settings = get_option( 'revostock_mediagallery_settings' );
			return $settings['_defaults'];
		}
		
		function add_settings_api_components(){
			register_setting( 'revostock_mediagallery_settings', 'revostock_mediagallery_settings', array( __CLASS__, 'settings_validate' ) );
		}
		
		/*
		 * Create settings pages in wp-admin
		 */
		function add_settings_pages() {
			add_options_page( 'Revostock Plugin Options', 'Revostock', 'edit_posts', 'revostock', array( __CLASS__, 'options_page' ) );
			
			add_settings_section( 'revostock-mediagallery-usage', __('Using the Shortcode', 'revostock_mediagallery'), array( __CLASS__, 'settings_usage_section'), 'usage' );
			
			add_settings_section( 'revostock-mediagallery-defaults', __('Shortcode Defaults', 'revostock_mediagallery'), array( __CLASS__, 'settings_defaults_section'), 'defaults' );
			add_settings_field( 'revostock-mediagallery-defaults-scope', __('Producer', 'revostock_mediagallery'), array( __CLASS__, 'settings_field_scope' ), 'defaults', 'revostock-mediagallery-defaults' );
			add_settings_field( 'revostock-mediagallery-defaults-producer', __('Producer ID', 'revostock_mediagallery'), array( __CLASS__, 'settings_field_producer' ), 'defaults', 'revostock-mediagallery-defaults' );
			add_settings_field( 'revostock-mediagallery-defaults-columns', __('Columns', 'revostock_mediagallery'), array( __CLASS__, 'settings_field_columns' ), 'defaults', 'revostock-mediagallery-defaults' );
			add_settings_field( 'revostock-mediagallery-defaults-results-max', __('Limit media', 'revostock_mediagallery'), array( __CLASS__, 'settings_field_limit_media' ), 'defaults', 'revostock-mediagallery-defaults' );
			add_settings_field( 'revostock-mediagallery-defaults-css', __('Style sheet', 'revostock_mediagallery'), array( __CLASS__, 'settings_field_css' ), 'defaults', 'revostock-mediagallery-defaults' );
			add_settings_field( 'revostock-mediagallery-defaults-mediabox', __('Media Box', 'revostock_mediagallery'), array( __CLASS__, 'settings_field_mediabox' ), 'defaults', 'revostock-mediagallery-defaults' );
			add_settings_field( 'revostock-mediagallery-defaults-content-type', __('Return these media types', 'revostock_mediagallery'), array( __CLASS__, 'settings_field_content_type' ), 'defaults', 'revostock-mediagallery-defaults' );
			
			add_settings_section( 'revostock-mediagallery-account', __('Revostock Account Credentials', 'revostock_mediagallery'), array( __CLASS__, 'settings_account_section'), 'account' );
			add_settings_field( 'revostock-mediagallery-account-username', __('Account Username', 'revostock_mediagallery'), array( __CLASS__, 'settings_field_accountusername' ), 'account', 'revostock-mediagallery-account' );
			add_settings_field( 'revostock-mediagallery-account-password', __('Account Password', 'revostock_mediagallery'), array( __CLASS__, 'settings_field_accountpassword' ), 'account', 'revostock-mediagallery-account' );
		}
		
		function options_page() {
			if ( !current_user_can( 'edit_posts' ) ){
				wp_die( __('You do not sufficient priviledges to access this page.', 'revostock_mediagallery') );
			}
			
			?>
			<div class="wrap">
				<?php 
				if ( current_user_can( 'manage_options' ) ) {
					// Redirect to Account tab if there are no saved credentials
					$settings = get_option( 'revostock_mediagallery_settings' );
					if ( empty( $settings['_credentials']['username'] ) || empty( $settings['_credentials']['password'] ) ){
						add_settings_error( 'revostock_mediagallery_settings', 'need-info', __('You must have an account with Revostock to use this plugin.  Please enter your credentials.', 'revostock_mediagallery') ); 
						$tab = 'account';
					} else {
						// Render different sections depending on which tab
						$tab = ( isset( $_GET['revostock_mediagallery_tab'] ) ) ? $_GET['revostock_mediagallery_tab'] : 'usage';
					}
					self::admin_options_page_tabs( $tab );
					settings_errors( 'revostock_mediagallery_settings' );
					// Help text at top of page
					switch ( $tab ){
						case 'account':
							?>
							<div class ="wrap" style="max-width:800px;">
								<p><?php printf( __('Welcome to the RevoStock WordPress Plug-In! This Plug-In will allow you to show RevoStock Stock Media on your WordPress page! To get started you will need 2 items. First, you will need to be a member of RevoStock. If you don\'t have an account yet, visit: %s to create a free account.', 'revostock_mediagallery'), '<a target="_blank" href="http://www.revostock.com/RegMember.html">http://www.revostock.com/RegMember.html</a>' ); ?></p>
								<p><?php printf( __('You will also need a RevoStock API Authorization. To get this, after logging in, visit: %s', 'revostock_mediagallery'), '<a target="_blank" href="http://www.revostock.com/api.html">www.revostock.com/api.html</a>' ); ?></p>
								<p><?php _e('Currently, API authorization is only available to RevoStock Producers (users who sell content through RevoStock) but will be open to all members soon.', 'revostock_mediagallery'); ?></p>
								<p><?php printf( __('To get the most out of using our plug-in, make sure you sign up to be a RevoStock affiliate! %s', 'revostock_mediagallery'), '<a target="_blank" href="http://www.revostock.com/Affiliate.html">http://www.revostock.com/Affiliate.html</a>'); ?> </p>
							</div>
							<?php 
					}
					?>
					<form action="options.php" method="post">
						<?php 
						settings_fields( 'revostock_mediagallery_settings' );
						do_settings_sections( $tab );
						if ( $tab != 'usage' ){ 
							?>
							<input name="Submit" type="submit" class="button-primary" value="<?php _e('Save Changes', 'revostock_mediagallery'); ?>" />
							<?php 
						}
						if ( $tab == 'defaults' ) {
							?>
							<input name="revostock_mediagallery_settings[reset-defaults]" type="submit" class="button-secondary" value="<?php _e('Reset Defaults', 'revostock_mediagallery'); ?>" />
							<?php 
						} elseif ( $tab == 'account' ) {
							?>
							<input name="revostock_mediagallery_settings[reset-account]" type="submit" class="button-secondary" value="<?php _e('Clear info', 'revostock_mediagallery'); ?>" />
							<?php 
						}
						?>
					</form>
				<?php 
				} else {
					do_settings_sections( 'usage' );
				}
				?>
			</div>
			
			
			<?php 
		}
		
		/*
		 * Helper for options_page rendering tabs at top
		 */
		function admin_options_page_tabs( $current = '' ){
			if ( empty( $current ) ){
				if ( isset( $_GET['revostock_mediagallery_tab'] ) )
					$current = $_GET['revostock_mediagallery_tab'];
				else
					$current = 'usage';
			}
			$tabs = array( 
				'usage' => 'Usage',
				'defaults' => 'Defaults',
				'account' => 'Account',
			);
			$links = array();
			foreach ( $tabs as $tab => $name ){
				$current_class = ( $tab == $current ) ? 'nav-tab-active' : '';
				$links[] = '<a class="nav-tab '.$current_class.'" href="?page=revostock&revostock_mediagallery_tab='.$tab.'">'.$name.'</a>';
			}
			?>
			<div id="icon-settings" class="icon32"><br /></div>
			<h2 class="nav-tab-wrapper">
			<?php 
			foreach ( $links as $link )
				echo $link;
			?> </h2> <?php
		}
		
		/*
		 * Preamble to settings_section before settings_fields output
		 */
		function settings_usage_section(){
			?>
			<p><?php _e('A gallery of media items may be inserted into a page or post by using the shortcode', 'revostock_mediagallery');?> [revostock-gallery].<br/>
				<?php _e('The parameters below may be added to specify different settings than are saved on the Defaults tab (shown above).', 'revostock_mediagallery'); ?></p>
			<p>
				<?php printf(__('For example, say you have "Video" selected for "Return these media types" on the Defaults tab and you use the shortcode %1$s.  '
							.'This would display a gallery containing video results of a search for "%2$s" and "&3$s".  If you wanted to return all types, regardless of your default setting, '
							.'you could specify that: %4$s.', 'revostock_mediagallery'), '[revostock-gallery search=forest,moon]', 'forest', 'moon', '[revostock-gallery search=forest,moon content_type=all]' ); ?>
			</p>
			<p>
				<?php printf(__('Note that "%1$s", "%2$s", and "%3$s" all require the numeric ID of that item on Revostock.', 'revostock_mediagallery'), 'asset_id', 'mediabox_id', 'producer_id');?>
			</p>
			<h3><?php _e('Shorcode parameters' , 'revostock_mediagallery'); ?></h3>
			<?php 
			include( 'settings-page-usage.html' );
		}
		
		/*
		 * Preamble to settings_section before settings_fields output
		 */
		function settings_defaults_section(){
			if ( !current_user_can( 'manage_options' ) ) {
				wp_die( __('You are not authorized to change settings for this plugin.', 'revostock_mediagallery') );
			}
			?>
			<p><?php _e('You’ll use a shortcode in your page or post to retrieve asset thumbnails — the small images that represent your audio, video, and after effects ﬁles. You can change any of these values for a particular shortcode expression. If you don’t provide a value in the shortcode expression we’ll use these values as a default.', 'revostock_mediagallery'); ?></p>
			<?php 
		}
		
		/*
		 * Output revostock-mediagallery-defaults-scope settings_field
		 */
		function settings_field_scope(){
			$defaults = self::get_shortcode_defaults();
			$checked = ( $defaults['scope'] == "producer" ) ? 'checked="checked" ' : '';
			?>
			<input id="revostock-mediagallery-defaults-scope" name="revostock_mediagallery_settings[_defaults][scope]" type="checkbox" <?php echo $checked; ?>/>
			<span class="description"><?php _e('Show only my Revostock media assets', 'revostock_mediagallery')?></span>
			<?php 
		}
		
		/*
		 * Output revostock-mediagallery-defaults-producer settings_field
		 */
		function settings_field_producer(){
			$defaults = self::get_shortcode_defaults();
			?>
			<input id="revostock-mediagallery-defaults-producer" name="revostock_mediagallery_settings[_defaults][producer_id]" size="10" type="text" value="<?php echo $defaults['producer_id'];?>" /><br />
			<span class="description"><?php _e('The ID of the producer to show if the previous setting is active', 'revostock_mediagallery')?></span>
			<?php 
		}
		
		/*
		 * Output revostock-mediagallery-defaults-columns settings_field
		 */
		function settings_field_columns(){
			$defaults = self::get_shortcode_defaults();
			?>
			<input id="revostock-mediagallery-defaults-columns" name="revostock_mediagallery_settings[_defaults][columns]" size="5" type="text" value="<?php echo $defaults['columns']; ?>" /><br />
			<span class="description">The maximum number of items to display on one row</span>
			<?php 
		}
		
		/*
		 * Output revostock-mediagallery-defaults-results-max stetings_field
		 */
		function settings_field_limit_media(){
			$defaults = self::get_shortcode_defaults();
			?>
			<input id="revostock-mediagallery-defaults-results-max" name="revostock_mediagallery_settings[_defaults][limit_media]" size="5" type="text" value="<?php echo $defaults['limit_media']; ?>" /><br />
			<span class="description">Limit the number of results returned</span>
			<?php 
		}
		
		/*
		 * Output revostock-mediagallery-defaults-css settings_field
		 */
		function settings_field_css(){
			$defaults = self::get_shortcode_defaults();
			$styles = array( 	"blackandwhite" => "Black and white",
								"grey" => "Grey",
								"red" => "Red",
								"blue" => "Blue",
			);
			foreach ( $styles as $key => $choice ){
				$checked = ( $key == $defaults['css_color_scheme'] ) ? 'checked="checked" ' : '';
				?>
				<input id="revostock-mediagallery-defaults-stylesheet-<?php echo $key; ?>" name="revostock_mediagallery_settings[_defaults][css_color_scheme]" type="radio" value="<?php echo $key; ?>" <?php echo $checked; ?>/><?php _e($choice, 'revostock_mediagallery');?><br />
				<?php 
			}
			?>
			&nbsp;<br /><label>CSS prefix  <input id="revostock-mediagallery-defaults-css" name="revostock_mediagallery_settings[_defaults][css_prefix]" type="text" size="25" value="<?php echo $defaults['css_prefix']; ?>" /></label><br />
			<span class="description"><?php _e('Space-seperated class names to add to the gallery container', 'revostock_mediagallery'); ?></span>
			<?php 
		}
		
		/*
		 * Output revostock-mediagallery-defaults-mediabox settings_field
		 */
		function settings_field_mediabox(){
			$defaults = self::get_shortcode_defaults();
			?>
			<input id="revostock-mediagallery-defaults-mediabox" name="revostock_mediagallery_settings[_defaults][mediabox_id]" size="10" type="text" value="<?php echo $defaults['mediabox_id']; ?>" /><br />
			<span class="description"><?php _e('The ID of the mediabox to display', 'revostock_mediagallery'); ?></span>
			<?php 
		}
		
		/*
		 * Output revostock-mediagallery-defaults-content-type settings_field
		 */
		function settings_field_content_type(){
			$defaults = self::get_shortcode_defaults();
			$types = array( 
				"all" => "All",
				"video" => "Video",
				"audio" => "Audio",
				"aftereffects" => "AfterEffects templates",
				"motion" => "Apple Motion templates",
			);
			foreach ( $types as $key => $choice ){
				$checked = ( $key == $defaults['content_type'] ) ? 'checked="checked" ' : '';
				?>
				<input id="revostock-mediagallery-defaults-content-type" name="revostock_mediagallery_settings[_defaults][content_type]" type="radio" value="<?php echo $key; ?>" <?php echo $checked; ?>/><?php _e($choice, 'revostock_mediagallery');?><br />
				<?php 
			}
		}
		
		/*
		 * Preamble to settings_section before settings_fields output
		 */
		function settings_account_section(){
			if ( !current_user_can( 'manage_options' ) ){
				wp_die( __('You are not authorized to change settings for this plugin.', 'revostock_mediagallery') );
			}
			?>
			<p><?php _e('Enter your Revostock account information here.', 'revostock_mediagallery') ?></p>
			<?php 
		}
		
		/*
		 * Output revostock-mediagallery-account-username settings_field
		 */
		function settings_field_accountusername(){
			$opts = get_option( 'revostock_mediagallery_settings' );
			?>
			<input id="revostock-mediagallery-account-username" name="revostock_mediagallery_settings[_credentials][username]" size="40" type="text" value="<?php echo $opts['_credentials']['username']; ?>" /><br />
			<span class="description">Your Revostock username</span>
			<?php 
		}
		
		function settings_field_accountpassword(){
			$opts = get_option( 'revostock_mediagallery_settings' );
			?>
			<input id="revostock-mediagallery-account-password" name="revostock_mediagallery_settings[_credentials][password]" size="40" type="password" value="<?php echo $opts['_credentials']['password']; ?>" /><br />
			<span class="description">Your Revostock password</span>
			<?php 
		}
		
		function settings_validate( $input ){
			if ( !current_user_can( 'manage_options' ) ){
				wp_die( __('You do not have sufficient priviledges to do that.', 'revostock_mediagallery') );
			}
			
			$settings = get_option( 'revostock_mediagallery_settings' );
			$input = self::trim_r( $input );
			
			//
			// Credentials
			//
			
			// Are we clearing the account details?
			if ( isset( $input['reset-account'] ) ){
				$settings['_credentials']['username'] = '';
				$settings['_credentials']['password'] = '';
			} elseif ( isset( $input['_credentials'] ) ) {
				// Check submitted credentials against Revostock API response and save
				if ( ( $input['_credentials']['username'] != null ) && ( $input['_credentials']['password'] != null ) ){
					if ( self::api_request( 'validate', array(
							'username' => $input['_credentials']['username'],
							'password' => $input['_credentials']['password'],
						)) )
						$settings['_credentials'] = $input['_credentials'];
				} else {
					add_settings_error( 'revostock_mediagallery_settings', 'user-error', __('You must enter both a username and a password', 'revostock_mediagallery') );
				}
			}
			
			//
			// Defaults
			//
			
			// Are we resetting the defaults?
			if ( isset( $input['reset-defaults'] ) ){
				$settings['_defaults'] = self::get_original_defaults();
			} elseif ( isset( $input['_defaults'] ) )  {
				$readable = array(
					'scope' => 'Producer',
					'producer_id' => 'Producer ID',
					'columns' => 'Columns',
					'limit_media' => 'Limit media',
					'css_color_scheme' => 'Style sheet',
					'mediabox_id' => 'Media Box',
					'content_type' => 'Return these media types',
				);
				
				// Checkboxes don't fit our validation function, so we pre-sanitize before we call our function
				// More checkboxes and we should handle this with an array and a foreach
				//
				// On Defaults settings tab, "Producer" checkbox sets "scope"
				if ( isset( $input['_defaults']['scope'] ) ){
					if ( $input['_defaults']['scope'] == "on" ){
						$input['_defaults']['scope'] = 'producer';
					}
				} else {
					$settings['_defaults']['scope'] = 'all';
				}
				
				// Sanitize
				$valid = self::sanitize_validate_input( $input['_defaults'] );
				
				// Walk through $POST data and compare to sanitized/validated version
				foreach ( $input['_defaults'] as $key => $value ){
					if ( !$value ){
						$settings['_defaults'][$key] = '';
					} else {
						if ( $value == $valid[$key] ){
							$settings['_defaults'][$key] = $valid[$key];
						} else {
							add_settings_error( 'revostock_mediagallery_settings', 'input-error', __('You need to enter a valid value for "'.$readable[$key].'".', 'revostock_mediagallery') );
						}
					}
				}
			}
			
			return $settings;
		}
		
		/*
		 * Sanitize/Validate function used in saving defaults and parsing shortcode atts
		 */
		function sanitize_validate_input( $input ){
			// In case not trimmed
			$input = self::trim_r($input);
			
			$sane = array(
				'scope' => FILTER_SANITIZE_STRING, // Values: all, producer
				'asset_id' => FILTER_SANITZE_STRING, // Valid: int?
				'mediabox_id' => FILTER_SANITIZE_STRING,  // Valid: int?
				'producer_id' => FILTER_SANITIZE_STRING, // Valid: int?
				'search' => FILTER_SANITIZE_STRING, // Valid: strings
				'content_type' => FILTER_SANITIZE_STRING, // Values: all, video, audio, aftereffects
				'group' => FILTER_SANITIZE_STRING, // Values: newest, most_downloaded, editors_choice
				'asset_display' => FILTER_SANITIZE_STRING, // Values: all, description, thumbnail, content_type, TODO asset_specifics
				'css_prefix' => FILTER_SANITIZE_STRING, // Valid type: string
				'css_color_scheme' => FILTER_SANITIZE_STRING, // Values: grey, blackandwhite, red, blue
			
				// These two are not only sanitized, but validated here for efficiency's sake
				'limit_media' => array( "filter" => FILTER_VALIDATE_INT, "options" => array( "min_range" => 0, "max_range" => 40 ) ), // FILTER_SANITIZE_NUMBER_INT, // Values: 0-40
				'columns' => array( "filter" => FILTER_VALIDATE_INT, "options" => array( "min_range" => 0, "max_range" => 10 ) ), // FILTER_SANITIZE_NUMBER_INT, // Values: 0-10
			);
			
			$sanitized = filter_var_array( $input, $sane );
			
			// Validate is complicated
			foreach ( $sanitized as $key => $value ){
				if ( !$value ){
					$validated[$key] = $value;  // preserves empty string, false, and null
				} else {
					switch ( $key ){
						case 'scope':
							$allowed = array( 'all', 'producer' );
							$validated['scope'] = ( ( $index = array_search( $value, $allowed ) ) !== false ) ? $allowed[$index] : false;
							break;
						case 'asset_id':
							// TODO Is there a valid set?
							$validated['asset_id'] = $value;
							break;
						case 'mediabox_id':
							// TODO Is there a valid set?
							$validated['mediabox_id'] = $value;
							break;
						case 'producer_id':
							// TODO Is there a valid set?
							$validated['producer_id'] = $value;
							break;
						case 'search':
							$validated['search'] = $value;
							break;
						case 'content_type':
							$allowed = array( 'all', 'video', 'audio', 'aftereffects', 'ae', 'motion' );
							$validated['content_type'] = ( ( $index = array_search( $value, $allowed ) ) !== false ) ? $allowed[$index] : false;
							if ( $validated['content_type'] == "aftereffects" ) $validated['content_type'] = "ae";
							break;
						case 'group':
							$allowed = array( 'newest', 'most_downloaded', 'editors_choice' );
							$validated['group'] = ( ( $index = array_search( $value, $allowed ) ) !== false ) ? $allowed[$index] : false;
							break;
						case 'asset_display':
							$allowed = array( 'all', 'description', 'thumbnail', 'content_type', 'asset_specifics' );
							$validated['asset_display'] = ( ( $index = array_search( $value, $allowed ) ) !== false ) ? $allowed[$index] : false;
							break;
						case 'css_prefix':
							// validate further?
							$validated['css_prefix'] = $value;
							break;
						case 'css_color_scheme':
							$allowed = array( 'blackandwhite', 'grey', 'red', 'blue' );
							$validated['css_color_scheme'] = ( ( $index = array_search( $value, $allowed ) ) !== false ) ? $allowed[$index] : false;
							break;
						case 'limit_media':
							// already validated in our call to filter_var_array
							$validated['limit_media'] = $value;
							break;
						case 'columns':
							// already validated in our call to filter_var_array
							$validated['columns'] = $value;
							break;
					}
				}
			}
			
			return $validated;
		}
		
		/*
		 * Trim an array recursively
		 * (Used for sanitation of input)
		 */
		function trim_r($array){
			if (!is_array($array))
        		return trim($array);
 
    		return array_map( array(__CLASS__, 'trim_r'), $array);
		}
		
		/*
		 * Add js and css files to page headers (template_redirect hook)
		 */
		function add_revostock_mediagallery_scripts_styles(){
			wp_enqueue_style( 'revostock_mediagallery_stylesheet' );
			$settings = self::get_shortcode_defaults();
			wp_enqueue_style( 'revostock_mediagallery_stylesheet-colors', plugins_url( 'styles', __FILE__ ).'/revostock-mediagallery-content-'.$settings['css_color_scheme'].'.css', 'revostock_mediagallery_stylesheet' );
			wp_enqueue_script( 'revostock_mediagallery_fetch_items_caller' );
			wp_localize_script( 'revostock_mediagallery_fetch_items_caller', 'revostockVars', array( 'the_url' => admin_url( 'admin-ajax.php' ) ) );
			wp_enqueue_script( 'imagetrail_script' );
		}
		
		/*
		 * Shortcode callback WP executes when rendering content
		 */
		function shortcode_revostock( $atts, $content = null ){
			$defaults = self::get_shortcode_defaults();
			$settings = shortcode_atts( $defaults, $atts );
			$data = json_encode( $atts );
			$display = '<div class="revostock-mediagallery-container '.$settings['css_prefix'].'">'
							.'Searching for Revostock media assets ...';
			$display .= '<script type="text/javascript">/* <![CDATA[ */var revostock_mediagallery_request = '.$data.';/* ]]> */</script>'; 
			$display .= '</div>';
			
			return $display;
		}
		
		/*
		 * Ajax handler to fetch items and send to browser (wp_ajax_ callback)
		 */
		function ajax_revostock_mediagallery_fetch_items(){
			// Get our shortcode attributes passed by the AJAX call
			global $_POST;
			$atts = $_POST['revostock_mediagallery_request'];
			// Sanitize and remove empty or invalid array elements
			if ( empty( $atts ) )
				$atts = array();
			$atts = self::sanitize_validate_input($atts);
			foreach ( $atts as $key => $value ){
				if ( !$value )
					unset( $atts[$key] );
			}
			
			
			// Now instantiate all our vars by merging attributes passed and defaults from db
			$defaults = self::get_shortcode_defaults();
			$short = shortcode_atts( $defaults, $atts ) ;
			extract( $short );

			// Construct the API request
			if ( $asset_id ){
				$request = 'content/'.$asset_id;
			} elseif ( $search ) {
				$request = 'search';
				switch ( $content_type ){
					case 'video':
						$request .= '-video/';
						break;
					case 'audio':
						$request .= '-audio/';
						break;
					case 'ae':
						$request .= '-ae/';
						break;
					case 'motion':
						$request .= '-motion/';
						break;
					case 'all':
						$request .= '/';
						break;
				}
				$terms = str_replace( ',', '/', $search );
				$request .= $terms;
			} elseif ( $mediabox_id ) {
				$request = 'mediabox-content/'.$mediabox_id;
			} else {
				$request = 'new';
			}
			
			// Add arguments to API request
			$args = array();
			if ( $scope == 'producer' && $producer_id )
				$args[] .= 'producer_id='.$producer_id;
			if ( !$search && $content_type && $content_type != 'all' )
				$args[] = 'type='.$content_type;
			if ( $group ) {
				$order = 'order=';
				switch ( $group ){
					case 'newest':
						$order .= 'date';
						break;
					case 'most_downloaded':
						$order .= 'downloads';
						break;
					case 'editors_choice':
						$order .= 'editors_choice';
						break;
				}
				$args[] = $order;
			}
			if ( $limit_media )
				$args[] = 'rpp='.$limit_media;
			
			if ( $args ) {
				$request .= '?';
				for ( $i=0; $i<count($args); $i++ ){
					if ( $i > 0 )
						$request .= '&';
					$request .= $args[$i];
				}
			}
			
			// 		API call
			// Pass the request to the API caller for a result
			$results = self::api_request( 'fetch_items', array( 'request' => $request ) );
			
			// Here's our return to the browser, empty if we don't have an array
			$output = '';
			if ( is_array( $results ) ) {
				// Eliminate "rows" by setting the for loop & array_slicing to run once with the whole set if we don't have limits
				if ( empty( $limit_media ) ){
					$limit_media = count( $results );
				}
				if ( empty( $columns ) ){
					$columns = count( $results );
				}
				
				// Readable filetype array
				$type = array(
					'video' => 'Stock Video Footage',
					'music' => 'Stock Music',
					'sound' => 'Sound Effects',
					'ae' => 'After Effects Template',
					'motion' => 'Apple Motion Template',
				);
				
				// Loop based on max_results and columns
				for ( $i=0; $i<$limit_media; $i+=$columns ){
					$row = array_slice( $results, $i, $columns );
					$output .= '<div class="revostock-mediagallery-items-row">';
					foreach ( $row as $item ) {
						// Clear $asset_spec for concat instances
						$asset_spec = '';
						// Build by piece based on asset_display=all or list: description,thumbnail,content_type,asset_specifics !! NOT IMPLEMENTED, all displayed
						// asset_specifics
						switch ( $item['FileType'] ){
							case 'video':
								$asset_spec = $item['Format']['title'];
								break;
							case 'ae':
								$asset_spec = $item['Format']['title'];
								break;
							case 'audio':
								if ( $item['ExactLength'] > 60 )
									$asset_spec = floor( $item['ExactLength'] / 60 ).' min';
								$asset_spec .= ( $item['ExactLength'] % 60 != 0 ) ? ' '.( $item['ExactLength'] % 60 ).' sec' : '';
								break;
							case 'motion':
								$asset_spec = $item['Format']['title'];
								break;
							default:
								$asset_spec = '';
						}
						// FileType parsing for readable label and icon filename
						//		Note: icon filename matches API item key "FileType" value except where set in the switch tree below
						$icon = $item['FileType'];
						switch ( $item['FileType'] ){
							case 'video':
								$type = 'Stock Video Footage';
								break;
							case 'audio':
								switch ( $item['PrimaryCategory']['ID'] ){
									case '78':
										$type = 'Stock Music';
										break;
									case '79':
										$type = 'Sound Effects';
										$icon = 'soundeffects';
										break;
									default:
										$type = 'Stock Audio';
								}
								break;
							case 'ae':
								$type = 'After Effects Template';
								break;
							case 'motion':
								$type = 'Apple Motion Template';
								$icon = 'applemotion';
								break;
							default:
								$type = '';
						}
						
						// Item link
						$itemlink = '<a target="_blank" href="'.$item['ProductURL'].'">';
						
						// Producer link
						$producerlink = '<a target="_blank" href="http://www.revostock.com/ViewProfile.html?&ID='.$item['Producer']['ID'].'">';
						
						// Shorten the title and producer names and add a ... 
						$itemshortname = substr( $item['Title'], 0,23);
						if ( strlen($item['Title'] ) > 23 )
							$itemshortname .='...';
							
						$producershortname =  substr ($item['Producer']['username'], 0, 8);
						if ( strlen ($item['Producer']['username']) > 8)
							$producershortname .= '...';
						
						// item container
						$output .= '<div id="'.$item['ID'].'" class="revostock-mediagallery-item">';
							$output .= '<div class="revostock-mediagallery-item-thumbnail">'.$itemlink.'<img src="'.$item['ThumbURL'].'" onmouseover="showhover(\'http://www.revostock.com/popupplugin.php?ID='.$item['ID'].'\')" onmouseout="hidetrail()" /></a></div>';
							$output .= '<div class="revostock-mediagallery-item-description revostock-mediagallery-clear">';
								$output .= '<div class="revostock-mediagallery-item-title"><div>'.$itemlink.$itemshortname.'</a></div></div>';
								$output .= '<div class="revostock-mediagallery-item-producer"><div>'.$producerlink.'By&nbsp;'.$producershortname.'</a></div></div>';
								$output .= '<div class="revostock-mediagallery-item-asset-specifics">'.$asset_spec.'</div>';
							$output .= '</div>';
							$output .= '<div class="revostock-mediagallery-item-type revostock-mediagallery-clear">';
								$output .= '<img src="'.plugins_url('images/', __FILE__ ).$icon.'.png" class="revostock-mediagallery-item-media-icon" />';
								// Removed by Craig CAS
								// $output .= '<div class="revostock-mediagallery-item-type-label">'.$type.'</div>';
							$output .= '</div>';
						$output .= '</div>';
					}
					$output .= '<div class="revostock-mediagallery-clear"></div></div>';
				}
			} else {
				$output = __('The Revostock plugin could not return a list of media assets. Please refresh and try again', 'revostock_mediagallery');
			}
			
			echo $output;
			die();
		}
		
		/*
		 * API functions
		 */
		function api_request( $action, $args = null ){
			$base_url = 'https://revostock.com/rest';
			$settings = get_option( 'revostock_mediagallery_settings' );
			$credentials = base64_encode( $settings['_credentials']['username'].':'.$settings['_credentials']['password'] );
			$remote_request_args = array( 'headers' => array( 'Authorization'=>'Basic '.$credentials ) ); 
			
			switch ( $action ) {
				case 'validate':
					if ( isset( $args['username'] ) && isset( $args['password'] ) ){
						$remote_request_args = array( 'headers' => array( 'Authorization'=>'Basic '.base64_encode( $args['username'].':'.$args['password'] ) ) );
						$response = wp_remote_get( $base_url.'/new/video?rpp=1', $remote_request_args );
						if ( !is_wp_error( $response ) ){
							switch ( $response['response']['code'] ) {
								case '401':
									add_settings_error( 'revostock_mediagallery_settings', 'user-error', __('Your username and password combination were not recognized by Revostock', 'revostock_mediagallery') );
									return false;
								case '200':
									return true;
							}
							
						} else {
							add_settings_error( 'revostock_mediagallery_settings', 'local-error', __('There was a problem reaching Revostock.<br />Please try again later or contact your site administrator.' , 'revostock_mediagallery') );
							return false;
						}
					} else {
						add_settings_error( 'revostock_mediagallery_settings', 'local-error', __('Something went wrong', 'revostock_mediagallery') );
						return false;
					}
					break;
				case 'fetch_items':
					$response = wp_remote_get( $base_url.'/'.$args['request'], $remote_request_args );
					if ( !is_wp_error( $response ) ) {
						switch ( $response['response']['code'] ) {
							case '200':
								$p = xml_parser_create();
								xml_parser_set_option( $p, XML_OPTION_CASE_FOLDING, 0 );
								xml_parser_set_option( $p, XML_OPTION_SKIP_WHITE, 1 );
								if ( xml_parse_into_struct( $p, $response['body'], $values ) ){
									xml_parser_free( $p );
									return self::parse_feed( $values );
									break;
								}
								xml_parser_free( $p );
								return false;
								break;
							default:
								return false;
						}
					}
					return false;
					break;
			}
		}
		
		function parse_feed( $data, &$index = -1, &$parsed = array() ){
			// First run, index = 0, every recursion it gets incremented
			$index++;
			
			// Top level tag returned by Revostock API
			if ( $data[$index]['tag'] == "Revostock_Rest_API" ) {
				// If it's not an "open" tag, the result was empty or we hit the end
				if ( $data[$index]['type'] != "open" )
					return $parsed;
				else // Skipping top level and moving on
					//$parsed = self::parse_feed( $data, $index ); // first call, top of recursion
					$index++;  // Just step over it
				// Now we're at first child, ensure we have what we want
				if ( $data[$index]['tag'] != "item" )
					return $parsed;  // empty array
			}
			
			// FIXME too much recursion, put a loop in
			/*while ( $data[$index]['tag'] == "completed") {
				;
			}*/
			
			// We're expecting <item>s to be in the feed, building a numeric keyed array of them
			if ( $data[$index]['tag'] == "item" ){
				if ( $data[$index]['type'] == "open" ) {
					$parsed[] = self::parse_feed( $data, $index );
					self::parse_feed( $data, $index, $parsed );
				}
			}
			
			// We've gotten here because we have children of <item>s
			if ( $data[$index]['type'] == "open" ){
				$parsed[ ( $data[$index]['tag'] ) ] = self::parse_feed( $data, $index );
				self::parse_feed( $data, $index, $parsed );
			} elseif ( $data[$index]['type'] == "complete" ) {
				$parsed[ ( $data[$index]['tag'] ) ] = $data[$index]['value'];
				self::parse_feed( $data, $index, $parsed );
				
			}
			
			// Base case: return on closing tag
			if ( $data[$index]['type'] == "close" ) {
				return $parsed;
			}
				
			//$index++;
			self::parse_feed( $data, $index, $parsed );
		}
		
	} // end class Revostock
	
	Revostock::on_load();
	
} // end !class_exists
