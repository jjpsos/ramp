<?php
/**
 * @wordpress-plugin
 * Plugin Name:       Sliced Invoices Client Area
 * Plugin URI:        https://slicedinvoices.com/extensions/client-area/
 * Description:       Creates a secure area for clients to view their invoices and quotes. Requirements: The Sliced Invoices Plugin
 * Version:           1.7.0
 * Author:            Sliced Invoices
 * Author URI:        https://slicedinvoices.com/
 * Text Domain:       sliced-invoices-client-area
 * Domain Path:       /languages
 *
 * -------------------------------------------------------------------------------
 * Copyright © 2022 Sliced Software, LLC.  All rights reserved.
 * This software may not be resold, redistributed or otherwise conveyed to a third party.
 * -------------------------------------------------------------------------------
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Initialize
 */
define( 'SLICED_INVOICES_CLIENT_AREA_VERSION', '1.7.0' );
define( 'SLICED_INVOICES_CLIENT_AREA_DB_VERSION', '2' );
define( 'SLICED_INVOICES_CLIENT_AREA_FILE', __FILE__ );
define( 'SLICED_INVOICES_CLIENT_AREA_PATH', plugin_dir_path( __FILE__ ) );

require_once( SLICED_INVOICES_CLIENT_AREA_PATH . 'admin/includes/class-sliced-invoices-client-area-admin.php' );
require_once( SLICED_INVOICES_CLIENT_AREA_PATH . 'includes/class-sliced-client-area.php' );
require_once( SLICED_INVOICES_CLIENT_AREA_PATH . 'includes/class-sliced-edit-profile.php' );
require_once( SLICED_INVOICES_CLIENT_AREA_PATH . 'includes/class-sliced-login-register.php' );
require_once( SLICED_INVOICES_CLIENT_AREA_PATH . 'includes/sliced-invoices-client-area-activate.php' );
require_once( SLICED_INVOICES_CLIENT_AREA_PATH . 'includes/sliced-invoices-client-area-deactivate.php' );
require_once( SLICED_INVOICES_CLIENT_AREA_PATH . 'includes/sliced-invoices-client-area-global-functions.php' );
require_once( SLICED_INVOICES_CLIENT_AREA_PATH . 'includes/sliced-invoices-client-area-update.php' );
require_once( SLICED_INVOICES_CLIENT_AREA_PATH . 'updater/plugin-updater.php' );


/**
 * Make it so...
 */
function sliced_invoices_client_area_init() {
	Sliced_Client_Area::get_instance();
}
add_action( 'init', 'sliced_invoices_client_area_init' );
