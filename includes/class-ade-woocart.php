<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
class AdeWooCart
{
    public $user;

    public function init()
    {
        //rest api init
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes()
    {
        register_rest_route('ade-woocart/v1', '/cart', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_cart'),
            'permission_callback' => array($this, 'permissions_check'),
        ));

        register_rest_route('ade-woocart/v1', '/cart', array(
            'methods' => 'POST',
            'callback' => array($this, 'add_to_cart'),
            'permission_callback' => array($this, 'permissions_check'),
        ));

        //update cart
        register_rest_route('ade-woocart/v1', '/cart/update', array(
            'methods' => 'POST',
            'callback' => array($this, 'update_cart'),
            'permission_callback' => array($this, 'permissions_check'),
        ));

        //remove from cart
        register_rest_route('ade-woocart/v1', '/cart/delete', array(
            'methods' => 'POST',
            'callback' => array($this, 'remove_from_cart'),
            'permission_callback' => array($this, 'permissions_check'),
        ));

        //remove_all
        register_rest_route('ade-woocart/v1', '/cart/delete/all', array(
            'methods' => 'POST',
            'callback' => array($this, 'remove_all'),
            'permission_callback' => array($this, 'permissions_check'),
        ));
    }

    //user data
    private function get_user_data_by_consumer_key($consumer_key)
    {
        global $wpdb;

        $consumer_key = wc_api_hash(sanitize_text_field($consumer_key));
        $user         = $wpdb->get_row(
            $wpdb->prepare(
                "
			SELECT key_id, user_id, permissions, consumer_key, consumer_secret, nonces
			FROM {$wpdb->prefix}woocommerce_api_keys
			WHERE consumer_key = %s
		",
                $consumer_key
            )
        );

        return $user;
    }

    //authenticate
    public function wooAuth($consumer_key, $consumer_secret)
    {
        // Stop if don't have any key.
        if (!$consumer_key || !$consumer_secret) {
            return false;
        }

        // Get user data.
        $user = $this->get_user_data_by_consumer_key($consumer_key);
        if (empty($user)) {
            return false;
        }

        // Validate user secret.
        if (!hash_equals($user->consumer_secret, $consumer_secret)) { // @codingStandardsIgnoreLine
            return false;
        }

        //log user
        $this->logUser();

        return true;
    }

    //permissions_check
    public function permissions_check()
    {
        $wc_ck = $_SERVER['PHP_AUTH_USER'];
        $wc_cs = $_SERVER['PHP_AUTH_PW'];
        //validate the credentials
        return $this->wooAuth($wc_ck, $wc_cs);
    }

    //logUser
    public function logUser()
    {
        //get $_get 'user_email'
        $username = sanitize_text_field(filter_input(INPUT_GET, 'username', FILTER_SANITIZE_STRING));

        //if empty
        if (empty($username)) {
            return new WP_REST_Response([
                'message' => 'Username is required',
                'status' => 'error',
            ], 200);
        }

        $user = get_user_by('login', $username);

        $this->user = $user;

        //if not logged in
        if (!is_user_logged_in()) {
            wp_set_current_user($user->ID, $user->user_login);
            wp_set_auth_cookie($user->ID);
        }
    }

    public function get_cart()
    {
        //check if class exists
        if (!class_exists('WC_Cart')) {
            return new WP_REST_Response([
                'message' => 'WooCommerce is not installed',
                'status' => 'error',
            ], 200);
        }
        $cart = WC()->cart->get_cart();
        //get current user ip
        $user_ip = $_SERVER['REMOTE_ADDR'];
        //check if cart is empty
        if (empty($cart)) {
            return new WP_REST_Response(array(
                'username' => $this->user->user_login,
                'message' => 'Cart is empty',
                'data' => $user_ip,
                'time' => current_time('mysql'),
                'cart_count' => 0,
                'cart_items' => [],
            ), 200);
        }
        //loop through cart
        $cart_data = [];
        foreach ($cart as $key => $value) {
            $product = wc_get_product($value['product_id']);
            $cart_data[] = array(
                'key' => $key,
                'product_id' => $value['product_id'],
                'product_name' => $product->get_name(),
                'product_price' => $product->get_price(),
                'product_image' => get_the_post_thumbnail_url($value['product_id'], 'thumbnail'),
                'quantity' => $value['quantity'],
            );
        }
        return new WP_REST_Response(array(
            'username' => $this->user->user_login,
            'message' => 'Cart items',
            'data' => $user_ip,
            'time' => current_time('mysql'),
            'cart_count' => count($cart_data),
            'cart_items' => $cart_data,
        ), 200);
    }

    public function add_to_cart(WP_REST_Request $request)
    {
        $product_id = $request->get_param('product_id');
        $quantity = $request->get_param('quantity');

        //check if product exists
        if (!wc_get_product($product_id)) {
            return new WP_REST_Response([
                'message' => 'Product not found',
                'status' => 'error',
            ], 200);
        }

        //add to cart
        WC()->cart->add_to_cart($product_id, $quantity);

        //calculate totals
        WC()->cart->calculate_totals();

        return new WP_REST_Response([
            'message' => 'Item added to cart',
            'status' => 'success',
            'data' => [
                'product_id' => $product_id,
                'quantity' => $quantity,
                'username' => $this->user->user_login,
            ]
        ], 200);
    }

    /**
     * Get Cart Key by Product ID
     */
    public function get_cart_key_by_product_id($product_id)
    {
        $cart = WC()->cart->get_cart();
        foreach ($cart as $key => $value) {
            if ($value['product_id'] == $product_id) {
                return $key;
            }
        }
        return false;
    }

    //update_cart
    public function update_cart(WP_REST_Request $request)
    {
        $product_id = $request->get_param('product_id');
        $quantity = $request->get_param('quantity');

        //check if product exists
        if (!wc_get_product($product_id)) {
            return new WP_REST_Response([
                'message' => 'Product not found',
                'status' => 'error',
            ], 200);
        }

        $product_key = $this->get_cart_key_by_product_id($product_id);

        //update cart
        WC()->cart->set_quantity($product_key, $quantity);

        return new WP_REST_Response([
            'message' => 'Item updated',
            'status' => 'success',
            'data' => [
                'product_id' => $product_id,
                'quantity' => $quantity,
                'username' => $this->user->user_login,
            ]
        ], 200);
    }

    //remove_from_cart
    public function remove_from_cart(WP_REST_Request $request)
    {
        $product_id = $request->get_param('product_id');

        //check if product exists
        if (!wc_get_product($product_id)) {
            return new WP_REST_Response([
                'message' => 'Product not found',
                'status' => 'error',
            ], 200);
        }

        $product_key = $this->get_cart_key_by_product_id($product_id);

        //remove from cart
        WC()->cart->remove_cart_item($product_key);

        return new WP_REST_Response([
            'message' => 'Item removed',
            'status' => 'success',
            'data' => [
                'product_id' => $product_id,
                'username' => $this->user->user_login,
            ]
        ], 200);
    }

    /**
     * remove_all
     */
    public function remove_all()
    {
        //remove all
        WC()->cart->empty_cart();

        return new WP_REST_Response([
            'message' => 'Cart emptied',
            'status' => 'success',
            'data' => [
                'username' => $this->user->user_login,
            ]
        ], 200);
    }
}

//init
$ade_woo_cart = new AdeWooCart();
$ade_woo_cart->init();
