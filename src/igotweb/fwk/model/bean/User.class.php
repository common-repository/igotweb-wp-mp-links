<?php

namespace igotweb_wp_mp_links\igotweb\fwk\model\bean;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\generic\GenericBean;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\utilities\Utils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\JSONUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\DBUtils;
use igotweb_wp_mp_links\igotweb\fwk\utilities\CryptographUtils;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\IwDateTime;
use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
use igotweb_wp_mp_links\igotweb\fwk\annotations\BeanProperty;
use igotweb_wp_mp_links\igotweb\fwk\annotations\BeanClass;
use igotweb_wp_mp_links\igotweb\fwk\Logger;
use Profile;

/**
 *	Class: User
 *	This class handle the User object.
 *  @BeanClass(sqlTable="users")
 */
class User extends GenericBean {

  private static $ROLE_SEPARATOR = ",";

  protected $login;
  protected $password; // md5 encrypted
  protected $firstName;
  protected $lastName;
  protected $email;
  protected $birthdayDate; // IwDateTime object
  protected $creationDateTime;
  protected $lastConnectionDateTime;
  protected $isEmailValidated;
  protected $resetPasswordCode; // This code is used to reset the password
  protected $isEnabled; // When false, the application will work as if it does not exists
  protected $roles; // This array contains the list of roles associated to the user

  /** @BeanProperty(isExcludedFromDB=true) */
  protected $profile; // Profile object associated to user if exists
  /** @BeanProperty(isExcludedFromDB=true) */
  protected $isLogged;

  /*
   *	Constructor
  *	It creates an User with no SQLid.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- User object.
  */
  public function __construct() {
    parent::__construct();
    
    $this->login = NULL;
    $this->password = NULL;
    $this->email = "";
    $this->firstName = "";
    $this->lastName = "";
    $this->birthdayDate = NULL;
    $this->creationDateTime = NULL;
    $this->lastConnectionDateTime = NULL;
    $this->isEmailValidated = false;
    $this->isEnabled = false;
    $this->resetPasswordCode = "";
    $this->roles = array();

    $this->profile = NULL;
    $this->isLogged = false;
  }
  
  /*
   * getCreateTableQuery
  * This method returns the query to create the table in DB.
  */
  public function getCreateTableQuery() {
    $query = "CREATE TABLE `".static::getTableName()."` (";
    $query .= "`idUser` int(5) NOT NULL AUTO_INCREMENT,";
    $query .= "`login` varchar(20) COLLATE utf8_bin NOT NULL,";
    $query .= "`password` varchar(32) COLLATE utf8_bin NOT NULL,";
    $query .= "`email` varchar(50) COLLATE utf8_bin NOT NULL,";
    $query .= "`firstName` varchar(50) COLLATE utf8_bin NOT NULL,";
    $query .= "`lastName` varchar(50) COLLATE utf8_bin NOT NULL,";
    $query .= "`birthdayDate` date DEFAULT NULL,";
    $query .= "`creationDateTime` datetime NOT NULL,";
    $query .= "`lastConnectionDateTime` datetime DEFAULT NULL,";
    $query .= "`isEmailValidated` tinyint(1) NOT NULL,";
    $query .= "`resetPasswordCode` varchar(8) COLLATE utf8_bin NOT NULL,";
    $query .= "`isEnabled` tinyint(1) NOT NULL,";
    $query .= "`roles` varchar(100) COLLATE utf8_bin NOT NULL,";
    $query .= "PRIMARY KEY (`idUser`),";
    $query .= "UNIQUE KEY `login` (`login`),";
    $query .= "UNIQUE KEY `email` (`email`)";
    $query .= ") ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=0;";
  
    return $query;
  }  
  
  /*
   * function: __call
  * Generic getter for properties.
  */
  public function __call($method, $params) {
    return $this->handleGetterSetter($method, $params);
  }

  /*
   *	function: storeInDB
  *	This function store the User in DB.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- ok if stored else an Error object.
  */
  public function storeInDB() {

    if(!isset($this->login) || !isset($this->password) || !isset($this->creationDateTime)) {
      // The user is anonymous
      return new Error(9006,1);
    }

    return parent::storeInDB();
  }

