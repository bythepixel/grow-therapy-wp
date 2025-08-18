<?php defined('ABSPATH') or exit; ?>
window.VWO = window.VWO || [];
VWO.event = VWO.event || function () {VWO.push(["event"].concat([].slice.call(arguments)))};
VWO.event("woocommerce.checkoutStarted", {});