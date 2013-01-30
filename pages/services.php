<?php
/**
 * Page where administer importing/export of arlima lists
 *
 * @package Arlima
 * @since 2.0
 */

$arlima_plugin = new Arlima_Plugin();
$export_manager = new Arlima_ExportManager($arlima_plugin);
$import_manager = new Arlima_ImportManager($arlima_plugin);
$factory = new Arlima_ListFactory();
$connector = new Arlima_ListConnector();

// Form posted
if( count($_POST) > 0 ) {

    // Export settings saved
    if($_POST['type'] == 'export') {
        $approved = empty($_POST['approved']) ? array() : $_POST['approved'];
        $export_manager->setListsAvailableForExport($approved);
        $message = __('Export settings saved successfully', 'arlima');
    }

    // Remove imported list
    elseif($_POST['type'] == 'remove_import') {
        $import_manager->removeImportedList($_POST['remove']);
        $message = __('Imported list successfully removed', 'arlima');
    }
}

// Create a list of our arlima lists sorted so that those lists
// approved for export comes first. Also find out from which
// page the approved lists can be exported
$lists_sorted = array();
$lists = $factory->loadListSlugs();
$has_exportable_list = false;
foreach($lists as &$list_data) {
    if($export_manager->isAvailableForExport($list_data->id)) {
        $has_exportable_list = true;

        // Monkey patch the list object
        $list_data->approved = true;
        $list_data->export_page = false;
        $connector->setList($factory->loadList($list_data->id));
        $pages = $connector->loadRelatedPages();

        if(!empty($pages)) { // monkey patch from which page list can be exported
            $list_data->export_page = rtrim(get_permalink($pages[0]->ID),'/') .'/'.Arlima_Plugin::EXPORT_FEED_NAME.'/';
        }

        array_unshift($lists_sorted, $list_data);

    }
    else {
        $list_data->approved = false;
        array_push($lists_sorted, $list_data);
    }
}

if( isset($message) ): ?>
    <div id="setting-error-settings_updated" class="updated settings-error success">
        <p><strong><?php echo $message; ?></strong></p>
    </div>
<?php endif; ?>
<div id="arlima-help">
    <h2><?php _e('Export', 'arlima') ?></h2>
    <p><?php _e('Choose which article lists that should be available for export', 'arlima') ?></p>
    <form action="admin.php?page=arlima-services" method="post">
        <input type="hidden" name="type" value="export" />
        <div id="export-list">
            <?php $i=0; foreach($lists_sorted as $list_data): $i++; ?>
            <p>
                <label for="list<?php echo $i; ?>">
                    <input id="list<?php echo $i; ?>" type="checkbox" name="approved[]" value="<?php echo $list_data->id ?>" <?php if($list_data->approved) echo ' checked="checked"'; ?> />
                    <strong><?php echo $list_data->title ?></strong>
                </label>
                <?php if($list_data->approved): ?>
                <span class="gray-small">
                    <?php if($list_data->export_page): ?>
                        <?php _e('URL', 'arlima') ?>: <a href="<?php echo $list_data->export_page ?>" target="_blank"><?php echo $list_data->export_page ?></a>
                    <?php else:
                            // todo: Translate
                            echo sprintf(__('This list is not related to any page!', 'arlima'), '&quot;'.$list_data->slug.'&quot;');
                     endif; ?>
                </span>
                <?php endif; ?>
            </p>
            <?php endforeach; ?>
        </div>
        <p>
            <input type="submit" value="<?php _e('Save', 'arlima') ?>" class="button-secondary action" />
            <?php if($has_exportable_list): ?>
                <em class="gray-small">(<?php _e('You can export your lists in RSS format by adding ?format=rss to the URL.', 'arlima') ?>)</em>
            <?php endif; ?>
        </p>
    </form>
    <h2><?php _e('Import', 'arlima') ?></h2>
    <p>
        <input type="text" id="import-url" placeholder="http://...." />
        <input type="button" value="<?php _e('Import list', 'arlima') ?>" class="button-secondary action" onclick="importExternalList(jQuery('#import-url'), jQuery('#imported-lists'));" />
    </p>
    <form action="admin.php?page=arlima-services" method="post" id="imported-lists">
        <input type="hidden" name="type" value="remove_import" />
        <?php
        $imported = $import_manager->getImportedLists();
        if(count($imported) > 0): ?>
            <h3><?php _e('Imported lists', 'arlima') ?></h3>
            <?php foreach($imported as $i) Arlima_ImportManager::displayImportedList($i['url'], $i['title']); ?>
        <?php endif; ?>
    </form>
</div>