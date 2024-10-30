<?php
    if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

    use igotweb_wp_mp_links\igotweb\wp\plugin\Utilities;
    use igotweb_wp_mp_links\igotweb\wp\utilities\ViewsUtils;
    use igotweb_wp_mp_links\igotweb\wp\utilities\OptionsUtils;

    $optionsUtils = OptionsUtils::getInstance();

    $data["exportAction"] = $optionsUtils->getExportAction();
    $data["importAction"] = $optionsUtils->getImportAction();

    $data[ViewsUtils::$VIEW_JS_RESOURCES_TAG] = array(
        'fileMandatory' => __('No file is selected.'),
        'exportGenericError' => __('Error while exporting configuration.'),
        'importGenericError' => __('Error while importing configuration.')
    );

?>