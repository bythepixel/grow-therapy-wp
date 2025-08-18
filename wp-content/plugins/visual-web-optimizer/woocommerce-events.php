<?php
/**
 * WooCommerce Event Tracking for VWO
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

include_once plugin_dir_path(__FILE__) . 'VwoPlugin.php';
// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}
function vwo_plugin_activate() {
    vwo_track_woocommerce_events(); // Ensure hooks are registered
}
register_activation_hook(__FILE__, 'vwo_plugin_activate');
register_deactivation_hook(__FILE__, 'vwo_plugin_deactivate');
// Hook into WooCommerce events if enabled in VWO settings
function vwo_track_woocommerce_events() {

    add_action('woocommerce_after_single_product', 'vwo_track_product_view');
    add_action('woocommerce_after_single_product', 'server_vwo_track_product_view');

    add_action('woocommerce_after_add_to_cart_button', 'vwo_track_add_to_cart', 10, 6);
    add_action('woocommerce_add_to_cart', 'server_vwo_track_add_to_cart', 10, 6);

    add_action('woocommerce_cart_item_removed', 'server_vwo_track_remove_from_cart', 10, 2);
    add_action('woocommerce_after_cart', 'vwo_track_remove_from_cart', 10, 2);

    add_action('woocommerce_thankyou', 'vwo_track_purchase');
    add_action('woocommerce_thankyou', 'server_vwo_track_purchase', 10, 1);
}

function vwo_plugin_deactivate() {
    // Remove WooCommerce event tracking hooks
    remove_action('woocommerce_after_single_product', 'vwo_track_product_view');
    remove_action('woocommerce_after_single_product', 'server_vwo_track_product_view');

    remove_action('woocommerce_after_add_to_cart_button', 'vwo_track_add_to_cart', 10, 6);
    remove_action('woocommerce_add_to_cart', 'server_vwo_track_add_to_cart', 10, 6);

    remove_action('woocommerce_cart_item_removed', 'server_vwo_track_remove_from_cart', 10, 2);
    remove_action('woocommerce_after_cart', 'vwo_track_remove_from_cart', 10, 2);

    remove_action('woocommerce_before_checkout_form', 'vwo_track_checkout', 10, 6);
    remove_action('woocommerce_checkout_before_customer_details', 'server_vwo_track_checkout', 10, 3);

    remove_action('woocommerce_thankyou', 'vwo_track_purchase');
    remove_action('woocommerce_thankyou', 'server_vwo_track_purchase', 10, 1);
}

add_action('init', 'vwo_track_woocommerce_events');
add_action('wp_footer', function () {
    $server_side_enabled = get_option('vwo_server_side_tracking', false);
    $enable_woocommerce_event_tracking= get_option('enable_woocommerce_event_tracking', false);
    if ($server_side_enabled || !$enable_woocommerce_event_tracking)
        return true;
    $track_add_to_cart = get_option('track_add_to_cart', false);
    if($track_add_to_cart ){
      wc_enqueue_js(VwoPlugin::render( 'loop' ));
    }
    $track_remove_from_cart = get_option('track_remove_from_cart', false);
    
    if($track_remove_from_cart){
      wc_enqueue_js(VwoPlugin::render( 'remove-mini' ));
    }
    $track_purchase = get_option('track_purchase', false);
    if ( $track_purchase && is_order_received_page() and $order = get_order() ) {
        wc_enqueue_js(VwoPlugin::render( 'order', get_order_data( $order) ));
        $order->update_meta_data( '_vwo_order_tracked', 1 );
        $order->save();
    }
});

// Product view event
function vwo_track_product_view() {
    global $product;
    $server_side_enabled = get_option('vwo_server_side_tracking', false);
    $track_product_view = get_option('track_product_view', false);
    $enable_woocommerce_event_tracking= get_option('enable_woocommerce_event_tracking',false);
    if ($server_side_enabled || !$product || !$enable_woocommerce_event_tracking || !$track_product_view)
        return;
        
    wc_enqueue_js(VwoPlugin::render('product', get_product_data($product->get_id())));
}

// Add to cart event
function vwo_track_add_to_cart() {
    $server_side_enabled = get_option('vwo_server_side_tracking', false);
    $enable_woocommerce_event_tracking= get_option('enable_woocommerce_event_tracking', false);
    $track_add_to_cart = get_option('track_add_to_cart', false);
    if ($server_side_enabled || ! is_single() || !$enable_woocommerce_event_tracking || !$track_add_to_cart)
        return;
        
    global $product;
    wc_enqueue_js( VwoPlugin::render( 'add', get_product_data( $product->get_id() ) ) );
}

function vwo_track_remove_from_cart() {
    $server_side_enabled = get_option('vwo_server_side_tracking', false);
    $enable_woocommerce_event_tracking= get_option('enable_woocommerce_event_tracking', false);
    $track_remove_from_cart = get_option('track_remove_from_cart', false);
    if ($server_side_enabled || !$enable_woocommerce_event_tracking || !$track_remove_from_cart)
        return;
    wc_enqueue_js(VwoPlugin::render( 'remove' ));
}

// Checkout event
function vwo_track_checkout() {
    $server_side_enabled = get_option('vwo_server_side_tracking', false);
    $enable_woocommerce_event_tracking= get_option('enable_woocommerce_event_tracking', false);
    if ($server_side_enabled || $enable_woocommerce_event_tracking)
        return;
    wc_enqueue_js(VwoPlugin::render( 'checkout' ));
}

function vwo_track_purchase($order_id) {
    $server_side_enabled = get_option('vwo_server_side_tracking', false);
    $track_purchase = get_option('track_purchase', false);
    $enable_woocommerce_event_tracking= get_option('enable_woocommerce_event_tracking', false);
    if ($server_side_enabled || $enable_woocommerce_event_tracking || !$track_purchase)
        return;
    $order = wc_get_order($order_id);
    wc_enqueue_js(VwoPlugin::render( 'order', get_order_data( $order) ));
}

function get_product_categories( $product_id ) {
    $categories = [];
    foreach ( get_the_terms( $product_id, 'product_cat' ) as $term )
        $categories[] = esc_js( $term->name );

    switch ( count( $categories ) ) {
        case 0: return '';
        case 1: return $categories[ 0 ];
        default : return array_slice( $categories, 0, 5 );
    }
}

function get_product_data( $product_id, $quantity = 1 ) {
    return ( $product = wc_get_product( $product_id ) ) ? [
        'variantId'=> esc_js($product->get_sku()),
        'productSku' => esc_js( $product->get_sku() ?: ( '#' . $product->get_id() ) ),
        'productTitle' => esc_js( $product->get_title() ),
        'productId' => esc_js($product->get_id()),
        'productCategory' => get_product_categories( $product->get_id() ),
        'price' => floatval( $product->get_price() ),
        'currency' => get_woocommerce_currency(),
        'quantity' => intval( $quantity ),
        'productUrl' => esc_url(get_permalink($product->get_id()))
    ] : [];
}

function get_order() {
    global $wp;

    $order_id = is_numeric( $wp->query_vars[ 'order-received' ] ) ? intval( $wp->query_vars[ 'order-received' ] ) : 0;
    if ( ! $order_id ) return null;

    $order = wc_get_order( $order_id );
    if ( $order and $order->has_status( 'failed' ) ) return null;
    if ( $order and (bool)$order->get_meta( '_vwo_order_tracked' ) ) return null;

    return $order;
}

function get_order_data( $order ) {
    $order_data = [
        'orderId'       => $order->get_id(),
        'totalPrice'    => floatval( $order->get_total() ),
        'shippingPrice' => floatval( $order->get_shipping_total() ),
        'totalTax'      => floatval( $order->get_total_tax() ),
        'discount'      => floatval( $order->get_total_discount() ),
        'currencyCode'  => $order->get_currency(),
        'vwoMeta'=> array(
            'source'=> "woocommerce-server",
        )
    ];

    // Get products from order
    $productIds = [];
    $productSkus = [];
    $productPrices = [];
    $productQuantities = [];

    foreach ( $order->get_items() as $item_id => $item ) {
        $product = $item->get_product();
        if ( $product ) {
            $productIds[] = $product->get_id();
            $productSkus[] = $product->get_sku();
            $productPrices[] = floatval( $item->get_total() );
            $productQuantities[] = intval( $item->get_quantity() );
        }
    }

    // Convert arrays to comma-separated strings
    $order_data['productId'] = implode(",", $productIds);
    $order_data['productSku'] = implode(",", $productSkus);
    $order_data['productPrice'] = implode(",", $productPrices);
    $order_data['productQuantity'] = implode(",", $productQuantities);

    return $order_data;
}


function server_vwo_track_product_view() {
    $server_side_enabled = get_option('vwo_server_side_tracking', false);
    $track_product_view = get_option('track_product_view', false);
    if (!$server_side_enabled || !$track_product_view  ||  !is_product())
        return;
    global $product;
    $vis_id = isset($_COOKIE['_vwo_uuid']) ? $_COOKIE['_vwo_uuid'] : '';
    // if (!$vis_id) return;
    $eventName="woocommerce.productViewed";
    $categories = get_product_categories( $product->get_id() );

    // Ensure it is an array before using implode
    if (is_array($categories)) {
        $category_list = implode(",", $categories);
    } else {
        $category_list = $categories; // If it's a string, use it directly
    }
    $event_data = array(
        'd' => array(
            'msgId' => $vis_id . '-' . time() . rand(1000, 9999),
            'visId' => $vis_id,
            'event' => array(
                'props' => array(
                    'variantId'       => $product->get_sku(),
                    'productSku'      => $product->get_sku(),
                    'productId'       => $product->get_id(),
                    'productTitle'    => $product->get_name(),
                    'productCategory' => $category_list,
                    'currency'        => get_woocommerce_currency(),
                    'price'           => $product->get_price(),
                    'quantity'        => 1,
                    'productUrl'      => get_permalink($product->get_id()),
                    'page'            => array(
                        'title' => get_the_title(),
                        'url'   => get_permalink()
                    ),
                    "vwoMeta"=> array(
                        "source"=> "woocommerce-server",
                    )
                ),
                'name' => $eventName,
                'time' => time() * 1000,
            ),
            'sessionId' => time(),
        ),
    );
    $resonse=vwo_send_event_to_vwo($eventName, $event_data);
}

function server_vwo_track_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data){
    $server_side_enabled = get_option('vwo_server_side_tracking', false);
    $track_add_to_cart = get_option('track_add_to_cart', false);
    if (!$server_side_enabled || !$track_add_to_cart)
        return;
    $eventName= "woocommerce.addToCart";
    $vis_id = isset($_COOKIE['_vwo_uuid']) ? $_COOKIE['_vwo_uuid'] : '';
    // if (!$vis_id) return;
    $product_data= get_product_data($product_id);
    $categories = get_product_categories($product_id);
    // Ensure it is an array before using implode
    if (is_array($categories)) {
        $category_list = implode(",", $categories);
    } else {
        $category_list = $categories; // If it's a string, use it directly
    }
    $event_data = array(
        'd' => array(
            'msgId' => $vis_id . '-' . time() . rand(1000, 9999),
            'visId' => $vis_id,
            'event' => array(
                'props' => array(
                    'variantId'       => $variation_id,
                    'productSku'      => $product_data['productSku'],
                    'productId'       => $product_data['productId'],
                    'productTitle'    => $product_data['productTitle'],
                    'productCategory' => $category_list,
                    'currency'        => get_woocommerce_currency(),
                    'price'           => $product_data['price'],
                    'quantity'        => 1,
                    'productUrl'      => $product_data['productUrl'],
                    'page'            => array(
                        'title' => get_the_title(),
                        'url'   => get_permalink()
                    ),
                    "vwoMeta"=> array(
                        "source"=> "woocommerce-server",
                    )
                ),
                'name' => $eventName,
                'time' => time() * 1000,
            ),
            'sessionId' => time(),
        ),
    );
    $response=vwo_send_event_to_vwo($eventName, $event_data);
}

function server_vwo_track_remove_from_cart($cart_item_key, $cart){
    $server_side_enabled = get_option('vwo_server_side_tracking', false);
    $track_remove_from_cart = get_option('track_remove_from_cart', false);
    if (!$server_side_enabled || !$track_remove_from_cart)
        return;
    $eventName= "woocommerce.removeFromCart";
    $vis_id = isset($_COOKIE['_vwo_uuid']) ? $_COOKIE['_vwo_uuid'] : '';
    // if (!$vis_id) return;
    $cart_item = $cart->removed_cart_contents[$cart_item_key];
    $product_id= $cart_item['product_id'];
    $quantity= $cart_item['quantity'];
    $variation_id= $cart_item['variation_id'];
    $product_data= get_product_data($product_id);
    $event_data = array(
        'd' => array(
            'msgId' => $vis_id . '-' . time() . rand(1000, 9999),
            'visId' => $vis_id,
            'event' => array(
                'props' => array(
                    'variantId'       => $variation_id,
                    'productSku'      => $product_data['productSku'],
                    'productId'       => $product_data['productId'],
                    'productTitle'    => $product_data['productTitle'],
                    'currency'        => get_woocommerce_currency(),
                    'price'           => $product_data['price'],
                    'quantity'        => $quantity,
                    'productUrl'      => $product_data['productUrl'],
                    'page'            => array(
                        'title' => get_the_title(),
                        'url'   => get_permalink()
                    ),
                    "vwoMeta"=> array(
                        "source"=> "woocommerce-server",
                    )
                ),
                'name' => $eventName,
                'time' => time() * 1000,
            ),
            'sessionId' => time(),
        ),
    );
    return vwo_send_event_to_vwo($eventName, $event_data);
}

function server_vwo_track_checkout(){
    
    $track_checkout = get_option('track_checkout', false);
    $server_side_enabled = get_option('vwo_server_side_tracking', false);
    if (!$server_side_enabled || !$track_checkout)
        return;
    $eventName= "woocommerce.checkoutStarted";
    $vis_id = isset($_COOKIE['_vwo_uuid']) ? $_COOKIE['_vwo_uuid'] : '';
    // if (!$vis_id) return;

    $event_data = array(
        'd' => array(
            'msgId' => $vis_id . '-' . time() . rand(1000, 9999),
            'visId' => $vis_id,
            'event' => array(
                'props' => array(
                    'page'            => array(
                        'title' => get_the_title(),
                        'url'   => get_permalink()
                    ),
                    "vwoMeta"=> array(
                        "source"=> "woocommerce-server",
                    )
                ),
                'name' => $eventName,
                'time' => time() * 1000,
            ),
            'sessionId' => time(),
        ),
    );
    return vwo_send_event_to_vwo( $eventName, $event_data);
}

function server_vwo_track_purchase($order_id){
    $server_side_enabled = get_option('vwo_server_side_tracking', false);
    $track_purchase = get_option('track_purchase', false);
    if (!$server_side_enabled || ! $track_purchase)
        return;
    $eventName= "woocommerce.purchase";
    $vis_id = isset($_COOKIE['_vwo_uuid']) ? $_COOKIE['_vwo_uuid'] : '';
    // if (!$vis_id) return;
    $order = wc_get_order($order_id);
    $order_data = get_order_data( $order);
    $order_data['page' ]=array(
        'title' => get_the_title(),
        'url'   => get_permalink()
    );
    $event_data = array(
        'd' => array(
            'msgId' => $vis_id . '-' . time() . rand(1000, 9999),
            'visId' => $vis_id,
            'event' => array(
                'props' => $order_data,
                'name' => $eventName,
                'time' => time() * 1000,
            ),
            'sessionId' => time(),
        ),
    );
    $order->update_meta_data( '_vwo_order_tracked', 1 );
    $order->save();
    return vwo_send_event_to_vwo($eventName, $event_data);
}


function vwo_send_event_to_vwo($eventName, $eventData) {
    $vwo_account_id = get_option('vwo_id');
    $vwo_coll_url = get_option('vwo_coll_url');
    $enable_woocommerce_event_tracking= get_option('enable_woocommerce_event_tracking');
    if(!$enable_woocommerce_event_tracking){
        return;
    }
    // Construct the URL based on collUrl availability
    if(!$vwo_coll_url){
        $vwo_coll_url=vwo_clhf_fetch_and_save_coll_url($vwo_account_id);
    }
    $url = $vwo_coll_url
        ? "https://$vwo_coll_url/events/t?en=$eventName&a=$vwo_account_id"
        : "https://dev.visualwebsiteoptimizer.com/events/t?en=$eventName&a=$vwo_account_id";
    
    // Set User-Agent, default to "vwo-woocommerce-plugin" if not provided
    $user_agent = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "vwo-woocommerce-plugin";

    $response = wp_remote_post($url, array(
        'body'    => json_encode($eventData),
        'headers' => array(
            'Content-Type' => 'application/json',
            'User-Agent'   => $user_agent
        ),
        'timeout' => 20,
    ));

    return $response;
}
function vwo_clhf_fetch_and_save_coll_url($vwo_id) {

    if (empty($vwo_id) || !is_numeric($vwo_id)) {
        return;
    }

    $api_url = "https://dev.visualwebsiteoptimizer.com/accInfo?a=" . $vwo_id;
    $response = wp_remote_get($api_url, array(
        'timeout' => 15,
        'sslverify' => false, // Sometimes WordPress has SSL issues
        'headers' => array(
            'Accept-Encoding' => 'gzip, deflate, br',
            'User-Agent' => 'vwo-woocommerce-plugin'
        )
    ));
    if (is_wp_error($response)) {
        return;
    }
    $http_code = wp_remote_retrieve_response_code($response);
    // Extract body
    $body = wp_remote_retrieve_body($response);
    if (empty($body)) {
        return '';
    }
    $data = json_decode($body, true);
    if (!empty($data['collUrl'])) {
        $collUrl=$data['collUrl'];
        update_option('vwo_coll_url', sanitize_text_field($data['collUrl']));
    }
    return $collUrl;
}

?>
