<?php
/**
 *
 * Plugin Name: VWO
 * Plugin URI: https://vwo.com/
 * Description: VWO is the all-in-one platform that helps you conduct visitor research, build an optimization roadmap, and run continuous experimentation. Simply enable the plugin and start running tests on your WordPress website without doing any other code changes. Visit <a href="https://vwo.com/">VWO</a> for more details.
 * Author: VWO
 * Version: 4.8
 * visual-website-optimizer.php
 * Author URI: https://vwo.com/
 *
 * @package VWO
 * @author VWO
 * @version 4.8
 **/

/**
 * Generate Common Code
 *
 * @param integer $vwo_clicks integer. Defaults to 10.
 */
function get_vwo_clhf_script_common_code( $vwo_clicks = 10 ) {
    ob_start();
    // @codingStandardsIgnoreStart
    ?>
    <!-- Start VWO Common Smartcode -->
    <script <?php echo vwo_clhf_ignore_js_attr(); ?> type='text/javascript'>
        var _vwo_clicks = <?php echo esc_html( $vwo_clicks ); ?>;
    </script>
    <!-- End VWO Common Smartcode -->
    <?php
    // @codingStandardsIgnoreEnd
    $script_code = ob_get_clean();
    return $script_code;
}

/**
 * Get ignore js field setting
 *
 * @return bool boolean
 */
function get_vwo_clhf_ignore_js() {
    $ignore_js = get_option( 'ignore_js' );
    $ignore_js = ( '1' === $ignore_js ) ? true : false;
    return $ignore_js;
}

/**
 * Get ignore js script attribute value
 *
 * @return string Returns script attribute value
 */
function vwo_clhf_ignore_js_attr() {
    $ignore_js = get_vwo_clhf_ignore_js();
    $js_attr   = '';
    if ( function_exists( 'get_vwo_clhf_ignore_js' ) && $ignore_js ) {
        $js_attr = 'data-cfasync="false" nowprocket';
    }
    return $js_attr;
}

/**
 * Generate Synchronous Code
 *
 * @param int $vwo_id integer. Defaults to 0.
 * @return string string for sync script.
 */
function get_vwo_clhf_script_sync_code( $vwo_id = 0 ) {
    ob_start();
    // @codingStandardsIgnoreStart
    ?>
    <!-- Start VWO Smartcode -->
    <script <?php echo vwo_clhf_ignore_js_attr(); ?> src="https://dev.visualwebsiteoptimizer.com/lib/<?php echo esc_html( $vwo_id ); ?>.js"></script>
    <!-- End VWO Smartcode -->
    <?php
    // @codingStandardsIgnoreEnd
    $sync_script = ob_get_clean();
    return $sync_script;
}

/**
 * Generate Asynchronous Code
 *
 * @param int  $vwo_id integer.
 * @param int  $settings_tolerance integer.
 * @param int  $library_tolerance integer.
 * @param bool $use_existing_jquery boolean.
 * @return string String for async script.
 */
