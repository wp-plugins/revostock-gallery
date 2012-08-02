<?php
/*
 * Plugin Name: RevoStock Media Gallery
 * Plugin URI: http://www.revostock.com/wordpress
 * Description: Display a gallery of RevoStock.com media files available for purchase
 * Version:  1.1.1
 * Author: RevoStock
 * Author URI: http://www.revostock.com/
 * Text Domain: revostock-gallery
 * License: GPLv2
 *
 *  Copyright 2012 RevoStock ()
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


//++++++ PLUGIN FILE LOCATION AND PATH ++++++++++++++++++++
$revostock_gallery_file = __FILE__;

/*if (isset($plugin)) {
	$revostock_gallery_file = $plugin;
}
else if (isset($mu_plugin)) {
	$revostock_gallery_file = $mu_plugin;
}
else if (isset($network_plugin)) {
	$revostock_gallery_file = $network_plugin;
}*/

define( 'REVOSTOCK_GALLERY_FILE', $revostock_gallery_file );
define( 'REVOSTOCK_GALLERY_PATH', WP_PLUGIN_DIR.'/'.basename( dirname( $revostock_gallery_file ) ) );
//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

//+++++++++ CONSTANTS +++++++++++++++++++++++
define( 'REVOSTOCK_GALLERY_MIN_PHP', '5.2' );
define( 'REVOSTOCK_GALLERY_MIN_WP', '3.2' );
define( 'REVOSTOCK_GALLERY_VER', '1.1.1' );
//+++++++++++++++++++++++++++++++++++++++++++

