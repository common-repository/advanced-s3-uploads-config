<?php
/**
 * Plugin name: Advanced S3 Uploads Config
 * Plugin URI:  https://git.feneas.org/noplanman/advanced-s3-uploads-config
 * Description: Endpoint and bucket configuration for <a href="https://github.com/humanmade/S3-Uploads">S3 Uploads</a>.
 * Version:     1.1.0
 * Author:      Armando Lüscher
 * Author URI:  https://noplanman.ch
 * Text Domain: advanced-s3-uploads-config
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Advanced_S3_Uploads_Config
 */

/**
 * Copyright 2020 Armando Lüscher (email: armando@noplanman.ch)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
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

declare( strict_types=1 );

namespace noplanman\Advanced_S3_Uploads_Config;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', __NAMESPACE__ . '\check_dependencies', 9 );
add_action( 'admin_init', __NAMESPACE__ . '\register_settings' );
add_action( 'admin_menu', __NAMESPACE__ . '\settings_page' );

// The filter for S3 Uploads that modifies the AWS client connection.
add_filter( 's3_uploads_s3_client_params', __NAMESPACE__ . '\get_aws_client_params' );

/**
 * Make sure S3 Uploads is installed and activated.
 */
function check_dependencies() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	if ( ! is_plugin_active( 's3-uploads/s3-uploads.php' ) ) {
		remove_action( 'admin_init', __NAMESPACE__ . '\register_settings' );
		remove_action( 'admin_menu', __NAMESPACE__ . '\settings_page' );
		remove_filter( 's3_uploads_s3_client_params', __NAMESPACE__ . '\get_aws_client_params' );

		deactivate_plugins( plugin_basename( __FILE__ ) );

		add_action( 'admin_notices', __NAMESPACE__ . '\admin_notice_missing_plugin' );
		add_action( 'network_admin_notices', __NAMESPACE__ . '\admin_notice_missing_plugin' );
	}
}

/**
 * Admin notice to alert about missing S3 Uploads plugin.
 */
function admin_notice_missing_plugin() {
	?>
	<div class="notice notice-error is-dismissible">
		<p><strong><?php esc_html_e( 'S3 Uploads must be installed and activated!', 'advanced-s3-uploads-config' ); ?></strong></p>
		<p><em><?php esc_html_e( 'Advanced S3 Uploads Config has been deactivated.', 'advanced-s3-uploads-config' ); ?></em></p>
	</div>
	<?php
	unset( $_GET['activate'] );
}

/**
 * Add the "S3 Uploads Config" subpage to the "Media" menu.
 */
function settings_page() {
	add_media_page(
		__( 'S3 Uploads Config', 'advanced-s3-uploads-config' ),
		__( 'S3 Uploads Config', 'advanced-s3-uploads-config' ),
		'manage_options',
		'advanced-s3-uploads-config',
		__NAMESPACE__ . '\settings_page_render'
	);
}

/**
 * Render the "S3 Uploads Config" page.
 */
