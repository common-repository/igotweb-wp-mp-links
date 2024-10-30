<?php

/**
 *	Class: AnnotationsUtils
 *	Version: 0.1
 *	This class handle annotations utilities.
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use ReflectionClass;
use ReflectionProperty;
use Doctrine\Common\Annotations\AnnotationReader;

class AnnotationsUtils {

  private static $instance;
  private $annotationReader;
    
	private function __construct() {
    $this->annotationReader = new AnnotationReader();
  }

  /**
    * This method get the instance of AnnotationUtils.
    * @param void
    * @return AnnotationUtils instance
    */
  private static function getInstance() {
    if(is_null(static::$instance)) {
      static::$instance = new AnnotationsUtils();  
    }
    return static::$instance;
  }

  /**
    * getFromClass
    * This method get an annotation bean from a class.
    * @param className the target classeName
    * @param annotationName the annotation bean name
    * @return instance of annotation bean (default instance used if no annotation) or Error object
    */
    public static function getFromClass($className, $annotationName) {
      $annotationClassName = "igotweb\\fwk\\annotations\\".ucfirst($annotationName);
      $beanPropertyAnnotation = null;
      // We check if the target class exists
      if(!class_exists($className)) {
        return new Error(9651,1, $className);        
      }
      // We check if the annotation class exist and we load it.
      if(!class_exists($annotationClassName)) {
        return new Error(9651,2, $annotationClassName);
      }
  
      $utils = static::getInstance();
      $reflectionClass = new ReflectionClass($className);
      $beanClassAnnotation = $utils->annotationReader->getClassAnnotation($reflectionClass, $annotationClassName);

      // If there is no annotation, we return the default values.
      if($beanClassAnnotation == null) {
        $beanClassAnnotation = new $annotationClassName();
      }

      // We merge bean with all parents
      $beanClassAnnotationProperties = (new ReflectionClass($annotationClassName))->getProperties();
      while ($parent = $reflectionClass->getParentClass()) {
        $parentBeanClassAnnotation = $utils->annotationReader->getClassAnnotation($parent, $annotationClassName);
        // If parent has annotation bean
        if($parentBeanClassAnnotation != null) {
          // We check for all non static properties
          foreach($beanClassAnnotationProperties as $property) {
            if(!$property->isStatic()) {
              $propertyName = $property->getName();
              // We set the property if not already defined and available at parent level
              if(!isset($beanClassAnnotation->$propertyName) && $parentBeanClassAnnotation->$propertyName != null) {
                $beanClassAnnotation->$propertyName = $parentBeanClassAnnotation->$propertyName;
              }
            }
          }
        }
        $reflectionClass = $parent;
      }
        
      return $beanClassAnnotation;
    }
  
  /**
    * getFromProperty
    * This method get an annotation bean from the property of a class.
    * @param className the target classeName
    * @param propertyName the class property
    * @param annotationName the annotation bean name
    * @return instance of annotation bean (default instance used if no annotation) or Error object
    */
  public static function getFromProperty($className, $propertyName, $annotationName) {
    $annotationClassName = "igotweb\\fwk\\annotations\\".ucfirst($annotationName);
    $beanPropertyAnnotation = null;
    // We check if the target class exists
    if(!class_exists($className)) {
      return new Error(9651,3, $className);
    }
    // We check if the annotation class exist and we load it.
    if(!class_exists($annotationClassName)) {
      return new Error(9651,4, $annotationClassName);
    }

    $utils = static::getInstance();
    $reflectionProperty = new ReflectionProperty($className, $propertyName);
    $beanPropertyAnnotation = $utils->annotationReader->getPropertyAnnotation($reflectionProperty, $annotationClassName);

    // If there is no annotation, we return the default values.
    if($beanPropertyAnnotation == null) {
      $beanPropertyAnnotation = new $annotationClassName();
    }

    return $beanPropertyAnnotation;
  }

  /**
    * getListProperties
    * This method get the list of properties on a class which match annotation having specific property and value.
    * @param className the target classeName
    * @param annotationName the annotation bean name
    * @param annotationProperty the property of annotation bean
    * @param annotationValue the expected value of annotation bean property
    * @return list of class properties name or Error object
    */
  public static function getListProperties($className, $annotationName, $annotationProperty, $annotationValue) {
    $annotationClassName = "igotweb\\fwk\\annotations\\".ucfirst($annotationName);
    // We check if the target class exists
    if(!class_exists($className)) {
      return new Error(9651,5, $className);
    }
    // We check if the annotation class exist and we load it.
    if(!class_exists($annotationClassName)) {
      return new Error(9651,6, $annotationClassName);
    }

    $reflectionClass = new ReflectionClass($className);
		$classProperties = $reflectionClass->getProperties();
		
    // We get the properties
		$properties = array();
		foreach ($classProperties as $property) {
			$propertyName = $property->getName();
			if(!$property->isStatic()) {
        // We check if we find the annotation
        $annotationBean = static::getFromProperty($className, $propertyName, $annotationName);
        if($annotationBean instanceof $annotationClassName &&
            property_exists($annotationBean,$annotationProperty) &&
            $annotationBean->$annotationProperty === $annotationValue) {
          $properties[] = $propertyName;
        }
			}
		}

    return $properties;
  }
}
?>
