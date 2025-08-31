<?php
if (!defined('ABSPATH')) exit;

function jwi_import_products(array $rows, array $mapping, array $attr_names, array $opts): array {
    $created = $updated = $skipped = $img_count = 0;
    $split_regex = '/\s*[|,]\s*/';

    foreach ($rows as $row) {
        // Build data bucket
        $data = [
            'name'              => '',
            'description'       => '',
            'short_description' => '',
            'sku'               => '',
            'type'              => $opts['default_type'] ?? 'simple',
            'status'            => 'publish',
            'regular_price'     => '',
            'sale_price'        => '',
            'manage_stock'      => '',
            'stock_quantity'    => '',
            'stock_status'      => '',
            'weight'            => '',
            'length'            => '',
            'width'             => '',
            'height'            => '',
            'categories'        => '',
            'tags'              => '',
            'image'             => '',
            'gallery'           => '',
            'attributes'        => [],
            'meta'              => [],
        ];

        // Fill from mapping
        foreach ($mapping as $header => $target) {
            if (!array_key_exists($header, $row)) continue;
            $value = trim((string) $row[$header]);
            if ($target === 'skip' || $value === '') continue;

            switch ($target) {
                // direct fields
                case 'name': case 'description': case 'short_description':
                case 'sku':  case 'type':        case 'status':
                case 'regular_price': case 'sale_price':
                case 'manage_stock':  case 'stock_quantity': case 'stock_status':
                case 'weight': case 'length': case 'width': case 'height':
                case 'categories': case 'tags': case 'image': case 'gallery':
                    $data[$target] = $value;
                    break;

                case 'attribute':
                    $label = trim((string) ($attr_names[$header] ?? ''));
                    if ($label !== '') {
                        $attr_slug = jwi_normalize_attribute_name($label);
                        $vals = preg_split($split_regex, $value);
                        $vals = array_filter(array_map('trim', (array)$vals));
                        if (!empty($vals)) {
                            $data['attributes'][$attr_slug] = [
                                'label'  => $label,
                                'values' => array_values(array_unique($vals)),
                            ];
                        }
                    }
                    break;

                case 'meta':
                    $data['meta'][$header] = $value;
                    break;
            }
        }

        if ($data['name'] === '' && $data['sku'] === '') { $skipped++; continue; }

        // Find existing by SKU
        $product_id = 0;
        if ($data['sku'] !== '') $product_id = wc_get_product_id_by_sku($data['sku']);

        // Instantiate product (NO set_type()—choose class by type on create)
        $type = strtolower($data['type'] ?: 'simple');
        if ($product_id) {
            $product = wc_get_product($product_id);
            if (!$product) { $skipped++; continue; }
        } else {
            switch ($type) {
                case 'external':  $product = new WC_Product_External();  break;
                case 'grouped':   $product = new WC_Product_Grouped();   break;
                case 'variable':  $product = new WC_Product_Variable();  break;
                case 'simple':
                default:          $product = new WC_Product_Simple();    break;
            }
        }

        // Basic fields
        if ($data['name'])              $product->set_name($data['name']);
        if ($data['description'])       $product->set_description($data['description']);
        if ($data['short_description']) $product->set_short_description($data['short_description']);
        if ($data['sku'] && !$product_id) $product->set_sku($data['sku']);

        if ($data['regular_price'] !== '') $product->set_regular_price($data['regular_price']);
        if ($data['sale_price']    !== '') $product->set_sale_price($data['sale_price']);

        // Inventory
        if ($data['manage_stock'] !== '') {
            $product->set_manage_stock( in_array(strtolower($data['manage_stock']), ['yes','true','1'], true) );
        }
        if ($data['stock_quantity'] !== '') $product->set_stock_quantity((int)$data['stock_quantity']);
        if ($data['stock_status']   !== '') $product->set_stock_status(strtolower($data['stock_status']));

        // Dimensions
        if ($data['weight'] !== '') $product->set_weight($data['weight']);
        if ($data['length'] !== '') $product->set_length($data['length']);
        if ($data['width']  !== '') $product->set_width($data['width']);
        if ($data['height'] !== '') $product->set_height($data['height']);

        // Status
        if ($data['status'] !== '') $product->set_status($data['status']);

        // Save to get ID
        $new_id = $product->save();
        if (is_wp_error($new_id) || !$new_id) { $skipped++; continue; }
        $product_id = $new_id;

        // Taxonomies
        if ($data['categories'] !== '') jwi_assign_categories($product_id, $data['categories']);
        if ($data['tags'] !== '') {
            $tags = preg_split('/\s*[|,]\s*/', $data['tags']);
            $tags = array_filter(array_map('trim', (array)$tags));
            if (!empty($tags)) wp_set_post_terms($product_id, $tags, 'product_tag', false);
        }

        // Attributes
        if (!empty($data['attributes'])) jwi_assign_attributes($product_id, $data['attributes']);

        // Images
        if (!empty($opts['download_imgs'])) {
            if ($data['image'] !== '') {
                $att_id = jwi_attach_image_from_url($data['image'], $product_id, (int)$opts['image_timeout']);
                if ($att_id) { set_post_thumbnail($product_id, $att_id); $img_count++; }
            }
            if ($data['gallery'] !== '') {
                $gallery_urls = preg_split('/\s*[|,]\s*/', $data['gallery']);
                $gids = [];
                foreach ($gallery_urls as $url) {
                    $aid = jwi_attach_image_from_url(trim($url), $product_id, (int)$opts['image_timeout']);
                    if ($aid) { $gids[] = $aid; $img_count++; }
                }
                if (!empty($gids)) update_post_meta($product_id, '_product_image_gallery', implode(',', $gids));
            }
        }

        // Meta
        if (!empty($data['meta'])) {
            foreach ($data['meta'] as $k => $v) {
                update_post_meta($product_id, sanitize_key($k), $v);
            }
        }

        // Created vs Updated
        if ($product_id && $data['sku'] && wc_get_product_id_by_sku($data['sku']) == $product_id) {
            // If it existed, count as updated; else created
            $post = get_post($product_id);
            if ($post && (time() - strtotime($post->post_date_gmt)) < 60) $created++; else $updated++;
        } else {
            $updated++;
        }
    }

    return [
        'created' => $created,
        'updated' => $updated,
        'skipped' => $skipped,
        'images'  => $img_count,
    ];
}
