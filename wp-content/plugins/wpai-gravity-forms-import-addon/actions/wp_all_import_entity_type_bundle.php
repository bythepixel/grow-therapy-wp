<?php
/**
 * Render list of gravity form on first step of import wizard.
 *
 * @param $post
 * @param $is_edit_screen
 */
function pmgi_wp_all_import_entity_type_bundle( $post, $is_edit_screen ) {
    if ( $is_edit_screen ) { ?>
        <!-- Always render gravity forms wrapper for JavaScript show/hide functionality -->
        <div class="gravity_form_to_import_wrapper" style="display: <?php echo ($post['custom_type'] == 'gf_entries') ? 'block' : 'none'; ?>;">
            <div class="wp_all_import_change_gravity_form">
                <input type="hidden" name="gravity_form_title" value="<?php echo $post['gravity_form_title'];?>">
                <h2><?php _e('Select Form to import entries into...');?></h2>
                <select id="gravity_form_to_import">
                    <option value=""><?php _e('Select Form', 'wp_all_import_gf_add_on'); ?></option>
                    <?php $forms = GFFormsModel::get_forms(); ?>
                    <?php foreach ($forms as $form): ?>
                        <option value="<?php echo $form->title;?>" <?php if ($post['gravity_form_title'] == $form->title): ?>selected="selected"<?php endif;?>><?php echo $form->title;?></option>
                    <?php endforeach;?>
                </select>
            </div>
        </div>
    <?php } else { ?>
        <div class="gravity_form_to_import_wrapper">
            <input type="hidden" name="gravity_form_title" value="<?php echo $post['gravity_form_title'];?>">
            <h2><?php _e('Select Form to import entries into...');?></h2>
            <select id="gravity_form_to_import">
                <option value=""><?php _e('Select Form', 'wp_all_import_gf_add_on'); ?></option>
                <?php $forms = GFFormsModel::get_forms(); ?>
                <?php foreach ($forms as $form): ?>
                    <option value="<?php echo $form->title;?>" <?php if ($post['gravity_form_title'] == $form->title): ?>selected="selected"<?php endif;?>><?php echo $form->title;?></option>
                <?php endforeach;?>
            </select>
        </div>
    <?php }
}
