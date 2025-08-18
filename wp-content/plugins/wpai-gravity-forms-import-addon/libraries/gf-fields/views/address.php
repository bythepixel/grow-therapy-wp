<div class="columns-wrapper">
<?php
    foreach ($field->get_entry_inputs() as $key => $input) {
        if (($key + 1) % 2) {
            ?>
            </div>
            <div class="columns-wrapper">
            <?php
        }
        ?>
        <div class="column">
            <label for=""><?php echo $input['label']; ?></label>
            <input
                type="text"
                value="<?php echo esc_attr( $current_field[$input['id']] );?>"
                name="pmgi[fields][<?php echo $input['id'];?>]"
                class="text widefat rad4" />
        </div>
        <?php
    }
?>
</div>
