<?php

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('EDD_Putler_Connector')) {

    class EDD_Putler_Connector {

        private $name = 'Edd';

        public function __construct() {
            add_filter('putler_connector_get_order_count', array(&$this, 'get_order_count'));
            add_filter('putler_connector_get_orders', array(&$this, 'get_orders'));
        }

        public function get_order_count($count) {
            global $wpdb;
            $order_count = 0;

            $query_to_fetch_order_count = "SELECT COUNT(posts.ID) as id
                                            FROM {$wpdb->prefix}posts AS posts
                                            JOIN {$wpdb->prefix}postmeta AS postmeta
                                            ON posts.ID = postmeta.post_id
                                            WHERE posts.post_type LIKE 'edd_payment'
                                            AND postmeta.meta_key = '_edd_payment_mode'
                                            AND postmeta.meta_value = 'live'";

            $order_count_result = $wpdb->get_col($query_to_fetch_order_count);
            if (!empty($order_count_result)) {
                $order_count = $order_count_result[0];
            }

            return $count + $order_count;
        }

        public function get_orders($params) {
            global $wpdb;

            $edd_order_status = array(
                'pending' => 'Pending',
                'publish' => 'Completed',
                'refunded' => 'Refunded',
                'failed' => 'Pending',
                'abandoned' => 'Cancelled',
                'revoked' => 'Cancelled'
            );

            //Code to get the last order sent

            $cond = '';

            if (empty($params['order_id'])) {
                $start_limit = (isset($params[$this->name]['start_limit'])) ? $params[$this->name]['start_limit'] : 0;
                $batch_limit = (isset($params['limit'])) ? $params['limit'] : 50;
            } else {
                $start_limit = 0;
                $batch_limit = 1;
                $cond = 'AND posts.ID IN(' . intval($params['order_id']) . ')';
            }

            $query_order_details = "SELECT posts.ID as id,
                                        posts.post_status as order_status,
                                        date_format(posts.post_date_gmt,'%Y-%m-%d %T') AS date,
                                        date_format(posts.post_modified_gmt,'%Y-%m-%d %T') AS modified_date
                                        FROM {$wpdb->prefix}posts AS posts
                                        JOIN {$wpdb->prefix}postmeta AS postmeta
                                        ON posts.ID = postmeta.post_id
                                        WHERE posts.post_type LIKE 'edd_payment'
                                        AND posts.post_status NOT IN ('trash', 'auto-draft', 'draft')
                                        AND posts.post_date_gmt != '0000-00-00 00:00:00'
                                        AND posts.post_modified_gmt != '0000-00-00 00:00:00'
                                        AND postmeta.meta_key = '_edd_payment_mode'
                                        AND postmeta.meta_value = 'live'
                                        $cond
                                        GROUP BY posts.ID
                                        LIMIT " . $start_limit . "," . $batch_limit;

            $results_order_details = $wpdb->get_results($query_order_details, 'ARRAY_A');
            $results_order_details_count = $wpdb->num_rows;

            if ($results_order_details_count > 0) {

                $order_ids = array();

                foreach ($results_order_details as $results_order_detail) {
                    $order_ids[] = $results_order_detail['id'];
                }

                //Query to get the Order_items

                $query_cart_items = "SELECT postmeta.post_id,
                                                postmeta.meta_key AS meta_key,
                                                postmeta.meta_value AS meta_value
                                        FROM {$wpdb->prefix}postmeta AS postmeta
                                        WHERE postmeta.meta_key NOT IN ('_edit_lock', '_edit_last')
                                        AND postmeta.post_id IN (" . implode(",", $order_ids) . ")
                                        GROUP BY postmeta.post_id, meta_key
                                            ";
                $results_cart_items = $wpdb->get_results($query_cart_items, 'ARRAY_A');

                $results_cart_items_count = $wpdb->num_rows;

                $order_items = array();

                if ($results_cart_items_count > 0) {

                    foreach ($results_cart_items as $cart_item) {
                        $order_id = $cart_item['post_id'];

                        if (!isset($order_items[$order_id])) {
                            $order_items[$order_id] = array();
                            $order_items[$order_id]['tot_qty'] = 0;
                            $order_items[$order_id]['payment_total'] = 0;
                            $order_items[$order_id]['payment_details'] = array();
                        }

                        if ($cart_item['meta_key'] == '_edd_payment_meta') {
                            $order_meta_data = maybe_unserialize($cart_item['meta_value']);

                            foreach ($order_meta_data as $order_key => $order_data) {
                                $order_meta_data[$order_key] = is_serialized($order_data) ? maybe_unserialize($order_data) : $order_data;
                            }

                            $order_items[$order_id]['payment_details'] = $order_meta_data;
                            $order_items[$order_id]['tot_qty'] = count($order_items[$order_id]['payment_details']['cart_details']);
                        } else {
                            $order_items[$order_id][$cart_item['meta_key']] = $cart_item['meta_value'];
                        }
                    }
                }

                if ($results_order_details > 0) {

                    //Code for Data Mapping as per Putler
                    foreach ($results_order_details as $order_detail) {

                        $order_id = $order_detail['id'];
                        $date_gmt = $order_detail['date'];
                        $dateInGMT = date('m/d/Y', (int) strtotime($date_gmt));
                        $timeInGMT = date('H:i:s', (int) strtotime($date_gmt));
                        $order_status = $order_detail['order_status'];
                        $order_total = $order_items[$order_id]['_edd_payment_total'];

                        // $response['date_time'] = $date_gmt;
                        $response ['Date'] = $dateInGMT;
                        $response ['Time'] = $timeInGMT;
                        $response ['Time_Zone'] = 'GMT';

                        $response ['Source'] = $this->name;
                        $response ['Name'] = $order_items[$order_id]['payment_details']['user_info']['first_name'] . ' ' . $order_items[$order_id]['payment_details']['user_info']['last_name'];
                        // $response ['Type'] = ( $status == "refunded") ? 'Refund' : 'Shopping Cart Payment Received';
                        $response ['Type'] = 'Shopping Cart Payment Received';
                        $response ['Status'] = $edd_order_status[$order_status];

                        $response ['Currency'] = $order_items[$order_id]['payment_details']['currency'];

                        $response ['Gross'] = $order_total;
                        $response ['Fee'] = 0.00;
                        $response ['Net'] = $order_total;

                        $response ['From_Email_Address'] = $order_items[$order_id]['payment_details']['user_info']['email'];
                        $response ['To_Email_Address'] = '';
                        $response ['Transaction_ID'] = $order_id;
                        $response ['Counterparty_Status'] = '';
                        $response ['Address_Status'] = '';
                        $response ['Item_Title'] = 'Shopping Cart';
                        $response ['Item_ID'] = 0; // Set to 0 for main Order Transaction row
                        $response ['Shipping_and_Handling_Amount'] = 0.00;
                        $response ['Insurance_Amount'] = '';
                        $response ['Discount'] = 0.00;

                        $response ['Sales_Tax'] = (!empty($order_items[$order_id]['payment_details']['tax'])) ? $order_items[$order_id]['payment_details']['tax'] : 0;

                        $response ['Option_1_Name'] = '';
                        $response ['Option_1_Value'] = '';
                        $response ['Option_2_Name'] = '';
                        $response ['Option_2_Value'] = '';

                        $response ['Auction_Site'] = '';
                        $response ['Buyer_ID'] = '';
                        $response ['Item_URL'] = '';
                        $response ['Closing_Date'] = '';
                        $response ['Escrow_ID'] = '';
                        $response ['Invoice_ID'] = '';
                        $response ['Reference_Txn_ID'] = '';
                        $response ['Invoice_Number'] = '';
                        $response ['Custom_Number'] = '';
                        $response ['Quantity'] = $order_items[$order_id]['tot_qty'];
                        $response ['Receipt_ID'] = '';

                        $response ['Balance'] = '';
                        $response ['Note'] = ''; // No customer notes.
                        $response ['Address_Line_1'] = ( isset($order_items[$order_id]['payment_details']['user_info']['address']['line1']) ) ? $order_items[$order_id]['payment_details']['user_info']['address']['line1'] : '';
                        $response ['Address_Line_2'] = isset($order_items[$order_id]['payment_details']['user_info']['address']['line2']) ? $order_items[$order_id]['payment_details']['user_info']['address']['line2'] : '';
                        $response ['Town_City'] = isset($order_items[$order_id]['user_info']['payment_details']['address']['city']) ? $order_items[$order_id]['payment_details']['user_info']['address']['city'] : '';
                        $response ['State_Province'] = isset($order_items[$order_id]['payment_details']['user_info']['address']['state']) ? $order_items[$order_id]['payment_details']['user_info']['address']['state'] : '';
                        $response ['Zip_Postal_Code'] = isset($order_items[$order_id]['payment_details']['user_info']['address']['zip']) ? $order_items[$order_id]['payment_details']['user_info']['address']['zip'] : '';
                        $response ['Country'] = isset($order_items[$order_id]['payment_details']['user_info']['address']['country']) ? $order_items[$order_id]['payment_details']['user_info']['address']['country'] : '';
                        $response ['Contact_Phone_Number'] = ''; // TODO
                        $response ['Subscription_ID'] = '';

                        if ($order_status != "refunded") {
                            $response ['Payment_Source'] = (!empty( $order_items[$order_id]['_edd_payment_gateway'])) ? edd_get_gateway_admin_label($order_items[$order_id]['_edd_payment_gateway']) : '';
                            $response ['External_Trans_ID'] = (!empty( $order_items[$order_id]['_edd_payment_transaction_id'])) ? $order_items[$order_id]['_edd_payment_transaction_id'] : '';
                        }

                        //customer ip_address
                        $response ['IP_Address'] = (!empty( $order_items[$order_id]['_edd_payment_user_ip'])) ? $order_items[$order_id]['_edd_payment_user_ip'] : '';

                        $transactions [] = $response;
                        foreach ($order_items[$order_id]['payment_details']['cart_details'] as $cart_item) {

                            $order_item = array();
                            $order_item ['Type'] = 'Shopping Cart Item';
                            $order_item ['Item_Title'] = $cart_item['name'];
                            $order_item ['Item_ID'] = $cart_item['id'];
                            $order_item ['Gross'] = round($cart_item['price'], 2);
                            $order_item ['Quantity'] = $cart_item['quantity'];

                            if (isset($cart_item['item_number']['options']['price_id'])) {
                                // Do code for option name and option value

                                $price_id = $cart_item['item_number']['options']['price_id'];
                                $order_item['Option_1_Name'] = '';
                                $order_item['Option_1_Value'] = edd_get_price_option_name($cart_item['id'], $price_id, $order_id);
                            }

                            $transactions [] = array_merge($response, $order_item);

                            if ($order_status == "refunded") {

                                $date_gmt_modified = $order_detail['modified_date'];

                                $response ['Date'] = date('m/d/Y', (int) strtotime($date_gmt_modified));
                                $response ['Time'] = date('H:i:s', (int) strtotime($date_gmt_modified));

                                $response ['Type'] = 'Refund';
                                $response ['Status'] = 'Completed';
                                $response ['Gross'] = -$order_total;
                                $response ['Net'] = -$order_total;
                                $response ['Transaction_ID'] = $order_id . '_R';
                                $response ['Reference_Txn_ID'] = $order_id;

                                $transactions [] = $response;
                            }
                        }
                    }
                } else {
                    
                }

                if (empty($params['order_id'])) {
                    $order_count = (is_array($results_order_details)) ? count($results_order_details) : 0;
                    $params[$this->name] = array('count' => $order_count, 'last_start_limit' => $start_limit, 'data' => $transactions);
                } else {
                    $params['data'] = $transactions;
                }
            } else {
                
            }

            return $params;
        }

    }

}
