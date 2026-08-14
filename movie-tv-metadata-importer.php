<?php
/**
 * Plugin Name:       Movie & TV Metadata Importer for OMDb
 * Plugin URI:        https://github.com/kamranhajhossein/movie-tv-metadata-importer
 * Description:       Import movie and TV series metadata, ratings and posters from OMDb using an IMDb ID. Works with any public post type.
 * Version:           1.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Kamran Haj Hossein
 * Author URI:        https://webrabin.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       movie-tv-metadata-importer
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'MTMI_VERSION', '1.1.0' );
define( 'MTMI_FILE', __FILE__ );
define( 'MTMI_PATH', plugin_dir_path( __FILE__ ) );
define( 'MTMI_URL', plugin_dir_url( __FILE__ ) );

require_once MTMI_PATH . 'includes/class-mtmi-api.php';
require_once MTMI_PATH . 'includes/class-mtmi-importer.php';
require_once MTMI_PATH . 'includes/class-mtmi-admin.php';
require_once MTMI_PATH . 'includes/class-mtmi-plugin.php';

MTMI_Plugin::instance();
