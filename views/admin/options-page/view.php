<?php
    if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

/**
 * The Options page
 */

use igotweb_wp_mp_links\igotweb\wp\plugin\Options;
use igotweb_wp_mp_links\igotweb\wp\utilities\ViewsUtils;
use igotweb_wp_mp_links\igotweb\wp\Plugin;
?>

<div class="wrap admin-options-page">
    <img src="<?php echo ViewsUtils::getAssetURL('img/logo-hi.gif'); ?>" class="logo"/>
    <h1><?php _e('Links - Options','igotweb-wp-mp-links'); ?></h1>

    <?php settings_errors() ?>

    <?php if(WP_DEBUG): ?>
        <p>
            <?php _e('Current locale: ','igotweb-wp-mp-links'); echo get_locale(); ?>
        </p>
    <?php endif; ?>


    <form method="post" action="options.php">
        <?php
        
            //We generate the specific fields for this settings group.
            settings_fields(Options::$PAGE_SLUG);
            do_settings_sections(Options::$PAGE_SLUG);
        
            // Add the submit button to serialize the options
            submit_button(); 
            
        ?>          
    </form>

</div>