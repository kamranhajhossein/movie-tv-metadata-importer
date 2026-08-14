<?php
defined( 'ABSPATH' ) || exit;

final class MTMI_API {
	private const ENDPOINT = 'https://www.omdbapi.com/';

	/** @return array<string,mixed>|WP_Error */
	public function fetch_by_imdb_id( string $imdb_id ) {
		$imdb_id = strtolower( trim( $imdb_id ) );
		if ( ! preg_match( '/^tt\d{7,10}$/', $imdb_id ) ) {
			return new WP_Error( 'invalid_imdb_id', __( 'Invalid IMDb ID. Example: tt0133093', 'movie-tv-metadata-importer' ) );
		}

		$api_key = trim( (string) get_option( 'mtmi_api_key', '' ) );
		if ( '' === $api_key ) {
			return new WP_Error( 'missing_api_key', __( 'Save your OMDb API key in the plugin settings first.', 'movie-tv-metadata-importer' ) );
		}

		$url = add_query_arg(
			array( 'apikey' => $api_key, 'i' => $imdb_id, 'plot' => 'full', 'r' => 'json' ),
			self::ENDPOINT
		);
		$response = wp_safe_remote_get( $url, array( 'timeout' => 20, 'redirection' => 2, 'user-agent' => 'Movie-TV-Metadata-Importer/' . MTMI_VERSION . '; ' . home_url( '/' ) ) );
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'request_failed', __( 'Could not connect to OMDb.', 'movie-tv-metadata-importer' ), $response->get_error_message() );
		}
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'http_error', sprintf( __( 'OMDb returned HTTP status %d.', 'movie-tv-metadata-importer' ), wp_remote_retrieve_response_code( $response ) ) );
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'invalid_json', __( 'The OMDb response could not be parsed.', 'movie-tv-metadata-importer' ) );
		}
		if ( isset( $data['Response'] ) && 'False' === $data['Response'] ) {
			return new WP_Error( 'omdb_error', sanitize_text_field( (string) ( $data['Error'] ?? __( 'Title not found.', 'movie-tv-metadata-importer' ) ) ) );
		}
		return $data;
	}
}
