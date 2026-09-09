<?php
/**
 * Plugin Name: Hexis
 * Description: Annual character-development reviews with directional virtue tracking.
 * Version: 0.1.0
 * Author: Mariusz Mirecki
 * Text Domain: mm-hexis
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'HEXIS_VERSION', '0.1.0' );
define( 'HEXIS_FILE', __FILE__ );
define( 'HEXIS_DIR', plugin_dir_path( __FILE__ ) );
define( 'HEXIS_URL', plugin_dir_url( __FILE__ ) );

require_once HEXIS_DIR . 'includes/class-hexis-db.php';
require_once HEXIS_DIR . 'includes/class-hexis-ajax.php';
require_once HEXIS_DIR . 'includes/class-hexis-shortcode.php';

register_activation_hook( __FILE__, array( 'Hexis_DB', 'activate' ) );

function hexis_bootstrap() {
    Hexis_Ajax::init();
    Hexis_Shortcode::init();
}
add_action( 'plugins_loaded', 'hexis_bootstrap' );
