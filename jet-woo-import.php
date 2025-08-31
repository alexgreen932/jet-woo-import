<?php
/**
 * Plugin Name: Jet Woo Import Wizard (CSV/XLSX)
 * Description: Step-by-step wizard to import/update Woo products from CSV/XLSX with header mapping and renaming.
 * Version: 1.1.0
 * Author: You
 */

if (!defined('ABSPATH')) exit;

define('JWI_PATH', plugin_dir_path(__FILE__));
define('JWI_URL',  plugin_dir_url(__FILE__));

add_action('admin_menu', function () {
    add_submenu_page(
        'woocommerce',
        'Jet Import Wizard',
        'Jet Import Wizard',
        'manage_woocommerce',
        'jet-woo-import',
        'jwi_render_page'
    );
});

// Load small modules
require_once JWI_PATH . 'admin/page.php';
require_once JWI_PATH . 'admin/upload.php';
require_once JWI_PATH . 'admin/mapping.php';

require_once JWI_PATH . 'core/reader.php';
require_once JWI_PATH . 'core/importer.php';
require_once JWI_PATH . 'core/targets.php';
require_once JWI_PATH . 'core/utils-tax.php';
require_once JWI_PATH . 'core/utils-media.php';

// Ensure media functions for sideloads
add_action('admin_init', function () {
    if (!is_admin()) return;
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
});

// Optional: if your local uses XAMPP/LAMPP and prompts FTP, uncomment:
// if (!defined('FS_METHOD')) define('FS_METHOD', 'direct');
