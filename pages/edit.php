<?php
/**
 * Admin page where you create/edit/delete article lists
 *
 * @package Arlima
 * @since 1.0
 */

$list_repo = new Arlima_ListRepository();
$sys = Arlima_CMSFacade::load();
$list_id = !empty( $_REQUEST[ 'alid' ] ) ? (int)$_REQUEST[ 'alid' ] : null;

$tmpl_path_resolver = new Arlima_TemplatePathResolver();
$article_templates = $tmpl_path_resolver->getTemplateFiles();


if( $list_id ) {
    $list = $list_repo->load($list_id);
} else {
    $list = new Arlima_List(); // We use an empty list as preset
}


if( count($_POST) > 0 ) {

    // Add default template to available templates if not already there
    if( empty($_POST['options']['available_templates']) || !in_array($_POST['options']['template'], $_POST['options']['available_templates'])) {
        $_POST['options']['available_templates'][] = $_POST['options']['template'];
    }

    // If all templates is set as available then remove the option completely which means
    // that all current template and all future templates will be available
    if( count($_POST['options']['available_templates']) == count($article_templates) ) {
        $_POST['options']['available_templates'] = false;
    }

    // Update
    if($list->exists()) {
        $old_slug = $list->getSlug() != $_POST['slug'] ? $list->getSlug() : false;
        $list->setTitle( $_POST['title'] );
        $list->setSlug( $_POST['slug'] );
        $list->setOptions( $_POST['options'] );
        $list->setMaxlength( $_POST['maxlength'] );
        $list_repo->update($list);
        $message = sprintf(__('List %s was successfully updated', 'arlima'), '&quot;'.$list->getTitle().'&quot;');
    }

    // Create
    else {
        $list = $list_repo->create($_POST['title'], $_POST['slug'], $_POST['options'], $_POST['maxlength']);
        $message = sprintf(__('List %s was successfully created', 'arlima'), '&quot;'.$list->getTitle().'&quot;');
    }
}

// Delete
elseif($list->exists() && isset($_GET['remove_list'])) {
    $sys->removeAllRelations($list);
    $list_repo->delete($list);
    $message = sprintf(__('List %s was successfully removed', 'arlima'), '&quot;'.$list->getTitle().'&quot;');
    $list = new Arlima_List();
}

$available_lists = $list_repo->loadListSlugs();

