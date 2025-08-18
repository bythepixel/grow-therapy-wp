<?php
// Check if $isWizard is defined
$isWizard = isset($isWizard) ? $isWizard : false;

if ( ! $isWizard  or ! empty(PMXI_Plugin::$session->deligate) and PMXI_Plugin::$session->deligate == 'wpallexport' or $isWizard and "new" != $post['wizard_type']): ?>
<h4><?php _e('For each record in this import file, every time this import is run...', 'wp_all_import_gf_add_on'); ?></h4>
<?php else: ?>
<h4><?php _e('If this import is run again and WP All Import finds new or changed data...', 'wp_all_import_gf_add_on'); ?></h4>
<?php endif; ?>
<?php
$post_type = $post['custom_type'];
$cpt_name = 'Gravity Forms entries';
?>
<div class="input">
	<input type="hidden" name="create_new_records" value="0" />
	<input type="checkbox" id="create_new_records" name="create_new_records" value="1" <?php echo $post['create_new_records'] ? 'checked="checked"' : '' ?> />
	<label for="create_new_records"><?php printf(__('Create new %s from records newly present in this import file', 'wp_all_import_gf_add_on'), $cpt_name); ?></label>
	<?php if ( ! empty(PMXI_Plugin::$session->deligate) and PMXI_Plugin::$session->deligate == 'wpallexport' ): ?>
	<a href="#help" class="wpallimport-help" title="<?php printf(__('New %s will only be created when ID column is present and value in ID column is unique.', 'wp_all_import_gf_add_on'), $cpt_name) ?>" style="top: -1px;">?</a>
	<?php endif; ?>
