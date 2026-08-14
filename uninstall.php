<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;
delete_option( 'mtmi_api_key' );
delete_option( 'mtmi_post_types' );
