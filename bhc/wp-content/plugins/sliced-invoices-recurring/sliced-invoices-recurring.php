<?php
/**
 * @wordpress-plugin
 * Plugin Name:       Sliced Invoices Recurring
 * Plugin URI:        https://slicedinvoices.com/extensions/recurring-invoices/
 * Description:       Create recurring invoices with the click of a button. Requirements: The Sliced Invoices Plugin
 * Version:           2.5.0
 * Author:            Sliced Invoices
 * Author URI:        https://slicedinvoices.com/
 * Text Domain:       sliced-invoices-recurring
 * Domain Path:       /languages
 *
 * -------------------------------------------------------------------------------
 * Copyright © 2022 Sliced Software, LLC.  All rights reserved.
 * This software may not be resold, redistributed or otherwise conveyed to a third party.
 * -------------------------------------------------------------------------------
 */

if ( ! defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}

define( 'SLICED_INVOICES_RECURRING_VERSION', '2.5.0' );
define( 'SLICED_INVOICES_RECURRING_DB_VERSION', '2' );
define( 'SLICED_INVOICES_RECURRING_FILE', __FILE__ );
define( 'SLICED_INVOICES_RECURRING_PATH', plugin_dir_path( __FILE__ ) );

require_once( SLICED_INVOICES_RECURRING_PATH . 'admin/includes/class-sliced-invoices-recurring-admin.php' );
require_once( SLICED_INVOICES_RECURRING_PATH . 'includes/class-sliced-recurring.php' );
require_once( SLICED_INVOICES_RECURRING_PATH . 'includes/sliced-invoices-recurring-deactivate.php' );
require_once( SLICED_INVOICES_RECURRING_PATH . 'includes/sliced-invoices-recurring-update.php' );
require_once( SLICED_INVOICES_RECURRING_PATH . 'updater/plugin-updater.php' );


/**
 * Make it so...
 */
function sliced_invoices_recurring_init() {
	Sliced_Recurring::get_instance();
	do_action( 'sliced_invoices_recurring_loaded' );
}
add_action( 'init', 'sliced_invoices_recurring_init' );