</div>
<div class="input">
	<input type="hidden" id="is_keep_former_posts" name="is_keep_former_posts" value="yes" />
	<input type="checkbox" id="is_not_keep_former_posts" name="is_keep_former_posts" value="no" <?php echo "yes" != $post['is_keep_former_posts'] ? 'checked="checked"': '' ?> class="switcher" />
	<label for="is_not_keep_former_posts"><?php printf(__('Update existing %s with the data in this import file', 'wp_all_import_gf_add_on'), $cpt_name); ?></label>
	<?php if ( $isWizard and "new" == $post['wizard_type'] and empty(PMXI_Plugin::$session->deligate)): ?>
	<a href="#help" class="wpallimport-help" style="position: relative; top: -2px;" title="<?php printf(__('These options will only be used if you run this import again later. All data is imported the first time you run an import.<br/><br/>Note that WP All Import will only update/remove %s created by this import. If you want to match to %s that already exist on this site, select \'Search for and update all %s on this site\' above.', 'wp_all_import_gf_add_on'), $cpt_name, $cpt_name, $cpt_name) ?>">?</a>
	<?php endif; ?>

	<div class="switcher-target-is_not_keep_former_posts" style="padding-left:17px;">

        <div class="input" style="margin-left: 4px;">
            <input type="hidden" name="is_selective_hashing" value="0" />
            <input type="checkbox" id="is_selective_hashing" name="is_selective_hashing" value="1" <?php echo $post['is_selective_hashing'] ? 'checked="checked"': '' ?> />
            <label for="is_selective_hashing"><?php printf(__('Skip %s if their data in this import file has not changed', 'wp_all_import_gf_add_on'), $cpt_name); ?></label>
            <a href="#help" class="wpallimport-help" style="position: relative; top: -2px;" title="<?php _e('When enabled, WP All Import will keep track of every entry\'s data as it is imported. When the import is run again, entries will be skipped if their data in the import file has not changed since the last run.<br/><br/>Gravity Forms entries will not be skipped if the import template or settings change, or if you make changes to the custom code in the Function Editor.', 'wp_all_import_gf_add_on') ?>">?</a>
        </div>

		<input type="radio" id="update_all_data" class="switcher" name="update_all_data" value="yes" <?php echo 'no' != $post['update_all_data'] ? 'checked="checked"': '' ?>/>
		<label for="update_all_data"><?php _e('Update all data', 'wp_all_import_gf_add_on' )?></label><br>

		<input type="radio" id="update_choosen_data" class="switcher" name="update_all_data" value="no" <?php echo 'no' == $post['update_all_data'] ? 'checked="checked"': '' ?>/>
		<label for="update_choosen_data"><?php _e('Choose which data to update', 'wp_all_import_gf_add_on' )?></label><br>
		<div class="switcher-target-update_choosen_data"  style="padding-left:27px;">
			<div class="input">
				<h4 class="wpallimport-trigger-options wpallimport-select-all" rel="<?php _e("Unselect All", "wp_all_import_gf_add_on"); ?>"><?php _e("Select All", "wp_all_import_gf_add_on"); ?></h4>
			</div>
            <div class="input">
                <input type="hidden" name="pmgi_is_update_entry_fields" value="0" />
                <input type="checkbox" id="pmgi_is_update_entry_fields" name="pmgi_is_update_entry_fields" value="1" <?php echo $post['pmgi_is_update_entry_fields'] ? 'checked="checked"': '' ?>  class="switcher"/>
                <label for="pmgi_is_update_entry_fields"><?php _e('Form Entry Fields', 'wp_all_import_gf_add_on') ?></label>
                <div class="switcher-target-pmgi_is_update_entry_fields" style="padding-left:20px;">
                    <div class="input">
                        <h4 class="wpallimport-trigger-entry-fields wpallimport-select-all" rel="<?php _e("Unselect All", "wp_all_import_gf_add_on"); ?>"><?php _e("Select All", "wp_all_import_gf_add_on"); ?></h4>
                    </div>
	                <?php if (!empty($form)): ?>
		                <?php foreach ($form->getFields() as $field) : ?>
			                <?php
			                if ($field instanceof \wpai_gravityforms_add_on\gf\fields\FieldNotSupported || $field instanceof \wpai_gravityforms_add_on\gf\fields\FieldEmpty) {
				                continue;
			                }
			                $fieldData = $field->getGField();
			                ?>
                            <div class="input">
                                <input type="hidden" name="pmgi_is_update_entry_fields_list[<?php echo $fieldData->id; ?>]" value="0"/>
                                <input type="checkbox" class="exclude-select-all" id="pmgi_update_entry_field_<?php echo $fieldData->id; ?>" name="pmgi_is_update_entry_fields_list[<?php echo $fieldData->id; ?>]" value="1" <?php echo ( ! empty($post['pmgi_is_update_entry_fields_list'][$fieldData->id]) || !isset($post['pmgi_is_update_entry_fields_list'][$fieldData->id])) ? 'checked="checked"': '' ?>/>
                                <label for="pmgi_update_entry_field_<?php echo $fieldData->id; ?>"><?php echo $fieldData->label; ?></label>
                            </div>
		                <?php endforeach; ?>
	                <?php endif; ?>
                </div>
            </div>
            <div class="input">
                <input type="hidden" name="is_pmgi_update_entry_notes" value="0" />
                <input type="checkbox" id="is_pmgi_update_entry_notes" name="is_pmgi_update_entry_notes" value="1" <?php echo $post['is_pmgi_update_entry_notes'] ? 'checked="checked"': '' ?>  class="switcher"/>
                <label for="is_pmgi_update_entry_notes"><?php _e('Entry Notes', 'wp_all_import_gf_add_on') ?></label>
                <div class="switcher-target-is_pmgi_update_entry_notes" style="padding-left:17px;">
                    <div class="input">
                        <input type="radio" id="pmgi_update_entry_notes_logic_full_update" name="pmgi_update_entry_notes_logic" value="full_update" <?php echo ( "full_update" == $post['pmgi_update_entry_notes_logic'] ) ? 'checked="checked"': '' ?>/>
                        <label for="pmgi_update_entry_notes_logic_full_update"><?php _e('Update all notes', 'wp_all_import_gf_add_on') ?></label>
                    </div>
                    <div class="input">
                        <input type="radio" id="pmgi_update_entry_notes_logic_add_new" name="pmgi_update_entry_notes_logic" value="add_new" <?php echo ( "add_new" == $post['pmgi_update_entry_notes_logic'] ) ? 'checked="checked"': '' ?>/>
                        <label for="pmgi_update_entry_notes_logic_add_new"><?php _e('Don\'t touch existing notes, append new notes', 'wp_all_import_gf_add_on') ?></label>
                    </div>
                </div>
            </div>
			<div class="input">
				<input type="hidden" name="is_pmgi_update_date_created" value="0" />
				<input type="checkbox" id="is_pmgi_update_date_created" name="is_pmgi_update_date_created" value="1" <?php echo $post['is_pmgi_update_date_created'] ? 'checked="checked"': '' ?> />
				<label for="is_pmgi_update_date_created"><?php _e('Date Created', 'wp_all_import_gf_add_on') ?></label>
				<a href="#help" class="wpallimport-help" title="<?php printf(__('Check this option if you want previously imported %s to change their created date.', 'wp_all_import_gf_add_on'), $cpt_name); ?>">?</a>
			</div>
            <div class="input">
                <input type="hidden" name="is_pmgi_update_date_updated" value="0" />
                <input type="checkbox" id="is_pmgi_update_date_updated" name="is_pmgi_update_date_updated" value="1" <?php echo $post['is_pmgi_update_date_updated'] ? 'checked="checked"': '' ?> />
                <label for="is_pmgi_update_date_updated"><?php _e('Date Updated', 'wp_all_import_gf_add_on') ?></label>
                <a href="#help" class="wpallimport-help" title="<?php printf(__('Check this option if you want previously imported %s to change their updated date.', 'wp_all_import_gf_add_on'), $cpt_name); ?>">?</a>
            </div>
            <div class="input">
                <input type="hidden" name="is_pmgi_update_starred" value="0" />
                <input type="checkbox" id="is_pmgi_update_starred" name="is_pmgi_update_starred" value="1" <?php echo $post['is_pmgi_update_starred'] ? 'checked="checked"': '' ?> />
                <label for="is_pmgi_update_starred"><?php _e('Starred', 'wp_all_import_gf_add_on') ?></label>
                <a href="#help" class="wpallimport-help" title="<?php printf(__('Check this option if you want previously imported %s to change their starred flag.', 'wp_all_import_gf_add_on'), $cpt_name) ?>">?</a>
            </div>
            <div class="input">
                <input type="hidden" name="is_pmgi_update_read" value="0" />
                <input type="checkbox" id="is_pmgi_update_read" name="is_pmgi_update_read" value="1" <?php echo $post['is_pmgi_update_read'] ? 'checked="checked"': '' ?> />
                <label for="is_pmgi_update_read"><?php _e('Read', 'wp_all_import_gf_add_on') ?></label>
                <a href="#help" class="wpallimport-help" title="<?php printf(__('Check this option if you want previously imported %s to change their read flag.', 'wp_all_import_gf_add_on'), $cpt_name) ?>">?</a>
            </div>
            <div class="input">
                <input type="hidden" name="is_pmgi_update_ip" value="0" />
                <input type="checkbox" id="is_pmgi_update_ip" name="is_pmgi_update_ip" value="1" <?php echo $post['is_pmgi_update_ip'] ? 'checked="checked"': '' ?> />
                <label for="is_pmgi_update_ip"><?php _e('IP', 'wp_all_import_gf_add_on') ?></label>
                <a href="#help" class="wpallimport-help" title="<?php printf(__('Check this option if you want previously imported %s to change their IP data.', 'wp_all_import_gf_add_on'), $cpt_name) ?>">?</a>
            </div>
            <div class="input">
                <input type="hidden" name="is_pmgi_update_source_url" value="0" />
                <input type="checkbox" id="is_pmgi_update_source_url" name="is_pmgi_update_source_url" value="1" <?php echo $post['is_pmgi_update_source_url'] ? 'checked="checked"': '' ?> />
                <label for="is_pmgi_update_source_url"><?php _e('Source URL', 'wp_all_import_gf_add_on') ?></label>
                <a href="#help" class="wpallimport-help" title="<?php printf(__('Check this option if you want previously imported %s to change their Source URL data.', 'wp_all_import_gf_add_on'), $cpt_name) ?>">?</a>
            </div>
            <div class="input">
                <input type="hidden" name="is_pmgi_update_user_agent" value="0" />
                <input type="checkbox" id="is_pmgi_update_user_agent" name="is_pmgi_update_user_agent" value="1" <?php echo $post['is_pmgi_update_user_agent'] ? 'checked="checked"': '' ?> />
                <label for="is_pmgi_update_user_agent"><?php _e('User Agent', 'wp_all_import_gf_add_on') ?></label>
                <a href="#help" class="wpallimport-help" title="<?php printf(__('Check this option if you want previously imported %s to change their User Agent data.', 'wp_all_import_gf_add_on'), $cpt_name) ?>">?</a>
            </div>
            <div class="input">
                <input type="hidden" name="is_pmgi_update_created_by" value="0" />
                <input type="checkbox" id="is_pmgi_update_created_by" name="is_pmgi_update_created_by" value="1" <?php echo $post['is_pmgi_update_created_by'] ? 'checked="checked"': '' ?> />
                <label for="is_pmgi_update_created_by"><?php _e('Created By', 'wp_all_import_gf_add_on') ?></label>
                <a href="#help" class="wpallimport-help" title="<?php printf(__('Check this option if you want previously imported %s to change their Created By data.', 'wp_all_import_gf_add_on'), $cpt_name) ?>">?</a>
            </div>
            <div class="input">
                <input type="hidden" name="is_pmgi_update_status" value="0" />
                <input type="checkbox" id="is_pmgi_update_status" name="is_pmgi_update_status" value="1" <?php echo $post['is_pmgi_update_status'] ? 'checked="checked"': '' ?> />
                <label for="is_pmgi_update_status"><?php _e('Status', 'wp_all_import_gf_add_on') ?></label>
                <a href="#help" class="wpallimport-help" title="<?php printf(__('Check this option if you want previously imported %s to change their Status data.', 'wp_all_import_gf_add_on'), $cpt_name) ?>">?</a>
            </div>
			<?php
			// add-ons re-import options
			do_action('pmgi_reimport', $post['custom_type'], $post);
			?>
		</div>
	</div>
</div>
<div class="switcher-target-auto_matching">
    <?php
    $hidden_delete_missing_options = [
        'is_update_missing_cf'
    ];
    if (file_exists(WP_ALL_IMPORT_ROOT_DIR . '/views/admin/import/options/_delete_missing_options.php')) {
        include( WP_ALL_IMPORT_ROOT_DIR . '/views/admin/import/options/_delete_missing_options.php' );
    }
    ?>
</div>
