<?php
/**
 * Plugin Name: REVELA Contratos
 * Description: Gestão profissional de contratos fotográficos — Gutenberg + REST + React
 * Version: 3.0.0
 * Author: Djoni Silveira
 * Text Domain: revela-contratos
 * Requires PHP: 8.1
 * Requires WP: 6.3
 * Requires at least: 6.3
 * Tested up to: 6.6
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

define( 'REVELA_VERSION', '3.0.0' );
define( 'REVELA_DIR', __DIR__ );
define( 'REVELA_URL', plugin_dir_url( __FILE__ ) );
define( 'REVELA_PATH', plugin_dir_path( __FILE__ ) );
define( 'REVELA_SRC', REVELA_DIR . '/src' );

spl_autoload_register( static function ( string $class ): void {
	$prefix = 'Revela\\';
	if ( ! str_starts_with( $class, $prefix ) ) {
		return;
	}

	$relative = str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) );
	$file = REVELA_SRC . '/' . $relative . '.php';

	if ( file_exists( $file ) ) {
		require_once $file;
		return;
	}

	$fallback = str_replace( ['Blocks/', 'ClientForm/'], ['blocks/', 'client-form/'], $relative );
	$file_fallback = REVELA_SRC . '/' . $fallback . '.php';
	if ( file_exists( $file_fallback ) ) {
		require_once $file_fallback;
	}
}, true, true );

function revela_boot(): void {
	load_plugin_textdomain( 'revela-contratos', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	Revela\Services\SettingsService::instance();
	Revela\Services\ContractService::instance();
	Revela\Services\EmailService::instance();
	Revela\Services\SecurityService::instance();

	Revela\Models\Package::register();
	Revela\Models\Proposal::register();

	Revela\REST\Router::register();

	if ( is_admin() ) {
		Revela\Blocks\PackageBlock::register();
		Revela\Blocks\ProposalBlock::register();
		Revela\Admin\Assets::enqueue_block_assets();
		Revela\Admin\SettingsPage::register();
		Revela\Admin\Notices::register();
	}

	add_action( 'wp_enqueue_scripts', [ Revela\Admin\Assets::class, 'enqueue_frontend_assets' ] );
	add_shortcode( 'revela_proposta', [ Revela\Blocks\ClientFormShortcode::class, 'render' ] );

	if ( ! wp_next_scheduled( 'revela_cleanup_expired' ) ) {
		wp_schedule_event( time(), 'daily', 'revela_cleanup_expired' );
	}
	add_action( 'revela_cleanup_expired', [ Revela\Services\CleanupService::class, 'run' ] );
}
add_action( 'plugins_loaded', 'revela_boot' );

function revela_activate(): void {
	Revela\Models\Package::register();
	Revela\Models\Proposal::register();
	flush_rewrite_rules();

	if ( false === get_option( 'revela_settings' ) ) {
		add_option( 'revela_settings', Revela\Services\SettingsService::get_defaults(), '', 'no' );
	}

	$upload_dir = wp_upload_dir();
	$contracts_dir = $upload_dir['basedir'] . '/revela-contratos';
	if ( ! file_exists( $contracts_dir ) ) {
		wp_mkdir_p( $contracts_dir );
		$htaccess = $contracts_dir . '/.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			file_put_contents( $htaccess, "Deny from all\n" );
		}
	}
}
register_activation_hook( __FILE__, 'revela_activate' );

function revela_deactivate(): void {
	wp_clear_scheduled_hook( 'revela_cleanup_expired' );
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'revela_deactivate' );

function revela_uninstall(): void {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	delete_option( 'revela_settings' );
	delete_option( 'revela_seeded' );

	$posts = get_posts( [
		'post_type'      => [ 'revela_package', 'revela_proposal' ],
		'numberposts'    => -1,
		'post_status'    => 'any',
		'fields'         => 'ids',
	] );
	foreach ( $posts as $post_id ) {
		wp_delete_post( $post_id, true );
	}
}
register_uninstall_hook( __FILE__, 'revela_uninstall' );