function get_vwo_clhf_script_async_code( $vwo_id, $settings_tolerance, $library_tolerance, $use_existing_jquery ) {
    ob_start();
    // @codingStandardsIgnoreStart
    ?>
    <!-- Start VWO Async SmartCode -->
    <link rel="preconnect" href="https://dev.visualwebsiteoptimizer.com" />
    <script <?php echo vwo_clhf_ignore_js_attr(); ?> type='text/javascript' id='vwoCode'>
        /* Fix: wp-rocket (application/ld+json) */
        window._vwo_code || (function () {
            var account_id=<?php echo esc_html( $vwo_id ); ?>,
                version=2.1,
                settings_tolerance=<?php echo esc_html( $settings_tolerance ); ?>,
                library_tolerance=<?php echo esc_html( $library_tolerance ); ?>,
                use_existing_jquery=<?php echo ( $use_existing_jquery ) ? 'true' : 'false'; ?>,
                hide_element='body',
                hide_element_style = 'opacity:0 !important;filter:alpha(opacity=0) !important;background:none !important;transition:none !important;',
                /* DO NOT EDIT BELOW THIS LINE */
                f=false,w=window,d=document,v=d.querySelector('#vwoCode'),cK='_vwo_'+account_id+'_settings',cc={};try{var c=JSON.parse(localStorage.getItem('_vwo_'+account_id+'_config'));cc=c&&typeof c==='object'?c:{}}catch(e){}var stT=cc.stT==='session'?w.sessionStorage:w.localStorage;code={use_existing_jquery:function(){return typeof use_existing_jquery!=='undefined'?use_existing_jquery:undefined},library_tolerance:function(){return typeof library_tolerance!=='undefined'?library_tolerance:undefined},settings_tolerance:function(){return cc.sT||settings_tolerance},hide_element_style:function(){return'{'+(cc.hES||hide_element_style)+'}'},hide_element:function(){if(performance.getEntriesByName('first-contentful-paint')[0]){return''}return typeof cc.hE==='string'?cc.hE:hide_element},getVersion:function(){return version},finish:function(e){if(!f){f=true;var t=d.getElementById('_vis_opt_path_hides');if(t)t.parentNode.removeChild(t);if(e)(new Image).src='https://dev.visualwebsiteoptimizer.com/ee.gif?a='+account_id+e}},finished:function(){return f},addScript:function(e){var t=d.createElement('script');t.type='text/javascript';if(e.src){t.src=e.src}else{t.text=e.text}d.getElementsByTagName('head')[0].appendChild(t)},load:function(e,t){var i=this.getSettings(),n=d.createElement('script'),r=this;t=t||{};if(i){n.textContent=i;d.getElementsByTagName('head')[0].appendChild(n);if(!w.VWO||VWO.caE){stT.removeItem(cK);r.load(e)}}else{var o=new XMLHttpRequest;o.open('GET',e,true);o.withCredentials=!t.dSC;o.responseType=t.responseType||'text';o.onload=function(){if(t.onloadCb){return t.onloadCb(o,e)}if(o.status===200||o.status===304){_vwo_code.addScript({text:o.responseText})}else{_vwo_code.finish('&e=loading_failure:'+e)}};o.onerror=function(){if(t.onerrorCb){return t.onerrorCb(e)}_vwo_code.finish('&e=loading_failure:'+e)};o.send()}},getSettings:function(){try{var e=stT.getItem(cK);if(!e){return}e=JSON.parse(e);if(Date.now()>e.e){stT.removeItem(cK);return}return e.s}catch(e){return}},init:function(){if(d.URL.indexOf('__vwo_disable__')>-1)return;var e=this.settings_tolerance();w._vwo_settings_timer=setTimeout(function(){_vwo_code.finish();stT.removeItem(cK)},e);var t;if(this.hide_element()!=='body'){t=d.createElement('style');var i=this.hide_element(),n=i?i+this.hide_element_style():'',r=d.getElementsByTagName('head')[0];t.setAttribute('id','_vis_opt_path_hides');v&&t.setAttribute('nonce',v.nonce);t.setAttribute('type','text/css');if(t.styleSheet)t.styleSheet.cssText=n;else t.appendChild(d.createTextNode(n));r.appendChild(t)}else{t=d.getElementsByTagName('head')[0];var n=d.createElement('div');n.style.cssText='z-index: 2147483647 !important;position: fixed !important;left: 0 !important;top: 0 !important;width: 100% !important;height: 100% !important;background: white !important;';n.setAttribute('id','_vis_opt_path_hides');n.classList.add('_vis_hide_layer');t.parentNode.insertBefore(n,t.nextSibling)}var o='https://dev.visualwebsiteoptimizer.com/j.php?a='+account_id+'&u='+encodeURIComponent(d.URL)+'&vn='+version;if(w.location.search.indexOf('_vwo_xhr')!==-1){this.addScript({src:o})}else{this.load(o+'&x=true')}}};w._vwo_code=code;code.init();})();
    </script>
    <!-- End VWO Async SmartCode -->
    <?php
    // @codingStandardsIgnoreEnd
    $async_script = ob_get_clean();
    return $async_script;
}

// ------------------------------------------------------------------------//
// ---Hook-----------------------------------------------------------------//
// ------------------------------------------------------------------------//
add_action( 'wp_head', 'vwo_clhf_headercode', 1 );
add_action( 'admin_menu', 'vwo_clhf_plugin_menu' );
add_action( 'admin_init', 'vwo_clhf_register_mysettings' );
add_action( 'admin_notices', 'vwo_clhf_warn_nosettings' );


// ------------------------------------------------------------------------//
// ---Functions------------------------------------------------------------//
// ------------------------------------------------------------------------//
// options page link