  /*
   * function: getFromDB
  *	This function try to get the User from the DB.
  *
  *	parameters:
  *		- $SQLid - the id of the User.
  *		- $includeDisabled - if true, then we also return the user if disabled.
  *	return:
  *		- The User object if found else an Error object.
  */
  public function getFromDB($SQLid,$includeDisabled = false) {

    // We set the query params
    $params = NULL;
    if(!$includeDisabled) {
      $params .= "`isEnabled`=".DBUtils::getBoolean(true);
    }

    parent::getFromDB($SQLid, $params);
  }

  /*
   *	static function: getFromDBWithEmail
  *	This function try to get the User from the DB based on email.
  *
  *	parameters:
  *		- $email - the email the User.
  *		- $includeDisabled - if true, then we also return the user if disabled.
  *	return:
  *		- The User object if found else an Error object.
  */
  public static function getFromDBWithEmail($email,$includeDisabled = false) {

    // 1. We check that SQL id exists
    if(!isset($email) || $email == "") {
      return new Error(9009,1);
    }

    // We set the query params
    $params = "`email`='".$email."'";
    if(!$includeDisabled) {
      $params .= " AND `isEnabled`=".DBUtils::getBoolean(true);
    }
    // We get the list of users
    $user = new User();
    $users = $user->getBeans($params);
    if($users instanceof Error) {
      return $users;
    }

    if(count($users) < 1) {
      return new Error(9009,3);
    }

    return $users[0];
  }

  /*
   *	static function: getFromSession
  *	This function try to get the User from the Session.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- The User object if found else a New User.
  */
  public static function getFromSession(Request $request) {
    $user = new User();
    if($request->sessionExistsElement("user")) {
      $user = $request->sessionGetElement("user");
    }
    else {
      if(!$user->logInFromCookie($request)) {
        $user = new User();
        $request->sessionPutElement("user",$user);
      }
    }

    return $user;
  }

  /*
   *	function: storeInSession
  *	This function store the User in Session.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- ok if stored else an Error object.
  */
  public function storeInSession(Request $request) {
    $request->sessionPutElement("user",$this);
    return "ok";
  }

  /*
   *	function: removeFromSession
  *	This function remove the User from Session.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- ok if removed else an Error object.
  */
  public function removeFromSession(Request $request) {
    $request->sessionRemoveElement("user");
    return "ok";
  }

  /*
   *	function: storeInInCookie
  *	This function store necessary information in cookie to be able to log in.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- ok if stored else an Error object.
  */
  public function storeInCookie(Request $request) {
    // We keep the cookie 30 days
    $expire = time()+60*60*24*30;

    // We encrypt the login and password
    $login = CryptographUtils::encode($this->login, $request);
    $password = CryptographUtils::encode($this->password, $request);

    $request->cookiePutElement($request->getConfig("fwk-cookiePrefix")."uid",$login,$expire,"/");
    $request->cookiePutElement($request->getConfig("fwk-cookiePrefix")."upd",$password,$expire,"/");

    return "ok";
  }

  /*
   *	function: removeFromCookie
  *	This function remove the User information stored in cookie.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- ok if removed else an Error object.
  */
  public function removeFromCookie(Request $request) {
    $request->cookieRemoveElement($request->getConfig("fwk-cookiePrefix")."uid");
    $request->cookieRemoveElement($request->getConfig("fwk-cookiePrefix")."upd");
  }

  /*
   *	static function: getUsers
  *	This function get all User from DB.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- An array of User if found else an Error object.
  */
  public static function getUsers() {
    $user = new User();
    return $user->getBeans();
  }

