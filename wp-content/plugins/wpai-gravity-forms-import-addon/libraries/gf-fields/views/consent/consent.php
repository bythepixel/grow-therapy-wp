<div class="switcher-target-is_multiple_field_value_<?php echo $field->id;?>_yes">
    <div class="input sub_input">
        <div class="input">
	        <?php $input = $field->get_field_input($form, $current_multiple_value); ?>
	        <?php foreach ($field->get_entry_inputs() as $choice): ?>
		        <?php $input = str_replace("name='input_" . $choice['id'] . "'", "name='pmgi[multiple_value][" . $choice['id'] . "]'", $input); ?>
	        <?php endforeach; ?>
	        <?php echo $input; ?>
        </div>
    </div>
</div>