/**
 * Generate a function comment for the given function body.
 *
 * @throws Exception Description of exception.
 * @return void
 */
function vwo_clhf_plugin_menu() {
    add_options_page( 'Visual Website Optimizer', 'VWO', 'create_users', 'clhf_vwo_options', 'vwo_clhf_plugin_options' );
}

/**
 * Register the settings for the VWO options.
 *
 * @return void
 */
function vwo_clhf_register_mysettings() {
    register_setting('clhf_vwo_options', 'vwo_id', array(
        'sanitize_callback' => 'vwo_clhf_sanitize_settings',
    ));
    register_setting( 'clhf_vwo_options', 'code_type' );
    register_setting( 'clhf_vwo_options', 'vwo_clicks' );
    register_setting( 'clhf_vwo_options', 'ignore_js', 'boolval' );
    register_setting( 'clhf_vwo_options', 'settings_tolerance', 'intval' );
    register_setting( 'clhf_vwo_options', 'library_tolerance', 'intval' );
    register_setting( 'clhf_vwo_options', 'use_existing_jquery', 'boolval' );
    register_setting( 'clhf_vwo_options', 'enable_woocommerce_event_tracking', 'boolval');
    register_setting( 'clhf_vwo_options', 'track_product_view', 'boolval' );
    register_setting( 'clhf_vwo_options', 'track_add_to_cart', 'boolval' );
    register_setting( 'clhf_vwo_options', 'track_remove_from_cart', 'boolval' );
    register_setting( 'clhf_vwo_options', 'track_checkout', 'boolval' );
    register_setting( 'clhf_vwo_options', 'track_purchase', 'boolval' );
    register_setting( 'clhf_vwo_options', 'vwo_server_side_tracking', 'boolval' );
}

// ------------------------------------------------------------------------//
// ---Output Functions-----------------------------------------------------//
// ------------------------------------------------------------------------//


/**
 * Generates the action vwo_clhf_headercode function.
 *
 * @throws Exception Description of exception.
 * @return void
 */
function vwo_clhf_headercode() {
    // Runs in the header.
    $vwo_id    = get_option( 'vwo_id' );
    $code_type = get_option( 'code_type' );

    if ( $vwo_id ) {
        if ( empty( get_option( 'vwo_clicks' ) ) ) {
            update_option( 'vwo_clicks', '10' );
        }
        $vwo_clicks = get_option( 'vwo_clicks' );

        // Common script code.
        // @codingStandardsIgnoreLine
        echo get_vwo_clhf_script_common_code( $vwo_clicks );

        if ( 'SYNC' === $code_type ) {
            // Sync script code.
            // @codingStandardsIgnoreLine
            echo get_vwo_clhf_script_sync_code( $vwo_id );
        } else {

            $settings_tolerance = get_option( 'settings_tolerance' );
            if ( ! is_numeric( $settings_tolerance ) ) {
                $settings_tolerance = 2000;
            }

            $library_tolerance = get_option( 'library_tolerance' );
            if ( ! is_numeric( $library_tolerance ) ) {
                $library_tolerance = 2500;
            }

            $use_existing_jquery = get_option( 'use_existing_jquery' );
            $use_existing_jquery = ( '1' === $use_existing_jquery ) ? true : false;

            // Async script code.
            // @codingStandardsIgnoreLine
            echo get_vwo_clhf_script_async_code( $vwo_id, $settings_tolerance, $library_tolerance, $use_existing_jquery );
        }
    }
}

// ------------------------------------------------------------------------//
// ---Page Output Functions------------------------------------------------//
// ------------------------------------------------------------------------//
// options page


/**
 * Generates the options page for the VWO plugin.
 *
 * @throws Exception Description of exception.
 * @return void
 */
function vwo_clhf_plugin_options() {

    ?>
    <div class="wrap">
        <h1 style="margin-bottom: 15px;"><img src="https://static.wingify.com/gcp/images/vwo-logo-color.svg" alt="VWO Logo" style="vertical-align: middle; margin-right: 10px; margin-top: -5px; height: 20px;">Configuration</h1>

        <form method="post" action="options.php" novalidate>
            <?php
            settings_fields('clhf_vwo_options');
            ?>
            <div class="vwo-admin-content">
                <?php vwo_clhf_render_tabs(); ?>
                <div class="tab-content">
                    <div id="tab-general" class="tab-pane active">
                        <?php vwo_clhf_render_general_settings(); ?>
                    </div>
                    <div id="tab-advanced" class="tab-pane">
                        <?php vwo_clhf_render_advanced_settings(); ?>
                    </div>
                    <div id="tab-woocommerce" class="tab-pane">
                        <?php vwo_clhf_render_woocommerce_settings(); ?>
                    </div>
                </div>
            </div>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
    vwo_clhf_render_styles();
    vwo_clhf_render_scripts();
}

