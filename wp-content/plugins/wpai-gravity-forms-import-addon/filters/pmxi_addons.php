<?php
/**
 * Register Gravity Forms Add-On.
 *
 * @param $addons
 *
 * @return mixed
 */
function pmgi_pmxi_addons( $addons ) {
	$addons['PMGI_Plugin'] = 1;
	return $addons;
}
