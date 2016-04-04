<?php
/**
 * Plugin Name: XTEC MultiSite Manager
 * Plugin URI: https://github.com/projectestac/wordpress-xtec-ms-manager
 * Description: Manage blogs in MultiSite configuration of WordPress
 * Version: 1.0.0
 * Author: Toni Ginard
 * Author URI: http://agora.xtec.cat/nodes
 * License: GPLv3
 */

// If this file is called directly, abort.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

include_once 'session.php'; // Activate PHP sessions in XMM
include_once 'includes/lib.php'; // Plugin general lib

register_activation_hook( __FILE__, 'xmm_install' );
function xmm_install() {
	// Actions executed during plugin activation go here
}

// Initialization tasks
add_action( 'init', 'xmm_init' );
function xmm_init() {
	load_plugin_textdomain( 'xmm', false, basename( dirname( __FILE__ ) ) . '/languages' );

	// This plugin is only for multisite version
	if ( is_multisite() ) {
		add_action( 'network_admin_menu', 'xmm_network_admin_menu' );
	}
}

// Create plugin menu
function xmm_network_admin_menu() {
	add_menu_page( __( 'Multisite Manager', 'xmm' ), __( 'Multisite Manager', 'xmm' ), 'manage_network_options', 'xmm', 'xmm_main', 'dashicons-analytics', 99 );
	add_submenu_page( 'xmm', __( 'SQL', 'xmm' ), __( 'SQL', 'xmm' ), 'manage_network_options', 'xmm-sql', 'xmm_sql' );
	add_submenu_page( 'xmm', __( 'Requests', 'xmm' ), __( 'Requests', 'xmm' ), 'manage_network_options', 'xmm-requests', 'xmm_requests' );
}

add_action( 'admin_print_styles', 'xmm_scripts' );
function xmm_scripts() {
	wp_enqueue_style( 'admin', plugins_url( 'css/admin.css', __FILE__ ) );
}

// Load functionalities that are used, only those
if ( isset( $_GET[ 'page' ] ) && 'xmm' == $_GET['page'] ) {
    include_once 'includes/main.php';
}

if ( isset( $_GET[ 'page' ] ) && 'xmm-sql' == $_GET['page'] ) {
    include_once 'includes/sql-exec.php';
}

if ( isset( $_GET[ 'page' ] ) && 'xmm-requests' == $_GET['page'] ) {
    include_once 'includes/requests.php';
}
