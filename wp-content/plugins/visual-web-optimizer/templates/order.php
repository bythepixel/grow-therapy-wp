<?php defined( 'ABSPATH' ) or exit; ; ?>
window.VWO = window.VWO || [];
VWO.event = VWO.event || function () {VWO.push(["event"].concat([].slice.call(arguments)))};
VWO.event('woocommerce.purchase', {
    orderId: '<?php echo $orderId ?>',
    totalPrice: <?php echo $totalPrice; ?>,
    shippingPrice: <?php echo $shippingPrice; ?>,
    totalTax: <?php echo $totalTax; ?>,
    discount: <?php echo $discount; ?>,
    currencyCode: '<?php echo $currencyCode; ?>',
    productId: '<?php echo $productId; ?>',
    productSku: '<?php echo $productSku; ?>',
    productPrice: '<?php echo $productPrice; ?>',
    productQuantity: '<?php echo $productQuantity; ?>'
});