function vwo_clhf_render_tabs() {
    ?>
    <h2 class="nav-tab-wrapper">
        <a href="#tab-general" class="nav-tab nav-tab-active">General Settings</a>
        <a href="#tab-advanced" class="nav-tab">Advanced Settings</a>
        <a href="#tab-woocommerce" class="nav-tab">WooCommerce</a>
    </h2>
    <?php
}

function vwo_clhf_render_general_settings() {
    ?>
    <main class="main-grid">
        <div class="form-container">
            <?php
            vwo_clhf_render_field([
                'id' => 'vwo_id',
                'label' => 'Your VWO Account ID',
                'type' => 'text',
                'tooltip' => 'Enter your VWO Account ID here',
            ]);
            vwo_clhf_render_field([
                'id' => 'code_type',
                'label' => 'Code Type',
                'type' => 'radio',
                'options' => [
                    [
                        'label' => 'Asynchronous',
                        'value' => 'ASYNC',
                        'sublabel' => 'Loads faster without blocking other elements',
                    ],
                    [
                        'label' => 'Synchronous',
                        'value' => 'SYNC',
                        'sublabel' => 'Executes immediately but may slow down page load',
                    ],
                ],
            ]);
            ?>
        </div>
        <div class="main-grid__description" style="width:350px;">
        <div style="font-size: 18px; font-weight: 700; color: #000000; margin-bottom: 8px;">New to VWO?</div>
            <div style="font-size: 14px; color: #757575; font-weight: 400; line-height: 20px; margin-bottom: 20px;">Create a free account to start optimising your website, no credit card required.</div>
            <div>
            <a  target="_blank" href="https://vwo.com/free-trial/?utm_source=integration_wordpress&utm_medium=referral&utm_campaign=plugin_page&utm_content=config_screen_banner" style="text-decoration: none; display: block; width: 162px; background: #fff; color: #2271B1; border: 1px solid #2271B1; padding: 10px 12px; font-size: 14px; font-weight: 600; cursor: pointer; text-align: center; border-radius: 8px;">
             Get Started for Free</a>
            <div>
            <div style="margin-bottom: 28px; margin-top: 20px;">
                <div style="font-weight: 600; color: #363A42; font-size: 14px; margin-bottom: 8px;">VWO Dashboard?</div>
                <div style="font-size: 14px; color: #757575; font-weight: 400;">Visit our knowledge base to learn how to use the VWO dashboard.</div>
                 <a href="https://app.vwo.com" target="_blank" style="display: inline-flex; align-items: center; color: #2271B1; border-radius: 8px; padding: 6px 12px; font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: none; margin-top: 10px; border: 1px solid #2271B1;">
                    <span style="margin-right: 4px;">Go to Dashboard</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                        <path fill="#2271B1" d="M17.88 1v3.75h5.62l-9.38 9.38 3.76 3.74 9.37-9.37v5.63H31V1H17.87zm9.37 26.25H4.75V4.75h7.5V1H1v30h30V19.75h-3.75v7.5z"/>
                    </svg>
                </a>
            </div>
            <div style="font-size: 13px;">
                Need help? Visit our <a style="color: inherit;" href="https://help.vwo.com/hc/en-us/articles/360020745993-Integrating-VWO-With-WordPress">Knowledge Base</a> for guides and FAQs.
            </div>
        </div>
    </main>
    <?php
}