  /*
   *	function: logIn
  *	This function tries to log in the user.
  *
  *	parameters:
  *		- $password - the user password (not encrypted).
  *		- $remember - true if we need to remember the login in cookie.
  *	return:
  *		- true if logged or Error object.
  */
  public function logIn($password,$remember = false) {
    global $request;
    $logger = Logger::getInstance();

    $queryGetUser = "";
    if($request->getConfigAsBoolean("users-useEmailAsLogin")) {
      if($this->email == "" || $password == "") {
        return new Error(9007,3);
      }

      // 1. We get the Users from DB.
      $queryGetUser = "SELECT * FROM `".static::getTableName()."` ";
      $queryGetUser .= "WHERE `email`='".$this->email."' AND `password`='".static::encryptPassword($password)."'";
      $queryGetUser .= " AND `isEnabled`=".DBUtils::getBoolean(true);
    }
    else {
      if($this->login == "" || $password == "") {
        return new Error(9007,4);
      }

      // 1. We get the Users from DB.
      $queryGetUser = "SELECT * FROM `".static::getTableName()."` ";
      $queryGetUser .= "WHERE `login`='".$this->login."' AND `password`='".static::encryptPassword($password)."'";
      $queryGetUser .= " AND `isEnabled`=".DBUtils::getBoolean(true);
    }

    $resultGetUser = DBUtils::query($queryGetUser);

    if(!$resultGetUser) {
      $logger->addLog("queryGetUser: ".$queryGetUser);
      return new Error(9007,1);
    }

    if(DBUtils::getNbRows($resultGetUser) < 1) {
      $logger->addLog("queryGetUser: ".$queryGetUser);
      return new Error(9007,2);
    }

    // 2. We update the user.
    $userDatas = DBUtils::fetchAssoc($resultGetUser);
    $this->updateFromSQLResult($userDatas);
    	
    // 3. We check that the email is validated
    if($request->getConfigAsBoolean("users-validateEmail") && !$this->getIsEmailValidated()) {
      return new Error(9013);
    }

    // We call the profile onUserLogIn method
    $profile = $this->getProfile();
    if(isset($profile) && method_exists($profile,"onUserLogIn")) {
      $loggedIn = $profile->onUserLogIn();
      if($loggedIn instanceof Error) {
        return $loggedIn;
      }
    }

    $this->setIsLogged(true);
    $this->storeInSession($request);
    if($remember) {
      $this->storeInCookie($request);
    }
     
    // We update the last connection date time
    $this->setLastConnectionDateTime(IwDateTime::getNow());

    // We store the updated user
    $stored = $this->storeInDB();
    if($stored instanceof Error) {
      return $stored;
    }

    return true;
  }

  /*
   *	function: logInFromCookie
  *	This function tries to log in the user regarding cookie.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- true if logged or false.
  */
  public function logInFromCookie(Request $request) {
  $logger = Logger::getInstance();
  
    $login = $request->cookieGetElement($request->getConfig("fwk-cookiePrefix")."uid");
    $encryptedPassword = $request->cookieGetElement($request->getConfig("fwk-cookiePrefix")."upd");

    if(!isset($login) || $login == "" ||
        !isset($encryptedPassword) || $encryptedPassword == "") {
	  $logger->addLog("User::logInFromCookie - cannot find user and password cookies.");	
      return false;
    }
	
    // We decode the login and password
    $login = CryptographUtils::decode($login, $request);
    $encryptedPassword = CryptographUtils::decode($encryptedPassword, $request);

    $queryGetUser = "SELECT * FROM `".static::getTableName()."` ";
    $queryGetUser .= "WHERE `login`='".$login."' AND `password`='".$encryptedPassword."'";
    $queryGetUser .= " AND `isEnabled`=".DBUtils::getBoolean(true);
	
    $resultGetUser = DBUtils::query($queryGetUser);
	
    if(!$resultGetUser) {
      $logger->addLog("queryGetUser: ".$queryGetUser);
      return false;
    }

    if(DBUtils::getNbRows($resultGetUser) < 1) {
      return false;
    }

    // 2. We update the user.
    $userDatas = DBUtils::fetchAssoc($resultGetUser);
    $this->updateFromSQLResult($userDatas);

    // 3. We check that the email is validated
    if($request->getConfigAsBoolean("users-validateEmail") && !$this->getIsEmailValidated()) {
      return false;
    }

    // We call the profile onUserLogIn method
    $profile = $this->getProfile();
    if(isset($profile) && method_exists($profile,"onUserLogIn")) {
      $loggedIn = $profile->onUserLogIn();
      if($loggedIn instanceof Error) {
        return false;
      }
    }

    $this->setIsLogged(true);
    $this->storeInSession($request);
    $this->storeInCookie($request);

    return true;
  }

