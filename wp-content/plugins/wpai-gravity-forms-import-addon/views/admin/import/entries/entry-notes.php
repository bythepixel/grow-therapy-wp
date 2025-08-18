<div class="wpallimport-collapsed closed">
	<div class="wpallimport-content-section" style="padding-bottom: 0;">
        <div class="wpallimport-collapsed-header" style="margin-bottom: 15px;">
			<h3><?php _e('Entry Notes','wp_all_import_gf_add_on');?></h3>
		</div>
		<div class="wpallimport-collapsed-content" style="padding: 0;">
			<div class="wpallimport-collapsed-content-inner wpallimport-gf-data" style="padding-top:0">
                <div class="postbox pmgi_postbox pmgi_single_form">
                    <div class="inside">
                        <?php
                            $note = [];
                            if (!empty($post['pmgi']['notes'])) {
                                $note = array_shift($post['pmgi']['notes']);
                            }
                            $note += [ 'username' => '', 'date' => 'now', 'note_text' => '', 'note_type' => '', 'note_sub_type' => '' ];
                        ?>

                        <div class="columns-wrapper">
                            <div class="column">
                                <h4><?php _e('Username', 'wp_all_import_gf_add_on'); ?><a href="#help" class="wpallimport-help" style="position:relative; top: -1px; left: 8px;" title="<?php _e('Assign the note to an existing user account by specifying the user ID, username, or e-mail address. Use 0 or leave blank for notification notes.', 'wp_all_import_gf_add_on') ?>">?</a></h4>
                                <div class="input">
                                    <input type="text" name="pmgi[notes][0][username]" style="width: 100%;font-size: 15px !important;" class="rad4" value="<?php echo esc_attr($note['username']); ?>"/>
                                </div>
                            </div>
                            <div class="column">
                                <h4><?php _e('Date Created', 'wp_all_import_gf_add_on') ?><a href="#help" class="wpallimport-help" style="position:relative; top: -1px; left: 8px;" title="<?php _e('Use any format supported by the PHP <b>strtotime</b> function. That means pretty much any human-readable date will work.', 'wp_all_import_gf_add_on') ?>">?</a></h4>
                                <div class="input">
                                    <input type="text" class="datepicker" name="pmgi[notes][0][date]" value="<?php echo esc_attr($note['date']) ?>"/>
                                </div>
                            </div>
                        </div>
                        <div class="columns-wrapper">
                            <div class="one-column">
                                <h4><?php _e('Note Text', 'wp_all_import_gf_add_on') ?></h4>
                                <div class="input">
                                    <input type="text" name="pmgi[notes][0][note_text]" value="<?php echo esc_attr($note['note_text']) ?>"/>
                                </div>
                            </div>
                        </div>
                        <div class="columns-wrapper">
                            <div class="column">
                                <h4><?php _e('Note Type', 'wp_all_import_gf_add_on'); ?><a href="#help" class="wpallimport-help" style="position:relative; top: -1px; left: 8px;" title="<?php _e('Possible values are \'user\' and \'notification\'.', 'wp_all_import_gf_add_on') ?>">?</a></h4>
                                <div class="input">
                                    <input type="text" name="pmgi[notes][0][note_type]" value="<?php echo esc_attr($note['note_type']) ?>"/>
                                </div>
                            </div>
                            <div class="column">
                                <h4><?php _e('Note Sub-Type', 'wp_all_import_gf_add_on') ?></h4>
                                <div class="input">
                                    <input type="text" name="pmgi[notes][0][note_sub_type]" value="<?php echo esc_attr($note['note_sub_type']) ?>"/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
			</div>

            <div class="wpallimport-collapsed closed wpallimport-section">
                <div style="margin:0; border-top:1px solid #ddd; border-bottom: none; border-right: none; border-left: none; background: #f1f2f2;" class="wpallimport-content-section rad0">
                    <div class="wpallimport-collapsed-header">
                        <h3 style="color:#40acad;"><?php _e("Advanced Options", 'wp_all_import_gf_add_on');?></h3>
                    </div>
                    <div style="padding: 0px;" class="wpallimport-collapsed-content">
                        <div class="wpallimport-collapsed-content-inner">
							<?php if ( empty(PMXI_Plugin::$session->options['delimiter']) ): ?>
                                <div class="form-field wpallimport-radio-field wpallimport-clear pmxi_option">
                                    <input type="radio" id="notes_repeater_mode_variable_csv" name="pmgi[notes_repeater_mode]" value="csv" <?php echo 'csv' == $post['pmgi']['notes_repeater_mode'] ? 'checked="checked"' : '' ?> class="switcher variable_repeater_mode"/>
                                    <label for="notes_repeater_mode_variable_csv" style="width:auto;"><?php _e('Fixed Repeater Mode', 'wp_all_import_gf_add_on') ?></label>
                                    <div class="switcher-target-notes_repeater_mode_variable_csv wpallimport-clear" style="padding: 10px 0 10px 25px; overflow: hidden;">
									<span class="order-separator-label wpallimport-slide-content" style="padding-left:0;">
										<label><?php _e('Multiple notes separated by', 'wp_all_import_gf_add_on'); ?></label>
										<input type="text" class="short rad4 order-separator-input" name="pmgi[notes_repeater_mode_separator]" value="<?php echo esc_attr($post['pmgi']['notes_repeater_mode_separator']) ?>" style="width:10%; text-align: center;"/>
										<a href="#help" class="wpallimport-help" style="top:0px;left:8px;" title="For example, two notes would be imported like this Note 1|Note 2">?</a>
									</span>
                                    </div>
                                </div>
                                <div class="form-field wpallimport-radio-field wpallimport-clear pmxi_option">
                                    <input type="radio" id="notes_repeater_mode_variable_xml" name="pmgi[notes_repeater_mode]" value="xml" <?php echo 'xml' == $post['pmgi']['notes_repeater_mode'] ? 'checked="checked"' : '' ?> class="switcher variable_repeater_mode"/>
                                    <label for="notes_repeater_mode_variable_xml" style="width:auto;"><?php _e('Variable Repeater Mode', 'wp_all_import_gf_add_on') ?></label>
                                    <div class="switcher-target-notes_repeater_mode_variable_xml wpallimport-clear" style="padding: 10px 0 10px 25px; overflow: hidden;">
									<span class="wpallimport-slide-content" style="padding-left:0;">
										<label style="width: 60px;"><?php _e('For each', 'wp_all_import_gf_add_on'); ?></label>
										<input type="text" class="short rad4" name="pmgi[notes_repeater_mode_foreach]" value="<?php echo esc_attr($post['pmgi']['notes_repeater_mode_foreach']) ?>" style="width:50%;"/>
										<label style="padding-left: 10px;"><?php _e('do...', 'wp_all_import_gf_add_on'); ?></label>
									</span>
                                    </div>
                                </div>
							<?php else: ?>
                                <input type="hidden" name="pmgi[notes_repeater_mode]" value="csv"/>
                                <div class="form-field input" style="padding-left: 20px;">
                                    <label class="order-separator-label" style="line-height: 30px;"><?php _e('Multiple notes separated by', 'wp_all_import_gf_add_on'); ?></label>
                                    <input type="text" class="short rad4 order-separator-input" name="pmgi[notes_repeater_mode_separator]" value="<?php echo esc_attr($post['pmgi']['notes_repeater_mode_separator']) ?>" style="width:10%; text-align: center;"/>
                                    <a href="#help" class="wpallimport-help" style="top:0px;left:8px;" title="For example, two notes would be imported like this 'Note 1|Note 2'">?</a>
                                </div>
							<?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>
