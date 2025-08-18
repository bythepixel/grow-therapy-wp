<?php
include_once plugin_dir_path(__FILE__) . 'VwoTemplater.php';

defined( 'ABSPATH' ) or exit;

class VwoPlugin
{
    static public function render( $template, $args = [] ) {
        return call_user_func(
            [ new VwoTemplater( [ plugin_dir_path(__FILE__) . '/templates' ] ), 'render' ],
            $template,
            $args );
    }
}
