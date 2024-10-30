<?php

namespace igotweb_wp_mp_links\igotweb\fwk\model\bean\blog;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\IwDateTime;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\blog\Tag;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\generic\GenericBean;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\database\Table;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\database\Column;
use igotweb_wp_mp_links\igotweb\fwk\model\manager\LanguageManager;
use igotweb_wp_mp_links\igotweb\fwk\utilities\UrlUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\FileUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\blog\PostUtils;
use igotweb_wp_mp_links\igotweb\fwk\annotations\BeanProperty;
use igotweb_wp_mp_links\igotweb\fwk\annotations\BeanClass;
use igotweb_wp_mp_links\igotweb\fwk\Logger;

/**
 *	Class: Post
 *	This class handle the generic Post object.
 *  @BeanClass(sqlTable="blogPosts")
 */
class Post extends GenericBean {
	
  /** @BeanProperty(isLocalized=true) */
  protected $key;
  /** @BeanProperty(isLocalized=true) */
  protected $title;
  protected $publishedDateTime; // The date time when the gallery has been created.
  protected $author;
  protected $idTags; // The list of tags SQLids
  /** @BeanProperty(isExcludedFromDB=true) */
  protected $tags;
  /** @BeanProperty(isLocalized=true) */
  protected $shortDescription;
  /** @BeanProperty(isLocalized=true) */
  protected $rssDescription;
  /** @BeanProperty(isJson=true) */
  protected $contentKeys; // The list of content keys.
  /** @BeanProperty(isExcludedFromDB=true) */
  protected $contents;
  /** @BeanProperty(isExcludedFromDB=true) */
  protected $photos;
  /** @BeanProperty(isExcludedFromDB=true) */
  protected $staticPath;
	
	/*
	 *	Constructor
	 *	It creates a Post.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- Post object.
	 */	
	public function __construct() {
		// We call the parent
		parent::__construct();
		
    $this->key = NULL;
    $this->title = NULL;
		$this->publishedDateTime = IwDateTime::getNow();
		
		$this->author = NULL;
    $this->shortDescription = NULL;
    $this->rssDescription = NULL;
    $this->staticPath = NULL;
		
    $this->idTags = array();
    $this->tags = NULL;

    $this->contentKeys = NULL;
    $this->contents = NULL;

    $this->photos = NULL;
  }

  /*
   *  getRssFormattedDescription
   *  This method returns the formatted description to be used in item/description and item/content:encoded tags in RSS Feed.
   *  <img width=\"1200\" height=\"675\" src=\"URL\" alt=\"\" srcset=\"URL1200.jpg 1200w, URL768.jpg 768w, URL600.jpg 600w\" sizes=\"(max-width: 1200px) 100vw, 1200px\" />
   *  <p>
   *    RssDescription <br/>
   *    <a href="">Read more</a>
   *  </p>
   */
  public function getRssFormattedDescription(Request $request) {
    $formattedDescription = "";    
    // We add the associated picture if any
    // $formattedDescription .= "<img width=\"1200\" height=\"675\" src=\"URL\" alt=\"\" srcset=\"URL1200.jpg 1200w, URL768.jpg 768w, URL600.jpg 600w\" sizes=\"(max-width: 1200px) 100vw, 1200px\" />";
    // We add the rss description
    $formattedDescription .= "<p>";
    $formattedDescription .= $this->getRssDescription()."<br/>";
    // We add the link to the post
    $formattedDescription .= "<a href=\"".UrlUtils::getSiteURL($this->getLink()["href"], $request)."\">".$request->getString("rss.blog.readMore")."</a>";
    $formattedDescription .= "<p>";
    return $formattedDescription;
  }
  
  public function getTags() {
		if(!isset($this->tags) && isset($this->idTags)) {
      $this->tags = array();
      $tags = (new Tag())->getBeans("idTag IN (".implode(",",$this->idTags).")");
      if($tags instanceof Error) {
        $logger = Logger::getInstance();
        $logger->addErrorLog($tags);
      }
      else {
        $this->tags = $tags;
      }
		}
		return $this->tags;
  }
  
  /*
   *   function: __call
   *   Generic getter and setter for properties.
   */
  public function __call($method, $params) {
    return $this->handleGetterSetter($method, $params);
  }

  /*
	 * getTable
	 * This method returns the table object associated to the bean.
	 */
	public function getTable() {
    $supportedLanguages = LanguageManager::$SUPPORTED_LANGUAGES;

    $table = new Table(static::getTableName());
    $table->addColumn(Column::getIdColumn(static::getSQLidColumnName()));
    $table->addColumn(new Column("author","varchar(100)"));
    $table->addColumn(new Column("publishedDateTime","datetime"));
    $table->addColumn(new Column("idTags","varchar(100)"));
    $table->addColumn(new Column("contentKeys","varchar(200)"));
    // We generate the localized properties
    foreach($supportedLanguages as $languageCode) {
      $table->addColumn(new Column("key-".$languageCode,"varchar(100)",true,"NULL"));
      $table->addColumn(new Column("title-".$languageCode,"varchar(100)",true,"NULL"));
      $table->addColumn(Column::getTextColumn("shortDescription-".$languageCode));
      $table->addColumn(Column::getTextColumn("rssDescription-".$languageCode));
    }

	  return $table;
	}
	
