<?php

declare(strict_types=1);

/**
 * Delete all form entries immediately after submission
 * Business requirement: HIPAA compliance, don't want to store any PII (Personal Identifiable Information)
 * 
 * @param array $entry The submitted entry data
 * @param array $form The form configuration
 */
add_action('gform_after_submission', 'gravity_forms_delete_entries_after_submission', 10, 2);

function gravity_forms_delete_entries_after_submission(array $entry, array $form): void
{
    // Get all entry IDs for the form to avoid loading full entry data
    $entry_ids = GFAPI::get_entry_ids($form['id']);
    
    if (empty($entry_ids)) {
        return;
    }
    
    // Delete entries in batch for better performance
    foreach ($entry_ids as $entry_id) {
        GFAPI::delete_entry($entry_id);
    }
}
