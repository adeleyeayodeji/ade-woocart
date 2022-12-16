<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
class AdeWooCartNoAuth
{
    public function init()
    {
        //rest api init
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes()
    {
        register_rest_route('ade-woocart-no-auth/v1', '/cart', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_cart'),
            'permission_callback' => array($this, 'permissions_check'),
        ));

        register_rest_route('ade-woocart-no-auth/v1', '/cart', array(
            'methods' => 'POST',
            'callback' => array($this, 'add_to_cart'),
            'permission_callback' => array($this, 'permissions_check'),
        ));

        //remove from cart
        register_rest_route('ade-woocart-no-auth/v1', '/cart', array(
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

    //permissions_check
    public function permissions_check()
    {
        return true;
    }

    //logUser
    public function logUser($user_id)
    {
        $user = get_user_by('id', $user_id);
        //if not logged in
        if (!is_user_logged_in()) {
            wp_set_current_user($user_id, $user->user_login);
            wp_set_auth_cookie($user_id);
        }
        $this->check_woo_files();
    }

    public function get_cart($request)
    {
        $user_id = $request->get_param('user_id');
        //check if user exists
        if (!get_user_by('id', $user_id)) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }
        //log user
        $this->logUser($user_id);
        //get cart
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
        $user_id = $request->get_param('user_id');

        //check if user exists
        if (!get_user_by('id', $user_id)) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }

        //log user
        $this->logUser($user_id);

        //check if product exists
        if (!wc_get_product($product_id)) {
            return new WP_Error('product_not_found', 'Product not found', array('status' => 404));
        }

        //add to cart
        WC()->cart->add_to_cart($product_id, $quantity);

        return $this->get_cart($request);
    }

    //remove_from_cart
    public function remove_from_cart(WP_REST_Request $request)
    {
        $key = $request->get_param('cart_key');
        $user_id = $request->get_param('user_id');
        //check if user exists
        if (!get_user_by('id', $user_id)) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }
        //log user
        $this->logUser($user_id);

        //confirm key exists
        if (!isset(WC()->cart->cart_contents[$key])) {
            return new WP_Error('key_not_found', 'Key not found', array('status' => 404));
        }
        WC()->cart->remove_cart_item($key);
        return new WP_REST_Response([
            'message' => 'Item removed from cart',
            'cart' => $this->get_cart($request),
        ], 200);
    }
}

//init
$ade_woo_cart = new AdeWooCartNoAuth();
$ade_woo_cart->init();
