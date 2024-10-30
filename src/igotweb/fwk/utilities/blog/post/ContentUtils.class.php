<?php
/**
 *	Class: ContentUtils
 *	This class handle the Content utilities.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities\blog\post;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\blog\Post;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\blog\post\Content;
use igotweb_wp_mp_links\igotweb\fwk\utilities\generic\GenericBeanUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\FileUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\UrlUtils;

class ContentUtils extends GenericBeanUtils {
  
  private function __construct() {}
  
  /*
   *   static function: getBeanClass
   *   This function returns the bean class with namespace.
   */
  protected static function getBeanClass() {
    return 'igotweb\fwk\model\bean\blog\post\Content';
  }
}

?>
