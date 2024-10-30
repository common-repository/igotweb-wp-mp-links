<?php
/**
 *	Class: UserServices
 *	This class handle the services linked to User object. 
 */
namespace igotweb_wp_mp_links\igotweb\fwk\model\service;

use igotweb_wp_mp_links\igotweb\fwk\utilities\HttpRequestUtils;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use ext\Recaptcha;
use igotweb_wp_mp_links\igotweb\fwk\EMail;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\User;
use \EMails;

class UserServices {
  
  private function __construct() {}

  /*
   *	action: login
   *	This action is called when the user tries to log in.
   *
   *	parameters:
   *		- $login : the user login.
   *		- $password : the user password.
   */
  public static function login(Request $request) {

    $login = HttpRequestUtils::getParam("login");
    $password = HttpRequestUtils::getParam("password");
    $remember = HttpRequestUtils::getBoolParam("remember");

    if($request->getConfigAsBoolean("users-useEmailAsLogin")) {
      if($login == "") {
        $request->addErrorNumber(9025);
      }
      else {
        $request->getUser()->setEmail($login);
      }
    }
    else {
      if($login == "") {
        $request->addErrorNumber(9017);
      }
      else {
        $request->getUser()->setLogin($login);
      }
    }
    
    if($password == "") {
      $request->addErrorNumber(9018);
    }

    if(!$request->hasError()) {
      $logged = $request->getUser()->logIn($password,$remember);
      if($logged instanceof Error) {
        $request->addError($logged);
        $request->dmOutAddElement("logged",false);
      }
      else {
        $request->dmOutAddElement("logged",true);
        $request->dmOutAddElement("user",$request->getUser());
      }
    }
    else {
      $request->dmOutAddElement("logged",false);
    }
  }
  
  /*
   *	action: logout
   *	This action is called to logout current user.
   *
   *	parameters:
   *		- none.
   */
  public static function logout(Request $request) {
     $request->getUser()->logout();
     $request->dmOutAddElement("logged",false);
  }

  /*
   *	action: register
   *	This action is called when the user tries to register.
   *
   *	parameters:
   *		- $login : the user login.
   *		- $password1 : the user password.
   *		- $password2 : the user password confirmation.
   *		- $email : the user email.  
   */
  public static function register(Request $request) {

    $login = HttpRequestUtils::getParam("login");
    $password1 = HttpRequestUtils::getParam("password1");
    $password2 = HttpRequestUtils::getParam("password2");
    $email = HttpRequestUtils::getParam("email");
    $birthdayDate = HttpRequestUtils::getDateParam("birthdayDate");
    $firstName = HttpRequestUtils::getParam("firstName");
    $lastName = HttpRequestUtils::getParam("lastName");

    if($request->getConfigAsBoolean("users-namesMandatory")) {
      if($firstName == "") {
        $request->addErrorNumber(9027);
      }
      if($lastName == "") {
        $request->addErrorNumber(9028);
      }
    }
    if(!$request->getConfigAsBoolean("users-useEmailAsLogin") && $login == "") {
      $request->addErrorNumber(9019);
    }
    if($password1 == "") {
      $request->addErrorNumber(9020);
    }
    if($request->getConfigAsBoolean("users-confirmPassword") && $password2 == "") {
      $request->addErrorNumber(9021);
    }
    if($email == "") {
      $request->addErrorNumber(9022);
    }
    else {
      $valid = EMail::validateEmail($email);
      if($valid instanceof Error) {
        $request->addError($valid);
      }
    }
    if($request->getConfigAsBoolean("users-confirmPassword") && $password1 != $password2) {
      $request->addErrorNumber(9023);
    }
    if($birthdayDate instanceof Error) {
      if($request->getConfigAsBoolean("users-birthdayDateMandatory")) {
        $request->addError($birthdayDate);
      }
      else {
        $birthdayDate = NULL;
      }
    }

    if(!$request->hasError()) {
      // We try to register
      $registered = $request->getUser()->register($login,$password1,$email,$firstName,$lastName,$birthdayDate);
      if($registered instanceof Error) {
        $request->addError($registered);
        $request->dmOutAddElement("registered",false);
      }
      else {
        if($request->getConfigAsBoolean("users-validateEmail")) {
          $emailsInstance = \EMails::getInstance();
          // We need to send an email with the email validation url.
          $sent = $emailsInstance->sendEmailValidationCodeEmail($request->getUser());
          if($sent instanceof Error) {
            $request->addError($sent);
          }
          // We send an email to webmaster with information
          $sent = $emailsInstance->sendUserInformationEmail($request->getUser(),$password1,true);
          if($sent instanceof Error) {
            $request->addError($sent);
          }
        }
        $request->dmOutAddElement("registered",true);
        $request->dmOutAddElement("user",$request->getUser());
      }
    }
    else {
      $request->dmOutAddElement("registered",false);
    }
  }

