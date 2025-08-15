<?php

function pmgi_admin_notices() {

	// Notify user if Gravity Forms is not installed.
	if ( ! class_exists( 'GFForms' )) {
		?>
        <div class="error"><p>
				<?php printf(
					__('<b>%s Plugin</b>: Gravity Forms must be installed.', 'wp_all_import_gf_add_on'),
					PMGI_Plugin::getInstance()->getName()
				) ?>
            </p></div>
		<?php

		deactivate_plugins( PMGI_ROOT_DIR . '/wpai-gravityforms-add-on.php');

	}

	// Notify user if WP All Import Free is installed.
	if ( ! class_exists( 'PMXI_Plugin' ) || PMXI_EDITION == 'free' ) {
		?>
        <div class="error"><p>
				<?php printf(
					__('<b>%s Plugin</b>: WP All Import Pro must be installed. If you have a license download it from&nbsp;<a href="http://www.wpallimport.com/portal/">here</a> or purchase a license <a href="https://www.wpallimport.com/pricing/">here</a>.', 'wp_all_import_gf_add_on'),
					PMGI_Plugin::getInstance()->getName()
				) ?>
            </p></div>
		<?php

		deactivate_plugins( PMGI_ROOT_DIR . '/wpai-gravityforms-add-on.php');
	}

	// Notify user if WP All Import is not up to date.
    if ( class_exists( 'PMXI_Plugin' ) and ( version_compare(PMXI_VERSION, '4.7.8') < 0 and PMXI_EDITION == 'paid' or version_compare(PMXI_VERSION, '3.6.9') < 0 and PMXI_EDITION == 'free') ) {
        ?>
        <div class="error"><p>
                <?php printf(
                    __('<b>%s Plugin</b>: The latest version of WP All Import is required to use this add-on. Any imports that require this add-on will not run correctly until you update WP All Import.', 'wp_all_import_user_add_on'),
                    PMGI_Plugin::getInstance()->getName()
                ) ?>
            </p></div>
        <?php
    }

	$input = new PMGI_Input();
	$messages = $input->get('pmgi_nt', array());
	if ($messages) {
		is_array($messages) or $messages = array($messages);
		foreach ($messages as $type => $m) {
			in_array((string)$type, array('updated', 'error')) or $type = 'updated';
			?>
			<div class="<?php echo $type ?>"><p><?php echo $m ?></p></div>
			<?php
		}
	}

}
