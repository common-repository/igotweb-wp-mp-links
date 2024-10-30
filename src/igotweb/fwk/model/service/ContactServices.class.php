<?php
/**
 *	Class: ContactServices
 *	This class handle the services linked to Contact. 
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\service;

use igotweb_wp_mp_links\igotweb\fwk\utilities\HttpRequestUtils;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use ext\Recaptcha;
use igotweb_wp_mp_links\igotweb\fwk\EMail;
use \EMails;

class ContactServices {
  
  private function __construct() {}

  /*
   *	action: message
   *	This action is called when the user wants to send a message to contact the site.
   *
   *	parameters:
   *		- $email : the user email.
   *		- $message : the user message.
   *		- $captchaParameters : the parameters needed to validate the captcha (if user is not logged).
   */
  public static function message(Request $request) {

    $email = HttpRequestUtils::getParam("email");
    $message = HttpRequestUtils::getParam("message");
    
    $useUser = $request->getConfigAsBoolean("useUsers");
    $user = NULL;
    if($useUser) {
      $user = $request->getUser();
    }

    if(!($useUser && $user->getIsLogged())) {
      
      $recaptcha = new \ReCaptcha\ReCaptcha($request->getConfig("fwk-recaptchaPrivateKey"));
      $resp = $recaptcha->verify(HttpRequestUtils::getParam("captchaResponse"));
      if (!$resp->isSuccess()) {
        $errors = $resp->getErrorCodes();
        foreach($errors as $errorCode) {
          switch ($errorCode) {
            case "missing-input-secret":
              $request->addErrorNumber(9901,1);
              break;
            case "invalid-input-secret":
              $request->addErrorNumber(9901,2);
              break;
            case "missing-input-response":
              $request->addErrorNumber(9903,1);
              break;
            case "invalid-input-response":
              $request->addErrorNumber(9904,1);
              break;
            case "bad-request":
              $request->addErrorNumber(9904,2);
              break;
          }
        }
      }
    }

    // We validate the email
    if(trim($email) == "") {
      $request->addErrorNumber(6051);
    }
    else {
      $valid = EMail::validateEmail($email);
      if($valid instanceof Error) {
        $request->addError($valid);
      }
    }
    if(trim($message) == "") {
      $request->addErrorNumber(6052);
    }

    if(!$request->hasError()) {
      // We send the message by email.
      $emailsInstance = \EMails::getInstance();
      $sent = $emailsInstance->sendContactMessageEmail($email,$message, $user);
      if($sent instanceof Error) {
        $request->addError($sent);
        $request->dmOutAddElement("ok",false);
      }
      else {
        $request->dmOutAddElement("ok",true);
      }
    }
    else {
      $request->dmOutAddElement("ok",false);
    }
  }
}
?>