<?php
class WC_Order_Export_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function enqueue_scripts($hook) {
        if ('woocommerce_page_aco-woo-order-export' !== $hook) {
            return;
        }
        wp_enqueue_script('wc-order-export-admin', ACO_ORDER_EXPORT_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), ACO_ORDER_EXPORT_VERSION, true);
        wp_localize_script('wc-order-export-admin', 'wcOrderExport', array(
            'rest_url' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
        ));
    }

    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'Woo Order Export',
            'Export Order',
            'manage_woocommerce',
            'aco-woo-order-export',
            array($this, 'render_admin_page')
        );
    }

    public function render_admin_page() {
        include ACO_ORDER_EXPORT_PLUGIN_DIR . 'templates/admin-ui.php';
    }

    static private function get_export_fields() {
        $fields = array(
            'order_number' => __('Order Number', 'wc-order-export'),
            'order_status' => __('Order Status', 'wc-order-export'),
            'order_date' => __('Order Date', 'wc-order-export'),
            'customer_note' => __('Customer Note', 'wc-order-export'),
            'last_name' => __('Last Name', 'wc-order-export'),
            'postcode' => __('Postcode', 'wc-order-export'),
            'email' => __('Email', 'wc-order-export'),
            'phone' => __('Phone', 'wc-order-export'),
            'payment_method' => __('Payment Method', 'wc-order-export'),
            'product_name' => __('Product Name', 'wc-order-export'),
            'sku' => __('SKU', 'wc-order-export'),
            'quantity' => __('Quantity', 'wc-order-export'),
            'item_cost' => __('Item Cost', 'wc-order-export'),
            'cart_discount' => __('Cart Discount Amount', 'wc-order-export'),
            'shipping_method' => __('Shipping Method Title', 'wc-order-export'),
        );

        return $fields;
    }

    static private function get_custom_addon_fields() {
        $custom_fields = array();
        if (is_plugin_active('woo-custom-product-addons/start.php')) {
            $args  = [
                'post_type'      => 'wcpa_pt_forms',
                'posts_per_page' => -1,
                'post_status'    => ['draft', 'publish']
            ];
            $posts = get_posts($args);
            $forms = array();
            foreach($posts as $key => $form){
                $form_id = $form->ID;
                $value = get_post_meta($form_id, '_wcpa_fb_json_data', true);
                if($value){
                    $json_decode = json_decode($value);
                    if ($json_decode && is_object($json_decode)) {
                        foreach ($json_decode as $key => $section) {
                            foreach ($section->fields as $i => $row) {
                                foreach ($row as $j => $field) {
                                    if (isset($field->elementId)) {
                                        $field_name = $field->elementId;
                                        $field_label = $field->label;
                                        $custom_fields[$field_name] = $field_label;
                                    }
                                }
                            }
                        }
                    }
                }

            }
        }

        return $custom_fields;
    }

    static public function get_all_fields() {
        $export_fields = self::get_export_fields();
        $custom_fields = self::get_custom_addon_fields();
        $fields = array_merge($export_fields, $custom_fields);
        return $fields;
    }
}