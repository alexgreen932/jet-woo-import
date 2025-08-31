<?php
if (!defined('ABSPATH')) exit;

function jwi_render_upload() { ?>
    <form method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('jwi_wizard'); ?>
        <input type="hidden" name="jwi_step" value="mapping">

        <table class="form-table" role="presentation">
            <tr>
                <th><label for="jwi_file">File (.csv or .xlsx)</label></th>
                <td><input type="file" name="jwi_file" id="jwi_file" required></td>
            </tr>
            <tr>
                <th><label for="default_type">Default product type</label></th>
                <td>
                    <select name="default_type" id="default_type">
                        <option value="simple" selected>simple</option>
                        <option value="external">external</option>
                        <option value="grouped">grouped</option>
                        <option value="variable">variable (basic attributes only)</option>
                    </select>
                    <p class="description">If no type column is mapped, new products use this type.</p>
                </td>
            </tr>
        </table>
        <?php
        // Upload handling when a file is picked
        if (!empty($_FILES['jwi_file']['tmp_name'])) {
            $upload = wp_handle_upload($_FILES['jwi_file'], ['test_form' => false]);
            if (isset($upload['file'])) {
                $file_path = $upload['file'];
                echo '<input type="hidden" name="jwi_file_path" value="' . esc_attr($file_path) . '">';
                echo '<p><b>Uploaded:</b> ' . esc_html(basename($file_path)) . '</p>';
                echo '<p class="submit"><button type="submit" class="button button-primary">Continue to Mapping</button></p>';
                return;
            } else {
                echo '<div class="notice notice-error"><p>Upload failed.</p></div>';
            }
        }
        ?>
        <p class="submit"><button type="submit" class="button button-primary">Upload & Continue</button></p>
    </form>
<?php }
