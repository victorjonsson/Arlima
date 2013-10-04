<?php
/**
 * Admin page where you create/edit/delete article lists
 *
 * @package Arlima
 * @since 1.0
 */

$factory = new Arlima_ListFactory();
$list_id = !empty( $_REQUEST[ 'alid' ] ) ? (int)$_REQUEST[ 'alid' ] : null;

if( $list_id ) {
    $list = $factory->loadLatestPreview($list_id);
} else {
    $list = new Arlima_List(); // We use an empty list as preset
}

$connector = new Arlima_ListConnector($list);

if( count($_POST) > 0 ) {

    // Update
    if($list->exists()) {
        $old_slug = $list->getSlug() != $_POST['slug'] ? $list->getSlug() : false;
        $list->setTitle( $_POST['title'] );
        $list->setSlug( $_POST['slug'] );
        $list->setOptions( $_POST['options'] );
        $list->setMaxlength( $_POST['maxlength'] );
        $factory->updateListProperties($list);
        $message = sprintf(__('List %s was successfully updated', 'arlima'), '&quot;'.$list->getTitle().'&quot;');
    }

    // Create
    else {
        $list = $factory->createList($_POST['title'], $_POST['slug'], $_POST['options'], $_POST['maxlength']);
        $message = sprintf(__('List %s was successfully created', 'arlima'), '&quot;'.$list->getTitle().'&quot;');
    }
}

// Delete
elseif($list->exists() && isset($_GET['remove_list'])) {
    $factory->deleteList($list);
    $connector->removeAllRelations();
    $message = sprintf(__('List %s was successfully removed', 'arlima'), '&quot;'.$list->getTitle().'&quot;');
    $list = new Arlima_List();
    $connector->setList($list);
}

$available_lists = $factory->loadListSlugs();
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
					<div class="alignleft">
						
						<div id="arlima-lists" >
							<input type="text" name="arlima-search-lists" id="arlima-search-lists" placeholder="<?php _e('Search', 'arlima') ?>..." />
							<ul>
								<?php foreach($available_lists as $list_data): ?>
									<li class="arlima-list-link" id="arlima-list-link_<?php echo $list_data->id; ?>" style="display:none;">
                                        <a href="admin.php?page=arlima-edit&alid=<?php echo $list_data->id; ?>">
                                            <?php echo $list_data->id . '. ' . $list_data->title; ?>
                                        </a>
                                    </li>
								<?php endforeach; ?>
							</ul>
						</div>
					
						<form method="get" action="admin.php" id="arlima-select-list" style="display:inline;margin-right:50px">
							<input type="hidden" name="page" value="arlima-edit" />
							<select name="alid" id="arlima-add-list-select" style="margin-left: 50px;">
								<option value=""><?php _e('Choose article list', 'arlima') ?></option>
								<?php
								foreach( $available_lists as $list_data ): ?>
									<option value="<?php echo $list_data->id; ?>" <?php if( $list_data->id == $list->id() ) echo 'selected="selected"'; ?>>
                                        <?php echo $list_data->title; ?>
                                    </option>
								<?php endforeach; ?>
							</select>
						</form>
						
						<a href="admin.php?page=arlima-edit">
                            <input type="button" value="<?php _e('New list', 'arlima') ?>" class="button-secondary action" />
                        </a>

					</div><!-- .alignleft actions -->
				</div><!-- .tablenav -->
				
				<?php
				if( $list->exists() ) {
					$header = '<strong>'.__('Edit list', 'arlima').':</strong> ' . $list->getTitle() . ' (id ' . $list->id() . ')';
				}else{
					$header = '<strong>'.__('New list', 'arlima').'</strong>';
				}

				?>
				<div class="arlima-postbox">
					<h3><span><?php echo $header; ?></span></h3>
					<div class="inside">
						
						<form method="post" id="arlima-edit-list" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
							<?php if( $list->exists() ): ?>
								<input type="hidden" name="alid" id="alid" value="<?php echo $list->id(); ?>" />
							<?php endif; ?>
							<table class="form-table">
								<tbody>
								<tr valign="top">
									<th scope="row"><label for="title"><?php _e('Title', 'arlima') ?><span> *</span></th>
									<td><input id="title" name="title" class="regular-text" value="<?php echo $list->getTitle(); ?>" /></td>
								</tr>
								<tr valign="top">
									<th scope="row"><label for="slug"><?php _e('Slug', 'arlima') ?><span> *</span></th>
									<td><input id="slug" name="slug" class="regular-text" value="<?php echo $list->getSlug(); ?>" /></td>
								</tr>
								<tr valign="top">
									<th scope="row"><label for="maxlength"><?php _e('Maximum number of articles', 'arlima') ?><span> *</span> </label></th>
									<td><input id="maxlength" name="maxlength" class="small-text" value="<?php echo $list->getMaxlength(); ?>" />
									<span class="description"></span></td>
								</tr>
								<tr valign="top">
									<th scope="row"><label for="article-template"><?php _e('Default template', 'arlima'); ?></label></th>
									<td>
                                        <select name="options[template]" id="article-template">
                                            <?php
                                            $tmpl = new Arlima_TemplatePathResolver();
                                            foreach($tmpl->getTemplateFiles() as $file) {
                                                $selected = $file['name'] == $list->getOption('template') ? ' selected="selected"':'';
                                                printf(
                                                    '<option value="%s"%s>%s</option>',
                                                    $file['name'],
                                                    $selected, $file['label'] . ($file['name'] != $file['label'] ? ' ('.$file['name'].'.tmpl)':'')
                                                );
                                            }
                                            ?>
                                        </select>
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
                                    $pages = $connector->loadRelatedPages();
                                    $widgets = $connector->loadRelatedWidgets();
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