  /*
   *	function: logOut
  *	This function logs out the user.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- true if logged out.
  */
  public function logOut() {
    global $request;
    
    // We check that the user to logout is the current user.
    if($request->getUser()->getSQLid() != $this->getSQLid()) {
      return;
    }

    $this->setIsLogged(false);

    $this->removeFromSession($request);
    $this->removeFromCookie($request);

    // We call the profile onUserLogOut method
    $profile = $this->getProfile();
    if(isset($profile) && method_exists($profile,"onUserLogOut")) {
      $loggedOut = $profile->onUserLogOut();
      if($loggedOut instanceof Error) {
        return false;
      }
    }

    return true;
  }

  /*
   *	function: removeAccount
  *	This function remove the user account.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- true if removed or array of Error object.
  */
  public function removeAccount() {
    $listErrors = array();

    if(!isset($this->SQLid)) {
      $listErrors[] = new Error(9010,1);
      return $listErrors;
    }

    // We remove the user from DB
    $removed = $this->removeFromDB();
    if($removed instanceof Error) {
      $listErrors[] = $removed;
    }

    // We remove the profile
    $profile = $this->getProfile();
    if(isset($profile) && method_exists($profile,"onUserRemoveAccount")) {
      $profileRemoved = $profile->onUserRemoveAccount();
      if(is_array($profileRemoved) and $profileRemoved[0] instanceof Error) {
        array_merge($listErrors,$profileRemoved);
      }
    }

    // We logout the user
    $loggedOut = $this->logOut();
    if($loggedOut instanceof Error) {
      $listErrors[] = $loggedOut;
    }

    if(count($listErrors) > 0) {
      return $listErrors;
    }

    return true;
  }

  /*
   *	function: register
  *	This function tries to register the user.
  *
  *	parameters:
  *		- $login - the user login.
  *		- $password - the user password (not encrypted).
  *		- $email - the user email.
  *		- $firstName - the user first name.
  *		- $lastName - the user last name.
  *		- birthdayDate - the user birthday date (IwDateTime)
  *	return:
  *		- true if registered or Error object.
  */
  public function register($login, $password, $email, $firstName = "", $lastName = "",$birthdayDate = NULL) {
    global $request;

    if($request->getConfigAsBoolean("users-useEmailAsLogin")) {
      $login = $email;
    }

    // 1. We check that every fields are not empty
    if($login == "" || $password == "" || $email == "" ||
        ($request->getConfigAsBoolean("users-birthdayDateMandatory") && !isset($birthdayDate)) ||
        ($request->getConfigAsBoolean("users-namesMandatory") && ($firstName == "" || $lastName == ""))) {
      return new Error(9008,1);
    }

    // We do not want to update current user...
    $this->SQLid = NULL;

    // 2. We check that the login or email does not already exists for an enabled account
    $queryGetUser = "SELECT * FROM `".static::getTableName()."` ";
    $queryGetUser .= "WHERE `login`='".$login."' OR `email`='".$email."'";
    $queryGetUser .= " AND `isEnabled`=".DBUtils::getBoolean(true);
    $resultGetUser = DBUtils::query($queryGetUser);

    if($resultGetUser && DBUtils::getNbRows($resultGetUser) > 0) {
      while($userDatas = DBUtils::fetchAssoc($resultGetUser)) {
        // We check if the account already exists
        if(!$request->getConfigAsBoolean("users-useEmailAsLogin") && $userDatas["login"] == $login) {
          return new Error(9014,1);
        }
        if($userDatas["email"] == $email) {
          return new Error(9015,1);
        }
      }
    }

    // 3. Everything is OK so we register the user.
    $this->setLogin($login);
    $this->setPassword(static::encryptPassword($password));
    $this->setEmail($email);
    $this->setFirstName($firstName);
    $this->setLastName($lastName);
    $this->setBirthdayDate($birthdayDate);
    $this->setCreationDateTime(DBUtils::getSQLDateTime());
    $this->setIsEmailValidated(false);
    $this->setIsEnabled(true);

    $stored = $this->storeInDB();
    if($stored instanceof Error) {
      return $stored;
    }
    
    // We call the profile event
    if(method_exists("Profile","onUserRegister")) {
      $result = Profile::onUserRegister($this);
      if($result instanceof Error) {
        return $result;
      }
    }

    $this->storeInSession($request);

    return true;
  }

