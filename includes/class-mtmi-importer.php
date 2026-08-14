<?php
defined( 'ABSPATH' ) || exit;

final class MTMI_Importer {
	public function __construct( private MTMI_API $api ) {}

	/** @return array<string,mixed>|WP_Error */
	public function preview( string $imdb_id ) {
		$data = $this->api->fetch_by_imdb_id( $imdb_id );
		return is_wp_error( $data ) ? $data : $this->normalize( $data );
	}

	/** @param array<string,bool> $options @return array<string,mixed>|WP_Error */
	public function import( int $post_id, string $imdb_id, array $options = array() ) {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error( 'forbidden', __( 'You are not allowed to edit this post.', 'movie-tv-metadata-importer' ) );
		}
		$raw = $this->api->fetch_by_imdb_id( $imdb_id );
		if ( is_wp_error( $raw ) ) return $raw;
		$data = $this->normalize( $raw );

		$defaults = array( 'title' => true, 'excerpt' => true, 'content' => false, 'poster' => true, 'overwrite_poster' => false );
		$options  = wp_parse_args( $options, $defaults );
		$update   = array( 'ID' => $post_id );
		if ( $options['title'] && '' !== $data['title'] ) $update['post_title'] = $data['title'];
		if ( $options['excerpt'] && '' !== $data['plot'] ) $update['post_excerpt'] = $data['plot'];
		if ( $options['content'] && '' !== $data['plot'] ) $update['post_content'] = $data['plot'];
		if ( count( $update ) > 1 ) {
			$result = wp_update_post( wp_slash( $update ), true );
			if ( is_wp_error( $result ) ) return $result;
		}

		$map = $this->meta_map();
		foreach ( $map as $meta_key => $data_key ) {
			if ( array_key_exists( $data_key, $data ) && '' !== $data[ $data_key ] && null !== $data[ $data_key ] ) {
				$this->update_field( $post_id, $meta_key, $data[ $data_key ] );
			}
		}
		update_post_meta( $post_id, '_mtmi_raw_response', wp_json_encode( $raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		update_post_meta( $post_id, '_mtmi_imported_at', current_time( 'mysql' ) );

		$poster_id = 0;
		if ( $options['poster'] && $data['poster'] && ( $options['overwrite_poster'] || ! has_post_thumbnail( $post_id ) ) ) {
			$poster_id = $this->import_poster( $post_id, $data['poster'], $data['title'] );
			if ( is_wp_error( $poster_id ) ) return $poster_id;
		}
		do_action( 'mtmi_after_import', $post_id, $data, $raw, $options );
		return array( 'data' => $data, 'poster_id' => (int) $poster_id );
	}

	/** @param array<string,mixed> $raw @return array<string,mixed> */
	private function normalize( array $raw ): array {
		$value = static fn( string $key ): string => isset( $raw[ $key ] ) && 'N/A' !== $raw[ $key ] ? sanitize_text_field( (string) $raw[ $key ] ) : '';
		$type = $value( 'Type' );
		$data = array(
			'imdb_id' => $value( 'imdbID' ), 'title' => $value( 'Title' ), 'original_title' => $value( 'Title' ),
			'year' => $value( 'Year' ), 'released' => $value( 'Released' ), 'runtime' => $value( 'Runtime' ),
			'genre' => $value( 'Genre' ), 'director' => $value( 'Director' ), 'writer' => $value( 'Writer' ),
			'actors' => $value( 'Actors' ), 'plot' => isset( $raw['Plot'] ) && 'N/A' !== $raw['Plot'] ? sanitize_textarea_field( (string) $raw['Plot'] ) : '',
			'language' => $value( 'Language' ), 'country' => $value( 'Country' ), 'awards' => $value( 'Awards' ),
			'poster' => filter_var( $value( 'Poster' ), FILTER_VALIDATE_URL ) ? esc_url_raw( $value( 'Poster' ) ) : '',
			'imdb_rating' => is_numeric( $value( 'imdbRating' ) ) ? min( 10, max( 0, (float) $value( 'imdbRating' ) ) ) : '',
			'imdb_votes' => preg_replace( '/[^0-9]/', '', $value( 'imdbVotes' ) ), 'metascore' => $value( 'Metascore' ),
			'box_office' => $value( 'BoxOffice' ), 'production' => $value( 'Production' ), 'website' => esc_url_raw( $value( 'Website' ) ),
			'total_seasons' => absint( $value( 'totalSeasons' ) ), 'content_type' => 'series' === $type ? 'series' : 'movie',
			'omdb_type' => $type, 'rated' => $value( 'Rated' ), 'dvd' => $value( 'DVD' ),
			'ratings' => isset( $raw['Ratings'] ) && is_array( $raw['Ratings'] ) ? array_values( $raw['Ratings'] ) : array(),
		);
		return apply_filters( 'mtmi_normalized_data', $data, $raw );
	}

	/** @return array<string,string> */
	private function meta_map(): array {
		$map = array(
			'omdb_imdb_id'=>'imdb_id', 'omdb_original_title'=>'original_title', 'omdb_year'=>'year', 'omdb_release_date'=>'released',
			'omdb_runtime'=>'runtime', 'omdb_genre'=>'genre', 'omdb_director'=>'director', 'omdb_writer'=>'writer', 'omdb_actors'=>'actors',
			'omdb_plot'=>'plot', 'omdb_language'=>'language', 'omdb_country'=>'country', 'omdb_awards'=>'awards',
			'omdb_poster_url'=>'poster', 'omdb_imdb_rating'=>'imdb_rating', 'omdb_imdb_votes'=>'imdb_votes', 'omdb_metascore'=>'metascore',
			'omdb_box_office'=>'box_office', 'omdb_production'=>'production', 'omdb_website'=>'website', 'omdb_total_seasons'=>'total_seasons',
			'omdb_content_type'=>'content_type', 'omdb_type'=>'omdb_type', 'omdb_age_rating'=>'rated', 'omdb_ratings'=>'ratings',
		);
		return apply_filters( 'mtmi_meta_map', $map );
	}

	private function update_field( int $post_id, string $key, mixed $value ): void {
		update_post_meta( $post_id, $key, $value );
	}

	/** @return int|WP_Error */
	private function import_poster( int $post_id, string $url, string $title ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$tmp = download_url( $url, 30 );
		if ( is_wp_error( $tmp ) ) return new WP_Error( 'poster_download_failed', __( 'The poster could not be downloaded.', 'movie-tv-metadata-importer' ), $tmp->get_error_message() );
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		$name = sanitize_file_name( basename( $path ) ?: sanitize_title( $title ) . '.jpg' );
		$file = array( 'name' => $name, 'tmp_name' => $tmp );
		$id = media_handle_sideload( $file, $post_id, sprintf( __( 'Poster for %s', 'movie-tv-metadata-importer' ), $title ) );
		if ( is_wp_error( $id ) ) { wp_delete_file( $tmp ); return $id; }
		update_post_meta( $id, '_wp_attachment_image_alt', $title );
		set_post_thumbnail( $post_id, $id );
		return $id;
	}
}