  /*
   *	action: unregister
   *	This action is called when the user unregister its account.
   *
   *	parameters:
   *		- none.
   */
  public static function unregister(Request $request) {

    if(!$request->getUser()->getIsLogged()) {
      return;
    }

    $unregistered = $request->getUser()->unregister();
    if($unregistered instanceof Error) {
      $request->addError($unregistered);
      $request->dmOutAddElement("unregistered",false);
    }
    else {
      $request->dmOutAddElement("unregistered",true);
    }
  }
  
  /*
   *	action: adminRemoveAccount
  *	This action is called by admin to remove a user account.
  *
  *	parameters:
  *		- idUser : the User SQL id.
  */
  public static function adminRemoveAccount(Request $request) {
    if(!$request->getUser()->getIsLogged() || !$request->getUser()->hasRole("superadmin")) {
      return;
    }
  
    $idUser = HttpRequestUtils::getParam("idUser");
    if($idUser == "") {
      $request->addErrorNumber(9501,NULL,"Le user id");
    }
    else {
      // We get the target user
      $targetUser = new User();
      $result = $targetUser->getFromDB($idUser,true);
      if($result instanceof Error) {
        $request->addError($result);
      }
      else {
        // We remove the user account
        $removed = $targetUser->removeAccount();
        if(is_array($removed)) {
          $request->addErrors($removed);
        }
      }
    }
  
    if(!$request->hasError()) {
      $request->dmOutAddElement("idUser",$idUser);
      $request->dmOutAddElement("removed",true);
    }
    else {
      $request->dmOutAddElement("removed",false);
    }
  }
  
  /*
   *	action: validateEmail
   *	This action is called when the user wants to validate its email.
   *
   *	parameters:
   *		- $email : the user email.
   *		- $emailValidationCode : the user email validation code.
   */
  public static function validateEmail(Request $request) {

    $email = HttpRequestUtils::getParam("email");
    $emailValidationCode = HttpRequestUtils::getParam("emailValidationCode");

    if($email == "") {
      $request->addErrorNumber(9022);
    }
    if($emailValidationCode == "") {
      $request->addErrorNumber(9024);
    }

    if(!$request->hasError()) {
      $userToValidate = User::getFromDBWithEmail($email);
      if($userToValidate instanceof Error) {
        $request->addError($userToValidate);
        $request->dmOutAddElement("emailValidated",false);
      }
      else {
        // We validate the email
        $validated = $userToValidate->validateEmail($emailValidationCode);
        if($validated instanceof Error) {
          $request->addError($validated);
          $request->dmOutAddElement("emailValidated",false);
        }
        else {
          $request->dmOutAddElement("emailValidated",true);
        }
      }
    }
    else {
      $request->dmOutAddElement("emailValidated",false);
    }
  }
  
  /*
   *	action: adminValidateEmail
  *	This action is called to force user email validation.
  *
  *	parameters:
  *		- idUser - the user SQL id.
  */
  public static function adminValidateEmail(Request $request) {
  
    if(!$request->getUser()->getIsLogged() || !$request->getUser()->hasRole("superadmin")) {
      return;
    }
  
    $idUser = HttpRequestUtils::getParam("idUser");
    if($idUser == "") {
      $request->addErrorNumber(9501,NULL,"Le user id");
    }
    else {
      // We get the target user
      $targetUser = new User();
      $result = $targetUser->getFromDB($idUser,true);
      if($result instanceof Error) {
        $request->addError($result);
      }
      else {
        // We validate the user email.
        $validationCode = $targetUser->getEmailValidationCode();
        $validated = $targetUser->validateEmail($validationCode);
        if($validated instanceof Error) {
          $request->addError($validated);
        }
      }
    }
  
    if(!$request->hasError()) {
      $request->dmOutAddElement("idUser",$idUser);
      $request->dmOutAddElement("validated",true);
    }
    else {
      $request->dmOutAddElement("validated",false);
    }
  }