function vwo_clhf_render_advanced_settings() {
    ?>
    <div class="form-container">
        <?php
        vwo_clhf_render_field([
            'id' => 'vwo_clicks',
            'label' => 'No. of Heatmap Clicks',
            'type' => 'number',
            'tooltip' => 'Set the number of heatmap clicks to record',
            'min' => 3,
            'default' => 10,
        ]);
        vwo_clhf_render_field([
            'id' => 'settings_tolerance',
            'label' => 'Settings Timeout',
            'type' => 'number',
            'tooltip' => 'Set the timeout for settings in milliseconds',
            'default' => 2000,
            'class' => 'async-option',
            'suffix' => 'ms',
        ]);
        vwo_clhf_render_field([
            'id' => 'library_tolerance',
            'label' => 'Library Timeout',
            'type' => 'number',
            'tooltip' => 'Set the timeout for library in milliseconds',
            'default' => 2500,
            'class' => 'async-option',
            'suffix' => 'ms',
        ]);
        vwo_clhf_render_field([
            'id' => 'use_existing_jquery',
            'label' => 'Use Existing jQuery',
            'type' => 'checkbox',
            'tooltip' => 'Use the existing jQuery library on your site',
            'class' => 'async-option',
        ]);
        vwo_clhf_render_field([
            'id' => 'ignore_js',
            'label' => 'Skip Deferred Execution',
            'type' => 'checkbox',
            'tooltip' => 'Skip deferred execution of the VWO script',
        ]);
        ?>
    </div>
    <?php
}

function vwo_clhf_render_woocommerce_settings() {
    $value = get_option('enable_woocommerce_event_tracking', false);
    $vwo_server_side_tracking = get_option('vwo_server_side_tracking', false);
    ?>
    <div>
        <div class="woocommerce-main-header">
            <div class="checkbox-container">
                <input type="checkbox" id="enable_woocommerce_event_tracking" name="enable_woocommerce_event_tracking" class="vwo-checkbox" <?php  echo checked($value, '1', false) ?>>
                <label class="vwo-checkbox-label" for="enable_woocommerce_event_tracking">Toggle</label>
                <label class="form-label form-label--checkbox" for="enable_woocommerce_event_tracking">Enable WooCommerce Event Tracking</label>
            </div>
            <p class="form-description form-description--checkbox">Event tracking will not work unless WooCommerce is enabled on your WordPress site.</p>
        </div>
        <div class="woocommerce-main-header">
            <div class="checkbox-container vwo_server_side_tracking">
                <input type="checkbox" id="vwo_server_side_tracking" name="vwo_server_side_tracking" class="vwo-checkbox" <?php  echo checked($vwo_server_side_tracking, '1', false) ?>>
                <label class="vwo-checkbox-label" for="vwo_server_side_tracking">Toggle</label>
                <label class="form-label form-label--checkbox" for="Enable Server-Side Tracking">Enable Server-Side Tracking</label>
            </div>
            <p class="form-description form-description--checkbox">Enable server-side tracking instead of JavaScript-based tracking.</p>
        </div>


        <div class="woocommerce-row woocommerce-row--disabled">
            <?php
            vwo_clhf_render_woocommerce_event_tracking([
                'id' => 'track_product_view',
                'label' => 'Product Viewed',
                'description' => 'This event logs an instance where a customer views a product details page.',
                'readmore_link' => 'https://help.vwo.com/hc/en-us/articles/360020745993-Integrating-VWO-With-WordPress'
            ]);
            vwo_clhf_render_woocommerce_event_tracking([
                'id' => 'track_add_to_cart',
                'label' => 'Add To Cart',
                'description' => 'This event logs an instance where a customer adds a product to their cart.',
                'readmore_link' => 'https://help.vwo.com/hc/en-us/articles/360020745993-Integrating-VWO-With-WordPress'
            ]);
            vwo_clhf_render_woocommerce_event_tracking([
                'id' => 'track_remove_from_cart',
                'label' => 'Product Removed From Cart',
                'description' => 'This event logs an instance where a customer removes a product from their cart.',
                'readmore_link' => 'https://help.vwo.com/hc/en-us/articles/360020745993-Integrating-VWO-With-WordPress'
            ]);
            // vwo_clhf_render_woocommerce_event_tracking([
            //     'id' => 'track_checkout',
            //     'label' => 'Checkout Page',
            //     'description' => 'This event logs an instance where a customer visits the checkout page.',
            //     'readmore_link' => 'https://help.vwo.com/hc/en-us/articles/360020745993-Integrating-VWO-With-WordPress'
            // ]);
            vwo_clhf_render_woocommerce_event_tracking([
                'id' => 'track_purchase',
                'label' => 'Purchase Order',
                'description' => 'This event logs an instance where a customer completes a purchase.',
                'readmore_link' => 'https://help.vwo.com/hc/en-us/articles/360020745993-Integrating-VWO-With-WordPress'
            ]);
            ?>
        </div>
    </div>
    <?php
}

