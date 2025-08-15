<div class="field field_type-<?php echo $field->type;?> field_key-<?php echo $field->id;?>">
    <h4>
        <?php echo $field->label; ?>
        <?php if (!empty($tooltip)): ?>
            <a href="#help" class="wpallimport-help" style="top:0;" title="<?php echo $tooltip; ?>">?</a>
        <?php endif; ?>
    </h4>
    <div class="wpallimport-clear"></div>
    <div class="pmgi-input-wrap">