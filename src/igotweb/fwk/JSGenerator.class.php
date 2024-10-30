<?php

/**
 *	Class: JSGenerator
 *	Version: 0.1
 *	This class handle generation of javascript content.
 *
 *	requires:
 *		- request.
 *
 */
namespace igotweb_wp_mp_links\igotweb\fwk;
 
class JSGenerator {
	
	function __construct() {
		
	}	
	
	/*
	 *	function: generateTemplateStyle
	 *	This function returns javascript which generates style tag for a template.
	 *	
	 *	parameters:
	 *		- $templateName - the name of the template.
	 *	return:
	 *		- Javascript code.
	 */
	public function generateTemplateStyle($templateName) {
		global $request;
		
		$content = "<style type=\"text/css\">";
		$content .= $request->getTemplateManager()->getTemplateStyle($templateName);	
		$content .= "</style>";
		
		return $this->writeJs($content);
	}
	
	/*
	 *	function: generateTemplateStyle
	 *	This function returns javascript which generates style tag for a template.
	 *	
	 *	parameters:
	 *		- $templateName - the name of the template.
	 *		- $datas : map of datas required by the template.
	 *	return:
	 *		- Javascript code.
	 */
	public function generateTemplate($templateName, $datas = array()) {
		global $request;
		// We generate the template content
		$content = $request->getTemplateManager()->generateTemplate($templateName, $datas);
		// We generate the js code
		return $this->writeJs($content);
	}
	
	/*
	 *	function: writeJs
	 *	This function returns javascript which writes any content.
	 *	
	 *	parameters:
	 *		- $content - html content to be written by the javascript.
	 *	return:
	 *		- Javascript code.
	 */
	public function writeJs($content) {
		// We do some checks on the content
		$content = str_replace("\"","\\\"",$content);
		$lines = preg_split("/[\n|\r]/i", $content);
		$js = "";
		foreach ($lines as $line) {
			$js .= "document.write(\"";
			$js .= $line;
			$js .= "\");";
		}
		
		return $js;
	}
}

?>