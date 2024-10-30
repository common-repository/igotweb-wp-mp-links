<?php

/**
 *	Class: CryptographUtils
 *	Version: 0.1
 *	This class handle encryption.
 *
 *	requires:
 *		- request.
 *		- Error.
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk\utilities;

use igotweb_wp_mp_links\igotweb\fwk\model\bean\Request;
 
class CryptographUtils {
	
	private function __construct() {}
	
	/*
	 *	function: encode
	 *	This function returns encoded value.
	 *	
	 *	parameters:
	 *		- $value - the string to encode.
	 *	return:
	 *		- encoded - the encoded string.
	 */
	public static function encode($value, Request $request) {
		$key = $request->getConfig("encryptionKey");
		$encoded = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $value, MCRYPT_MODE_CBC, md5(md5($key))));
		$encoded = str_replace(array('+', '/'), array(',', '-'), $encoded);
		return $encoded;
	}
	
	/*
	 *	function: decode
	 *	This function returns decoded value.
	 *	
	 *	parameters:
	 *		- $encoded - the string to decode.
	 *	return:
	 *		- value - the decoded string.
	 */
	public static function decode($encoded, Request $request) {
		$key = $request->getConfig("encryptionKey");
		$encoded = str_replace(array(',', '-'), array('+', '/'), $encoded);
		$value = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($encoded), MCRYPT_MODE_CBC, md5(md5($key))), "\0");
		return $value;
	}
	
}
?>