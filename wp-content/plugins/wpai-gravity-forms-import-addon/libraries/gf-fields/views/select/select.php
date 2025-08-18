<div class="switcher-target-is_multiple_field_value_<?php echo $field->id;?>_yes">
    <div class="input sub_input">
        <div class="input">
            <?php $input = $field->get_field_input($form, $current_multiple_value); ?>
            <?php $input = str_replace("name='input_" . $field->id . "'", "name='pmgi[multiple_value][" . $field->id . "]'", $input); ?>
            <?php echo $input; ?>
        </div>
    </div>
</div>