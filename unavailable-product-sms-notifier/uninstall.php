<?php
/**
 * Runs when the plugin is deleted (not on deactivation).
 *
 * The requests table (and its db version option) is kept on purpose: it is the
 * history behind the reports, so deleting the plugin must never destroy it.
 * Only the settings are removed, because they contain SMS gateway credentials.
 */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'upsn_settings' );
