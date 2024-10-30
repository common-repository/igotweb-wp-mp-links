<?php
/**
 *	Class: FolderUtils
 *	This class handle the Folder utilities.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\utilities\generic\GenericBeanUtils;
use Folder;

class FolderUtils extends GenericBeanUtils {
  
  private function __construct() {}

  /*
   *   static function: getBeanClass
   *   This function returns the bean class with namespace.
   */
  protected static function getBeanClass() {
    return 'Folder';
  }
}

?>
