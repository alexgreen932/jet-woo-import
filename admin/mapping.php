<?php
if (!defined('ABSPATH')) exit;

function jwi_render_mapping_form(array $headers, string $file_path) {
    $targets = jwi_target_options();

    echo '<form method="post">';
    wp_nonce_field('jwi_wizard');
    echo '<input type="hidden" name="jwi_step" value="run">';
    echo '<input type="hidden" name="jwi_file_path" value="' . esc_attr($file_path) . '">';

    echo '<h2>2) Map columns</h2>';
    echo '<p>Choose what each column represents. You can rename headers before import. For attributes, pick "Attribute" and provide a name (e.g. <code>Color</code> or <code>pa_color</code>).</p>';

    echo '<table class="widefat striped">';
    echo '<thead><tr><th>Original Header</th><th>Rename (optional)</th><th>Map To</th><th>Attribute Name (if Attribute)</th></tr></thead><tbody>';

    foreach ($headers as $h) {
        echo '<tr>';
        echo '<td><code>' . esc_html($h) . '</code></td>';
        echo '<td><input type="text" name="rename['.esc_attr($h).']" value="' . esc_attr($h) . '" class="regular-text"></td>';
        echo '<td><select name="map['.esc_attr($h).']">';
        $lc = strtolower($h);
        foreach ($targets as $group => $opts) {
            echo '<optgroup label="'.esc_attr($group).'">';
            foreach ($opts as $value => $label) {
                $selected = '';
                if (
                    ($value === 'name' && in_array($lc, ['name','title','product name'])) ||
                    ($value === 'sku' && $lc === 'sku') ||
                    ($value === 'regular_price' && in_array($lc, ['price','regular price','regular_price'])) ||
                    ($value === 'sale_price' && strpos($lc, 'sale') !== false)
                ) {
                    $selected = 'selected';
                }
                echo '<option value="'.esc_attr($value).'" '.$selected.'>'.esc_html($label).'</option>';
            }
            echo '</optgroup>';
        }
        echo '</select></td>';
        echo '<td><input type="text" name="attr['.esc_attr($h).']" placeholder="e.g. Color or pa_color" class="regular-text"></td>';
        echo '</tr>';
    }

    echo '</tbody></table>';

    echo '<h2>3) Options</h2>';
    echo '<label><input type="checkbox" name="download_imgs" value="1" checked> Download/attach images from URLs</label>';
    echo '<p class="description">Featured Image: single URL. Gallery: comma or pipe separated URLs.</p>';

    echo '<p class="submit"><button type="submit" class="button button-primary">Run Import</button></p>';
    echo '</form>';
}