function settings_page_render(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( isset( $_GET['settings-updated'] ) ) {
		add_settings_error( 'as3uc_settings', 'settings_saved', __( 'Settings Saved', 'advanced-s3-uploads-config' ), 'updated' );
	}
	settings_errors( 'as3uc_settings' );

	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'as3uc_settings' );
			do_settings_sections( 'as3uc_settings' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * Register the setting sections and fields.
 */
function register_settings(): void {
	register_setting( 'as3uc_settings', 'as3uc_settings', [ 'sanitize_callback' => __NAMESPACE__ . '\validate_settings' ] );

	// General connection settings.
	add_settings_section( 'connection_settings', __( 'S3 Connection', 'advanced-s3-uploads-config' ), null, 'as3uc_settings' );
	add_settings_field( 'key', __( 'Key', 'advanced-s3-uploads-config' ), __NAMESPACE__ . '\key_render', 'as3uc_settings', 'connection_settings', [ 'label_for' => 'as3uc_key' ] );
	add_settings_field( 'secret', __( 'Secret', 'advanced-s3-uploads-config' ), __NAMESPACE__ . '\secret_render', 'as3uc_settings', 'connection_settings', [ 'label_for' => 'as3uc_secret' ] );
	add_settings_field( 'endpoint', __( 'Endpoint', 'advanced-s3-uploads-config' ), __NAMESPACE__ . '\endpoint_render', 'as3uc_settings', 'connection_settings', [ 'label_for' => 'as3uc_endpoint' ] );
	add_settings_field( 'region', __( 'Region', 'advanced-s3-uploads-config' ), __NAMESPACE__ . '\region_render', 'as3uc_settings', 'connection_settings', [ 'label_for' => 'as3uc_region' ] );
	add_settings_field( 'api_version', __( 'API Version', 'advanced-s3-uploads-config' ), __NAMESPACE__ . '\api_version_render', 'as3uc_settings', 'connection_settings', [ 'label_for' => 'as3uc_api_version' ] );
	add_settings_field( 'api_signature', __( 'API Signature', 'advanced-s3-uploads-config' ), __NAMESPACE__ . '\api_signature_render', 'as3uc_settings', 'connection_settings', [ 'label_for' => 'as3uc_api_signature' ] );

	// Bucket settings.
	add_settings_section( 'bucket_settings', __( 'S3 Bucket', 'advanced-s3-uploads-config' ), null, 'as3uc_settings' );
	add_settings_field( 'bucket', __( 'Bucket', 'advanced-s3-uploads-config' ), __NAMESPACE__ . '\bucket_render', 'as3uc_settings', 'bucket_settings', [ 'label_for' => 'as3uc_bucket' ] );
	add_settings_field( 'use_path_style', __( 'Use Path Style', 'advanced-s3-uploads-config' ), __NAMESPACE__ . '\use_path_style_render', 'as3uc_settings', 'bucket_settings', [ 'label_for' => 'as3uc_use_path_style' ] );
	add_settings_field( 'object_acl', __( 'Object ACL', 'advanced-s3-uploads-config' ), __NAMESPACE__ . '\object_acl_render', 'as3uc_settings', 'bucket_settings', [ 'label_for' => 'as3uc_object_acl' ] );

	// URL rewriting settings.
	add_settings_section( 'url_rewriting_settings', __( 'URL Rewriting', 'advanced-s3-uploads-config' ), null, 'as3uc_settings' );
	add_settings_field( 'replace_upload_url', __( 'Replace Upload URL', 'advanced-s3-uploads-config' ), __NAMESPACE__ . '\replace_upload_url_render', 'as3uc_settings', 'url_rewriting_settings', [ 'label_for' => 'as3uc_replace_upload_url' ] );
	add_settings_field( 'bucket_url', __( 'Bucket URL', 'advanced-s3-uploads-config' ), __NAMESPACE__ . '\bucket_url_render', 'as3uc_settings', 'url_rewriting_settings', [ 'label_for' => 'as3uc_bucket_url' ] );

	// HTTP cache control settings.
	add_settings_section( 'http_cache_control_settings', __( 'HTTP Cache Control', 'advanced-s3-uploads-config' ), null, 'as3uc_settings' );
	add_settings_field( 'http_cache_control', __( 'HTTP "Cache-Control" header', 'advanced-s3-uploads-config' ), __NAMESPACE__ . '\http_cache_control_render', 'as3uc_settings', 'http_cache_control_settings', [ 'label_for' => 'as3uc_http_cache_control' ] );
	add_settings_field( 'http_expires', __( 'HTTP "Expires" header', 'advanced-s3-uploads-config' ), __NAMESPACE__ . '\http_expires_render', 'as3uc_settings', 'http_cache_control_settings', [ 'label_for' => 'as3uc_http_expires' ] );

	// All extra settings.
	add_settings_section( 'extra_settings', __( 'Extras', 'advanced-s3-uploads-config' ), null, 'as3uc_settings' );
	add_settings_field( 'autoenable', __( 'Auto-Enable S3 Uploads', 'advanced-s3-uploads-config' ), __NAMESPACE__ . '\autoenable_render', 'as3uc_settings', 'extra_settings', [ 'label_for' => 'as3uc_autoenable' ] );
	add_settings_field( 'debug', __( 'Debug', 'advanced-s3-uploads-config' ), __NAMESPACE__ . '\debug_render', 'as3uc_settings', 'extra_settings', [ 'label_for' => 'as3uc_debug' ] );
}

/**
 * Validate and sanitize settings.
 *
 * @param array $settings All settings that are to be saved.
 *
 * @return array
 */
function validate_settings( array $settings ): array {
	foreach ( [ 'endpoint', 'bucket_url' ] as $url_key ) {
		if ( $url = $settings[ $url_key ] ?? null ) {
			$settings[ $url_key ] = esc_url_raw( untrailingslashit( $url ) );
		}
	}

	$http_expires = $settings['http_expires'] ?? null;
	if ( $http_expires && false === strtotime( $http_expires ) ) {
		add_settings_error( 'as3uc_settings', 'http_expires_invalid', __( 'HTTP "Expires" header has an invalid input', 'advanced-s3-uploads-config' ), 'error' );
	}

	return $settings;
}

/**
 * Render the "Key" input field.
 */
function key_render(): void {
	?>
	<input class="regular-text" name="as3uc_settings[key]" type="text" id="as3uc_key" value="<?php echo esc_attr( get_option( 'key' ) ); ?>">
	<?php
}

/**
 * Render the "Secret" input field.
 */
function secret_render(): void {
	?>
	<input class="regular-text" name="as3uc_settings[secret]" type="text" id="as3uc_secret" value="<?php echo esc_attr( get_option( 'secret' ) ); ?>">
	<?php
}

/**
 * Render the "Endpoint" input field.
 */
function endpoint_render(): void {
	?>
	<input class="regular-text" name="as3uc_settings[endpoint]" type="url" id="as3uc_endpoint" value="<?php echo esc_attr( get_option( 'endpoint' ) ); ?>">
	<?php
	printf( '<p class="description">%s</p>', esc_html__( 'Endpoints must be full URIs and include a scheme and host', 'advanced-s3-uploads-config' ) );
}

/**
 * Render the "Region" input field.
 */
function region_render(): void {
	?>
	<input class="regular-text" name="as3uc_settings[region]" type="text" id="as3uc_region" value="<?php echo esc_attr( get_option( 'region' ) ); ?>">
	<?php
}

/**
 * Render the "API Version" input field.
 */
function api_version_render(): void {
	?>
	<input class="regular-text" name="as3uc_settings[api_version]" type="text" id="as3uc_api_version" value="<?php echo esc_attr( get_option( 'api_version' ) ); ?>" placeholder="latest">
	<?php
	printf( '<p class="description">%s</p>', esc_html__( 'The version of the API like "2006-03-01"', 'advanced-s3-uploads-config' ) );
}

/**
 * Render the "API Signature" input field.
 */
function api_signature_render(): void {
	?>
	<input class="regular-text" name="as3uc_settings[api_signature]" type="text" id="as3uc_api_signature" value="<?php echo esc_attr( get_option( 'api_signature' ) ); ?>" placeholder="v4">
	<?php
	printf( '<p class="description">%s</p>', esc_html__( 'The signature of the API like "v4"', 'advanced-s3-uploads-config' ) );
}

/**
 * Render the "Bucket" input field.
 */
function bucket_render(): void {
	$key      = get_option( 'key' );
	$secret   = get_option( 'secret' );
	$endpoint = get_option( 'endpoint' );

	if ( empty( $key ) || empty( $secret ) || empty( $endpoint ) ) {
		printf( '<p class="description">%s</p>', esc_html__( 'Save correct credentials first to load bucket list.', 'advanced-s3-uploads-config' ) );

		return;
	}

	$buckets = get_bucket_list();

	if ( is_wp_error( $buckets ) ) {
		printf( '<p class="error">%s</p>', esc_html__( 'Failed to fetch buckets, check your connection details!', 'advanced-s3-uploads-config' ) );
		printf( '<p class="description">%s</p>', esc_html( $buckets->get_error_message() ) );

		return;
	}

	if ( empty( $buckets ) ) {
		printf( '<p class="error">%s</p>', esc_html__( 'No buckets found, please create one first.', 'advanced-s3-uploads-config' ) );

		return;
	}

	echo '<select name="as3uc_settings[bucket]" id="as3uc_bucket">';
	foreach ( $buckets as $bucket ) {
		printf(
			'<option value="%2$s"%3$s>%1$s</option>',
			esc_html( $bucket ),
			esc_attr( $bucket ),
			selected( get_option( 'bucket' ), $bucket )
		);
	}
	echo '</select>';
}

/**
 * Render the "Use Path Style" checkbox.
 */
function use_path_style_render(): void {
	?>
	<input id="as3uc_use_path_style" name="as3uc_settings[use_path_style]" type="checkbox" value="1" <?php checked( get_option( 'use_path_style' ), 1 ); ?>>
	<?php
	printf( '<p class="description">%s</p>', esc_html__( 'Check this to use "s3.endpoint/bucket" instead of "bucket.s3.endpoint"', 'advanced-s3-uploads-config' ) );
}

/**
 * Render the "Object ACL" input field.
 */
function object_acl_render(): void {
	?>
	<input class="regular-text" name="as3uc_settings[object_acl]" type="text" id="as3uc_object_acl" placeholder="public-read" value="<?php echo esc_attr( get_option( 'object_acl' ) ); ?>">
	<?php
	printf( '<p class="description">%s</p>', esc_html__( 'Check documentation of your S3 provider for available permissions', 'advanced-s3-uploads-config' ) );
}

/**
 * Render the "Replace Upload URL" checkbox.
 */
function replace_upload_url_render(): void {
	?>
	<input id="as3uc_replace_upload_url" name="as3uc_settings[replace_upload_url]" type="checkbox" value="1" <?php checked( get_option( 'replace_upload_url' ), 1 ); ?>>
	<?php
}

/**
 * Render the "Bucket URL" input field.
 */
function bucket_url_render(): void {
	?>
	<input class="regular-text" name="as3uc_settings[bucket_url]" type="url" id="as3uc_bucket_url" value="<?php echo esc_attr( get_option( 'bucket_url' ) ); ?>">
	<?php
	// translators: Placeholders are for <a> and </a> tags, to link to "URL Rewrites" in the S3 Uploads readme.
	printf( '<p class="description">%s</p>', sprintf( esc_html__( 'Serve media from a custom URL (%1$sread more%2$s). Will only come into effect if "Replace Upload URL" is checked.', 'advanced-s3-uploads-config' ), '<a href="https://github.com/humanmade/S3-Uploads#url-rewrites">', '</a>' ) );
}

/**
 * Render the "HTTP Cache Control" input field.
 */
function http_cache_control_render(): void {
	?>
	<input class="regular-text" name="as3uc_settings[http_cache_control]" type="text" id="as3uc_http_cache_control" value="<?php echo esc_attr( get_option( 'http_cache_control' ) ); ?>">
	<?php
	printf( '<p class="description">%s</p>', esc_html__( 'A plain number will result in "max-age=<seconds>"', 'advanced-s3-uploads-config' ) );
}

/**
 * Render the "HTTP Expires" input field.
 */
function http_expires_render(): void {
	?>
	<input class="regular-text" name="as3uc_settings[http_expires]" type="text" id="as3uc_http_expires" value="<?php echo esc_attr( get_option( 'http_expires' ) ); ?>">
	<?php
	// translators: Placeholders are for <a> and </a> tags, to link to PHP "strtotime" documentation.
	printf( '<p class="description">%s</p>', sprintf( esc_html__( 'Can be static or dynamic date and time, check %1$sstrtotime%2$s for allowed values', 'advanced-s3-uploads-config' ), '<a href="https://www.php.net/manual/en/function.strtotime.php">', '</a>' ) );
}

/**
 * Render the "Auto-Enable" checkbox.
 */
function autoenable_render(): void {
	?>
	<input id="as3uc_autoenable" name="as3uc_settings[autoenable]" type="checkbox" value="1" <?php checked( get_option( 'autoenable' ), 1 ); ?>>
	<?php
	printf( '<p class="description">%s</p>', esc_html__( 'If this is unchecked, S3 Uploads must be enabled explicitly with "wp s3-uploads enable" WP-CLI command', 'advanced-s3-uploads-config' ) );
}

/**
 * Render the "Debug" checkbox.
 */
function debug_render(): void {
	?>
	<input id="as3uc_debug" name="as3uc_settings[debug]" type="checkbox" value="1" <?php checked( get_option( 'debug' ), 1 ); ?>>
	<?php
	printf( '<p class="description">%s</p>', esc_html__( 'Set this if uploads are failing, to display debug output and help find the problem', 'advanced-s3-uploads-config' ) );
}

/**
 * Get the list of buckets with the saved credentials.
 *
 * @return array|\WP_Error
 */
function get_bucket_list() {
	try {
		$s3_client = \Aws\S3\S3Client::factory( get_aws_client_params( [] ) );
		$buckets   = $s3_client->listBuckets()->toArray()['Buckets'] ?? [];

		return array_column( $buckets, 'Name' );
	} catch ( \Throwable $e ) {
		return new \WP_Error( 'as3uc_list_buckets_error', $e->getMessage() );
	}
}

/**
 * Get a specific option.
 *
 * @param null|string $option  ID of option to get.
 * @param mixed       $default Override default value if option not found.
 *
 * @return mixed
 */
function get_option( $option = null, $default = null ) {
	static $options = null;

	if ( null === $options ) {
		$options = \get_option( 'as3uc_settings' );
	}

	// If no specific option is requested, return them all.
	if ( null === $option ) {
		return $options;
	}

	// Return found option value or default.
	return $options[ $option ] ?? $default;
}

/**
 * Filter S3 Uploads AWS S3 client parameters.
 *
 * @param array $params AWS S3 Factory parameters.
 *
 * @return array
 */
function get_aws_client_params( array $params ): array {
	if ( ( $key = get_option( 'key' ) ) && $secret = get_option( 'secret' ) ) {
		$params['credentials']['key']    = $key;
		$params['credentials']['secret'] = $secret;
	}

	if ( $endpoint = get_option( 'endpoint' ) ) {
		$params['endpoint'] = $endpoint;
	}
	if ( $region = get_option( 'region' ) ) {
		$params['region'] = $region;
	}
	$params['use_path_style_endpoint'] = (bool) get_option( 'use_path_style' );

	$params['version']   = get_option( 'api_version' ) ?: 'latest';
	$params['signature'] = get_option( 'api_signature' ) ?: 'v4';

	if ( (bool) get_option( 'debug', false ) ) {
		$params['debug'] = [ 'logfn' => __NAMESPACE__ . '\aws_debug_log_callback' ];
	}

	// Make sure to use WP proxy if one is set.
	if ( defined( 'WP_PROXY_HOST' ) && defined( 'WP_PROXY_PORT' ) ) {
		$proxy_auth    = '';
		$proxy_address = WP_PROXY_HOST . ':' . WP_PROXY_PORT;

		if ( defined( 'WP_PROXY_USERNAME' ) && defined( 'WP_PROXY_PASSWORD' ) ) {
			$proxy_auth = WP_PROXY_USERNAME . ':' . WP_PROXY_PASSWORD . '@';
		}

		$params['request.options']['proxy'] = $proxy_auth . $proxy_address;
	}

	return $params;
}

/**
 * Callback for the AWS client debug output.
 *
 * @todo Maybe add an option to output to a file?
 *
 * @param string $message The log message itself.
 */
function aws_debug_log_callback( string $message ): void {
	// Note: Simply outputting debug info breaks AJAX requests!
	echo esc_html( $message );
}

// Now, let's actually set the constants!
$as3uc_settings = [
	'key'                => 'S3_UPLOADS_KEY',
	'secret'             => 'S3_UPLOADS_SECRET',
	'endpoint'           => 'S3_UPLOADS_ENDPOINT',
	'region'             => 'S3_UPLOADS_REGION',
	'bucket'             => 'S3_UPLOADS_BUCKET',
	'bucket_url'         => 'S3_UPLOADS_BUCKET_URL',
	'object_acl'         => 'S3_UPLOADS_OBJECT_ACL',
	'http_cache_control' => 'S3_UPLOADS_HTTP_CACHE_CONTROL',
];
foreach ( $as3uc_settings as $as3uc_setting => $s3u_const ) {
	if ( $option = get_option( $as3uc_setting ) ) {
		defined( $s3u_const ) || define( $s3u_const, $option );
	}
}
if ( ! defined( 'S3_UPLOADS_HTTP_EXPIRES' ) && ( $http_expires = get_option( 'http_expires' ) ) ) {
	// Convert the setting into an http-date timestamp.
	$http_expires = strtotime( $http_expires );
	if ( is_numeric( $http_expires ) ) {
		define( 'S3_UPLOADS_HTTP_EXPIRES', gmdate( DATE_RFC7231, $http_expires ) );
	}
}
if ( ! defined( 'S3_UPLOADS_DISABLE_REPLACE_UPLOAD_URL' ) ) {
	define( 'S3_UPLOADS_DISABLE_REPLACE_UPLOAD_URL', ! (bool) get_option( 'replace_upload_url' ) );
}
if ( ! defined( 'S3_UPLOADS_AUTOENABLE' ) ) {
	define( 'S3_UPLOADS_AUTOENABLE', (bool) get_option( 'autoenable', false ) );
}
