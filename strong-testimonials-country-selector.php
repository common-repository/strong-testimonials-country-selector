<?php
/**
 * Plugin Name: Strong Testimonials Country Selector
 * Plugin URI: https://strongplugins.com/plugins/strong-testimonials-country-selector/
 * Description: Add a country selector to your Strong Testimonials form.
 * Author: MachoThemes
 * Version: 1.1
 * Author URI: https://strongplugins.com/
 * Text Domain: strong-testimonials-country-selector
 * Domain Path: /languages
 * Requires: 3.7 or higher
 * License: GPLv2 or later
 *
 * Copyright 2017-2018 MachoThemes office@machothemes.com
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WPMTST_COUNTRY_SELECTOR_VERSION', '1.1' );
define( 'WPMTST_COUNTRY_SELECTOR_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPMTST_COUNTRY_SELECTOR_URL', plugin_dir_url( __FILE__ ) );


/**
 * Class Strong_Testimonials_Country_Selector
 *
 * A single class for simplicity.
 */
class Strong_Testimonials_Country_Selector {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// We are instantiating on plugins_loaded so let's load the text domain here too
		$this->load_textdomain();

		// Include our custom field.
		add_filter( 'wpmtst_fields', array( $this, 'fields_filter' ) );
		add_action( 'wpmtst_custom_field_country_selector_input', array( $this, 'field_input' ), 10, 2 );
		add_filter( 'wpmtst_custom_field_country_selector_output', array( $this, 'field_output' ), 10, 2 );
		add_filter( 'wpmtst_notification_custom_field_value', array( $this, 'notification_field_value' ), 10, 3 );

		// Selectize on the front end.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_resources' ) );
		add_filter( 'wpmtst_scripts', array( $this, 'add_script' ) );
		add_filter( 'wpmtst_styles', array( $this, 'add_style' ) );