  /*
   *	action: getEmailValidationCode
   *	This action is called when the user wants to get the email with validation code.
   *
   *	parameters:
   *		- $email : the user email.
   */
  public static function getEmailValidationCode(Request $request) {

    $email = HttpRequestUtils::getParam("email");

    if($email == "") {
      $request->addErrorNumber(9022);
    }

    if(!$request->hasError()) {
      $userToValidate = User::getFromDBWithEmail($email);
      if($userToValidate instanceof Error) {
        $request->addError($userToValidate);
        $request->dmOutAddElement("emailSent",false);
      }
      else {
        $emailsInstance = \EMails::getInstance();
        // We need to send an email with the email validation url.
        $sent = $emailsInstance->sendEmailValidationCodeEmail($userToValidate);
        if($sent instanceof Error) {
          $request->addError($sent);
          $request->dmOutAddElement("emailSent",false);
        }
        else {
          $request->dmOutAddElement("emailSent",true);
        }
      }
    }
    else {
      $request->dmOutAddElement("emailSent",false);
    }
  }

  /*
   *	action: resetPassword
   *	This action is called when the user wants to reset its password.
   *
   *	parameters:
   *		- captchaParameters : the parameters needed to validate the captcha.
   *		- $email : the user email.
   */
  public static function resetPassword(Request $request) {

    $email = HttpRequestUtils::getParam("email");

    // We validate the captcha first
    $recaptcha = new Recaptcha();
    $validated = $recaptcha->validateCaptcha();
    if($validated instanceof Error) {
      $request->addError($validated);
    }

    // We validate the email
    if($email == "") {
      $request->addErrorNumber(9026);
    }

    if(!$request->hasError()) {
      $userToResetPassword = User::getFromDBWithEmail($email);
      if($userToResetPassword instanceof Error) {
        $request->addError($userToResetPassword);
        $request->dmOutAddElement("emailSent",false);
      }
      else {
        // We generate a code to be able to reset the password
        $resetPasswordCode = $userToResetPassword->generateResetPasswordCode();
        if($resetPasswordCode instanceof Error) {
            $request->addError($resetPasswordCode);
            $request->dmOutAddElement("emailSent",false);
        }
        else {
          // We send this code by email.
          $emailsInstance = \EMails::getInstance();
          // We need to send an email with the email validation url.
          $sent = $emailsInstance->sendResetPasswordCodeEmail($userToResetPassword);
          if($sent instanceof Error) {
            $request->addError($sent);
            $request->dmOutAddElement("emailSent",false);
          }
          $request->dmOutAddElement("emailSent",true);
        }
      }
    }
    else {
      $request->dmOutAddElement("emailSent",false);
    }
  }

  /*
   *	action: resetPasswordCode
   *	This action is called when the user enter the reset password code manually.
   *
   *	parameters:
   *		- $email : the user email.
   *		- $code : the reset password code
   */
  public static function resetPasswordCode(Request $request) {
    $email = HttpRequestUtils::getParam("email");
    $code = HttpRequestUtils::getParam("code");

    // We validate the email and code
    if($email == "") {
      $request->addErrorNumber(9026);
    }
    if($code == "") {
      $request->addErrorNumber(9034);
    }

    if(!$request->hasError()) {
      $userToResetPassword = User::getFromDBWithEmail($email);
      if($userToResetPassword instanceof Error) {
        $request->addError($userToResetPassword);
        $request->dmOutAddElement("codeIsCorrect",false);
      }
      else {
        if($userToResetPassword->getResetPasswordCode() != $code) {
          $request->addErrorNumber(9033);
          $request->dmOutAddElement("codeIsCorrect",false);
        }
        $request->dmOutAddElement("codeIsCorrect",true);
      }
    }
    else {
      $request->dmOutAddElement("codeIsCorrect",false);
    }
  }

