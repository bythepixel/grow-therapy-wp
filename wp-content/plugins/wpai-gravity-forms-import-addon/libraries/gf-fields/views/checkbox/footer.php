</div>

<div class="clear"></div>

<div class="input">
    <div class="main_choise">
        <input type="radio" id="is_multiple_field_value_<?php echo $field->id;?>_no" class="switcher" name="pmgi[is_multiple_field_value][<?php echo $field->id;?>]" value="no" <?php echo 'no' == $current_is_multiple_field_value ? 'checked="checked"': '' ?>/>
        <label for="is_multiple_field_value_<?php echo $field->id;?>_no" class="chooser_label"><?php _e('Set with XPath', 'wp_all_import_gf_add_on' )?></label>
    </div>
    <div class="wpallimport-clear"></div>
    <div class="switcher-target-is_multiple_field_value_<?php echo $field->id;?>_no">
        <div class="input sub_input">
            <div class="input">
                <input type="text" class="smaller-text widefat rad4" name="pmgi[fields][<?php echo $field->id;?>]" style="width:300px;" value="<?php echo esc_attr($current_field); ?>"/>
                <a href="#help" class="wpallimport-help" style="top:0;" title="<?php _e('Specify the value. For multiple values, separate with commas.', 'wp_all_import_gf_add_on') ?>">?</a>
            </div>
        </div>
    </div>
</div>