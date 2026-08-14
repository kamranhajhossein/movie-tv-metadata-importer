<?php
defined( 'ABSPATH' ) || exit;

final class MTMI_Plugin {
	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		if ( is_admin() ) {
			new MTMI_Admin( new MTMI_Importer( new MTMI_API() ) );
		}
	}

	public function load_textdomain(): void {
		load_plugin_textdomain( 'movie-tv-metadata-importer', false, dirname( plugin_basename( MTMI_FILE ) ) . '/languages' );
	}
}
