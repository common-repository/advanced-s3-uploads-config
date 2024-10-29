<?php
/**
 * Delete all traces of the plugin on uninstall.
 *
 * @package Advanced_S3_Uploads_Config
 */

declare( strict_types=1 );

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! current_user_can( 'activate_plugins' ) ) {
	return;
}

check_admin_referer( 'bulk-plugins' );

delete_option( 'as3uc_settings' );
