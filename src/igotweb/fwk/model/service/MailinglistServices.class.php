<?php
/**
 *	Class: MailinglistServices
 *	This class handle the services linked to Mailinglist. 
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\service;

use igotweb_wp_mp_links\igotweb\fwk\utilities\HttpRequestUtils;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\utilities\MailinglistUtils;

class MailinglistServices {
  
  private function __construct() {}

  /*
   *	action: subscribe
   *	This action adds an email in mailing list.
   *
   *	parameters:
   *		- $email : the user email.
   */
  public static function subscribe(Request $request) {

    $email = HttpRequestUtils::getParam("email");

    $mailingListUtils  = new MailinglistUtils();
    $added = $mailingListUtils->addEmail($email);
    if($added instanceof Error) {
      $request->addError($added);
    }

    if(!$request->hasError()) {
      $request->dmOutAddElement("added",true);
      
      // We make sure that the specific template resources are available
      $languageManager = $request->getLanguageManager();
      // We set the context of the current template for language manager
      $languageManager->addTemplateContext("mailinglist.subscribe");
      
      $request->requireJsResource("mailingListSubscribeSuccess");
      
      // We remove the template context
      $languageManager->removeTemplateContext("mailinglist.subscribe");
    }
    else {
      $request->dmOutAddElement("added",false);
    }
  }

  /*
   *	action: unsubscribe
   *	This action removes an email from mailing list.
   *
   *	parameters:
   *		- $email : the user email.
   */
  public static function unsubscribe(Request $request) {

    $email = HttpRequestUtils::getParam("email");

    $mailingListUtils  = new MailinglistUtils();
    $added = $mailingListUtils->removeEmail($email);
    if($added instanceof Error) {
      $request->addError($added);
    }

    if(!$request->hasError()) {
      $request->dmOutAddElement("removed",true);
      
      // We make sure that the specific template resources are available
      $languageManager = $request->getLanguageManager();
      // We set the context of the current template for language manager
      $languageManager->addTemplateContext("mailinglist.subscribe");
      
      $request->requireJsResource("mailingListUnsubscribeSuccess");
      
      // We remove the template context
      $languageManager->removeTemplateContext("mailinglist.subscribe");
    }
    else {
      $request->dmOutAddElement("removed",false);
    }
  }
}
?>