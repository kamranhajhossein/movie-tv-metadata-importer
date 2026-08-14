<?php
defined( 'ABSPATH' ) || exit;

final class MTMI_Admin {
	public function __construct( private MTMI_Importer $importer ) {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'settings' ) );
		add_action( 'add_meta_boxes', array( $this, 'meta_box' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'wp_ajax_mtmi_preview', array( $this, 'ajax_preview' ) );
		add_action( 'wp_ajax_mtmi_import', array( $this, 'ajax_import' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( MTMI_FILE ), array( $this, 'action_links' ) );
	}

	public function menu(): void { add_options_page( 'Movie & TV Importer', 'Movie & TV Importer', 'manage_options', 'movie-tv-metadata-importer', array( $this, 'settings_page' ) ); }
	public function settings(): void {
		register_setting( 'mtmi', 'mtmi_api_key', array( 'type'=>'string', 'sanitize_callback'=>array( $this, 'sanitize_api_key' ), 'default'=>'', 'show_in_rest'=>false ) );
		register_setting( 'mtmi', 'mtmi_post_types', array( 'type'=>'array', 'sanitize_callback'=>array( $this, 'sanitize_post_types' ), 'default'=>array( 'post' ) ) );
	}
	/** @return string[] */
	public function sanitize_post_types( mixed $value ): array {
		$allowed = array_keys( get_post_types( array( 'show_ui'=>true ), 'names' ) );
		return array_values( array_intersect( $allowed, array_map( 'sanitize_key', (array) $value ) ) );
	}
	public function sanitize_api_key( mixed $value ): string {
		$value = sanitize_text_field( (string) $value );
		return '' !== $value ? $value : (string) get_option( 'mtmi_api_key', '' );
	}
	public function settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) return;
		$has_key = '' !== (string) get_option( 'mtmi_api_key', '' );
		$selected = (array) get_option( 'mtmi_post_types', array( 'post' ) );
		$post_types = get_post_types( array( 'show_ui'=>true ), 'objects' );
		?>
		<div class="wrap mtmi-settings"><h1><?php esc_html_e( 'Movie & TV Metadata Importer for OMDb', 'movie-tv-metadata-importer' ); ?></h1>
		<p><?php esc_html_e( 'Import movie and TV metadata into any public WordPress post type. No theme or field plugin is required.', 'movie-tv-metadata-importer' ); ?></p>
		<form method="post" action="options.php"><?php settings_fields( 'mtmi' ); ?>
		<table class="form-table"><tr><th><label for="mtmi_api_key">OMDb API Key</label></th><td><input type="password" class="regular-text" id="mtmi_api_key" name="mtmi_api_key" value="" placeholder="<?php echo esc_attr( $has_key ? __( 'API key is configured', 'movie-tv-metadata-importer' ) : __( 'Enter API key', 'movie-tv-metadata-importer' ) ); ?>" autocomplete="new-password"><p class="description"><a href="https://www.omdbapi.com/apikey.aspx" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get an OMDb API key', 'movie-tv-metadata-importer' ); ?></a>. <?php esc_html_e( 'Leave blank to keep the saved key.', 'movie-tv-metadata-importer' ); ?></p></td></tr>
		<tr><th><?php esc_html_e( 'Enabled post types', 'movie-tv-metadata-importer' ); ?></th><td><?php foreach ( $post_types as $pt ) : ?><label style="display:block;margin-bottom:6px"><input type="checkbox" name="mtmi_post_types[]" value="<?php echo esc_attr( $pt->name ); ?>" <?php checked( in_array( $pt->name, $selected, true ) ); ?>> <?php echo esc_html( $pt->labels->singular_name ); ?> <code><?php echo esc_html( $pt->name ); ?></code></label><?php endforeach; ?></td></tr></table>
		<?php submit_button(); ?></form></div><?php
	}
	public function meta_box(): void {
		foreach ( (array) get_option( 'mtmi_post_types', array( 'post' ) ) as $type ) add_meta_box( 'mtmi-importer', 'Movie & TV Metadata Importer', array( $this, 'render_meta_box' ), $type, 'side', 'high' );
	}
	public function render_meta_box( WP_Post $post ): void {
		$imdb = (string) get_post_meta( $post->ID, 'omdb_imdb_id', true );
		wp_nonce_field( 'mtmi_ajax', 'mtmi_nonce' ); ?>
		<div id="mtmi-box" data-post-id="<?php echo esc_attr( (string) $post->ID ); ?>">
		<label for="mtmi-id"><strong><?php esc_html_e( 'IMDb ID', 'movie-tv-metadata-importer' ); ?></strong></label>
		<input id="mtmi-id" type="text" class="widefat" value="<?php echo esc_attr( $imdb ); ?>" placeholder="tt0133093" dir="ltr">
		<div class="mtmi-actions"><button type="button" class="button" id="mtmi-preview"><?php esc_html_e( 'Preview', 'movie-tv-metadata-importer' ); ?></button><button type="button" class="button button-primary" id="mtmi-import"><?php esc_html_e( 'Import & Fill', 'movie-tv-metadata-importer' ); ?></button></div>
		<details><summary><?php esc_html_e( 'Import options', 'movie-tv-metadata-importer' ); ?></summary>
		<label><input type="checkbox" data-option="title" checked> <?php esc_html_e( 'Set post title', 'movie-tv-metadata-importer' ); ?></label>
		<label><input type="checkbox" data-option="excerpt" checked> <?php esc_html_e( 'Set post excerpt', 'movie-tv-metadata-importer' ); ?></label>
		<label><input type="checkbox" data-option="content"> <?php esc_html_e( 'Replace post content with plot', 'movie-tv-metadata-importer' ); ?></label>
		<label><input type="checkbox" data-option="poster" checked> <?php esc_html_e( 'Import featured image', 'movie-tv-metadata-importer' ); ?></label>
		<label><input type="checkbox" data-option="overwrite_poster"> <?php esc_html_e( 'Replace existing featured image', 'movie-tv-metadata-importer' ); ?></label></details>
		<div id="mtmi-result" aria-live="polite"></div></div><?php
	}
	public function assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php', 'settings_page_movie-tv-metadata-importer' ), true ) ) return;
		wp_enqueue_style( 'mtmi-admin', MTMI_URL . 'assets/admin.css', array(), MTMI_VERSION );
		if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			wp_enqueue_script( 'mtmi-admin', MTMI_URL . 'assets/admin.js', array(), MTMI_VERSION, true );
			wp_localize_script( 'mtmi-admin', 'MTMI', array( 'ajaxUrl'=>admin_url( 'admin-ajax.php' ), 'working'=>__( 'Fetching data…', 'movie-tv-metadata-importer' ), 'error'=>__( 'Something went wrong.', 'movie-tv-metadata-importer' ), 'done'=>__( 'Metadata imported successfully. Refresh the editor to see all changes.', 'movie-tv-metadata-importer' ) ) );
		}
	}
	private function verify_ajax(): void { check_ajax_referer( 'mtmi_ajax', 'nonce' ); if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( array( 'message'=>__( 'Unauthorized request.', 'movie-tv-metadata-importer' ) ), 403 ); }
	public function ajax_preview(): void { $this->verify_ajax(); $result = $this->importer->preview( sanitize_text_field( wp_unslash( $_POST['imdb_id'] ?? '' ) ) ); $this->send( $result ); }
	public function ajax_import(): void {
		$this->verify_ajax(); $post_id = absint( $_POST['post_id'] ?? 0 );
		$options = array(); foreach ( array( 'title','excerpt','content','poster','overwrite_poster' ) as $key ) $options[$key] = ! empty( $_POST['options'][$key] );
		$result = $this->importer->import( $post_id, sanitize_text_field( wp_unslash( $_POST['imdb_id'] ?? '' ) ), $options ); $this->send( $result );
	}
	private function send( mixed $result ): void { if ( is_wp_error( $result ) ) wp_send_json_error( array( 'message'=>$result->get_error_message(), 'code'=>$result->get_error_code() ) ); wp_send_json_success( $result ); }
	/** @param string[] $links @return string[] */
	public function action_links( array $links ): array { array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=movie-tv-metadata-importer' ) ) . '">' . esc_html__( 'Settings', 'movie-tv-metadata-importer' ) . '</a>' ); return $links; }
}
