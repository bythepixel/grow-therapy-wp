<input
    type="text"
    placeholder=""
    value="<?php echo esc_attr( $current_field );?>"
    name="pmgi[fields][<?php echo $field->id;?>]"
    class="text widefat rad4" />

<div class="input">
    <input
            type="hidden"
            name="pmgi[search_in_files][<?php echo $field->id;?>]"
            value="0"/>
    <input
            type="checkbox"
            id="<?php echo $field->id . '_search_in_files';?>"
            name="pmgi[search_in_files][<?php echo $field->id;?>]"
            value="1" <?php echo (!empty($post['pmgi']['search_in_files'][$field->id])) ? 'checked="checked"' : '';?>/>
    <label
            for="<?php echo $field->id . '_search_in_files';?>">
		<?php _e('Use files currently uploaded to /wp-content/uploads/gravity_forms.', 'wp_all_import_gf_add_on'); ?></label>
</div>