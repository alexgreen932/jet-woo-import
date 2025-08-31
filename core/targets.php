<?php
if (!defined('ABSPATH')) exit;

function jwi_target_options(): array {
    return [
        'Core Fields' => [
            'skip'              => '— Skip —',
            'name'              => 'Product Name',
            'description'       => 'Description (long)',
            'short_description' => 'Short Description',
            'sku'               => 'SKU (update if exists)',
            'type'              => 'Product Type',
            'status'            => 'Post Status (publish/draft)',
        ],
        'Pricing' => [
            'regular_price'     => 'Regular Price',
            'sale_price'        => 'Sale Price',
        ],
        'Inventory' => [
            'manage_stock'      => 'Manage Stock (yes/no)',
            'stock_quantity'    => 'Stock Quantity',
            'stock_status'      => 'Stock Status (instock/outofstock/onbackorder)',
        ],
        'Dimensions' => [
            'weight'            => 'Weight',
            'length'            => 'Length',
            'width'             => 'Width',
            'height'            => 'Height',
        ],
        'Taxonomies' => [
            'categories'        => 'Categories (>, , or | for hierarchy/list)',
            'tags'              => 'Tags (comma or pipe separated)',
        ],
        'Images' => [
            'image'             => 'Featured Image URL',
            'gallery'           => 'Gallery Image URLs (comma/pipe separated)',
        ],
        'Attributes' => [
            'attribute'         => 'Attribute (use right input to name it)',
        ],
        'Meta' => [
            'meta'              => 'Meta (saves as post meta, key = renamed header)',
        ],
    ];
}
