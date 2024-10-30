<?php
/**
 *	Class: TagUtils
 *	This class handle the Tag utilities.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities\blog;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\blog\Tag;
use igotweb_wp_mp_links\igotweb\fwk\utilities\generic\GenericBeanUtils;

class TagUtils extends GenericBeanUtils {
  
  private function __construct() {}
  
    /*
   *   static function: getBeanClass
   *   This function returns the bean class with namespace.
   */
  protected static function getBeanClass() {
    return 'igotweb\fwk\model\bean\blog\Tag';
  }
}

?>
