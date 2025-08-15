<?php defined( 'ABSPATH' ) or exit; ?>
$( '.add_to_cart_button:not(.product_type_variable, .product_type_grouped)' ).on( 'click', function() {
    window.VWO = window.VWO || [];
    VWO.event = VWO.event || function () {VWO.push(["event"].concat([].slice.call(arguments)))};
    VWO.event( 'woocommerce.productAddedToCart', {
    productId:$(this).data( 'product_id' ),
    productSku: ( $(this).data('product_sku') ) ? ( '' + $(this).data('product_sku') ) : ( '#' + $(this).data( 'product_id' ) ),
    quantity: $(this).data( 'quantity' )
    });
});