		// Selectize in admin.
		add_action( 'load-post.php', array( $this, 'admin_load_post' ) );
		add_action( 'load-post-new.php', array( $this, 'admin_load_post' ) );
	}

	/**
	 * Load text domain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'strong-testimonials-country-selector', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Register resources.
	 */
	public function register_resources() {
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_style( 'stcs-selectize',
		                   WPMTST_COUNTRY_SELECTOR_URL . "assets/vendor/selectize/css/selectize.custom{$min}.css",
		                   array(),
		                   WPMTST_COUNTRY_SELECTOR_VERSION );

		wp_register_script( 'stcs-selectize',
		                    WPMTST_COUNTRY_SELECTOR_URL . "assets/vendor/selectize/js/selectize{$min}.js",
		                    array( 'jquery' ),
		                    WPMTST_COUNTRY_SELECTOR_VERSION,
		                    true );
	}

	/**
	 * Enqueue resources (admin).
	 */
	public function enqueue_resources() {
		wp_enqueue_style( 'stcs-selectize' );
		wp_enqueue_script( 'stcs-selectize' );
	}

	/**
	 * On Add/Edit post screen.
	 */
	public function admin_load_post() {
		if ( function_exists( 'wpmtst_is_testimonial_screen' ) && wpmtst_is_testimonial_screen() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'register_resources' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_resources' ) );
			add_action( 'admin_print_footer_scripts', array( $this, 'selectize_init' ) );
		}
	}

	/**
	 * Initialize Selectize.
	 */
	public function selectize_init() {
		?>
		<script type="text/javascript">
          (function ($) { $('#wpmtst_country').selectize() })(jQuery)
		</script>
		<?php
	}

	/**
	 * Add script to internal queue (front-end).
	 *
	 * @param $scripts
	 *
	 * @return mixed
	 */
	public function add_script( $scripts ) {
		$scripts['stcs-selectize'] = 'stcs-selectize';
		add_action( 'wp_footer', array( $this, 'selectize_init' ), 20 );

		return $scripts;
	}

	/**
	 * Add style to internal queue (front-end).
	 *
	 * @param $styles
	 *
	 * @return mixed
	 */
	public function add_style( $styles ) {
		$styles['stcs-selectize'] = 'stcs-selectize';

		return $styles;
	}

	/**
	 * Add our custom field.
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	public function fields_filter( $fields ) {
		$field_options = get_option( 'wpmtst_fields' );
		if ( isset( $field_options['field_base'] ) ) {
			$base = $field_options['field_base'];
		}
		else {
			$base = Strong_Testimonials_Defaults::get_field_base();
		}

		$new_field = array(
			'input_type'              => 'country-selector',
			'action_input'            => 'wpmtst_custom_field_country_selector_input',
			'action_output'           => 'wpmtst_custom_field_country_selector_output',
			'option_label'            => __( 'country selector', 'strong-testimonials-country-selector' ),
			'show_default_options'    => 0,
			'placeholder'             => __( 'Select a country or start typing&hellip;', 'strong-testimonials-country-selector' ),
			'show_placeholder_option' => 1,
			'show_admin_table_option' => 1,
			'name_mutable'            => 1,
		);

		$fields['field_types']['optional']['country-selector'] = array_merge( $base, $new_field );

		return $fields;
	}

	/**
	 * Get the country name.
	 *
	 * @param string $country_code
	 * @since 1.1
	 *
	 * @return string
	 */
	public function get_country( $country_code = '' ) {
		if ( $country_code ) {
			require_once path_join( WPMTST_COUNTRY_SELECTOR_DIR, 'includes/countries.php' );
			$countries = apply_filters( 'strong_testimonials_countries', wpmtst_countries() );
			if ( isset( $countries[ $country_code ] ) ) {
				return __( $countries[ $country_code ], 'strong-testimonials-country-selector' );
			}
		}

		return '';
	}

	/**
	 * Callback to print input HTML both front and back.
	 *
	 * @param $field
	 * @param $value
	 */
	public function field_input( $field, $value ) {
		if ( is_admin() ) {
			$css_class = 'class="custom-input"';
			$name      = 'custom[' . $field['name'] . ']';
		}
		else {
			$css_class = '';
			$name      = $field['name'];
		}

		require_once path_join( WPMTST_COUNTRY_SELECTOR_DIR, 'includes/countries.php' );
		$countries = apply_filters( 'strong_testimonials_countries', wpmtst_countries() );

		ob_start();
		printf( '<select id="wpmtst_%s" name="%s" placeholder="%s" %s>', $field['name'], $name, $field['placeholder'], $css_class );
		printf( '<option value="" %s></option>', selected( $value, '', false ) );
		foreach ( $countries as $key => $country ) {
			printf( '<option value="%s" %s>%s</option>', $key, selected( $value, $key, false ), __( $country, 'strong-testimonials-country-selector' ) );
		}
		echo '</select>';
		echo ob_get_clean();
	}

	/**
     * Callback to print HTML output.
     *
	 * @param $field
	 * @param $value
     *
     * @return string
	 */
	public function field_output( $field, $value ) {
        return $this->get_country( $value );
	}

	/**
     * Return the country name for notification emails.
     *
	 * @param $replace string
	 * @param $field   array
	 * @param $post    array
	 * @since 1.1
     *
	 * @return string
	 */
	public function notification_field_value( $replace, $field, $post ) {
	    if ( 'country-selector' == $field['input_type'] ) {
		    if ( isset( $post[ $field['name'] ] ) && $post[ $field['name'] ] ) {
			    $replace = $this->get_country( $post[ $field['name'] ] );
			    $replace .= ' (' . $post[ $field['name'] ] . ')';
		    }
	    }

		return $replace;
	}
}


/**
 * Check main plugin for minimum required version.
 */
function strong_testimonials_country_selector_load() {
	if ( defined( 'WPMTST_VERSION' ) && version_compare( WPMTST_VERSION, '2.28.3', '>=' ) ) {
		new Strong_Testimonials_Country_Selector();
	}
}

add_action( 'plugins_loaded', 'strong_testimonials_country_selector_load' );
