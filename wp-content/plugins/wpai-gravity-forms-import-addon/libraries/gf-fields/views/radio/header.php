<div class="input">
    <div class="main_choise">
        <input type="radio" id="is_multiple_field_value_<?php echo $field->id;?>_yes" class="switcher" name="pmgi[is_multiple_field_value][<?php echo $field->id;?>]" value="yes" <?php echo 'no' != $current_is_multiple_field_value ? 'checked="checked"': '' ?>/>
        <label for="is_multiple_field_value_<?php echo $field->id;?>_yes" class="chooser_label"><?php _e("Select value for all records", 'wp_all_import_gf_add_on'); ?></label>
    </div>
    <div class="wpallimport-clear"></div>