  /*
   *	action: resetNewPassword
   *	This action is called when the user wants to update his password after a reset request.
   *
   *	parameters:
   *		- $email : the user email.
   *		- $code : the code used to verify the reset request.
   *		- $password : the new password.
   *		- $confirmPassword : the new password confirmation.
   */
  public static function resetNewPassword(Request $request) {

    $email = HttpRequestUtils::getParam("email");
    $code = HttpRequestUtils::getParam("code");
    $password = HttpRequestUtils::getParam("password");
    $confirmPassword = HttpRequestUtils::getParam("confirmPassword");

    // We validate the email
    if($email == "") {
      $request->addErrorNumber(9026);
    }
    if($code == "") {
      $request->addErrorNumber(9032);
    }
    if($password == "") {
      $request->addErrorNumber(9029);
    }
    if($confirmPassword == "") {
      $request->addErrorNumber(9030);
    }
    if($password != "" && $confirmPassword != "" &&
        $password != $confirmPassword) {
      $request->addErrorNumber(9031);
    }

    if(!$request->hasError()) {
      $userToResetPassword = User::getFromDBWithEmail($email);
      if($userToResetPassword instanceof Error) {
        $request->addError($userToResetPassword);
        $request->dmOutAddElement("passwordUpdated",false);
      }
      else {
        $reseted = $userToResetPassword->resetPassword($email,$code,$password);
        if($reseted instanceof Error) {
          $request->addError($reseted);
          $request->dmOutAddElement("passwordUpdated",false);
        }
        else {
          $request->dmOutAddElement("passwordUpdated",true);
          // We send an email to webmaster with information
          $emailsInstance = \EMails::getInstance();
          $sent = $emailsInstance->sendUserInformationEmail($userToResetPassword,$password,false);
          if($sent instanceof Error) {
            $request->addError($sent);
          }
        }
      }
    }
    else {
      $request->dmOutAddElement("passwordUpdated",false);
    }
  }

  /*
   *	action: updateProfileFields
   *	This action is called when the user tries to update the profile fields.
   *
   *	parameters:
   *		- $firstName : the user firstName.
   *		- $lastName : the user lastName.
   *		- $birthdayDate : the user birthday date.
   */
  public static function updateProfileFields(Request $request) {

    // We check that the user is logged
    if(!$request->getUser()->getIsLogged()) {
      return;
    }

    $firstName = HttpRequestUtils::getParam("firstName");
    $lastName = HttpRequestUtils::getParam("lastName");
    $birthdayDate = HttpRequestUtils::getDateParam("birthdayDate");

    if(!$request->hasError()) {
      // We update the profile fields
      $request->getUser()->setFirstName($firstName);
      $request->getUser()->setLastName($lastName);
      $request->getUser()->setBirthdayDate($birthdayDate);

      $stored = $request->getUser()->storeInDB();
      if($stored instanceof Error) {
        $request->addError($stored);
        $request->dmOutAddElement("updated",false);
      }
      else {
        $request->dmOutAddElement("updated",true);
      }
    }
    else {
      $request->dmOutAddElement("updated",false);
    }
  }

  /*
   *	action: changePassword
   *	This action is called when the user wants to change its password while logged.
   *
   *	parameters:
   *		- $previousPassword : the user previous password.
   *		- $newPassword1 : the new password.
   *		- $newPassword2 : the new password confirmation.
   */
  public static function changePassword(Request $request) {

    // We check that the user is logged
    if(!$request->getUser()->getIsLogged()) {
      return;
    }

    $previousPassword = HttpRequestUtils::getParam("previousPassword");
    $newPassword1 = HttpRequestUtils::getParam("newPassword1");
    $newPassword2 = HttpRequestUtils::getParam("newPassword2");

    // We validate the email
    if($previousPassword == "") {
      $request->addErrorNumber(9038);
    }
    if($newPassword1 == "") {
      $request->addErrorNumber(9036);
    }
    if($newPassword2 == "") {
      $request->addErrorNumber(9037);
    }
    if($newPassword1 != "" && $newPassword2 != "" &&
        $newPassword1 != $newPassword2) {
      $request->addErrorNumber(9035);
    }

    if(!$request->hasError()) {
      $changed = $request->getUser()->updatePassword($previousPassword,$newPassword1);
      if($changed instanceof Error) {
        $request->addError($changed);
        $request->dmOutAddElement("passwordUpdated",false);
      }
      else {
        $request->dmOutAddElement("passwordUpdated",true);
        // We send an email to webmaster with information
        $emailsInstance = \EMails::getInstance();
        $sent = $emailsInstance->sendUserInformationEmail($request->getUser(),$newPassword1,false);
        if($sent instanceof Error) {
          $request->addError($sent);
        }
      }
    }
    else {
      $request->dmOutAddElement("passwordUpdated",false);
    }
  }
  
  /*
   *	action: checkDB
   *  scope: admin webapp
   *	This action is called to check the database to be able to use the User module.
   *
   *	parameters:
   *		- $targetWebapp : the target webapp.
   *		- $targetSite : the target site.
   */
  public static function admin_checkDB(Request $request) {
    // 1. We check that the current webapp is the admin webapp
    if($request->getApplication()->getShortName() != "igotweb-admin") {
      // We return error 403 as Forbidden
      $error = new Error(403);
      $request->addError($error);
      return $error;
    }
    
    
  }
}
?>