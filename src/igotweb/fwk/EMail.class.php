<?php
/**
 *	Class: EMail
 *  Version: 0.1
 *	This class handle emails.
 *
 *	requires:
 *		- Error.
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Error;
use igotweb_wp_mp_links\igotweb\fwk\EMail;
use igotweb_wp_mp_links\igotweb\fwk\Logger;

class EMail {

  private static $instance;

	private $charset;
	private $encoding;
	private $textContentType;
  private $HTMLContentType;
	
	private function __construct() {
		global $request;
		
		$this->charset = $request->getConfig("charset");
		$this->encoding = "8bit";
		
		$this->textContentType = "text/plain";
		$this->HTMLContentType = "text/html";
  }

  /**
    * This method get the instance of Email.
    * @param void
    * @return Email instance
    */
  public static function getInstance() {
    if(is_null(static::$instance)) {
      static::$instance = new Email();  
    }
    return static::$instance;
  }
	
	/*
	 *	function: sendTextEmail
	 *	This function send an email in text format.
	 *	
	 *	parameters:
	 *		- $from - origin of the mail ("Nom" <'.$email_expediteur.'>).
	 *		- $to - destination of the mail ("Nom" <'.$email_expediteur.'>).
	 *		- $subject - sujet du mail.
	 *		- $textContent - content of EMail in text format.
	 *		- $replyTo - replyTo for EMail.
	 *	return:
	 *		- none.
	 */
	public function sendTextEmail($from, $to, $subject, $textContent, $replyTo, $bcc) {
    $logger = Logger::getInstance();

    // 1. HEADERS DU MAIL
		$headers = 'From: '.$from."\n";
		if($replyTo != NULL) {
			$headers .= 'Return-Path: <'.$replyTo.'>'."\n";
		}
		if($bcc != NULL) {
			$headers .= "Bcc: ".$bcc."\n";
		}
		$headers .= 'Content-Type: '.$this->textContentType.'; charset="'.$this->charset.'"'."\n";
		$headers .= 'MIME-Version: 1.0'."\n";
		$headers .= 'Content-Transfer-Encoding: '.$this->encoding;
	
		// 2. CONTENU DU MAIL
		$message = $textContent;
		
    // 3. We send the email
    $logger->addLog("EMail: To - " . $to, true);
    $logger->addLog("EMail: Header - " . $headers, true);
    $logger->addLog("EMail: Subject - " . $subject, true);
		if(mail($to,$subject,$message,$headers)) {
			return "ok";
		}
		else {
			return new Error(9200);
		}		
	}
	
	/*
	 *	function: sendTextAndHTMLEMail
	 *	This function send an email in text and HTML format.
	 *	
	 *	parameters:
	 *		- $from - origin of the mail ("Nom" <'.$email_expediteur.'>).
	 *		- $to - destination of the mail ("Nom" <'.$email_expediteur.'>).
	 *		- $subject - sujet du mail.
	 *		- $textContent - content of EMail in text format.
	 *		- $htmlContent - content of EMail in HTML format.
	 *		- $replyTo - replyTo for EMail.
	 *	return:
	 *		- none.
	 */
	public function sendTextAndHTMLEMail($from, $to, $subject, $textContent, $htmlContent, $replyTo) {
    $logger = Logger::getInstance();
    
    // 1. GENERE LA FRONTIERE DU MAIL ENTRE TEXTE ET HTML
		$frontiere = '===============' . md5(uniqid(mt_rand())) . '==';

		// 2. HEADERS DU MAIL
		$headers = 'From: '.$from."\n";
		if($replyTo != NULL) {
			$headers .= 'Return-Path: <'.$replyTo.'>'."\n";
		}
		$headers .= 'MIME-Version: 1.0'."\n";
		$headers .= 'Content-Type: multipart/alternative; boundary="'.$frontiere.'"';
		
		$message = "";
		
		// 3. MESSAGE TEXTE
		$message .= '--'.$frontiere."\n";
		$message .= 'Content-Type: '.$this->textContentType.'; charset="'.$this->charset.'"'."\n";
		$message .= 'MIME-Version: 1.0'."\n";
		$message .= 'Content-Transfer-Encoding: '.$this->encoding."\n\n";
		$message .= $textContent."\n\n";

		// 4. MESSAGE HTML
		$message .= '--'.$frontiere."\n";
		$message .= 'Content-Type: '.$this->HTMLContentType.'; charset="'.$this->charset.'"'."\n";
		$message .= 'MIME-Version: 1.0'."\n";
		$message .= 'Content-Transfer-Encoding: '.$this->encoding."\n\n";
		$message .= $htmlContent."\n\n";

		$message .= '--'.$frontiere.'--'."\n";

    // 5. We send the email
    $logger->addLog("EMail: To - " . $to, true);
    $logger->addLog("EMail: Header - " . $headers, true);
    $logger->addLog("EMail: Subject - " . $subject, true);
		if(mail($to,$subject,$message,$headers)) {
			return "ok";
		}
		else {
			return new Error(9200);
		}
	}
	
	/*
	 *	static function: validateEmail
	 *	This function validates an email address.
	 *	
	 *	parameters:
	 *		- $email - the email address to validate.
	 *	return:
	 *		- "ok" if valid else an Error object.
	 */
	public static function validateEmail($email)
	{
	   $isValid = true;
	   $atIndex = strrpos($email, "@");
	   if (is_bool($atIndex) && !$atIndex)
	   {
	      return new Error(9201,1);
	   }
	   else
	   {
	      $domain = substr($email, $atIndex+1);
	      $local = substr($email, 0, $atIndex);
	      $localLen = strlen($local);
	      $domainLen = strlen($domain);
	      if ($localLen < 1 || $localLen > 64)
	      {
	         // local part length exceeded
	         return new Error(9201,2);
	      }
	      else if ($domainLen < 1 || $domainLen > 255)
	      {
	         // domain part length exceeded
	         return new Error(9201,3);
	      }
	      else if ($local[0] == '.' || $local[$localLen-1] == '.')
	      {
	         // local part starts or ends with '.'
       	         return new Error(9201,4);
	      }
	      else if (preg_match('/\\.\\./', $local))
	      {
	         // local part has two consecutive dots
       	         return new Error(9201,5);
	      }
	      else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
	      {
	         // character not valid in domain part
       	         return new Error(9201,6);
	      }
	      else if (preg_match('/\\.\\./', $domain))
	      {
	         // domain part has two consecutive dots
       	         return new Error(9201,7);
	      }
	      else if
	(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
	                 str_replace("\\\\","",$local)))
	      {
	         // character not valid in local part unless 
	         // local part is quoted
	         if (!preg_match('/^"(\\\\"|[^"])+"$/',
	             str_replace("\\\\","",$local)))
	         {
			return new Error(9201,8);
	         }
	      }
	      if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))
	      {
	         // domain not found in DNS
			return new Error(9201,9);
	      }
	   }
	   return "ok";
	}

}

?>