function vwo_clhf_render_woocommerce_event_tracking($field) {
    $value = get_option($field['id'], isset($field['default']) ? $field['default'] : '');
    ?>
    <div class="woocommerce-row-item">
        <div class="woocommerce-row-item-header">
            <h3 class="woocommerce-row-item-title"><?php echo esc_html($field['label']); ?></h3>
            <input type="checkbox" name="<?php echo esc_attr($field['id']); ?>" id="<?php echo esc_attr($field['id']); ?>" <?php  echo checked($value, '1', false) ?> class="vwo-checkbox">
            <label for="<?php echo esc_attr($field['id']); ?>" class="vwo-checkbox-label">Toggle</label>
        </div>
        <p class="woocommerce-row-item-description">
            <?php echo esc_html($field['description']); ?>
            <?php /* if (isset($field['readmore_link'])): ?>
                <a href="<?php echo esc_url($field['readmore_link']); ?>" target="_blank">Read more</a>
            <?php endif; */ ?>
        </p>
    </div>
    <?php
}


function vwo_clhf_render_field($field) {
    $isCheckbox = $field['type'] === 'checkbox';
    $value = get_option($field['id'], isset($field['default']) ? $field['default'] : '');

    echo '<div class="' . esc_attr(isset($field['class'])?$field['class']:"") . '">';

    $label = '<label class="form-label" for="' . esc_attr($field['id']) . '">' . esc_html($field['label']) . '</label>';

    switch ($field['type']) {
        case 'text':
        case 'number':
            echo $label;
            echo '<input type="' . esc_attr($field['type']) . '" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['id']) . '" value="' . esc_attr($value) . '"';
            if (isset($field['min'])) echo ' min="' . esc_attr($field['min']) . '"';
            echo ' class="form-input" />';
            if (isset($field['suffix'])) echo ' ' . esc_html($field['suffix']);
            break;
        case 'radio':
            echo $label;
            foreach ($field['options'] as $options) {
                echo '<div class="radio-container-wrapper">
                    <div class="radio-container">';
                echo '<label class="form-radio-label"><input type="radio" name="' . esc_attr($field['id']) . '" value="' . esc_attr($options['value']) . '"' . checked($value, $options['value'], false) . ' /> ' . esc_html($options['label']) . '</label> ';
                echo '</div>';
                echo '<p class="radio-sublabel">' . esc_html($options['sublabel']) . '</p>';
                echo '</div>';
            }
            break;
        case 'checkbox':
            echo '<div class="checkbox-container">';
            echo '<input type="checkbox" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['id']) . '" class="vwo-checkbox" value="1"' . checked($value, '1', false) . ' />';
            echo '<label class="vwo-checkbox-label" for="' . esc_attr($field['id']) . '">Toggle</label>';
            echo '<label class="form-label form-label--checkbox" for="' . esc_attr($field['id']) . '">' . esc_html($field['label']) . '</label>';
            echo '</div>';
            break;
    }

    if (isset($field['tooltip'])) {
        echo '<p class="form-description ' . ($isCheckbox ? 'form-description--checkbox' : '') . '">' . esc_html($field['tooltip']) . '</p>';
    }

    echo '</div>';
}

