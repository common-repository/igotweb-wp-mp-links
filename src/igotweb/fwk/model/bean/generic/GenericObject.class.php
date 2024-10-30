<?php
/**
 *	Class: GenericObject
 *	This class handle the generic objects.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\bean\generic;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\utilities\JSONUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\Utils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\AnnotationsUtils;
use ReflectionClass;
use igotweb_wp_mp_links\igotweb\fwk\Logger;

class GenericObject {

  /*
   *	protected Constructor
  *	GenericObject should only be extended.
  */
  protected function __construct() {
  }
  
  /*
   *   static function: getClassName
   *   This function returns the class name without namespace.
   *   get_class($this) includes the namespace for instances.
   *   get_called_class() includes the namespace for static.
   */
  public static function getClassName() {
    $class_name = get_called_class();
    $lastPos = strripos($class_name, "\\");
    if($lastPos !== false) {
      $class_name = substr($class_name, $lastPos + 1);
    }
    return $class_name;
  }

  /*
   *   static function: getBeanRelativeNamespace
   *   This function returns the relative namespace from \bean\ if any.
   */
  public static function getBeanRelativeNamespace() {
    $class_name = get_called_class();
    $relativeNamespace = "";
    $beanPos = strrpos($class_name, "\\bean\\");
    $lastPos = strrpos($class_name, "\\");
    if($beanPos !== false && $lastPos !== false) {
      $relativeNamespace = substr($class_name, $beanPos + 6 , $lastPos - $beanPos - 5);
    }
    return $relativeNamespace;
  }
	
  /*
   *	handleGetterSetter
   *	This method should be used in __call method to handle generic getter and setter.
   */
  public function handleGetterSetter($method, $params) {
    $logger = Logger::getInstance();
  
    //did you call get or set
    if ( preg_match( "|^[gs]et([A-Z][\w]+)|", $method, $matches ) ) {
      // We get the property
      $property = lcfirst($matches[1]);
      $jsonProperty = "json".$property;
      if(!property_exists($this, $property) && property_exists($this, $jsonProperty)) {
        // We check if the property does not exist but we have a json associated property
        // set
        if ( 's' == $method[0] ) {
          $this->$jsonProperty = JSONUtils::buildJSONObject($params[0]);
        }
        // get
        elseif ( 'g' == $method[0] ) {
          return JSONUtils::getObjectFromJSON($this->$jsonProperty);
        }
      }
      else if(property_exists($this, $property)) {
        // If it is a simple property which exists
        //set
        if ( 's' == $method[0] ) {
          $this->$property = $params[0];
        }
        //get
        elseif ( 'g' == $method[0] ) {
          return $this->$property;
        }
      }
    }
    else {
      $error = Error::getGeneric(get_class($this) . ": Call to undefined method : " . $method . ".");
      $logger->addErrorLog($error,true);
    }
  }
  
  /*
	 *	function: toJSON
	 *	This function convert the object in json format.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- JSON representation of the bean.
	 */
	public function toJSON() {
		
		$bean = $this->toArray();
		$JSONBean = JSONUtils::buildJSONObject($bean);
		
		return $JSONBean;
	}
  
  /*
	 *	function: getProperties
	 *	This function return the list of object properties which are not static.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- list of properties name.
	 */
  public function getProperties() {
    // We get the reflection object
		$reflectionClass = new ReflectionClass(get_class($this));
		$classProperties = $reflectionClass->getProperties();
		
    // We get the properties
		$properties = array();
		foreach ($classProperties as $property) {
			$propertyName = $property->getName();
			if(!$property->isStatic()) {
				$properties[] = $propertyName;
			}
		}
    
    return $properties;
  }
  
    /*
	 *	function: toArray
	 *	This function convert the object in associative array format.
	 *	
	 *	parameters:
	 *		- none.
	 *	return:
	 *		- associative array representation of the bean.
	 */
	public function toArray() {
		// We get the properties 
    $properties = $this->getProperties();
		
		$bean = array();
		foreach($properties as $property) {
      $getter = "get".ucfirst($property);
      if(is_callable(array($this,$getter))) {
        $value = $this->$getter();
        $convertedValue = Utils::buildArrayFromObject($value);
        $bean[$property] = $convertedValue;
      }
		}
		
		return $bean;
	}
}

?>
