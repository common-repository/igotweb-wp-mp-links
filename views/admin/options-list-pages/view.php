<?php
    if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}
    
    use igotweb_wp_mp_links\igotweb\wp\utilities\ViewsUtils;
    use igotweb_wp_mp_links\igotweb\wp\Plugin;
?>
<div <?php ViewsUtils::generateViewContainerAttributes($data); ?>>
    <p class="no-page iw-description" style="display:none;">
        <?php _e('Add pages to the menu of the MemberPress account page', 'igotweb-wp-mp-links'); ?>
    </p>
    
    <div class="iw-list-pages"></div>
    <a href="javascript:void(0);" class="add" title="<?php _e('Add a page', 'igotweb-wp-mp-links'); ?>"><i class="fas fa-plus"></i></a>
</div>