function vwo_clhf_render_styles() {
    ?>
    <style>
        input[type="radio"] {
            margin: 0 !important;
        }
        input[type=radio]:checked::before {
            background-color: #3858E9;
        }
        .vwo-admin-content {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            margin-top: 20px;
        }
        .vwo-admin-content .nav-tab,
        .vwo-admin-content .nav-tab-active{
            outline:none;
            box-shadow: none;
        }
        .main-grid {
            display: flex;
            justify-content: space-between;
        }
        .main-grid__description {
            background-color: #f9f9f9;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            font-family: 'Segoe UI', sans-serif;
            height: fit-content;max-width: 650px;
            border: 1px solid #E0E0E0;
            margin: 20px;
        }
        .main-grid__description h2 {
            font-weight: 600;
            margin-bottom: 10px;
            margin-top: 0;
            font-size: 20px;
            color: #333;
        }
        .main-grid__description p {
            font-size: 14px;
            color: #757575;
            line-height: 1.6;
            margin-top: 21px;
            margin-bottom: 0px;
        }
        .main-grid__description a {
            color: #3858E9;
        }
        .form-container {
            margin-top: 24px;
            margin-bottom: 30px;
            display: flex;
            flex-direction: column;
            gap: 28px;
            flex: 1;
        }
        .form-label {
            font-size: 12px;
            color: #1E1E1E;
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
        }
        .form-label--checkbox {
            cursor: pointer;
            margin-bottom: 0;
        }
        .form-radio-label {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            cursor: pointer;
        }
        .form-radio-label:last-child {
            margin-bottom: 0;
        }
        .form-description {
            color: #757575;
            margin-top: 10px;
            margin-bottom: 0;
            font-size: 12px;
        }
        .form-description--checkbox {
            margin-top: 5px;
            margin-left: 42px;
        }
        .form-input {
            width: 100%;
            max-width: 400px;
            border: 1px solid #949494 !important;
            border-radius: 2px !important;
        }
        .checkbox-container, .radio-container {
            display: flex;
            gap: 10px;
        }
        .radio-container-wrapper {
            margin-bottom: 20px;
        }
        .radio-container-wrapper:last-child {
            margin-bottom: 0;
        }
        .radio-sublabel {
            font-size: 12px;
            color: #757575;
            margin-top: 5px;
            margin-bottom: 0;
            margin-left: 26px;
        }
        .checkbox-container .form-description {
            width: 100%;
        }
        .async-option {
            display: none;
        }
        .tab-pane {
            display: none;
        }
        .tab-pane.active {
            display: block;
        }
        .description {
            max-width: 800px;
            margin-bottom: 20px;
        }

        .woocommerce-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        .woocommerce-row-item {
            border: 1px solid rgba(0, 0, 0, 0.10);
            border-radius: 8px;
        }
        .woocommerce-row-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 5px;
            padding: 10px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.10);
        }
        .woocommerce-row-item-title {
            margin: 0;
            font-size: 13px;
            font-weight: 500;
            color: #1E1E1E;
        }
        .woocommerce-row-item-description {
            margin: 0;
            font-size: 12px;
            color: #757575;
            padding: 10px;
        }
        .woocommerce-row-item-description a {
            color: #3858E9;
        }
        .woocommerce-main-header {
            margin: 30px 0;
        }
        .woocommerce-row--disabled {
            opacity: 0.4;
            cursor: not-allowed;
            pointer-events: none;
        }
        input[type=checkbox].vwo-checkbox{
            height: 0;
            width: 0;
            visibility: hidden;
            position: absolute;
            clip: rect(1px,1px,1px,1px);
            overflow: hidden;
        }

        label.vwo-checkbox-label {
            box-sizing: border-box;
            cursor: pointer;
            text-indent: -9999px;
            width: 32px;
            height: 18px;
            border: 1px solid #000;
            background: #fff;
            display: block;
            border-radius: 100px;
            position: relative;
        }

        label.vwo-checkbox-label:after {
            content: '';
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            left: 2px;
            width: 12px;
            height: 12px;
            background: #000;
            border-radius: 90px;
            transition: 0.3s;
        }

        input.vwo-checkbox:checked + label.vwo-checkbox-label {
            background: #3858E9;
            border-color: #3858E9;
        }

        input.vwo-checkbox:checked + label.vwo-checkbox-label:after {
            left: calc(100% - 2px);
            transform: translateX(-100%) translateY(-50%);
            background-color: #fff;
        }

    </style>
    <?php
}