?>
    <div id="col-container">
		<div id="col-right">
			<div class="col-wrap">

			</div><!-- .col-wrap -->
		</div><!-- #col-right -->
		<div id="col-left">
			<div class="col-wrap">
                <?php if( isset($message) ): ?>
                    <div id="setting-error-settings_updated" class="updated settings-error success">
                        <p><strong><?php echo $message; ?></strong></p>
                    </div>
                <?php endif; ?>
				<div class="tablenav">
                    <form method="get" action="admin.php" id="arlima-select-list" style="display: inline; margin-right: 5px">
                        <input type="hidden" name="page" value="arlima-edit" />
                        <select name="alid" id="arlima-add-list-select" >
                            <option value=""><?php _e('Choose article list', 'arlima') ?></option>
                            <?php
                            foreach( $available_lists as $list_data ): ?>
                                <option value="<?php echo $list_data->id; ?>" <?php if( $list_data->id == $list->getId() ) echo 'selected="selected"'; ?>>
                                    <?php echo $list_data->title; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <a href="admin.php?page=arlima-edit">
                        <input type="button" value="<?php _e('New list', 'arlima') ?>" class="button-secondary action" />
                    </a>
				</div><!-- .tablenav -->
				
				<?php
				if( $list->exists() ) {
					$header = '<strong>'.__('Edit list', 'arlima').':</strong> ' . $list->getTitle() . ' (id ' . $list->getId() . ')';
				}else{
					$header = '<strong>'.__('New list', 'arlima').'</strong>';
				}

				?>
				<div class="arlima-postbox">
					<h3><span><?php echo $header; ?></span></h3>
					<div class="inside">
						
						<form method="post" id="arlima-edit-list" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
							<?php if( $list->exists() ): ?>
								<input type="hidden" name="alid" id="alid" value="<?php echo $list->getId(); ?>" />
							<?php endif; ?>
							<table class="form-table">
								<tbody>
								<tr valign="top">
									<th scope="row"><label for="title"><?php _e('Title', 'arlima') ?><span> *</span></th>
									<td><input id="title" name="title"
                                               data-validation="required"
                                               data-validation-error-msg="<?php _e('This field can not be empty', 'arlima') ?>"
                                               class="regular-text" value="<?php echo $list->getTitle(); ?>" /></td>
								</tr>
								<tr valign="top">
									<th scope="row"><label for="slug"><?php _e('Slug', 'arlima') ?><span> *</span></th>
									<td><input id="slug" name="slug"
                                               data-validation="required"
                                               data-validation-error-msg="<?php _e('This field can not be empty', 'arlima') ?>"
                                               class="regular-text" value="<?php echo $list->getSlug(); ?>" /></td>
								</tr>
								<tr valign="top">
									<th scope="row"><label for="maxlength"><?php _e('Maximum number of articles', 'arlima') ?><span> *</span> </label></th>
									<td><input id="maxlength" name="maxlength"
                                               data-validation="number"
                                               data-validation-error-msg="<?php _e('This field can only contain numbers', 'arlima') ?>"
                                               class="small-text" value="<?php echo $list->getMaxlength(); ?>" />
									<span class="description"></span></td>
								</tr>
								<tr valign="top">
									<th scope="row"><label for="article-template"><?php _e('Default template', 'arlima'); ?></label></th>
									<td>
                                        <select name="options[template]" id="article-template">
                                            <?php foreach($article_templates as $file) {
                                                $selected = $file['name'] == $list->getOption('template') ? ' selected="selected"':'';
                                                printf(
                                                    '<option value="%s"%s>%s</option>',
                                                    $file['name'],
                                                    $selected, $file['label'] . ($file['name'] != $file['label'] ? ' ('.$file['name']. Arlima_TemplatePathResolver::TMPL_EXT .')':'')
                                                );
                                            }
                                            ?>
                                        </select>
                                    </td>
								</tr>
                                <tr valign="top">
                                    <th scope="row">
                                        <label for="article-template"><?php _e('Available templates', 'arlima'); ?></label>
                                        <p class="small">
                                            <?php _e('Notice that templates hidden by the filter <em>arlima_hidden_templates</em> can not be made available in the list manager.', 'arlima') ?>
                                        </p>
                                    </th>
                                    <td>
                                        <div class="scroll-window">
                                            <?php
                                            $hidden_templates = apply_filters('arlima_hidden_templates', array('file-include'), null);
                                            foreach($article_templates as $file):
                                                if( in_array($file['name'], $hidden_templates) )
                                                    continue;
                                            ?>
                                                <p>
                                                    <label>
                                                        <input type="checkbox" name="options[available_templates][]" value="<?php echo basename($file['name']) ?>"<?php
                                                            if( $list->isAvailable($file['name'])) echo ' checked="checked"'; ?> />
                                                        <strong><?php echo $file['label'] . ($file['name'] != $file['label'] ? ' ('.$file['name']. Arlima_TemplatePathResolver::TMPL_EXT .')':'') ?></strong>
                                                    </label>
                                                </p>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><label for="allows_switch"><?php _e('Allow editors to switch template on articles in the list manager', 'arlima') ?></label></th>
                                    <td>
                                        <select id="allows_switch" name="options[allows_template_switching]">
                                            <option value="1"<?php if( $list->isSupportingEditorTemplateSwitch() ) echo ' selected="selected"' ?>>
                                                <?php _e('Yes', 'arlima') ?>
                                            </option>
                                            <option value="0"<?php if( !$list->isSupportingEditorTemplateSwitch() ) echo ' selected="selected"' ?>>
                                                <?php _e('No', 'arlima') ?>
                                            </option>
                                        </select>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><label for="supports_sections"><?php _e('Should this list support sections (read wiki for more info)', 'arlima') ?></label></th>
                                    <td>
                                        <select id="supports_sections" name="options[supports_sections]">
                                            <option value="1"<?php if( $list->isSupportingSections() ) echo ' selected="selected"' ?>>
                                                <?php _e('Yes', 'arlima') ?>
                                            </option>
                                            <option value="0"<?php if( !$list->isSupportingSections() ) echo ' selected="selected"' ?>>
                                                <?php _e('No', 'arlima') ?>
                                            </option>
                                        </select>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><label for="pages_to_purge"><?php _e('Pages to purge', 'arlima') ?></label></th>
                                    <td><input id="pages_to_purge" name="options[pages_to_purge]" class="regular-text" value="<?php echo $list->getOption('pages_to_purge'); ?>" />
                                        <br /><span class="description"><?php _e('Comma separated list of URL\'s where this list will be displayed (for caching control only)', 'arlima') ?></span></td>
                                </tr>
								<tr valign="top">
									<th scope="row"><?php _e('Title HTML-tags', 'arlima') ?></th>
									<td>
										<input name="options[before_title]" style="width:80%" class="regular-text" type="text" value="<?php echo htmlentities( $list->getOption('before_title') ); ?>" /> <label><?php _e('Before', 'arlima') ?></label> <br />
										<input name="options[after_title]" style="width:80%" class="regular-text" type="text" value="<?php echo htmlentities( $list->getOption('after_title') ); ?>" /> <label><?php _e('After', 'arlima') ?></label><br />
									<span class="description"><?php _e('Default &lt;h2&gt;, &lt;/h2&gt;', 'arlima') ?></span></td>
								</tr>
								</tbody>
							</table>

							<input type="submit" value="<?php _e('Save', 'arlima') ?>" class="button-secondary action button-primary" />

                            <?php if($list->exists()): ?>
                                <input type="button" value="<?php _e('Remove', 'arlima') ?>" style="color:red; margin-left: 5px;" class="button-secondary action"
                                       onclick="if(confirm('<?php _e('Are you sure that you want to remove this list?', 'arlima') ?>')) document.location = document.location.href + '&remove_list=1'" />

                                <div class="content-relations">
                                    <p><strong><?php _e('Pages') ?>:</strong></p>
                                    <?php
                                    $pages = $sys->loadRelatedPages($list);
                                    $widgets = $sys->loadRelatedWidgets($list);
                                    if( empty($pages) ):?>
                                        <p><em><?php _e('This list is not yet related to any page', 'arlima') ?></em></p>
                                    <?php else: ?>
                                        <?php foreach($pages as $p): ?>
                                            <a href="<?php echo get_permalink($p->ID) ?>" target="_blank">
                                            <?php echo $p->post_title ?>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <?php if( !empty($widgets) ): ?>
                                        <p><strong><?php _e('Widgets') ?>:</strong></p>
                                        <?php foreach($widgets as $data): ?>
                                            <?php echo __('Widget number ', 'arlima').$data['index'].__(' in ', 'arlima').' &quot;'.$data['sidebar'].'&quot;' ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

							<?php endif; ?>
						</form>
						
						
					</div><!-- .inside -->
				</div><!-- .arlima-postbox -->

			</div><!-- .col-wrap -->
		</div><!-- #col-left -->
	</div><!-- #col-container -->