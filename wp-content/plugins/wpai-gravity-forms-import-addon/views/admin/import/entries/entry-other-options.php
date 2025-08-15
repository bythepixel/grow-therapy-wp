<div class="wpallimport-collapsed closed">
	<div class="wpallimport-content-section">
		<div class="wpallimport-collapsed-header">
			<h3><?php _e('Other Entry Options','wp_all_import_gf_add_on');?></h3>
		</div>
		<div class="wpallimport-collapsed-content" style="padding: 0;">
			<div class="wpallimport-collapsed-content-inner wpallimport-gf-data">
                <div class="postbox pmgi_postbox pmgi_single_form">
                    <div class="inside">
                        <div class="columns-wrapper">
                            <div class="column">
                                <h4><?php _e('Date created', 'wp_all_import_gf_add_on'); ?><a href="#help" class="wpallimport-help" style="position:relative; top: -1px; left: 8px;" title="<?php _e('Use any format supported by the PHP <b>strtotime</b> function. That means pretty much any human-readable date will work.', 'wp_all_import_gf_add_on') ?>">?</a></h4>
                                <div class="input">
                                    <input type="text" class="datepicker" name="pmgi[date_created]" value="<?php echo esc_attr($post['pmgi']['date_created']) ?>" />
                                </div>
                            </div>
                            <div class="column">
                                <h4><?php _e('Date updated', 'wp_all_import_gf_add_on'); ?><a href="#help" class="wpallimport-help" style="position:relative; top: -1px; left: 8px;" title="<?php _e('Use any format supported by the PHP <b>strtotime</b> function. That means pretty much any human-readable date will work.', 'wp_all_import_gf_add_on') ?>">?</a></h4>
                                <div class="input">
                                    <input type="text" class="datepicker" name="pmgi[date_updated]" value="<?php echo esc_attr($post['pmgi']['date_updated']) ?>" />
                                </div>
                            </div>
                        </div>
                        <div class="columns-wrapper">
                            <div class="column">
                                <h4><?php _e('IP', 'wp_all_import_gf_add_on') ?></h4>
                                <div>
                                    <input type="text" name="pmgi[ip]" style="width:100%;" value="<?php echo esc_attr($post['pmgi']['ip']); ?>" />
                                </div>
                            </div>
                            <div class="column">
                                <h4><?php _e('Source URL', 'wp_all_import_gf_add_on') ?></h4>
                                <div>
                                    <input type="text" name="pmgi[source_url]" style="width:100%;" value="<?php echo esc_attr($post['pmgi']['source_url']); ?>" />
                                </div>
                            </div>
                        </div>
                        <div class="columns-wrapper">
                            <div class="column">
                                <h4><?php _e('User Agent', 'wp_all_import_gf_add_on') ?></h4>
                                <div>
                                    <input type="text" name="pmgi[user_agent]" style="width:100%;" value="<?php echo esc_attr($post['pmgi']['user_agent']); ?>" />
                                </div>
                            </div>
                            <div class="column">
                                <h4><?php _e('Created By', 'wp_all_import_gf_add_on') ?><a href="#help" class="wpallimport-help" title="<?php _e('Assign the entry to an existing user account by specifying the user ID, username, or e-mail address.', 'wp_all_import_gf_add_on') ?>" style="position:relative; top:-2px; left: 8px;">?</a></h4>
                                <div>
                                    <input type="text" name="pmgi[created_by]" style="width:100%;" value="<?php echo esc_attr($post['pmgi']['created_by']); ?>" />
                                </div>
                            </div>
                        </div>
                        <div class="columns-wrapper">
                            <div class="column">
                                <h4><?php _e('Starred', 'wp_all_import_gf_add_on'); ?></h4>
                                <div class="input">
                                    <input type="radio" id="starred_yes" name="pmgi[starred]" value="yes" <?php echo 'yes' == $post['pmgi']['starred'] ? 'checked="checked"' : '' ?> class="switcher"/>
                                    <label for="starred_yes"><?php _e('Yes', 'wp_all_import_gf_add_on') ?></label>
                                </div>
                                <div class="input">
                                    <input type="radio" id="starred_no" name="pmgi[starred]" value="no" <?php echo 'no' == $post['pmgi']['starred'] ? 'checked="checked"' : '' ?> class="switcher"/>
                                    <label for="starred_no"><?php _e('No', 'wp_all_import_gf_add_on') ?></label>
                                </div>
                                <div class="input fleft" style="position:relative; width:220px;">
                                    <input type="radio" id="starred_xpath" class="switcher" name="pmgi[starred]" value="xpath" <?php echo 'xpath' == $post['pmgi']['starred'] ? 'checked="checked"': '' ?>/>
                                    <label for="starred_xpath"><?php _e('Set with XPath', 'wp_all_import_gf_add_on' )?></label> <br>
                                    <div class="switcher-target-starred_xpath">
                                        <div class="input">
                                            <input type="text" class="smaller-text" name="pmgi[starred_xpath]" style="width:190px;" value="<?php echo esc_attr($post['pmgi']['starred_xpath']) ?>"/>
                                            <a href="#help" class="wpallimport-help" title="<?php _e('The value of presented XPath should be one of the following: (\'yes\', \'no\').', 'wp_all_import_gf_add_on') ?>" style="position:relative; top:8px; float: right;">?</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="column">
                                <h4><?php _e('Read', 'wp_all_import_gf_add_on'); ?></h4>
                                <div class="input">
                                    <input type="radio" id="read_yes" name="pmgi[read]" value="yes" <?php echo 'yes' == $post['pmgi']['read'] ? 'checked="checked"' : '' ?> class="switcher"/>
                                    <label for="read_yes"><?php _e('Yes', 'wp_all_import_gf_add_on') ?></label>
                                </div>
                                <div class="input">
                                    <input type="radio" id="read_no" name="pmgi[read]" value="no" <?php echo 'no' == $post['pmgi']['read'] ? 'checked="checked"' : '' ?> class="switcher"/>
                                    <label for="read_no"><?php _e('No', 'wp_all_import_gf_add_on') ?></label>
                                </div>
                                <div class="input fleft" style="position:relative; width:220px;">
                                    <input type="radio" id="read_xpath" class="switcher" name="pmgi[read]" value="xpath" <?php echo 'xpath' == $post['pmgi']['read'] ? 'checked="checked"': '' ?>/>
                                    <label for="read_xpath"><?php _e('Set with XPath', 'wp_all_import_gf_add_on' )?></label> <br>
                                    <div class="switcher-target-read_xpath">
                                        <div class="input">
                                            <input type="text" class="smaller-text" name="pmgi[read_xpath]" style="width:190px;" value="<?php echo esc_attr($post['pmgi']['read_xpath']) ?>"/>
                                            <a href="#help" class="wpallimport-help" title="<?php _e('The value of presented XPath should be one of the following: (\'yes\', \'no\').', 'wp_all_import_gf_add_on') ?>" style="position:relative; top:8px; float: right;">?</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="columns-wrapper">
                            <div class="column">
                                <h4><?php _e('Status', 'wp_all_import_gf_add_on'); ?></h4>
                                <div class="input">
                                    <input type="radio" id="status_active" name="pmgi[status]" value="active" <?php echo 'active' == $post['pmgi']['status'] ? 'checked="checked"' : '' ?> class="switcher"/>
                                    <label for="status_active"><?php _e('Active', 'wp_all_import_gf_add_on') ?></label>
                                </div>
                                <div class="input">
                                    <input type="radio" id="status_trash" name="pmgi[status]" value="trash" <?php echo 'trash' == $post['pmgi']['status'] ? 'checked="checked"' : '' ?> class="switcher"/>
                                    <label for="status_trash"><?php _e('Trash', 'wp_all_import_gf_add_on') ?></label>
                                </div>
                                <div class="input fleft" style="position:relative; width:220px;">
                                    <input type="radio" id="status_xpath" class="switcher" name="pmgi[status]" value="xpath" <?php echo 'xpath' == $post['pmgi']['status'] ? 'checked="checked"': '' ?>/>
                                    <label for="status_xpath"><?php _e('Set with XPath', 'wp_all_import_gf_add_on' )?></label> <br>
                                    <div class="switcher-target-status_xpath">
                                        <div class="input">
                                            <input type="text" class="smaller-text" name="pmgi[status_xpath]" style="width:190px;" value="<?php echo esc_attr($post['pmgi']['status_xpath']) ?>"/>
                                            <a href="#help" class="wpallimport-help" title="<?php _e('The value of presented XPath should be one of the following: (\'active\', \'trash\').', 'wp_all_import_gf_add_on') ?>" style="position:relative; top:8px; float: right;">?</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
			</div>
		</div>
	</div>
</div>