  /*
   *	function: unregister
  *	This function unregister the user.
  *	It disable the user account.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- true if unregistered or Error object.
  */
  public function unregister() {
    if(!isset($this->SQLid)) {
      return new Error(9010,1);
    }

    // We disable the account
    $this->setIsEnabled(false);
    $stored = $this->storeInDB();
    if($stored instanceof Error) {
      return $stored;
    }

    // We unregister the profile
    $profile = $this->getProfile();
    if(isset($profile) && method_exists($profile,"onUserUnregister")) {
      $profileUnregistered = $profile->onUserUnregister();
      if($profileUnregistered instanceof Error) {
        return $profileUnregistered;
      }
    }

    // We logout the user
    $loggedOut = $this->logOut();
    if($loggedOut instanceof Error) {
      return $loggedOut;
    }

    return true;
  }

  /*
   *	static function: removeNonValidatedAccount
  *	This function removes the non validated account in time.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- ok if removed or Error object.
  */
  public static function removeNonValidatedAccount() {
    global $request;
    $logger = Logger::getInstance();

    // 1. We do it only of account validation is done.
    if($request->getConfigAsBoolean("users-validateEmail")) {
      // We build the SQL date time regarding now + timeToValidated
      $currentSQLDateTime = DBUtils::getSQLDateTime();
      	
      $queryDeleteUsers = "DELETE FROM `".static::getTableName()."` ";
      $queryDeleteUsers .= "WHERE `isEmailValidated`=".DBUtils::booleanToDB(false);
      $queryDeleteUsers .= " AND '".$currentSQLDateTime."' > ADDTIME(`creationDateTime`,'".$request->getConfig("users-timeToValidateEmail").":00')";
      	
      $resultDeleteUsers = DBUtils::query($queryDeleteUsers);
      	
      if(!$resultDeleteUsers) {
        $logger->addLog("queryDeleteUsers: ".$queryDeleteUsers);
        return new Error(9016,1);
      }
    }
    return "ok";
  }

  /*
   *	function: validateEmail
  *	This function tries to validate the user email.
  *
  *	parameters:
  *		- $validationCode - the email validation code.
  *	return:
  *		- "ok" if validated or Error object.
  */
  public function validateEmail($validationCode) {
    global $request;
	
    if($this->getIsEmailValidated()) {
      return new Error(9011);
    }
    if($this->getEmailValidationCode() != $validationCode) {
      return new Error(9012);
    }

    // We validate the email
    $this->setIsEmailValidated(true);

    // We store the update
    $stored = $this->storeInDB();
    if($stored instanceof Error) {
      return $stored;
    }

    // We call the profile event
    if(method_exists("Profile","onUserEmailValidated")) {
      $result = Profile::onUserEmailValidated($this);
      if($result instanceof Error) {
        return $result;
      }
    }

    $this->storeInSession($request);
    return "ok";
  }

  /*
   *	function: getEmailValidationCode
  *	This function get the email validation code.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- email validation code.
  */
  public function getEmailValidationCode() {
    global $request;

    if((!isset($this->email) || $this->email == "") &&
        (!isset($this->login) || $this->login == "")) {
      return "";
    }

    if($request->getConfigAsBoolean("users-useEmailAsLogin")) {
      return md5($this->email.$this->SQLid."");
    }
    return md5($this->login.$this->email.$this->SQLid."");
  }

  /*
   *	function: generateResetPasswordCode
  *	This function generate a code to be used to reset the password.
  *
  *	parameters:
  *		- none.
  *	return:
  *		- the new password if reseted or Error object.
  */
  public function generateResetPasswordCode() {
    global $request;

    // 1. We check that the user is a registered one
    if(!isset($this->SQLid)) {
      return new Error(9008,5);
    }

    // The email has to be validated in order to reset the password
    if($request->getConfigAsBoolean("users-validateEmail") && !$this->getIsEmailValidated()) {
      return new Error(9013);
    }

    // 2. We generate a new password
    $resetPasswordCode = Utils::randomString(8);

    $this->setResetPasswordCode($resetPasswordCode);
    $stored = $this->storeInDB();
    if($stored instanceof Error) {
      return $stored;
    }

    return $resetPasswordCode;
  }

