<?php
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

        //remove from cart
        register_rest_route('ade-woocart/v1', '/cart/(?P<key>\w+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'remove_from_cart'),
            'permission_callback' => array($this, 'permissions_check'),
        ));
    }

    //woo support
    public function check_woo_files()
    {
        if (defined('WC_ABSPATH')) {
            // WC 3.6+ - Cart and other frontend functions are not included for REST requests.
            include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
            include_once WC_ABSPATH . 'includes/wc-notice-functions.php';
            include_once WC_ABSPATH . 'includes/wc-template-hooks.php';
        }

        if (
            null === WC()->session
        ) {
            $session_class = apply_filters('woocommerce_session_handler', 'WC_Session_Handler');

            WC()->session = new $session_class();
            WC()->session->init();
        }

        if (
            null === WC()->customer
        ) {
            WC()->customer = new WC_Customer(get_current_user_id(), true);
        }

        if (
            null === WC()->cart
        ) {
            WC()->cart = new WC_Cart();

            // We need to force a refresh of the cart contents from session here (cart contents are normally refreshed on wp_loaded, which has already happened by this point).
            WC()->cart->get_cart();
        }

        return true;
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
        $this->user = $this->get_user_data_by_consumer_key($consumer_key);
        if (empty($this->user)) {
            return false;
        }

        // Validate user secret.
        if (!hash_equals($this->user->consumer_secret, $consumer_secret)) { // @codingStandardsIgnoreLine
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
        $user_id = $this->user->user_id;
        $user = get_user_by('id', $user_id);
        //if not logged in
        if (!is_user_logged_in()) {
            wp_set_current_user($user_id, $user->user_login);
            wp_set_auth_cookie($user_id);
        }
        $this->check_woo_files();
    }

    public function get_cart()
    {
        $cart = WC()->cart->get_cart();
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
        return $cart_data;
    }

    public function add_to_cart(WP_REST_Request $request)
    {
        $product_id = $request->get_param('product_id');
        $quantity = $request->get_param('quantity');

        //check if product exists
        if (!wc_get_product($product_id)) {
            return new WP_Error('product_not_found', 'Product not found', array('status' => 404));
        }

        //add to cart
        WC()->cart->add_to_cart($product_id, $quantity);

        return $this->get_cart();
    }

    //remove_from_cart
    public function remove_from_cart(WP_REST_Request $request)
    {
        $key = $request->get_param('key');
        //confirm key exists
        if (!isset(WC()->cart->cart_contents[$key])) {
            return new WP_Error('key_not_found', 'Key not found', array('status' => 404));
        }
        WC()->cart->remove_cart_item($key);
        return new WP_REST_Response([
            'message' => 'Item removed from cart',
            'cart' => $this->get_cart(),
        ], 200);
    }
}

//init
$ade_woo_cart = new AdeWooCart();
$ade_woo_cart->init();
