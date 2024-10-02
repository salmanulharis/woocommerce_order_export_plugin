<div class="wrap">
    <h1><?php echo esc_html__('WooCommerce Order Export', 'wc-order-export'); ?></h1>
    <form id="wc-order-export-form">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="start_date"><?php echo esc_html__('Start Date', 'wc-order-export'); ?></label></th>
                <td><input type="date" id="start_date" name="start_date" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="end_date"><?php echo esc_html__('End Date', 'wc-order-export'); ?></label></th>
                <td><input type="date" id="end_date" name="end_date" required></td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html__('Export Fields', 'wc-order-export'); ?></th>
                <td>
                    <?php
                    $fields = $this->get_export_fields();
                    foreach ($fields as $field => $label) {
                        echo '<label><input type="checkbox" name="fields[]" value="' . esc_attr($field) . '"> ' . esc_html($label) . '</label><br>';
                    }
                    ?>
                </td>
            </tr>
            <?php if($this->get_custom_addon_fields()){ ?>
                <tr>
                    <th scope="row"><?php echo esc_html__('Custom Product Addons Fields', 'wc-order-export'); ?></th>
                    <td>
                        <?php
                        $fields = $this->get_custom_addon_fields();
                        foreach ($fields as $field => $label) {
                            echo '<label><input type="checkbox" name="custom_fields[]" value="' . esc_attr($field) . '"> ' . esc_html($label) . '</label><br>';
                        }
                        ?>
                    </td>
                </tr>
            <?php } ?>
            <tr>
                <th scope="row"><label for="format"><?php echo esc_html__('Export Format', 'wc-order-export'); ?></label></th>
                <td>
                    <select id="format" name="format" required>
                        <option value="csv">CSV</option>
                        <option value="pdf">PDF</option>
                    </select>
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo esc_attr__('Export Orders', 'wc-order-export'); ?>">
        </p>
    </form>
</div>