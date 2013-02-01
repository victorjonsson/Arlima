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


if( isset($message) ): ?>
    <div id="setting-error-settings_updated" class="updated settings-error success">
        <p><strong><?php echo $message; ?></strong></p>
    </div>
<?php endif; ?>
<div id="arlima-help">
    <form action="admin.php?page=arlima-services" method="post">


        <p>
            <input type="submit" value="<?php _e('Save export settings', 'arlima') ?>" class="button-primary action" />
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