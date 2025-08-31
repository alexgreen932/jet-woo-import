<?php
if (!defined('ABSPATH')) exit;

function jwi_render_page() {
    if (!current_user_can('manage_woocommerce')) {
        wp_die('Insufficient permissions.');
    }

    // Lazy-load SimpleXLSX
    if (!class_exists('SimpleXLSX')) {
        $xlsx_lib = JWI_PATH . 'vendor/SimpleXLSX.php';
        if (file_exists($xlsx_lib)) require_once $xlsx_lib;
    }

    $step = isset($_POST['jwi_step']) ? sanitize_text_field($_POST['jwi_step']) : 'upload';

    echo '<div class="wrap"><h1>Woo Product Import Wizard</h1>';

    if ($step === 'mapping' && check_admin_referer('jwi_wizard')) {
        $file_path = sanitize_text_field($_POST['jwi_file_path'] ?? '');
        $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $rows = jwi_read_rows($file_path, $ext);

        if (empty($rows)) {
            echo '<div class="notice notice-error"><p>Could not read the file or it is empty.</p></div>';
            jwi_render_upload();
            echo '</div>';
            return;
        }

        $headers = array_keys($rows[0]);
        jwi_render_mapping_form($headers, $file_path);
        echo '</div>';
        return;

    } elseif ($step === 'run' && check_admin_referer('jwi_wizard')) {
        $file_path = sanitize_text_field($_POST['jwi_file_path'] ?? '');
        $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $rows = jwi_read_rows($file_path, $ext);

        if (empty($rows)) {
            echo '<div class="notice notice-error"><p>Could not read the file or it is empty.</p></div>';
            jwi_render_upload();
            echo '</div>';
            return;
        }

        // Collect mapping/options
        $mapping = $_POST['map'] ?? [];       // header => target
        $renames = $_POST['rename'] ?? [];    // header => renamed
        $attr    = $_POST['attr'] ?? [];      // header => attribute (if target=attribute)
        $opts = [
            'default_type'  => sanitize_text_field($_POST['default_type'] ?? 'simple'),
            'download_imgs' => isset($_POST['download_imgs']),
            'image_timeout' => 25,
        ];

        if (!empty($renames)) {
            $rows = jwi_apply_renames($rows, $renames);
        }

        $result = jwi_import_products($rows, $mapping, $attr, $opts);

        printf(
            '<div class="notice notice-success"><p>Done. Created: <b>%d</b>, Updated: <b>%d</b>, Skipped: <b>%d</b>, Images: <b>%d</b></p></div>',
            $result['created'], $result['updated'], $result['skipped'], $result['images']
        );

        echo '<p><a class="button" href="' . esc_url(admin_url('edit.php?post_type=product')) . '">View Products</a> ';
        echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=jet-woo-import')) . '">Run Another Import</a></p>';
        echo '</div>';
        return;

    } else {
        jwi_render_upload();
        echo '</div>';
        return;
    }
}
