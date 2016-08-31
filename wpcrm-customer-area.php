<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://syllogic.in
 * @since             1.0.0
 * @package           Wpcrm_Customer_Area
 *
 * @wordpress-plugin
 * Plugin Name:       WP-CRM Customer Area Extension
 * Plugin URI:        http://wordpress.syllogic.in
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Aurovrata V.
 * Author URI:        http://syllogic.in
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpcrm-customer-area
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wpcrm-customer-area-activator.php
 */
function activate_wpcrm_customer_area() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpcrm-customer-area-activator.php';
	Wpcrm_Customer_Area_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wpcrm-customer-area-deactivator.php
 */
function deactivate_wpcrm_customer_area() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpcrm-customer-area-deactivator.php';
	Wpcrm_Customer_Area_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wpcrm_customer_area' );
register_deactivation_hook( __FILE__, 'deactivate_wpcrm_customer_area' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wpcrm-customer-area.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wpcrm_customer_area() {

	$plugin = new Wpcrm_Customer_Area();
	$plugin->run();
}
run_wpcrm_customer_area();
