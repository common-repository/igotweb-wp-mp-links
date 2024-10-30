<?php
    if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

    use igotweb_wp_mp_links\igotweb\wp\utilities\ViewsUtils;
    use igotweb_wp_mp_links\igotweb\wp\utilities\PageUtils;
    use igotweb_wp_mp_links\igotweb\wp\utilities\PluginUtils;
    use igotweb_wp_mp_links\igotweb\wp\Plugin;

    /*
     *  Input to be sent as parameter
     *  - data[settingName] => the settingName
     */

    $pageUtils = PageUtils::getInstance();
    $listPages = $pageUtils->getListPages();

    $plugin = Plugin::getInstance();
    $options = $plugin->getOptions();
    $settings = $options->getListPages();
    
    $options = array();
    foreach($listPages as $page) {
        $label = $page->post_title;
        $pageID = $page->ID;
        $option = array(
            "value" => $pageID,
            "label" => $label,
            "selected" => false
        );
        $options[] = $option;
    }

    $fieldIDPrefix = $data['settingName'];
    $fieldNamePrefix = $data['settingName'];

    $pageFieldID = $fieldIDPrefix.'_page';
    $menuTitleFieldID = $fieldIDPrefix.'_menuTitle';

    $data["pages"] = $options;
    $data["settings"] = $settings;
    $data["fieldNamePrefix"] = $fieldNamePrefix;
    $data["pageFieldID"] = $pageFieldID;
    $data["menuTitleFieldID"] = $menuTitleFieldID;

    $data[ViewsUtils::$VIEW_JS_RESOURCES_TAG] = array(
        'pageLabel' => __('Page','igotweb-wp-mp-links'),
        'removeTitle' => __('Remove the page','igotweb-wp-mp-links'),
        'menuTitleLabel' => __('Title within menu','igotweb-wp-mp-links')
    );

?>