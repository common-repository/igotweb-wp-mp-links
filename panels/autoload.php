<?php
/**
 * We register the autoloader function
 */

spl_autoload_register( function ( $class_name ) {
    // We only check classes for this plugin
    if ( false !== strpos( $class_name, 'igotweb_wp_mp_links\igotweb' ) ) {
        // We generate the 
        $classes_dir = realpath( plugin_dir_path(__FILE__) ) . DIRECTORY_SEPARATOR . '../src' . DIRECTORY_SEPARATOR;
        
        // We remove the plugin specific namespace to generate the folder path
        $pluginNamespace = 'igotweb_wp_mp_links';
        $class_name = str_replace($pluginNamespace . '\\','', $class_name);

        $class_file = str_replace( '\\', DIRECTORY_SEPARATOR, $class_name ) . '.php';
        if(false !== strpos( $class_name, 'igotweb\fwk')) {
            $class_file = str_replace( '\\', DIRECTORY_SEPARATOR, $class_name ) . '.class.php';
        }
        require_once $classes_dir . $class_file;
    }
} );

?>