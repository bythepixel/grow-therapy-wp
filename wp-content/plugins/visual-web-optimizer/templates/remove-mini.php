<?php defined('ABSPATH') or exit; ?>
$(document.body).on('click', '.remove_from_cart_button', function() {
console.log("click");
window.VWO = window.VWO || [];
VWO.event = VWO.event || function() {VWO.push(["event"].concat([].slice.call(arguments)));};
let $button = $(this);
let productSku = $button.data('product_sku') ? String($button.data('product_sku')) : `#${$button.data('product_id')}`;
let $miniCartItem = $button.closest('.mini_cart_item');
let productTitle = $miniCartItem.find('.product-name').text().trim() || '';
let price = $miniCartItem.find('.product-price').text().trim().replace(/[^0-9.]/g, '') || '';
let quantity = $miniCartItem.find('.qty').val() ? $miniCartItem.find('.qty').val() : '1';
VWO.event('woocommerce.productRemovedFromCart', {
    productSku: productSku,
    productTitle: productTitle,
    quantity: parseInt(quantity)
});
});
