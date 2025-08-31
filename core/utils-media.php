<?php
if (!defined('ABSPATH')) exit;

function jwi_attach_image_from_url(string $url, int $post_id, int $timeout = 25) {
    $url = trim($url);
    if ($url === '') return 0;

    $tmp = download_url($url, $timeout);
    if (is_wp_error($tmp)) return 0;

    $file_array = [
        'name'     => basename(parse_url($url, PHP_URL_PATH) ?: 'image.jpg'),
        'tmp_name' => $tmp,
    ];

    $id = media_handle_sideload($file_array, $post_id);
    if (is_wp_error($id)) {
        @unlink($file_array['tmp_name']);
        return 0;
    }
    return $id;
}