function vwo_clhf_render_scripts() {
    ?>
    <script>
        jQuery(document).ready(function($) {
            function toggleAsyncOptions() {
                var isAsync = $('input[name="code_type"]:checked').val() === 'ASYNC';
                $('.async-option').toggle(isAsync);
            }
            function toggleWooCommerceOptions() {
                var isWooCommerce = $('input[name="enable_woocommerce_event_tracking"]:checked');

                if(isWooCommerce.length > 0) {
                    $('.woocommerce-row').removeClass('woocommerce-row--disabled');
                     $('.vwo_server_side_tracking').removeClass('woocommerce-row--disabled');
                    
                } else {
                    $('.woocommerce-row').addClass('woocommerce-row--disabled');
                    $('.vwo_server_side_tracking').addClass('woocommerce-row--disabled');
                }
            }

            $('input[name="enable_woocommerce_event_tracking"]').change(toggleWooCommerceOptions);
            $("label[for='enable_woocommerce_event_tracking']").click(toggleWooCommerceOptions);
            toggleWooCommerceOptions(); // Run on page load

            $('input[name="code_type"]').change(toggleAsyncOptions);
            toggleAsyncOptions(); // Run on page load

            // Handle tab switching
            $('.nav-tab-wrapper .nav-tab').on('click', function(e) {
                e.preventDefault();
                var targetTab = $(this).attr('href');

                // Update active tab
                $('.nav-tab-wrapper .nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');

                // Show target tab content
                $('.tab-pane').removeClass('active').hide();
                $(targetTab).addClass('active').show();
            });

            // Ensure the first tab is visible on page load
            $('#tab-general').show();
        });
    </script>
    <?php
}

function vwo_clhf_enqueue_admin_scripts($hook) {
    if ('settings_page_clhf_vwo_options' !== $hook) {
        return;
    }
    wp_enqueue_style('wp-admin');
}
add_action('admin_enqueue_scripts', 'vwo_clhf_enqueue_admin_scripts');

/**
 * Displays a warning message if VWO settings are not configured.
 * This function checks if the user is an admin and if the VWO ID option is set. If the user is not an admin or
 * the VWO ID option is not set, it displays a warning message.
 *
 * @throws Exception Description of exception.
 * @return void
 */
function vwo_clhf_warn_nosettings() {
    if ( ! is_admin() ) {
        return;
    }

    $clhf_option = get_option( 'vwo_id' );
    if ( ! $clhf_option || $clhf_option < 1 ) {
        echo "<div id='vwo-warning' class='updated fade'><p><strong>VWO is almost ready.</strong> You must <a href=\"options-general.php?page=clhf_vwo_options\">enter your Account ID</a> for it to work.</p></div>";
    }
}


add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'vwo_clhf_add_plugin_page_settings_link' );

/**
 * Function to add Settings Links on Plugin page
 *
 * @param array $links Array of links.
 * @return array
 */
function vwo_clhf_add_plugin_page_settings_link( $links ) {
    $links[] = '<a href="' .
        admin_url( 'options-general.php?page=clhf_vwo_options' ) .
        '">' . __( 'Settings' ) . '</a>';
    return $links;
}

/**
 * Disables VWO in Divi Builder.
 *
 * @throws Exception Description of exception.
 * @return void
 */
function disable_vwo_in_divi_builder() {
    if ( has_action( 'wp_head', 'vwo_clhf_headercode' ) && function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled() ) {
        remove_action( 'wp_head', 'vwo_clhf_headercode', 1 );
    }
}

add_action( 'wp_head', 'disable_vwo_in_divi_builder', 0 );

// Add this new function to validate settings
function vwo_clhf_validate_settings() {
    $has_errors = false;

    // Validate VWO Account ID
    $vwo_id = get_option('vwo_id');
    if (empty($vwo_id) || !is_numeric($vwo_id)) {
        add_settings_error('vwo_clhf_options', 'vwo_id', 'Please enter a valid VWO Account ID.', 'error');
        $has_errors = true;
    }

    // Add more validation for other fields as needed

    return $has_errors;
}

// Add this new function to sanitize and validate settings
function vwo_clhf_sanitize_settings($input) {
    // Sanitize and validate VWO Account ID
    $vwo_id = sanitize_text_field($input);
    if (empty($vwo_id) || !is_numeric($vwo_id)) {
        add_settings_error('vwo_clhf_options', 'vwo_id', 'Please enter a valid VWO Account ID.', 'error');
    }

    // Add more sanitization and validation for other fields as needed
    // Check if vwo_id is changed
    $current_vwo_id = get_option('vwo_id');
    if ($current_vwo_id !== $vwo_id) {
        update_option('vwo_coll_url','');
    }
    return $vwo_id;
}

function enqueue_jquery_script() {
    wp_enqueue_script('jquery');
}
add_action('wp_enqueue_scripts', 'enqueue_jquery_script');

if (file_exists(plugin_dir_path(__FILE__) . 'woocommerce-events.php')) {
    include_once plugin_dir_path(__FILE__) . 'woocommerce-events.php';
}


?>