<?php
    if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}
    
    use igotweb_wp_mp_links\igotweb\wp\utilities\ViewsUtils;
    use igotweb_wp_mp_links\igotweb\wp\Plugin;
?>
<div <?php ViewsUtils::generateViewContainerAttributes($data); ?>>
    <ul class="iw-errors" style="display:none;"></ul>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row"><?php _e('Import/Export configuration','igotweb-wp'); ?></th>
                <td>
                    <form name="export">
                        <input type="submit" name="submit" class="button button-primary" value="<?php _e('Export current configuration','igotweb-wp'); ?>">
                    </form>
                    <form name="import">
                        <input type="file" name="file" />
                        <input type="submit" name="submit" class="button button-primary" value="<?php _e('Import','igotweb-wp'); ?>">
                    </form>
                </td>
            </tr>
        </tbody>
    </table>
</div>