<?php
/*
 * Plugin Name: Icon Font Meta Box
 * Description: Adds a metabox with a field where you can select a icons from a icomoon icon font.
 * Plugin URI: http://artgeni.us/
 * Author: ArtGenius
 * Author URI: http://artgeni.us/
 * Version: 1.0
 * License: GPL2
 * Text Domain: ifmb_text_domain
 * Domain Path: assets/languages/
 */

 /*
   	Copyright (C) 2014	Tor Morten Jensen  tormorten@tormorten.no
   	Copyright (C) 2017	Alex Costa  tormorten@tormorten.no

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
  */



  if ( ! defined( 'ABSPATH' ) ) {
	die( 'Do not access this file directly.' );
  }


  	/**
	* Plugin Directory
	**/

	defined( 'IFMB_DIR' ) or define( 'IFMB_DIR', plugin_dir_path( __FILE__ ) );
	defined( 'IFMB_URL' ) or define( 'IFMB_URL', plugin_dir_url( __FILE__ ) );



	if ( ! class_exists( 'Icon_Font_MetaBox' ) )
	{

	   /*
		*
		* Font Awesome Field Class
		* @author Tor Morten Jensen <tormorten@tormorten.no>
		*
		*/

		class Icon_Font_MetaBox {


			/**
			* The availiable icons
			* @var array
			**/
			var $icons;
			/**
				* The screen to get the field
			* @var array
			**/
			var $screens;



		/**
		* Loads up actions and translations for the plugins
		* @return void
		* @author Tor Morten Jensen <tormorten@tormorten.no>
		**/

		public function __construct()
		{
			// generate the icon array
			$this->generate_icon_array();
			// Set screens
			$this->screens = apply_filters( 'fa_post_types', get_post_types( array( 'public' => true ) ) );
			// These should only be loaded in the admin, and for users that can edit posts
			if ( is_admin() && ( current_user_can( 'edit_posts' ) || current_user_can( 'edit_pages' ) ) ) {
				// Load up the metabox
				add_action( 'add_meta_boxes', array( $this, 'metabox' ) );
				// Saves the data
				add_action( 'save_post', array( $this, 'save' ) );
				// Load up plugin styles and scripts
				add_action( 'admin_enqueue_scripts', array( $this, 'styles_and_scripts' ) );
				// Add a pretty font awesome modal
				add_action( 'admin_footer', array( $this, 'modal' ) );
			}
			// Load scripts and/or styles in the front-end
			add_action( 'wp_enqueue_scripts', array( $this, 'front_scripts' ) );
			// Include other PHP scripts
			add_action( 'init', array( $this, 'include_files' ) );
			// Add a shortcode
			add_shortcode( 'fa', array( $this, 'shortcode' ) );
		}



		/**
		* Font Awesome Shortcode
		* @param array|string $atts Shortcode attributes
		* @return string The formatted shortcode
		* @author Tor Morten Jensen <tormorten@tormorten.no>
		**/

		function shortcode( $atts )
		{

			$atts = extract( shortcode_atts( array( 'icon' => '' ), $atts ) );

			if ( ! $icon ) {
				global $post;
				$post_id = $post->ID;
				$icon = $this->retrieve( $post_id );
				if ( ! $icon ) {
					return;
				}
			}

			return '<span class="' . $icon . '"></span>';

		}



		/**
		 * Retrieve an icon from a post
		 * @param integer $post_id The post ID
		 * @param bool $format Format the output
		 * @return string The icon, either formatted as HTML, or just the name
		 * @author Tor Morten Jensen <tormorten@tormorten.no>
		 **/

		public function retrieve( $post_id = null, $format = false )
		{

			if ( ! $post_id ) {
				global $post;
				if ( ! is_object( $post ) ) {
				return;
			}

				$post_id = $post->ID;

			}

			$icon = get_post_meta( $post_id, 'fa_field_icon', true );

			if ( ! $icon ) {
				return;
			}

			if ( $format ) {
				//$output = '<i class="fa ' . $icon . '"></i>';
				$output = '<span class="' . $icon . '"></span>';
			} else {
				$output = $icon;
			}

			return $output;

		}



		/**
		 * Include other PHP scripts for the plugin
		 * @return void
		 * @author Tor Morten Jensen <tormorten@tormorten.no>
		 **/

		public function include_files()
		{

			// Files specific for the front-ned
			if ( ! is_admin() ) {
				// Load template tags (always last)
				require_once 'inc/template-tags.php';
			}

		}



		/**
		 * Loads scripts and/or styles in the front-end
		 * @return void
		 * @author Tor Morten Jensen <tormorten@tormorten.no>
		 **/

		public function front_scripts()
		{

			if ( apply_filters( 'fa_field_load_styles', true ) ) {
				wp_enqueue_style( 'icomoon-css', IFMB_URL . 'assets/css/icomoon.css' );
			}

		}



		/**
		 * Adds the icon modal
		 * @return void Echoes the modal
		 * @author Tor Morten Jensen <tormorten@tormorten.no>
		 **/

		public function modal()
		{ ?>

			<div class="fa-field-modal" id="fa-field-modal" style="display:none">

				<div class="fa-field-modal-close">&times;</div>

				<h1 class="fa-field-modal-title"><?php _e( 'Select Icon', 'ifmb_text_domain' ); ?></h1>

				<div class="fa-field-modal-icons">
				<?php if ( $this->icons ) : ?>

					<?php foreach ( $this->icons as $icon ) : ?>

					<div class="fa-field-modal-icon-holder" data-icon="<?php echo $icon['class']; ?>">

						<div class="icon">
							<span class="<?php echo $icon['class']; ?>"></span>
						</div>

						<div class="label">
							<?php echo $icon['class']; ?>
						</div>

					</div>

					<?php endforeach; ?>

				<?php endif; ?>
				</div>
			</div>

		<?php
		}



		/**
		 * Loads up styles and scripts
		 * @return void
		 * @author Tor Morten Jensen <tormorten@tormorten.no>
		 **/

		public function styles_and_scripts()
		{
			// only load the styles for eligable post types
			//if ( in_array( get_current_screen()->post_type, $this->screens ) ) {
				// load up font awesome
				wp_enqueue_style( 'fa-field-icomoon-css2', IFMB_URL . 'assets/css/icomoon.css','1.0.0' );
				// load up plugin css
				wp_enqueue_style( 'fa-field-css', IFMB_URL . 'assets/css/fa-field.css','1.0.0' );
				// load up plugin js
				wp_enqueue_script( 'fa-field-js', IFMB_URL . 'assets/js/fa-field.js', array( 'jquery' ),'1.0.0' );
			//}
		}



		/**
		 * Loads up actions and translations for the plugins
		 * @return void
		 * @author Tor Morten Jensen <tormorten@tormorten.no>
		 **/

		public function metabox()
		{
			// which screens to add the metabox to, by default all public post types are added
			//$screens = $this->screens;
			/**
			 * // change for all post types
			 **/
			$screens = get_post_types();

			foreach ( $screens as $screen ) {
				add_meta_box( 'fa_field', __( 'Icon Font', 'ifmb_text_domain' ), array(
					$this,
					'populate_metabox'
				), $screen, 'side' );
			}

		}



		/**
		 * Prints metabox content
		 * @param object $post The post object
		 * @return void Echoes the metabox contents
		 * @author Tor Morten Jensen <tormorten@tormorten.no>
		 **/

		public function populate_metabox( $post )
		{

			$icon = get_post_meta( $post->ID, 'fa_field_icon', true );
			?>

			<div class="fa-field-metabox">

				<div class="fa-field-current-icon">

					<div class="icon">

					<?php if ( $icon ) : ?>
						<span class="<?php echo $icon; ?>"></span>
					<?php endif; ?>

					</div>

					<div class="delete <?php echo $icon ? 'active' : ''; ?>">&times;</div>

				</div>

				<input type="hidden" name="fa_field_icon" id="fa_field_icon" value="<?php echo $icon; ?>">
				<?php wp_nonce_field( 'fa_field_icon', 'fa_field_icon_nonce' ); ?>

				<button class="button-primary add-fa-icon"><?php _e( 'Add Icon', 'ifmb_text_domain' ); ?></button>
			</div>

		<?php
		}



		/**
		 * Saves the data
		 * @param int $post_id The ID of the saved post
		 * @return void
		 * @author Tor Morten Jensen <tormorten@tormorten.no>
		 **/

		public function save( $post_id )
		{
			/**
			 * // change for all post types
			 **/
			/*if ( ! in_array( get_post_type( $post_id ), $this->screens ) ) {
			  return;
			}*/
			if ( isset( $_POST['fa_field_icon_nonce'] ) && ! wp_verify_nonce( $_POST['fa_field_icon_nonce'], 'fa_field_icon' ) ) {
				return;
			}

			if ( isset( $_POST['fa_field_icon'] ) ) {
				update_post_meta( $post_id, 'fa_field_icon', $_POST['fa_field_icon'] );
			}

		}



		/**
		 * Get an instance of the plugin
		 * @return object The instance
		 * @author Tor Morten Jensen <tormorten@tormorten.no>
		 **/
		public function instance()
		{

			return new self();

		}



		/**
		 * Generates an array of all icons in Font Awesome by reading it from the file and then storing it in the database.
		 * @return void
		 * @author Tor Morten Jensen <tormorten@tormorten.no>
		 **/

		private function generate_icon_array()
		{

			$icons = get_option( 'ifmb_icons' );
			if ( ! $icons ) {
				$pattern = '/\.(icomoon-(?:\w+(?:-)?)+):before\s+{\s*content:\s*"(.+)";\s+}/';
				$subject = file_get_contents( IFMB_DIR . 'assets/css/icomoon.css' );
				preg_match_all( $pattern, $subject, $matches, PREG_SET_ORDER );
				$icons = array();
				foreach ( $matches as $match ) {
					$icons[] = array( 'css' => $match[2], 'class' => stripslashes( $match[1] ) );
			}

			update_option( 'ifmb_icons', $icons );

			}

			$this->icons = $icons;

		}

	} // END class Icon_Font_MetaBox



	/**
	 * Add an instance of our plugin to WordPress
	 **/

	add_action( 'plugins_loaded', array( 'Icon_Font_MetaBox', 'instance' ) );


}