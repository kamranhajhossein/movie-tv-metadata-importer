# Movie & TV Metadata Importer for OMDb

[![WordPress](https://img.shields.io/badge/WordPress-6.4%2B-21759B)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.1--8.3-777BB4)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-green)](LICENSE)

A lightweight WordPress movie importer and TV series metadata plugin. Enter an IMDb ID to fetch movie data, IMDb ratings and posters from the OMDb API. It works with any theme and any public custom post type—no ACF, Elementor, WooCommerce or specific movie theme required.

## Features

- Import by IMDb ID such as `tt0133093`
- Preview metadata before importing
- Import title, full plot, excerpt and featured image
- Store year, release date, runtime, genres, director, writers and actors
- Store IMDb rating, votes, Metascore, age rating and awards
- Movie and TV series support, including total seasons
- Select which WordPress post types should display the importer
- Works with posts and public custom post types
- Safe server-side API requests; the API key is never exposed to JavaScript
- Developer filters for custom field mapping and third-party integrations
- Translation-ready and RTL-friendly admin interface

## Requirements

- WordPress 6.4 or later
- PHP 8.1–8.3
- An [OMDb API key](https://www.omdbapi.com/apikey.aspx)

## Installation

1. Download the ZIP from GitHub Releases.
2. In WordPress, open **Plugins → Add New → Upload Plugin**.
3. Activate the plugin.
4. Open **Settings → Movie & TV Importer** and save your OMDb API key.
5. Select the post types on which the importer should appear.
6. Edit a post, enter an IMDb ID and click **Import & Fill**.

## Stored custom fields

The plugin uses portable WordPress post meta with an `omdb_` prefix, including:

`omdb_imdb_id`, `omdb_original_title`, `omdb_year`, `omdb_release_date`, `omdb_runtime`, `omdb_genre`, `omdb_director`, `omdb_writer`, `omdb_actors`, `omdb_plot`, `omdb_language`, `omdb_country`, `omdb_awards`, `omdb_poster_url`, `omdb_imdb_rating`, `omdb_imdb_votes`, `omdb_metascore`, `omdb_box_office`, `omdb_production`, `omdb_website`, `omdb_total_seasons`, `omdb_content_type`, `omdb_type`, `omdb_age_rating`, and `omdb_ratings`.

These values are readable by WordPress, ACF, Meta Box, JetEngine, Elementor dynamic tags, REST customizations and theme code.

## Custom field mapping

```php
add_filter( 'mtmi_meta_map', function ( array $map ): array {
    $map['your_director_field'] = 'director';
    $map['your_rating_field']   = 'imdb_rating';
    return $map;
} );
```

Available hooks: `mtmi_normalized_data`, `mtmi_meta_map`, and `mtmi_after_import`.

## Search keywords

WordPress movie importer, IMDb WordPress plugin, OMDb WordPress plugin, movie metadata importer, TV series importer, IMDb rating importer, movie poster importer, WordPress film database, custom post type movie metadata.

## License and data source

## Changelog

### 1.1.1

- Updated WordPress compatibility metadata through 7.0
- Added automated PHP compatibility and secret scanning checks

The plugin is licensed under GPL-2.0-or-later. Movie metadata is provided by OMDb and is subject to the [OMDb terms](https://www.omdbapi.com/legal.htm). This project is not affiliated with IMDb or OMDb.
