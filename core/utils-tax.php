<?php
if (!defined('ABSPATH')) exit;

function jwi_normalize_attribute_name(string $name): string {
    $name = trim($name);
    $slug = wc_sanitize_taxonomy_name($name);
    if (strpos($slug, 'pa_') !== 0) $slug = 'pa_' . $slug;
    jwi_ensure_global_attribute($name, $slug);
    return $slug;
}

function jwi_ensure_global_attribute(string $label, string $slug) {
    global $wpdb;
    $raw = substr($slug, 3); // drop pa_
    $attr = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s",
        $raw
    ));

    if (!$attr) {
        $wpdb->insert(
            "{$wpdb->prefix}woocommerce_attribute_taxonomies",
            [
                'attribute_label'   => $label,
                'attribute_name'    => $raw,
                'attribute_type'    => 'select',
                'attribute_orderby' => 'menu_order',
                'attribute_public'  => 1,
            ]
        );
        delete_transient('wc_attribute_taxonomies');
        wc_register_taxonomy('pa_' . $raw, 'product');
        flush_rewrite_rules(false);
    }
}

function jwi_assign_attributes(int $product_id, array $attributes) {
    $product = wc_get_product($product_id);
    if (!$product) return;

    $wc_attrs = [];

    foreach ($attributes as $attr_slug => $info) {
        $label = $info['label'];
        $vals  = $info['values'];

        if (taxonomy_exists($attr_slug)) {
            // Global taxonomy attribute
            $term_ids = [];
            foreach ($vals as $v) {
                $term = term_exists($v, $attr_slug);
                if (!$term) $term = wp_insert_term($v, $attr_slug);
                if (!is_wp_error($term)) {
                    $term_ids[] = (int) ($term['term_id'] ?? $term['term_taxonomy_id']);
                }
            }
            if (!empty($term_ids)) {
                wp_set_post_terms($product_id, $vals, $attr_slug, false);
            }

            $attribute = new WC_Product_Attribute();
            $attribute->set_id(wc_attribute_taxonomy_id_by_name($attr_slug));
            $attribute->set_name($attr_slug);
            $attribute->set_options($term_ids);
            $attribute->set_visible(true);
            $attribute->set_variation(false);
            $wc_attrs[] = $attribute;

        } else {
            // Custom per-product attribute
            $attribute = new WC_Product_Attribute();
            $attribute->set_id(0);
            $attribute->set_name($label);
            $attribute->set_options($vals);
            $attribute->set_visible(true);
            $attribute->set_variation(false);
            $wc_attrs[] = $attribute;
        }
    }

    if (!empty($wc_attrs)) {
        $product->set_attributes($wc_attrs);
        $product->save();
    }
}

function jwi_assign_categories(int $product_id, string $raw) {
    $chunks = preg_split('/\s*[|,]\s*/', $raw);
    $cat_ids = [];

    foreach ($chunks as $chunk) {
        $hier = array_map('trim', explode('>', $chunk));
        $parent = 0; $term_id = 0;

        foreach ($hier as $name) {
            if ($name === '') continue;
            $term = term_exists($name, 'product_cat', $parent);
            if (!$term) $term = wp_insert_term($name, 'product_cat', ['parent' => $parent]);
            if (is_wp_error($term)) continue;
            $term_id = (int) $term['term_id'];
            $parent  = $term_id;
        }

        if ($term_id) $cat_ids[] = $term_id;
    }

    if (!empty($cat_ids)) {
        wp_set_post_terms($product_id, $cat_ids, 'product_cat', false);
    }
}