if ( ! class_exists( 'Revostock_Gallery' ) ) {

	class Revostock_Gallery {

		/*********************************************
		 * INITIAL SETUP
		 *********************************************
		 */

		/**
		 * @uses add_action()
		 * @uses register_activation_hook()
		 * @uses register_deactivation_hook()
		 * @uses REVOSTOCK_GALLERY_FILE
		 *
		 * @static
		 *
		 */
		static function on_load() {
			add_action( 'init', array( __CLASS__, 'init' ) );
			register_activation_hook( REVOSTOCK_GALLERY_FILE, array( __CLASS__, 'activate' ) );
			register_deactivation_hook( REVOSTOCK_GALLERY_FILE, array( __CLASS__, 'deactivate' ) );
			add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
			add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
			add_action( 'save_post', array( __CLASS__, 'save_post' ), '', 2 );
			add_action( 'revostock_gallery_cron_hook', array( __CLASS__, 'get_new_media' ) );
			add_action( 'deleted_transient', array( __CLASS__, 'deleted_transient' ) );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'wp_enqueue_scripts' ) );
			add_action( 'wp_print_styles', array( __CLASS__, 'wp_print_styles' ) );

		}

		/**
		 * @uses load_plugin_textdomain()
		 * @uses add_shortcode()
		 * @uses wp_register_style()
		 *
		 * @static
		 *
		 */
		static function init() {
			load_plugin_textdomain( 'revostock-gallery', false, basename( dirname( REVOSTOCK_GALLERY_FILE ) ) . '/languages' );
			add_shortcode( 'revostock-gallery', array( __CLASS__, 'revostock_gallery_shortcode' ) );
			//Hooks shortcode TinyMCE button into WordPress
			if ( current_user_can( 'edit_posts' ) &&  current_user_can( 'edit_pages' ) ) {
				add_filter( 'mce_external_plugins', array( __CLASS__, 'mce_external_plugins' ) );
				add_filter( 'mce_buttons', array( __CLASS__, 'mce_buttons' ) );
			}
		}

		/**
		 * @uses add_option()
		 * @uses wp_next_scheduled()
		 * @uses wp_schedule_event()
		 */
		function activate() {

			if ( version_compare( PHP_VERSION, REVOSTOCK_GALLERY_MIN_PHP, '<' ) ) {
				deactivate_plugins( basename( REVOSTOCK_GALLERY_FILE ) );
				trigger_error('Your site needs to be running PHP 5.2 or later in order to use RevoStock Gallery', E_USER_ERROR);
			}
			else {
				// Create default settings
				$credentials = array(
					'username' => '',
					'password' => '',
				);
				$options     = array(
					'_credentials'	=> $credentials,
					'_defaults'		=> self::set_default_options(),
				);
				add_option( 'revostock_gallery_settings', $options );

				add_option( 'revostock_gallery_cache', array() );

				if ( ! wp_next_scheduled( 'revostock_gallery_cron_hook') ) {
					//run twice daily
					wp_schedule_event( time(), 'twicedaily', 'revostock_gallery_cron_hook' );
				}
			}

		}

		/**
		 * @uses get_option()
		 * @uses delete_transient()
		 * @uses wp_next_scheduled()
		 * @uses wp_unschedule_event()
		 */
		function deactivate() {

			//delete transients
			$cache = get_option( 'revostock_gallery_cache' );
			foreach ( $cache as $request => $transient ) {
				delete_transient( $transient );
			}

			//unschedule cron
			$next_run = wp_next_scheduled( 'revostock_gallery_cron_hook' );
			wp_unschedule_event( $next_run, 'revostock_gallery_cron_hook');

		}

		/**
		 * Sets default shortcode options
		 *
		 * @static
		 * @return array
		 */
		static function set_default_options() {
			return array(
				'file'				=> '', 		// specific asset
				'mediabox'		 	=> '', 		// specific mediabox
				'producer'		 	=> '', 		// specific producer
				'search_terms'		=> '', 		// Holds search terms
				'type'				=> 'all', 	// Values: all, video, audio, aftereffects, motion
				'group'				=> '', 		// Values: newest, most_downloaded, editors_choice
				'max_results'		=> '', 		// Values: 1-40 // uses rpp= API arg
				'columns' 			=> '',		// Values: 0-10 // 0 indicates dynamic, non-columned output
				'file_info'	 		=> 'all', 	// Values: all, description, thumbnail, content_type, TODO asset_specifics
				'css_prefix'		=> '',
				'color_scheme'		=> 'grey',
			);
		}

		static function wp_enqueue_scripts() {
			wp_enqueue_script( 'revostock_imagetrail', plugins_url( 'js/imagetrail.js', REVOSTOCK_GALLERY_FILE ), array(), '1.0', true );
		}

		static function wp_print_styles() {
			wp_enqueue_style( 'revostock_gallery_stylesheet', plugins_url( 'css/styles.css', REVOSTOCK_GALLERY_FILE ) );
			$settings = self::get_shortcode_defaults();
			wp_enqueue_style( 'revostock_gallery_stylesheet-colors', plugins_url( 'css', REVOSTOCK_GALLERY_FILE ).'/styles-'.$settings['color_scheme'].'.css', array( 'revostock_gallery_stylesheet' ) );
		}
		/********** END INITIAL SETUP ***************/

		/*********************************************
		 * ADMIN SETTINGS
		 *********************************************
		 */

		/**
		 * Adds admin options page and admin CSS
		 *
		 * @uses add_options_page()
		 * @uses add_action()
		 *
		 * @static
		 *
		 */
		static function admin_menu() {
			$page = add_options_page( __( 'RevoStock Gallery Admin', 'revostock-gallery' ), __( 'RevoStock Gallery', 'revostock-gallery' ), 'edit_posts', 'revostock-gallery', array( __CLASS__, 'options_page' ) );
			add_action( 'admin_print_styles-' . $page, array( __CLASS__, 'admin_print_styles' ));
		}

		/**
		 * Registers admin CSS, new settings and defines sections and fields for admin options page
		 *
		 * @uses register_setting()
		 * @uses add_settings_section()
		 * @uses add_settings_field()
		 *
		 * @static
		 *
		 */
		static function admin_init() {
			register_setting( 'revostock_gallery_settings', 'revostock_gallery_settings', array( __CLASS__, 'validate_options' ) );

			//Section 1
			add_settings_section( 'revostock_gallery_usage', __('Using the Shortcode', 'revostock-gallery'), array( __CLASS__, 'settings_section_usage'), 'usage' );

			//Section 2
			add_settings_section( 'revostock_gallery_defaults', __('Shortcode Defaults', 'revostock-gallery'), array( __CLASS__, 'settings_section_defaults'), 'defaults' );
			add_settings_field( 'revostock_gallery_defaults_mediabox_id', __('Mediabox', 'revostock-gallery'), array( __CLASS__, 'settings_field_mediabox_id' ), 'defaults', 'revostock_gallery_defaults' );
			add_settings_field( 'revostock_gallery_defaults_producer_id', __('Producer', 'revostock-gallery'), array( __CLASS__, 'settings_field_producer_id' ), 'defaults', 'revostock_gallery_defaults' );
			add_settings_field( 'revostock_gallery_defaults_content_type', __('Types', 'revostock-gallery'), array( __CLASS__, 'settings_field_content_type' ), 'defaults', 'revostock_gallery_defaults' );
			add_settings_field( 'revostock_gallery_defaults_max_results', __('Maximum Results', 'revostock-gallery'), array( __CLASS__, 'settings_field_max_results' ), 'defaults', 'revostock_gallery_defaults' );
			add_settings_field( 'revostock_gallery_defaults_color_scheme', __('Color Scheme', 'revostock-gallery'), array( __CLASS__, 'settings_field_color_scheme' ), 'defaults', 'revostock_gallery_defaults' );

			//Section 3
			add_settings_section( 'revostock_gallery_account', __('RevoStock Account Credentials', 'revostock-gallery'), array( __CLASS__, 'settings_section_account'), 'account' );
			add_settings_field( 'revostock_gallery_account_username', __('Account Email', 'revostock-gallery'), array( __CLASS__, 'settings_field_account_username' ), 'account', 'revostock_gallery_account' );
			add_settings_field( 'revostock_gallery_account_password', __('Account Password', 'revostock-gallery'), array( __CLASS__, 'settings_field_account_password' ), 'account', 'revostock_gallery_account' );
		}

		/*+++++++++++ OPTIONS PAGE ++++++++++++*/

		/**
		 * Displays options page
		 *
		 * @uses current_user_can()
		 * @uses get_option()
		 * @uses add_settings_error()
		 * @uses Revostock_Gallery::options_page_tabs()
		 * @uses settings_errors()
		 * @uses settings_fields
		 * @uses do_settings_sections()
		 *
		 * @static
		 *
		 */
		static function options_page() {
			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_die( __( 'You do not have sufficient privileges to access this page.', 'revostock-gallery' ) );
			} ?>
			<div class="wrap">
			<?php if ( current_user_can( 'manage_options' ) ) { ?>
				<div id="icon-revostock" class="icon32"><?php echo '<img src="'.plugins_url( 'images/', REVOSTOCK_GALLERY_FILE ).'revostock-logo.gif" />' ?></div>
				<h2 class="nav-tab-wrapper"><?php _e( 'RevoStock Media Gallery', 'revostock-gallery' ); ?>
				<?php
					// Redirect to Account tab if there are no saved credentials
					$settings = get_option( 'revostock_gallery_settings' );
					if ( empty( $settings['_credentials']['username'] ) || empty( $settings['_credentials']['password'] ) ) {
						add_settings_error( 'revostock_gallery_settings', 'need-info', __( 'You must have an account with RevoStock to use this plugin.  Please enter your credentials.', 'revostock-gallery' ) );
						$tab = 'account';
					} else {
						// Render different sections depending on which tab
						$tab = ( isset( $_GET[ 'tab' ] ) ) ? $_GET[ 'tab' ] : 'usage';
					}
					self::options_page_tabs( $tab );
				?></h2><?php
				settings_errors( 'revostock_gallery_settings' );
				// Help text at top of page
				switch ( $tab ) {
					case 'account':

				} ?>
				<form action="options.php" method="post"><?php
					settings_fields( 'revostock_gallery_settings' );
					do_settings_sections( $tab );
					if ( $tab != 'usage' ){ ?>
						<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'revostock-gallery' ); ?>" /><?php
					}
					if ( $tab == 'defaults' ) { ?>
						<input name="revostock_gallery_settings[reset-defaults]" type="submit" class="button-secondary" value="<?php esc_attr_e( 'Reset Defaults', 'revostock-gallery' ); ?>" />
						<?php self::revostock_admin_footer(); ?>
						</div><?php
					} elseif ( $tab == 'account' ) { ?>
						<input name="revostock_gallery_settings[reset-account]" type="submit" class="button-secondary" value="<?php esc_attr_e( 'Clear info', 'revostock-gallery' ); ?>" />
						</div>
						</div>
						<?php self::revostock_affiliate_info(); ?>
						<?php self::revostock_admin_footer(); ?>
						</div><?php
					} ?>
				</form><?php
			} else {
				do_settings_sections( 'usage' );
			} ?>
			</div><?php
		}
		/**
		 * Creates and displays the admin options page tabs
		 *
		 * @param string $current
		 *
		 */
		static function options_page_tabs( $current = '' ) {
			if ( empty( $current ) ) {
				if ( isset( $_GET['tab'] ) )
					$current = $_GET['tab'];
				else
					$current = 'usage';
			}
			$tabs = array(
				'usage'			=> 'Usage',
				'defaults'	=> 'Defaults',
				'account'		=> 'Account',
				);
			$links = array();
			foreach ( $tabs as $tab => $name ) {
				$current_class = ( $tab == $current ) ? 'nav-tab-active' : '';
				$links[] = '<a class="nav-tab '.$current_class.'" href="?page=revostock-gallery&amp;tab='.$tab.'">'.sprintf( esc_attr__('%s', 'revostock-gallery' ), $name ).'</a>';
			}

			foreach ( $links as $link )
				echo $link;
		}

		/**
		 * @uses wp_enqueue_style()
		 *
		 * @static
		 *
		 */
		static function admin_print_styles() {
			wp_enqueue_style( 'revostock_gallery_admin_style', plugins_url( 'css/admin-styles.css', REVOSTOCK_GALLERY_FILE ) );
		}

		/*++++++++++++ SECTIONS & FIELDS ++++++++++++*/

		/**
		 * Displays 'Usage' tab of admin options page
		 *
		 * @static
		 *
		 */
		static function settings_section_usage() { ?>
			<div class="revostock-admin" id="usage-tab">

				<div class="revostock-admin-badge"><?php printf( __( 'Version %s', 'revostock-gallery' ), REVOSTOCK_GALLERY_VER ); ?></div>

				<div class="revostock-admin-content">
					<h3><?php _e( 'Welcome to RevoStock Admin Gallery for WordPress! ' ); ?></h3>
					<p class="about-description">
						<?php _e( 'This plugin allows you to insert a gallery of RevoStock media items (video, audio,
					AfterEffects templates, Apple Motion templates) into your posts or pages, using
					the shortcode', 'revostock-gallery' ); ?>
						<span style="color: #942323;"><?php printf( __( '%s', 'revostock-gallery' ), '[revostock-gallery]' ); ?></span>
						<?php _e( 'or by clicking on the RevoStock star', 'revostock-gallery' ); ?>
						<img src="<?php echo plugins_url( 'images/', REVOSTOCK_GALLERY_FILE ); ?>revostock-editor-button.png" alt="" />
						<?php _e( 'on the post editor.', 'revostock-gallery' );?>
						<span style="font-family: 'Helvetica Neue',sans-serif; font-weight: 500;"><?php _e( 'First, set your', 'revostock-gallery' ); ?>
						<a href="?page=revostock-gallery&amp;tab=defaults"><?php _e( 'Default', 'revostock-gallery' ); ?></php></a>
						<?php _e( 'settings.', 'revostock-gallery' ); ?></span>
					</p>
					<div class="revostock-admin-column-container">
						<div class="revostock-admin-column">
							<p><?php _e( 'You can override the ', 'revostock-gallery' ); ?>
								<a href="?page=revostock-gallery&amp;tab=defaults"><?php _e( 'default settings', 'revostock-gallery' ); ?></php></a>
								<?php _e( 'for any particular shortcode using the attributes below - or just selecting your desired option from the
															RevoStock star on the post editor.', 'revostock-gallery' ); ?>
							</p>
						</div>
						<div class="revostock-attr-container">
							<?php self::revostock_attr_info( array(
								'name'=>'file',
								'desc'=>'Insert a specific file (media item)',
								'values'=>'Requires the unique numeric ID of the media item.',
								'example'=>'[revostock-gallery file=12345]'
							 ) ); ?>
							<?php self::revostock_attr_info( array(
								'name'=>'mediabox',
								'desc'=>'Insert from mediabox',
								'values'=>'Requires the unique numeric ID of the mediabox.',
								'example'=>'[revostock-gallery mediabox=12345]'
							) ); ?>
							<?php self::revostock_attr_info( array(
								'name'=>'producer',
								'desc'=>'Insert from specific producer',
								'values'=>'Requires the unique numeric ID of the producer.',
								'example'=>'[revostock-gallery producer=12345]'
							) ); ?>
							<?php self::revostock_attr_info( array(
								'name'=>'type',
								'desc'=>'Insert from specific type of content',
								'values'=>'Categories:<br />all, audio, video, aftereffects, motion',
								'example'=>'[revostock-gallery type=audio]'
							) ); ?>
							<?php self::revostock_attr_info( array(
								'name'=>'search_terms',
								'desc'=>'Insert from specific search terms',
								'values'=>'Requires comma,separated,list,of,terms.',
								'example'=>'[revostock-gallery search_terms=forest,moon]'
							) ); ?>
							<?php self::revostock_attr_info( array(
								'name'=>'group',
								'desc'=>'Insert from specific group',
								'values'=>'Groups:<br />newest, most_downloaded, editors_choice',
								'example'=>'[revostock-gallery group=newest]'
							) ); ?>
							<?php self::revostock_attr_info( array(
								'name'=>'max_results',
								'desc'=>'Limit the number of items',
								'values'=>'Requires a number from 1 to 40.',
								'example'=>'[revostock-gallery max_results=5]'
							) ); ?>
							<?php self::revostock_attr_info( array(
								'name'=>'css_prefix',
								'desc'=>'Add custom CSS',
								'values'=>'Requires a unique CSS class name.',
								'example'=>'[revostock-gallery css_prefix=myclassname]'
							) ); ?>
						</div>
						<?php self::revostock_support_info(); ?>
						<?php self::revostock_admin_footer(); ?>
					</div>
				</div>
			</div><?php
		}

		/**
		 * Displays shortcode attribute info, given the attribute's name, description, values, and example
		 *
		 * @static
		 * @param $attr
		 *
		 * @var $name string
		 * @var $desc string
		 * @var $values string
		 * @var $example string
		 */
		static function revostock_attr_info( $attr ) {
			extract( $attr );
			echo '<div class="revostock-attr" id="revostock-attr-' . $name . '">';
			echo '<h3>' . $name . '</h3>';
			echo '<div class="revostock-attr-desc">';
			echo '<p>' . esc_html__( $desc ) . '</p>';
			echo '</div>';
			echo '<div class="revostock-attr-values">';
			echo '<p>' . $values . '</p>';
			echo '</div>';
			echo '<div class="revostock-attr-example">';
			echo '<p>Example:<br /><span style="color: #942323;">' . $example . '</span></p>';
			echo '</div>';
			echo '</div>';
		}

		/**
		 * Displays admin options page support info and link
		 *
		 * @static
		 *
		 */
		static function revostock_support_info() {
			echo '<div class="revostock-banner" id="revostock-support">';
			echo '<div class="revostock-banner-container">';
			echo '<div class="revostock-banner-text">';
			echo '<h4>' . __( 'Have feedback? Need help?', 'revostock-gallery' ) . '</h4>';
			echo '</div>';
			echo '<div class="revostock-banner-button-container">';
			echo '<p class="submit">' . sprintf( '<a id="revostock-support-button" class="button-primary revostock-banner-button" target="_blank" href="%1$s">%2$s</a>', 'http://revostock.com/wordpress.html', __( 'Contact Support', 'revostock-gallery' ) ) . '</p>';
			echo '</div>';
			echo '</div>';
			echo '</div>';
		}

		/**
			* Displays affiliate program info and link
			*
			* @static
			*
			*/
		static function revostock_affiliate_info() {
			echo '<div class="revostock-banner" id="revostock-affiliate-info">';
			echo '<div class="revostock-banner-container">';
			echo '<div class="revostock-banner-text">';
			echo '<h4><span style="color: #932424;">' . __( 'Want to earn more? ', 'revostock-gallery' ) . '</span>';
			echo  __( 'Join ', 'revostock-gallery' );
			echo '<img class="sharetherevo" src="' . plugins_url( 'images/', REVOSTOCK_GALLERY_FILE ) . 'share-the-revo.png" />';
			echo __( ' today.', 'revostock-gallery' ) . '</h4>';
			echo '</div>';
			echo '<div class="revostock-banner-button-container">';
			echo '<p class="submit">' . sprintf( '<a id="revostock-affiliate-button" class="button-primary revostock-banner-button" target="_blank" href="%1$s">%2$s</a>', 'http://www.revostock.com/Affiliate.html', __( 'Join', 'revostock-gallery' ) ) . '</p>';
			echo '</div>';
			echo '</div>';

			echo '</div>';
		}

		/**
		 * Displays admin options page footer
		 *
		 * @static
		 *
		 */
		static function revostock_admin_footer() {
			echo '<div class="revostock-footer">';
			echo '<p class="revostock-logo">';
			echo '<span>RevoStock</span>';
			echo '</p>';
			echo '<p class="small">';
			echo '<a href="http://revostock.com/wordpress.html" target="_blank">' . esc_html__( 'RevoStock Media Gallery for WordPress ', 'revostock-gallery' ) . esc_html__( REVOSTOCK_GALLERY_VER, 'revostock-gallery' ) . '</a> | ';
			echo '<a href="http://revostock.com/wordpress.html" target="_blank">' . esc_html__( 'Support', 'revostock-gallery' ) . '</a>';
			echo '</p>';
			echo '</div>';
		}

		/**
		 * Displays 'Defaults' tab of admin options page
		 *
		 * @static
		 *
		 */
		static function settings_section_defaults() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'You are not authorized to change settings for this plugin.', 'revostock-gallery' ) );
			}
			?>
			<div class="revostock-admin" id="defaults-tab">
				<div class="revostock-admin-content">
					<h3><?php _e( 'Shortcode Defaults ' ); ?></h3>
					<p class="about-description">
						<?php _e( 'These are the default options your shortcode will use if no other attributes are provided. You can always
						override these default values with the attributes explained', 'revostock-gallery' ); ?>
						<a href="?page=revostock-gallery&amp;tab=usage"><?php _e( 'here', 'revostock-gallery' ); ?></php></a>
						<?php _e( 'or by clicking on the RevoStock star on the post editor.', 'revostock-gallery' ); ?>
					</p>
				</div>

			<?php
		}

		/**
		 * Displays 'Accounts' tab of admin options page
		 *
		 * @static
		 *
		 */
		static function settings_section_account() {
			if ( ! current_user_can( 'manage_options' ) ){
				wp_die( __( 'You are not authorized to change settings for this plugin.', 'revostock-gallery' ) );
			} ?>
			<div class="revostock-admin" id="account-tab">
				<div id="revostock-auth">
					<div class="revostock-requirements">
						<h1><?php _e( 'The RevoStock Media Gallery plugin <strong>requires', 'revostock-gallery' ); ?></h1>
						<div class="revostock-requires-user-account">
							<?php _e( 'a user account', 'revostock-gallery' ); ?></strong><p>it's free!</p>

							<p class="submit revostock-requires-button">
								<?php printf( '<a id="revostock-getyouraccount" class="button-primary" target="_blank" href="%1$s">%2$s</a>', 'http://www.revostock.com/RegMember.html', __( 'Get your account', 'revostock-gallery' ) ); ?>
							</p>

						</div>
						<div class="plus">+</div>
						<div class="revostock-requires-api">
							<strong><?php _e( 'API access', 'revostock-gallery' ); ?></strong><p class="revostock-require-desc">after logging in to RevoStock</p>

							<p class="submit revostock-requires-button">
							<?php printf( '<a id="revostock-getyouraccess" class="button-primary" target="_blank" href="%1$s">%2$s</a>', 'http://www.revostock.com/api.html', __( 'Get your API access', 'revostock-gallery' ) ); ?>
							</p>

						</div>

					</div>

					<div class="revostock-credentials">
						<h3><?php _e( 'Already have a RevoStock account <em>with API access</em>?', 'revostock-gallery' ); ?></h3>
						<p><?php _e( 'Enter your credentials below:', 'revostock-gallery' ); ?></p><?php
		}

		/**
		 * @uses Revostock_Gallery::get_shortcode_defaults()
		 *
		 * @static
		 *
		 */
		static function settings_field_producer_id() {
			$defaults = self::get_shortcode_defaults(); ?>
			<input id="revostock_gallery_defaults_producer_id" name="revostock_gallery_settings[_defaults][producer]" size="10" type="text" value="<?php echo esc_attr( $defaults['producer'] );?>" />
			<br />
			<span class="description"><?php esc_html_e('Unique numeric ID of the RevoStock producer you\'d like to display from', 'revostock-gallery'); ?></span><?php
		}

		/**
		 * @uses Revostock_Gallery::get_shortcode_defaults()
		 *
		 * @static
		 *
		 */
		static function settings_field_max_results() {
			$defaults = self::get_shortcode_defaults(); ?>
			<input id="revostock_gallery_defaults_max_results" name="revostock_gallery_settings[_defaults][max_results]" size="5" type="text" value="<?php echo esc_attr( $defaults['max_results'] ); ?>" />
			<br />
			<span class="description"><?php esc_html_e( 'Enter a number from 1 to 40', 'revostock-gallery' ) ?></span><?php
		}

		/**
		 * @uses Revostock_Gallery::get_shortcode_defaults()
		 *
		 * @static
		 *
		 */
		static function settings_field_color_scheme() {
			$defaults = self::get_shortcode_defaults();
			$styles = array(
				"blackandwhite"	=> "Black and white",
				"grey"					=> "Grey",
				"red"						=> "Red",
				"blue"					=> "Blue",
			);
			foreach ( $styles as $key => $choice ) {
				$checked = ( $key == $defaults['color_scheme'] ) ? 'checked="checked" ' : ''; ?>
				<input id="revostock_gallery_defaults_color_scheme-<?php echo $key; ?>" name="revostock_gallery_settings[_defaults][color_scheme]" type="radio" value="<?php echo $key; ?>" <?php echo $checked; ?>/>
				<?php _e( $choice, 'revostock-gallery' );?><br /><?php
			} ?>&nbsp;<br />
			<label><?php _e( 'CSS prefix ', 'revostock-gallery' ); ?><input id="revostock_gallery_defaults_color_scheme" name="revostock_gallery_settings[_defaults][css_prefix]" type="text" size="25" value="<?php echo esc_attr( $defaults['css_prefix'] ); ?>" /></label><br />
			<span class="description">
				<?php _e(' Space-separated class names to add to the gallery container', 'revostock-gallery' ); ?><br /><br />
				<?php _e( 'You can use this prefix in your WordPress stylesheet to customize the gallery display (background color, border, etc.)', 'revostock-gallery' ); ?><br />
				<?php _e( 'Example: .myprefix {background-color: gray; border: solid 1px yellow;}', 'revostock-gallery' ); ?>
			</span><?php
		}

		/**
		 * @uses Revostock_Gallery::get_shortcode_defaults()
		 *
		 * @static
		 *
		 */
		static function settings_field_mediabox_id() {
			$defaults = self::get_shortcode_defaults(); ?>
			<input id="revostock_gallery_defaults_mediabox_id" name="revostock_gallery_settings[_defaults][mediabox]" size="10" type="text" value="<?php echo esc_attr( $defaults['mediabox'] ); ?>" /><br />
			<span class="description"><?php _e( 'Unique numeric ID of the RevoStock mediabox to display from', 'revostock-gallery' ); ?></span><?php
		}

		/**
		 * @uses Revostock_Gallery::get_shortcode_defaults()
		 *
		 * @static
		 *
		 */
		static function settings_field_content_type() {
			$defaults = self::get_shortcode_defaults();
			$types = array(
				"all"		=> "All",
				"video"	=> "Video",
				"audio"	=> "Audio",
				"ae"		=> "AfterEffects templates",
				"motion"=> "Apple Motion templates",
			);
			foreach ( $types as $key => $choice ) {
				$checked = ( $key == $defaults[ 'type' ] ) ? 'checked="checked" ' : ''; ?>
				<input id="revostock_gallery_defaults_content_type" name="revostock_gallery_settings[_defaults][type]" type="radio" value="<?php echo $key; ?>" <?php echo $checked; ?>/>
				<?php _e( $choice, 'revostock-gallery' );?><br /><?php
			}
		}

		/**
		 * @uses get_option()
		 *
		 * @static
		 *
		 */
		static function settings_field_account_username() {
			$opts = get_option( 'revostock_gallery_settings' ); ?>
			<input id="revostock_gallery_account_username" name="revostock_gallery_settings[_credentials][username]" size="40" type="text" value="<?php echo esc_attr( $opts['_credentials']['username'] ); ?>" />
			<br />
			<span class="description"><?php _e( 'Your RevoStock user email', 'revostock-gallery' ); ?></span><?php
		}

		/**
		 * @uses get_option()
		 *
		 * @static
		 *
		 */
		static function settings_field_account_password() {
			$opts = get_option( 'revostock_gallery_settings' ); ?>
			<input id="revostock_gallery_account_password" name="revostock_gallery_settings[_credentials][password]" size="40" type="password" value="<?php echo esc_attr( $opts['_credentials']['password'] ); ?>" />
			<br />
			<span class="description"><?php _e( 'Your RevoStock password', 'revostock-gallery' ); ?></span><?php
		}

		/**
		 * Validates options
		 *
		 * @uses get_option()
		 * @uses Revostock_Gallery::trim_r()
		 * @uses Revostock_Gallery::call_API()
		 * @uses add_settings_error()
		 * @uses Revostock_Gallery::set_default_options()
		 * @uses Revostock_Gallery::sanitize_validate_input()
		 *
		 * @static
		 * @param $input
		 * @return array|mixed|void
		 */
		static function validate_options( $input ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'You do not have sufficient priviledges to do that.', 'revostock-gallery' ) );
			}

			$settings = get_option( 'revostock_gallery_settings' );
			$input = self::trim_r( $input );

			//
			// Credentials
			//

			// Are we clearing the account details?
			if ( isset( $input[ 'reset-account' ] ) ) {
				$settings[ '_credentials' ][ 'username' ] = '';
				$settings[ '_credentials' ][ 'password' ] = '';
			} elseif ( isset( $input[ '_credentials' ] ) ) {
				// Check submitted credentials against Revostock API response and save
				if ( ( $input['_credentials']['username'] != null ) && ( $input['_credentials']['password'] != null ) ) {
					if ( self::call_API( 'validate', array(
						'username' => $input['_credentials']['username'],
						'password' => $input['_credentials']['password'],
					) ) )
						$settings['_credentials'] = $input['_credentials'];
				} else {
					add_settings_error( 'revostock_gallery_settings', 'user-error', __( 'You must enter both a username and a password', 'revostock-gallery' ) );
				}
			}

			//
			// Defaults
			//

			// Are we resetting the defaults?
			if ( isset( $input['reset-defaults'] ) ) {
				$settings['_defaults'] = self::set_default_options();
			} elseif ( isset( $input['_defaults'] ) )  {
				$readable = array(
					'producer' 		=> 'Producer ID',
					'columns'			=> 'Columns',
					'max_results'	=> 'Limit media',
					'color_scheme'=> 'Style sheet',
					'mediabox' 		=> 'Media Box ID',
					'type' 				=> 'Return these media types',
				);

				// Checkboxes don't fit our validation function, so we pre-sanitize before we call our function
				// More checkboxes and we should handle this with an array and a foreach

				// Sanitize
				$valid = self::sanitize_validate_input( $input['_defaults'] );

				// Walk through $POST data and compare to sanitized/validated version
				foreach ( $input['_defaults'] as $key => $value ) {
					if ( ! $value ) {
						$settings['_defaults'][$key] = '';
					} else {
						if ( $value == $valid[$key] ) {
							$settings['_defaults'][$key] = $valid[$key];
						} else {
							add_settings_error( 'revostock_gallery_settings', 'input-error', sprintf( __( 'You need to enter a valid value for "%s".', 'revostock-gallery' ), $readable[$key] ) );
						}
					}
				}
			}

			return $settings;

		}

		/********** END ADMIN SETTINGS **************/

		/*********************************************
		 * SHORTCODE FOR DISPLAY
		 *********************************************
		 */

		/**
		 * Renders the shortcode
		 *
		 * @uses Revostock_Gallery::get_shortcode_defaults()
		 * @uses shortcode_atts()
		 * @uses Revostock_Gallery::get_new_media()
		 * @uses sanitize_html_class()
		 * @uses Revostock_Gallery::output_items()
		 * @uses Revostock_Gallery::sanitize_and_remove_empty()
		 *
		 * @static
		 * @param $attr
		 * @return string
		 */
		static function revostock_gallery_shortcode( $attr ) {
			if ( self::check_for_credentials() ) {
				$defaults = self::get_shortcode_defaults();
				$settings = shortcode_atts( $defaults, $attr );
				$results = self::get_new_media( $settings );


         $display = '<div id="coord_display"></div>';
				$display .= '<div class="revostock-gallery-container ' . sanitize_html_class( $settings[ 'css_prefix' ] ) . '">';

				$display .= self::output_items( $results, self::sanitize_and_remove_empty( $settings ) );
				$display .= '</div>';

				return $display;
			}
			else {
				$message = '<div class="revostock-gallery-container">';
				$message .= esc_html__( 'There is a problem with your account. Please check settings.', 'revostock-gallery' ) . '</div>';
				return $message;
			}
		}

		/**
		 * Retrieve default shortcode options
		 *
		 * @uses get_option()
		 *
		 * @static
		 * @return mixed
		 */
		static function get_shortcode_defaults() {
			$settings = get_option( 'revostock_gallery_settings' );
			return $settings['_defaults'];
		}

		/**
		 * Register shortcode button as TinyMCE plugin
		 *
		 * @static
		 * @param $plugin_array
		 * @return array
		 */
		static function mce_external_plugins( $plugin_array ) {
			$plugin_array['revostock_gallery'] = plugins_url( 'js/editor_plugin.js', REVOSTOCK_GALLERY_FILE );
			return $plugin_array;
		}

		/**
		 * Add shortcode button to the array of TinyMCE editor buttons
		 *
		 * @static
		 * @param $buttons
		 * @return array
		 */
		static function mce_buttons( $buttons ) {
			array_push( $buttons, "|", "revostock_gallery" );
			return $buttons;
		}

		/********** END SHORTCODE *****************/

		/*********************************************
		 * MEDIA RETRIEVAL & STORAGE
		 *********************************************
		 */

		/**
		 * Retrieves media items by checking cache first, then sending new API request and saving results to cache
		 *
		 * @uses get_option()
		 * @uses get_transient()
		 * @uses Revostock_Gallery::call_API()
		 * @uses set_transient()
		 * @uses Revostock_Gallery::build_request()
		 * @uses update_option()
		 *
		 * @static
		 * @param string $settings
		 * @return array|bool|mixed
		 */
		static function get_new_media( $settings = '' ) {

			//is this a cron call?
			if ( empty( $settings ) ) {

				//cleanup cache by getting rid of expired transients
				$cache = get_option( 'revostock_gallery_cache' );
				foreach ( $cache as $request => $transient ) {
					get_transient( $transient );
				}

				//loop through $cache array
				// - call_API with request key & save updated results to transient
				$cache = get_option( 'revostock_gallery_cache' );
				foreach ( $cache as $request => $transient ) {
					$updated_results = self::call_API( 'fetch_items', array( 'request' => $request ) );
					set_transient( $transient, $updated_results, 86400 );
				}

			} else {
				//check cache first
				$cache = get_option( 'revostock_gallery_cache' );
				$request = self::build_request( $settings );
				$key = array_search( $request, $cache );

				//if request already in $cache, return transient w/ cached results
				if ( $key ) {
					return get_transient( $key );
				} else {

				//else, call_API to get results, save results to transient, save transient name to $cache, and return results
					$media_items = self::call_API( 'fetch_items', array( 'request' => $request ) );
					if ( $media_items ) {
						$transient_name = 'revostock_gallery_' . time();
						set_transient( $transient_name, $media_items, 86400 );
						$new_cache_item = array( $request => $transient_name );
						$cache = array_merge( $cache, $new_cache_item );
						update_option( 'revostock_gallery_cache', $cache );
					}
					return $media_items;
				}
			}
		}

		/**
		 * Removes transient from cache when it is deleted
		 *
		 * @uses get_option()
		 * @uses update_option
		 *
		 * @static
		 * @param $transient
		 */
		static function deleted_transient ( $transient ) {
			$cache = get_option( 'revostock_gallery_cache' );
			foreach ( $cache as $request => $transient_name ) {
				if ( $transient_name == $transient )
					unset( $cache[$request] );
			}
			update_option( 'revostock_gallery_cache', $cache );
		}

		/**
		 * On save_post, saves updated shortcode attributes by
		 * 1) retrieving shortcode attributes from post_content
		 * 2) checking against cache
		 * 3) If they don't exist in cache, then issue an API request and save results to cache
		 *
		 * @uses get_option()
		 * @uses get_shortcode_regex()
		 * @uses shortcode_parse_atts()
		 * @uses Revostock_Gallery::build_request()
		 * @uses Revostock_Gallery::call_API()
		 * @uses set_transient()
		 * @uses update_option()
		 *
		 * @static
		 * @param $post_ID
		 * @param $post
		 */
		static function save_post( $post_ID, $post ) {
				if ( self::check_for_credentials() ) {
					$cache = get_option( 'revostock_gallery_cache' );	//get revostock_gallery_cache option array

					//while revostock-gallery shortcode(s) exist, for each shortcode:
					// - get attributes
					// - build_request
					// - if request is not a key in revostock_gallery_cache,
					// -- call_API, save results in a transient, and save request=>transient_name in options array
					$pattern = get_shortcode_regex();
					preg_match_all( '/' . $pattern . '/s', $post->post_content, $matches );
					if ( is_array( $matches ) && array_key_exists( 2, $matches ) && in_array( 'revostock-gallery', $matches[2] ) ) {

						foreach( $matches[2] as $i => $name ) {

							if( 'revostock-gallery' == $name ) {

								$attr = shortcode_parse_atts( $matches[3][$i] );
								$request = self::build_request( $attr );
								$key = array_search( $request, $cache );
								if ( ! $key ) {
									$media_items = self::call_API( 'fetch_items', array( 'request' => $request ) );
									if ( $media_items ) {
										$transient_name = 'revostock_gallery_' . time();
										set_transient( $transient_name, $media_items, 86400 );
										$new_cache_item = array( $request => $transient_name );
										$cache = array_merge( $cache, $new_cache_item );
										update_option( 'revostock_gallery_cache', $cache );
									}
								}
							}
						}

					}
				}


		}

		/**
		 * Builds API request URL from shortcode attributes
		 *
		 * @uses Revostock_Gallery::sanitize_and_remove_empty()
		 *
		 * @static
		 *
		 * @var $file string
		 * @var $search_terms string
		 * @var $type string
		 * @var $mediabox string
		 * @var $producer string
		 * @var $max_results string
		 *
		 * @param $settings
		 *
		 * @return string
		 */
		static function build_request( $settings ) {
			$request = '';

			extract( $settings ); //remove & reference variables through settings array

			// Construct the API request
			if ( $file ) {
				$request = 'content/'.$file;
			} elseif ( $search_terms ) {
				$request = 'search';
				switch ( $type ) {
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
					default:
						$request .= '/';
						break;
				}
				$terms = str_replace( ',', '/', $search_terms );
				$request .= $terms;
			} elseif ( $mediabox ) {
				$request = 'mediabox-content/'.$mediabox;
			} else {
				$request = 'new';
			}

			// Add arguments to API request
			$args = array();
			if ( $producer )
				$args[] .= 'producer_id='.$producer;
			if ( ! $search_terms && $type && $type != 'all' )
				$args[] = 'type='.$type;
			if ( $group ) {
				$order = 'order=';
				switch ( $group ) {
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
			if ( $max_results )
				$args[] = 'rpp='.$max_results;

			if ( $args ) {
				$request .= '?';
				for ( $i = 0; $i < count( $args ); $i ++ ) {
					if ( $i > 0 )
						$request .= '&';
					$request .= $args[$i];
				}
			}

			return $request;
		}

		/**
		 * Displays results to page
		 *
		 * @static
		 * @param $results
		 * @param $settings
		 * @return string|void
		 */
		static function output_items( $results, $settings ) {
			extract( $settings );
			// Here's our return to the browser, empty if we don't have an array
			$output = '';
			if ( is_array( $results ) && ! empty( $results ) ) {
				// Eliminate "rows" by setting the for loop & array_slicing to run once with the whole set if we don't have limits
				if ( empty( $max_results ) ) {
					$max_results = count( $results );
				}
				if ( empty( $columns ) ) {
					$columns = count( $results );
				}

				// Readable filetype array
				$type = array(
					'video'	=> 'Stock Video Footage',
					'music'	=> 'Stock Music',
					'sound'	=> 'Sound Effects',
					'ae'		=> 'After Effects Template',
					'motion'=> 'Apple Motion Template',
				);

				$preview_base = 'http://www.revostock.com/popuppluginlarge.php';

				// Loop based on max_results and columns FIXME "rows" deprecated...code refactoring should happen throughout
				for ( $i = 0; $i < $max_results; $i += $columns ) {
					$row = array_slice( $results, $i, $columns );
					$output .= '<div class="revostock-gallery-items-row">';
					foreach ( $row as $item ) {
						// Clear $asset_spec for concat instances
						$asset_spec = '';
						// Build by piece based on asset_display=all or list: description,thumbnail,content_type,asset_specifics !! NOT IMPLEMENTED, all displayed
						// asset_specifics
						switch ( $item['FileType'] ) {
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

						switch ( $item['FileType'] ) {
							case 'video':
								$type = 'Stock Video Footage';
								break;
							case 'audio':
								switch ( $item['PrimaryCategory']['ID'] ) {
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
						$itemlink = '<a rel="nofollow" target="_blank" href="'.$item['ProductURL'].'">';

						// Producer link
						$producerlink = '<a rel="nofollow" target="_blank" href="http://www.revostock.com/ViewProfile.html?&amp;ID='.$item['Producer']['ID'].'">';

						// Shorten the title and producer names and add a ...
						$itemshortname = substr( $item['Title'], 0, 34 );
						if ( strlen($item['Title'] ) > 34 )
							$itemshortname .='...';

						$producershortname =  substr( $item['Producer']['username'], 0, 17 );
						if ( strlen( $item['Producer']['username'] ) > 17 )
							$producershortname .= '...';

						$preview_url = $preview_base . '?ID=' . $item['ID'];
						$loader_url = plugins_url( 'images/', REVOSTOCK_GALLERY_FILE ).'ajax-loadersmall.gif';
						// item container
						$output .= '<div id="item-'.$item['ID'].'" class="revostock-gallery-item">';

						$output .= '<div class="revostock-gallery-item-thumbnail">'.$itemlink.'<img src="'.$item['ThumbURL'].'" alt="'.$itemshortname.'" onmouseover="showhover(\'' . $preview_url .'\',\'' . $item['FileType'] .'\',\'' . $item['Format']['ID'] .'\',\'' . $loader_url .'\');" onmouseout="hidetrail();" /></a></div>';
						$output .= '<div class="revostock-gallery-item-description revostock-gallery-clear">';
						$output .= '<div class="revostock-gallery-item-title"><div>'.$itemlink.$itemshortname.'</a></div></div>';
						$output .= '<div class="revostock-gallery-item-producer"><div>'.$producerlink.'By&nbsp;'.$producershortname.'</a></div></div>';
						$output .= '</div>';
						$output .= '<div class="revostock-gallery-item-type revostock-gallery-clear">';
						$output .= '<img src="'.plugins_url( 'images/', REVOSTOCK_GALLERY_FILE ).$icon.'.png" class="revostock-gallery-item-media-icon" alt="'. $type . '" />';

						$output .= '<div class="revostock-gallery-item-type-label">'.$type.'</div>';
						$output .= '</div>';
						$output .= '</div>';
					}
					$output .= '<div class="revostock-gallery-clear"></div></div>';
				}
			} else {
				$output = __( 'No media items found.', 'revostock-gallery' );
			}

			return $output;
		}

		/**
		 * API call
		 *
		 * @uses get_option()
		 * @uses wp_remote_get()
		 * @uses is_wp_error()
		 * @uses add_settings_error()
		 * @uses Revostock_Gallery::parse_feed()
		 *
		 * @static
		 * @param $action
		 * @param null $args
		 * @return array|bool
		 */
		static function call_API( $action, $args =  null ) {
			$base_url = 'https://revostock.com/rest';
			$settings = get_option( 'revostock_gallery_settings' );
			$credentials = base64_encode( $settings['_credentials']['username'].':'.$settings['_credentials']['password'] );
			$remote_request_args = array(
				'headers' => array( 'Authorization'=>'Basic '.$credentials ),
				'sslverify' => false,
			);

			switch ( $action ) {
				case 'validate':
					if ( isset( $args['username'] ) && isset( $args['password'] ) ) {
						$remote_request_args = array(
							'headers' => array( 'Authorization'=>'Basic '.base64_encode( $args['username'].':'.$args['password'] ) ),
							'sslverify' => false,
						);
						$response = wp_remote_get( $base_url.'/new/video?rpp=1', $remote_request_args );
						if ( ! is_wp_error( $response ) ) {
							switch ( $response['response']['code'] ) {
								case '401':
									add_settings_error( 'revostock_gallery_settings', 'user-error', __( 'Your username and password combination were not recognized by RevoStock', 'revostock-gallery' ) );
									return false;
								case '200':
									return true;
							}

						} else {
							add_settings_error( 'revostock_gallery_settings', 'local-error', __( 'There was a problem reaching RevoStock.<br />Please try again later or contact your site administrator.' , 'revostock-gallery' ) );
							return false;
						}
					} else {
						add_settings_error( 'revostock_gallery_settings', 'local-error', __( 'Something went wrong', 'revostock-gallery' ) );
						return false;
					}
					break;
				case 'fetch_items':
						$response = wp_remote_get( $base_url.'/'.$args['request'], $remote_request_args );
						if ( ! is_wp_error( $response ) ) {
							switch ( $response['response']['code'] ) {
								case '200':
									$p = xml_parser_create();
									xml_parser_set_option( $p, XML_OPTION_CASE_FOLDING, 0 );
									xml_parser_set_option( $p, XML_OPTION_SKIP_WHITE, 1 );
									if ( xml_parse_into_struct( $p, $response['body'], $values ) ) {
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

		/**
		 * Converts API response into readable format
		 *
		 * @static
		 * @param $data
		 * @param $index
		 * @param array $parsed
		 * @return array
		 */
		static function parse_feed( $data, &$index = -1, &$parsed = array() ) {
			// First run, index = 0, every recursion it gets incremented
			$index ++;

			// Top level tag returned by Revostock API
			if ( 'Revostock_Rest_API' == $data[$index]['tag'] ) {
				// If it's not an "open" tag, the result was empty or we hit the end
				if ( $data[$index]['type'] != "open" )
					return $parsed;
				else // Skipping top level and moving on
					$index++;  // Just step over it
				// Now we're at first child, ensure we have what we want
				if ( $data[$index]['tag'] != "item" )
					return $parsed;  // empty array
			}

			// We're expecting <item>s to be in the feed, building a numeric keyed array of them
			if ( $data[$index]['tag'] == "item" ) {
				if ( $data[$index]['type'] == "open" ) {
					$parsed[] = self::parse_feed( $data, $index );
					self::parse_feed( $data, $index, $parsed );
				}
			}

			// We've gotten here because we have children of <item>s
			if ( $data[$index]['type'] == "open" ) {
				$parsed[ ( $data[$index]['tag'] ) ] = self::parse_feed( $data, $index );
				self::parse_feed( $data, $index, $parsed );
			} elseif ( $data[$index]['type'] == "complete" ) {
				$parsed[ ( $data[$index]['tag'] ) ] = ( isset( $data[$index]['value'] ) ) ? $data[$index]['value'] : '';
				self::parse_feed( $data, $index, $parsed );

			}

			// Base case: return on closing tag
			if ( $data[$index]['type'] == "close" ) {
				return $parsed;
			}

			//$index++;
			self::parse_feed( $data, $index, $parsed );
		}

		/********** END MEDIA RETRIEVAL & STORAGE *****************/

		/*********************************************
		 * SANITIZATION & VALIDATION, HELPERS
		 *********************************************
		 */

		/**
		 * Sanitizes and validates shortcode attributes
		 *
		 * @uses Revostock_Gallery::trim_r()
		 *
		 * @static
		 * @param $input
		 * @return array
		 */
		static function sanitize_validate_input( $input ) {
			// In case not trimmed
			$input = self::trim_r( $input );

			$sane = array(
				'file' 			=> FILTER_SANITIZE_STRING, // Valid: int?
				'mediabox' 		=> FILTER_SANITIZE_STRING, // Valid: int?
				'producer' 		=> FILTER_SANITIZE_STRING, // Valid: int?
				'search_terms'	=> FILTER_SANITIZE_STRING, // Valid: strings
				'type' 			=> FILTER_SANITIZE_STRING, // Values: all, video, audio, aftereffects
				'group' 		=> FILTER_SANITIZE_STRING, // Values: newest, most_downloaded, editors_choice
				'file_info' 	=> FILTER_SANITIZE_STRING, // Values: all, description, thumbnail, content_type, TODO asset_specifics
				'css_prefix' 	=> FILTER_SANITIZE_STRING, // Valid type: string
				'color_scheme'	=> FILTER_SANITIZE_STRING, // Values: grey, blackandwhite, red, blue

				// These two are not only sanitized, but validated here for efficiency's sake
				'max_results' => array(
					'filter'  => FILTER_VALIDATE_INT,
					'options' => array( 'min_range' => 0, 'max_range' => 40 )
				), // FILTER_SANITIZE_NUMBER_INT, // Values: 0-40
				'columns' 	  => array(
					'filter'  => FILTER_VALIDATE_INT,
					'options' => array( 'min_range' => 0, 'max_range' => 10 )
				), // FILTER_SANITIZE_NUMBER_INT, // Values: 0-10
			);

			$sanitized = filter_var_array( $input, $sane );

			// Validate is complicated
			foreach ( $sanitized as $key => $value ) {
				if ( ! $value ) {
					$validated[$key] = $value;  // preserves empty string, false, and null
				} else {
					switch ( $key ){
						case 'file':
							// TODO Is there a valid set?
							$validated['file'] = $value;
							break;
						case 'mediabox':
							// TODO Is there a valid set?
							$validated['mediabox'] = $value;
							break;
						case 'producer':
							// TODO Is there a valid set?
							$validated['producer'] = $value;
							break;
						case 'search_terms':
							$validated['search_terms'] = $value;
							break;
						case 'type':
							$allowed = array( 'all', 'video', 'audio', 'aftereffects', 'ae', 'motion' );
							$validated['type'] = ( ( $index = array_search( $value, $allowed ) ) !== false ) ? $allowed[$index] : false;
							if ( $validated['type'] == "aftereffects" )
								$validated['type'] = "ae";
							break;
						case 'group':
							$allowed = array( 'newest', 'most_downloaded', 'editors_choice' );
							$validated['group'] = ( ( $index = array_search( $value, $allowed ) ) !== false ) ? $allowed[$index] : false;
							break;
						case 'file_info':
							$allowed = array( 'all', 'description', 'thumbnail', 'content_type', 'asset_specifics' );
							$validated['file_info'] = ( ( $index = array_search( $value, $allowed ) ) !== false ) ? $allowed[$index] : false;
							break;
						case 'css_prefix':
							// validate further?
							$validated['css_prefix'] = $value;
							break;
						case 'color_scheme':
							$allowed = array( 'blackandwhite', 'grey', 'red', 'blue' );
							$validated['color_scheme'] = ( ( $index = array_search( $value, $allowed ) ) !== false ) ? $allowed[$index] : false;
							break;
						case 'max_results':
							// already validated in our call to filter_var_array
							$validated['max_results'] = $value;
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

		/**
		 * Helper function to trim array
		 *
		 * @static
		 * @param $array
		 * @return array|string
		 */
		static function trim_r( $array ) {
			if ( ! is_array( $array ) )
				return trim( $array );

			return array_map( array( __CLASS__, 'trim_r' ), $array );
		}

		/**
		 * Sanitizes and removes empty or invalid shortcode attributes
		 * ( primarily needed for Revostock_Gallery::output_items() )
		 *
		 * @uses Revostock_Gallery::sanitize_validate_input()
		 *
		 * @static
		 * @param $settings
		 * @return array
		 */
		static function sanitize_and_remove_empty( $settings ) {
			// Sanitize and remove empty or invalid array elements
			if ( empty( $settings ) )
				$settings = array();
			$settings = self::sanitize_validate_input( $settings );
			foreach ( $settings as $key => $value ) {
				if ( ! $value )
					unset( $settings[$key] );
			}
			return $settings;
		}

		/**
		 * Checks to make sure there are valid credentials before placing any calls to API to fetch items
		 *
		 * @uses get_option()
		 * @uses Revostock_Gallery::call_API()
		 *
		 * @static
		 * @return bool
		 */
		static function check_for_credentials() {
			$settings = get_option( 'revostock_gallery_settings' );
			if ( isset( $settings[ '_credentials' ] ) ) {
				if ( ( $settings['_credentials']['username'] != null ) && ( $settings['_credentials']['password'] != null ) ) {
					if ( self::call_API( 'validate', array(
						'username' => $settings['_credentials']['username'],
						'password' => $settings['_credentials']['password'],
					) ) ) {
						return true;
					}
					else {
						return false;
					}
				}
				else {
					return false;
				}
			}
			else {
				return false;
			}
		}

		/********** END SANITIZATION & VALIDATION, HELPERS *****************/
	}

	Revostock_Gallery::on_load();
}
