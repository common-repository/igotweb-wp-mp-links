<?php
/**
 *	Class: PostUtils
 *	This class handle the Post utilities.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities\blog;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\blog\Post;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\blog\post\Content;
use igotweb_wp_mp_links\igotweb\fwk\utilities\generic\GenericBeanUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\FileUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\UrlUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\blog\post\ContentUtils;
use igotweb_wp_mp_links\igotweb\fwk\Logger;

class PostUtils extends GenericBeanUtils {
  
  private function __construct() {}
  
  /*
   *   static function: getBeanClass
   *   This function returns the bean class with namespace.
   */
  protected static function getBeanClass() {
    return 'igotweb\fwk\model\bean\blog\Post';
  }

  /*
   *  function: getRootPath
   *  This function returns the blog / posts root path for current context.
   *
   *  parameters:
   *    - request - the request object.
   *  return:
   *    - The simple galleries root path.
   */
   public static function getRootPath(Request $request) {
    $siteContext = $request->getSiteContext();
    $path = $siteContext->getContentPath() . $request->getConfig("blogPostsContentRootPath");
    return FileUtils::cleanPath($path);
  }
  
    /*
   *  function: getAbsoluteStaticPath
   *  This function returns the blog / posts absolute static path for current context.
   *
   *  parameters:
   *    - request - the request object.
   *  return:
   *    - The simple galleries absolute static path.
   */
  public static function getAbsoluteStaticPath(Request $request) {
    // We populate the absolute static path
    $siteContext = $request->getSiteContext();
    $path = UrlUtils::getSiteURL(UrlUtils::cleanPath($siteContext->getStaticContentPath() . $request->getConfig("blogPostsContentRootPath")), $request);
    return $path;
  }

  /*
   *  function: getContentToLoad
   *  This action is called to get the web content to load.
   *  It returns a map with admin directory (key) and active directory (value).
   *
   *  parameters:
   *    - request - the request object.
   *  return:
   *    - content : map with admin directory (key) and active directory (value).
   */
   public static function getContentToLoad(Request $request) {
    $content = array();
    
    // 1. We add the posts content to the list
    $contentAdminPath = static::getRootPath($request);
    $request->getSiteContext()->setAdminMode(false);
    $contentActivePath = static::getRootPath($request);
    $request->getSiteContext()->setAdminMode(true);
    $content[$contentAdminPath] = $contentActivePath;
    
    return $content;
  }

  /*
   *  function: checkModuleAvailability
   *  This function check if the module is available for current context.
   *
   *  parameters:
   *    @param Request $request The request object.
   *  return:
   *    @return result object or error object.
   * 
   *  result object:
   *    isModuleAvailable : boolean true if available.
   *    unavailablePaths : the list of unavailable paths.
   *    tables : list of GenericBean::isAvailableInDB output.
   */
   public static function checkModuleAvailability(Request $request) {
    $logger = Logger::getInstance();

    $result = static::$MODULE_AVAILABILITY_RESULT;
    
    // 1. We check the root path
    $rootPath = static::getRootPath($request);
    if(!is_dir($rootPath)) {
      $logger->addLog("PostUtils::checkModuleAvailability - the blog/posts root path does not exists (" . $rootPath . ").");
      $result["unavailablePaths"][] = $rootPath;
      $result["isModuleAvailable"] = false;
    }

    // 2. We check that content module is available
    $contentModuleAvailability = ContentUtils::checkModuleAvailability($request);
    if($contentModuleAvailability instanceof Error) {
      return $contentModuleAvailability;
    }
    $result = static::mergeModuleAvailabilityResults($result, $contentModuleAvailability);
    
    // 3. We check the generic part
    $genericAvailability = parent::checkModuleAvailability($request);
    if($genericAvailability instanceof Error) {
      return $genericAvailability;
    }
    $result = static::mergeModuleAvailabilityResults($result, $genericAvailability);

    return $result;
  }

  /*
   *  static function: extractPost
   *  This function is used to extract SQL and content of a Post and store it in the target directory.
   *
   *  parameters:
   *    - request - the request object.
   *    - post - the post object.
   *    - target - the target directory
   *  return:
   *    - ok or Error object.
   */
  public static function extractPost(Request $request, Post $post, $targetDirectory) {
    // We get the create query
    $createQueryPost = $post->getCreateQuery();
    // We get the create query for all associated contents
    // TODO - check that we have all correct values and linked between content and post.

    // TODO - In the table admin template, add a generic way to add buttons and actions. Supprimer should be one of these actions.

    // We write the SQL file within the target directory
    $result = FileUtils::writeFile($targetDirectory . DIRECTORY_SEPARATOR . "post.sql", $createQuery);
    if($result instanceof Error) {
      return $result;
    } 

    return "ok";
  }
}

?>