	/*
	 *	static function: publishedDateComparator
	 *	This function compare two posts by published date.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- int for ordering.
	 */
  public static function publishedDateComparator($a, $b) {
    $aDate = $a->getPublishedDateTime()->getTimestamp();
    $bDate = $b->getPublishedDateTime()->getTimestamp();
  
    if($aDate == $bDate) {
      // If same date, they are ordered alphabetically.
      // The a Letter will be displayed as latest post.
      // The z Letter will be displayed as oldest post.
      return strcasecmp($a->getTitle(),$b->getTitle());
    }
    
    // The latest date first, oldest date last.
    return ($aDate < $bDate) ? 1 : -1;
  }

  /*
   *	function: populateStaticPath
  *	This function populates the static path based on published date and french key.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- none.
  */
  public function populateStaticPath() {
    global $request;

    if(!isset($this->staticPath)) {
      // We populate the staticPath property as custom.
      $contentRelativePath = $this->getContentRelativePath();
      $staticPath = PostUtils::getAbsoluteStaticPath($request).DIRECTORY_SEPARATOR.$contentRelativePath;
      $this->setStaticPath($staticPath);
    }
  }

  /*
   *	function: getContentRelativePath
  *	This function gets the content relative path based on published date and french key.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- none.
  */
  private function getContentRelativePath() {
    // It is based on the published date and key in french.
    $staticPath = $this->getPublishedDateTime()->format("Y" . DIRECTORY_SEPARATOR . "m") . DIRECTORY_SEPARATOR;

    // We get the key in french.
    $frenchBean = $this->getLocalizedBean("fr");
    $frenchKey = $frenchBean->getKey();
    $staticPath .= $frenchKey . DIRECTORY_SEPARATOR;
    return $staticPath;
  }

  /*
   *	function: populatePhotos
  *	This function populates the photos based on files that are uploaded within the post content folder / photos.
  * We check within the full folder. The thumbs can be uploaded or will be generated.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- none.
  */
  public function populatePhotos() {
    global $request;

    if(!isset($this->photos)) {
      $contentRelativePath = $this->getContentRelativePath();
      $fullContentPath = PostUtils::getRootPath($request).DIRECTORY_SEPARATOR.$contentRelativePath.DIRECTORY_SEPARATOR."photos".DIRECTORY_SEPARATOR."full".DIRECTORY_SEPARATOR;
  
      $files = FileUtils::getFilesList($fullContentPath);
      $photos = array();
      if(is_array($files) && count($files) > 0) {
        foreach($files as $file) {
          $photo = array(
            "thumbPath" => $this->getStaticPath()."photos".DIRECTORY_SEPARATOR."thumb".DIRECTORY_SEPARATOR.$file,
            "fullPath" => $this->getStaticPath()."photos".DIRECTORY_SEPARATOR."full".DIRECTORY_SEPARATOR.$file
          );
          $photos[] = $photo;
        }
      }

      $this->setPhotos($photos);
    }
  }

  /*
   *	function: populateContents
  *	This function populates the contents based on contentKeys and staticPath.
  *
  *	parameters:
  *		- $contentKeys - the content keys from DB.
  *   - $staticPath - the static path.
  *	return:
  *		- none.
  */
  public function populateContents($contentKeys, $staticPath) {
    if(!isset($this->contents)) {
      // We populate the contents based on contentKeys
      if(is_array($contentKeys)) {
        $contents = array();
        foreach($contentKeys as $key) {
          $contentClass = "igotweb\\fwk\\model\\bean\\blog\\post\\".ucfirst($key->type);
          $content = new $contentClass();
          $result = $content->getFromDB($key->id);
          if($result === "ok") {
            $contents[] = array(
              "type" => $content->getType(),
              "value" => $content
            );
          }
        }
        $this->setContents($contents);
      } 
    }
  }

  /*
   *	function: updateFromSQLResult
  *	This function update the object based on a SQL result associative array.
  *
  *	parameters:
  *		- $datas - a map generated with sql query.
  *	return:
  *		- none.
  */
  public function updateFromSQLResult($datas) {
    // we call the generic updateFromSQLResult
    $result = parent::updateFromSQLResult($datas);
    if($result instanceof Error) {
      return $result;
    }

    // We populate the staticPath property.
    $this->populateStaticPath();

    // We populate the photos
    $this->populatePhotos();

    // We populate the contents
    $contentKeys = $this->getContentKeys();
    $staticPath = $this->getStaticPath();
    $this->populateContents($contentKeys, $staticPath);

    
  }
}

?>
