<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>

<div class="error" style="padding: 10px;">
    <b>
        <?php 
            $plugin = Plugin::getInstance();
            printf( esc_html__( '%s doesn’t have a valid license key installed.', 'igotweb-wp' ), $plugin->getName() );
        ?>
    </b>
</div>