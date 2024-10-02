<?php
require_once ACO_ORDER_EXPORT_PLUGIN_DIR . 'fpdf/fpdf.php';

class WC_Order_Export_API {

    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        register_rest_route('wc-order-export/v1', '/export', array(
            'methods' => 'POST',
            'callback' => array($this, 'export_orders'),
            'permission_callback' => array($this, 'check_permission'),
        ));
    }

    public function check_permission() {
        return current_user_can('manage_woocommerce');
    }

    public function export_orders($request) {
        $params = $request->get_params();
        
        $start_date = isset($params['start_date']) ? sanitize_text_field($params['start_date']) : '';
        $end_date = isset($params['end_date']) ? sanitize_text_field($params['end_date']) : '';
        $format = isset($params['format']) ? sanitize_text_field($params['format']) : '';
        $fields = isset($params['fields[]']) ? array_map('sanitize_text_field', (array) $params['fields[]']) : array();

        $orders = $this->get_orders($start_date, $end_date);
        $data = $this->prepare_data($orders, $fields);

        if(is_plugin_active('woo-custom-product-addons/start.php')){
            $custom_fields = isset($params['custom_fields[]']) ? array_map('sanitize_text_field', (array) $params['custom_fields[]']) : array();
            $data = $this->prepare_custom_field_data($orders, $custom_fields, $data);
        }   

        if ($format === 'csv') {
            return $this->generate_csv($data);
        } elseif ($format === 'pdf') {
            return $this->generate_pdf($data);
        }

        return new WP_Error('invalid_format', 'Invalid export format', array('status' => 400));
    }

    private function get_orders($start_date, $end_date) {
        $args = array(
            'date_created' => $start_date . '...' . $end_date,
            'limit' => -1,
        );

        return wc_get_orders($args);
    }

    private function prepare_data($orders, $fields) {
        $data = array();

        foreach ($orders as $order) {
            $order_data = array();
            foreach ($fields as $field) {
                $order_data[$field] = $this->get_order_field($order, $field);
            }
            $data[] = $order_data;
        }

        return $data;
    }

    private function prepare_custom_field_data($orders, $custom_fields, $data) {
        foreach ($orders as $key => $order) {
            $order_data = array();
            foreach ($custom_fields as $field) {
                $order_data[$field] = $this->get_order_custom_field($order, $field);
            }
            $data[$key] = array_merge($data[$key], $order_data);
        }

        return $data;
    }

    private function get_order_field($order, $field) {
        switch ($field) {
            case 'order_number':
                return $order->get_order_number();
            case 'order_status':
                return $order->get_status();
            case 'order_date':
                return $order->get_date_created()->format('Y-m-d H:i:s');
            case 'customer_note':
                return $order->get_customer_note();
            case 'last_name':
                return $order->get_billing_last_name();
            case 'postcode':
                return $order->get_billing_postcode();
            case 'email':
                return $order->get_billing_email();
            case 'phone':
                return $order->get_billing_phone();
            case 'payment_method':
                return $order->get_payment_method_title();
            case 'product_name':
                $product_names = array();
                foreach ( $order->get_items() as $item_id => $item ) {
                    $product_names[] = $item->get_name();
                }
                return implode( ', ', $product_names );
            case 'sku':
                $skus = array();
                foreach ( $order->get_items() as $item_id => $item ) {
                    $product = $item->get_product();
                    if ( $product ) {
                        $sku = $product->get_sku();
                        $skus[] = $sku ? $sku : 'N/A';
                    }
                }
                return implode( ', ', $skus );
            case 'quantity':
                $products_with_quantities = array();
                foreach ( $order->get_items() as $item_id => $item ) {
                    $product_name = $item->get_name();
                    $quantity = $item->get_quantity();
                    $products_with_quantities[] = $product_name . ' (' . $quantity . ')';
                }
                return implode( ', ', $products_with_quantities );
            case 'item_cost':
                $products_with_total_cost = array();
                foreach ( $order->get_items() as $item_id => $item ) {
                    $product_name = $item->get_name();
                    $item_total = $item->get_total();
                    $products_with_total_cost[] = $product_name . ' (' . $item_total . ')';
                }
                return implode( ', ', $products_with_total_cost );
            case 'cart_discount':
                $cart_discount = $order->get_discount_total();
                return $cart_discount;
            case 'shipping_method':
                return $order->get_shipping_method();
            default:
                return $order->get_meta($field);
        }
    }

    private function get_order_custom_field($order, $field) {
        if ($field) {
            $field_values = '';
            foreach ($order->get_items() as $item_id => $item_data) {
                $item_name = $item_data->get_name();
                $meta_data = $item_data->get_meta('_WCPA_order_meta_data');
                if($meta_data && isset($meta_data)){
                    foreach ($meta_data as $data) {
                        if(isset($data)){
                            $meta_fields = isset($data['fields']) ? $data['fields'] : array();
                            foreach ($meta_fields as $fields){
                                foreach($fields as $values){
                                    if($field == $values['elementId']){
                                        $field_values .= $values['value'] . '(' . $item_name . '), ';
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $field_values = rtrim($field_values, ", ");
            return $field_values;
        }
        return array();
    }


    private function generate_csv($data) {
        $fields_data = WC_Order_Export_Admin::get_all_fields();
        ob_start();
        $f = fopen('php://output', 'w');
        if (!empty($data)) {
            $key_array = array_intersect_key($fields_data, array_flip(array_keys($data[0])));
            fputcsv($f, $key_array);
        }
        
        foreach ($data as $row) {
            fputcsv($f, $row);
        }
        
        fclose($f);
        
        $csv_content = ob_get_clean();
        
        return rest_ensure_response([
            'success' => true,
            'data' => base64_encode($csv_content),
            'filename' => 'orders.csv',
            'content_type' => 'text/csv',
        ]);
    }

    private function generate_pdf($data) {
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 10);
        
        $fields_data = WC_Order_Export_Admin::get_all_fields();
        $key_array = array_intersect_key($fields_data, array_flip(array_keys($data[0])));
    
        if (!empty($data)) {
            foreach ($data as $row) {
                foreach ($key_array as $key => $header) {
                    if (isset($row[$key])) {
                        $pdf->Cell(60, 6, "{$header}: ", 0, 0, 'L');
                        $pdf->SetFont('Arial', '', 10);
                        $pdf->Cell(0, 6, $row[$key], 0, 1, 'L');
                        $pdf->SetFont('Arial', 'B', 10);
                    }
                }
                $pdf->Ln(1);
                $pdf->Cell(0, 0, '', 'T');
                $pdf->Ln(1);
            }
        }
    
        ob_start();
        $pdf->Output('F', 'php://output');
        $pdf_content = ob_get_clean();
    
        return rest_ensure_response([
            'success' => true,
            'data' => base64_encode($pdf_content),
            'filename' => 'orders.pdf',
            'content_type' => 'application/pdf',
        ]);
    }
    
    

}