  /*
   *	function: resetPassword
  *	This function reset the user password.
  *	It checks that the reset password code is valid.
  *
  *	parameters:
  *		- code : the user reset password code.
  *		- password : the user new password (not encrypted).
  *	return:
  *		- "ok" if reseted or Error object.
  */
  public function resetPassword($email,$code,$password) {
    global $request;
	
    // 1. We check that the user is a registered one
    if(!isset($this->SQLid)) {
      return new Error(9008,6);
    }

    if($this->resetPasswordCode == "" ||
        $this->resetPasswordCode != $code) {
      // The reset password code is incorrect or not valid
      return new Error(9033);
    }

    $passwordValid = $this->isPasswordValid($password);
    if($passwordValid instanceof Error) {
      return $passwordValid;
    }

    // We update the password
    $this->setPassword(static::encryptPassword($password));
    $this->setResetPasswordCode("");
    $stored = $this->storeInDB();
    if($stored instanceof Error) {
      return $stored;
    }

    // We log the user
    $this->setIsLogged(true);
    $this->storeInSession($request);

    return "ok";
  }

  /*
   *	function: updatePassword
  *	This function update the user password.
  *
  *	parameters:
  *		- previousPassword : the user previous password (not encrypted).
  *		- newPassword : the user new password (not encrypted).
  *	return:
  *		- "ok" if reseted or Error object.
  */
  public function updatePassword($previousPassword, $newPassword) {
    // 1. We check that the user is a registered one
    if(!isset($this->SQLid)) {
      return new Error(9008,7);
    }

    // 2. We check that the previous password is correct
    if($this->getPassword() != static::encryptPassword($previousPassword)) {
      return new Error(9039);
    }

    $passwordValid = $this->isPasswordValid($newPassword);
    if($passwordValid instanceof Error) {
      return $passwordValid;
    }

    // We update the password
    $this->setPassword(static::encryptPassword($newPassword));
    $stored = $this->storeInDB();
    if($stored instanceof Error) {
      return $stored;
    }

    return "ok";
  }

  /*
   *	isPasswordValid
  *	This function checks if a password is valid.
  *
  *	parameters:
  *		- password : the user new password.
  *	return:
  *		- "ok" if valid or Error object.
  */
  public function isPasswordValid($password) {
    if($password == "") {
      return new Error();
    }
    return "ok";
  }

  public static function encryptPassword($password) {
    return md5($password);
  }

  public function getProfile() {
    if(!isset($this->profile)) {
      if(method_exists("Profile","getFromIdUser") && isset($this->SQLid)) {
        $this->profile = Profile::getFromIdUser($this->SQLid);
      }
    }
    return $this->profile;
  }

  /*
   *	function: addRole
  *	This function add a role to the user.
  *
  *	parameters:
  *		- role - the role to add.
  *	return:
  *		- "ok" if added, else an error object.
  */
  public function addRole($role) {
    // We add the role if not already available.
    if(!$this->hasRole($role)) {
      array_push($this->roles,strtolower($role));
    }

    return "ok";
  }

  /*
   *	function: removeRole
  *	This function removes a role to the user.
  *
  *	parameters:
  *		- role - the role to add.
  *	return:
  *		- "ok" if removed, else an error object.
  */
  public function removeRole($role) {
    // We remove the role if already available.
    if($this->hasRole($role)) {
      $this->roles = array_values(array_diff($this->roles,array(strtolower($role))));
    }

    return "ok";
  }

  /*
   *	function: hasRole
  *	This function checks if the user has a specific role.
  *
  *	parameters:
  *		- role - the role to check.
  *	return:
  *		- true if found else false.
  */
  public function hasRole($role) {
    return in_array(strtolower($role), $this->roles);
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
    $user = array();

    if($this->isLogged) {
      $user["idUser"] = $this->SQLid;
      $user["login"] = $this->login;
      $user["isLogged"] = $this->isLogged;
      $user["email"] = $this->email;
      $user["firstName"] = $this->firstName;
      $user["lastName"] = $this->lastName;
      $user["profile"] = $this->getProfile();
      $user["isEmailValidated"] = $this->getIsEmailValidated();
      $user["birthdayDate"] = $this->birthdayDate;
      $user["lastConnectionDateTime"] = $this->lastConnectionDateTime;
      $user["creationDateTime"] = $this->creationDateTime;
      $user["roles"] = $this->roles;
    }
    else {
      $user["login"] = $this->login;
      $user["email"] = $this->email;
      $user["isLogged"] = $this->isLogged;
    }

    return $user;
  }
}

?>
