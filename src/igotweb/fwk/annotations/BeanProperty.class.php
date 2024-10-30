<?php

namespace igotweb_wp_mp_links\igotweb\fwk\annotations;

/**
* @Annotation
* @Target("PROPERTY")
*/
class BeanProperty extends ObjectProperty
{
  /** 
   * This property is used to identify bean properties that must be stored as JSON and restored from JSON.
   * @var boolean
   */
  public $isJson = false;
  /** 
   * @var boolean
   */
  public $isLocalized = false;

  /** 
   * @var boolean
   */
  public $isExcludedFromDB = false;
}