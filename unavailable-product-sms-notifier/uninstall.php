<?php
/**
 * Runs when the plugin is deleted (not on deactivation).
 * Removes the custom table and all plugin options.
 */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

$table = $wpdb->prefix . 'upsn_notify_requests';
$wpdb->query( "DROP TABLE IF EXISTS {$table}" );

delete_option( 'upsn_settings' );
delete_option( 'upsn_db_version' );
