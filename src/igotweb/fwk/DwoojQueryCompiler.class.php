<?php
/**
 * jQuery Compiler
 */
namespace igotweb_wp_mp_links\igotweb\fwk;

use igotweb_wp_mp_links\igotweb\fwk\model\manager\TemplateManager;

class DwoojQueryCompiler extends \Dwoo_Compiler
{

	/**
	 * constructor
	 *
	 * saves the created instance so that child templates get the same one
	 */
	public function __construct()
	{
		self::$instance = $this;
	}

	public function setDebug($debug) {
		$this->debug = $debug;
	}

	/**
	 * entry point of the parser, it redirects calls to other parse* functions
	 *
	 * @param string $in the string within which we must parse something
	 * @param int $from the starting offset of the parsed area
	 * @param int $to the ending offset of the parsed area
	 * @param mixed $parsingParams must be an array if we are parsing a function or modifier's parameters, or false by default
	 * @param string $curBlock the current parser-block being processed
	 * @param mixed $pointer a reference to a pointer that will be increased by the amount of characters parsed, or null by default
	 * @return string parsed values
	 */
	protected function parse($in, $from, $to, $parsingParams = false, $curBlock='', &$pointer = null)
	{
		$output = parent::parse($in, $from, $to, $parsingParams, $curBlock, $pointer);

		// In case the block has to display some text, <?php echo block; ? >
		$pattern = preg_quote(self::PHP_OPEN)."echo\s+([^;]+);\s*".preg_quote(self::PHP_CLOSE);
		$htmlPattern = "Dwoo_Plugin_html";
		if(is_string($output) && preg_match("/".$pattern."/i",$output,$matches) && !preg_match("/".$pattern."/i",$output)) {
			$output = self::PHP_OPEN .'TemplateManager::_echo('.$matches[1].');'. self::PHP_CLOSE;
		}

		return $output;
	}

	/**
	 * parses various constants, operators or non-quoted strings
	 *
	 * @param string $in the string within which we must parse something
	 * @param int $from the starting offset of the parsed area
	 * @param int $to the ending offset of the parsed area
	 * @param mixed $parsingParams must be an array if we are parsing a function or modifier's parameters, or false by default
	 * @param string $curBlock the current parser-block being processed
	 * @param mixed $pointer a reference to a pointer that will be increased by the amount of characters parsed, or null by default
	 * @return string parsed values
	 */
	protected function parseOthers($in, $from, $to, $parsingParams = false, $curBlock='', &$pointer = null)
	{
		$output = parent::parseOthers($in, $from, $to, $parsingParams, $curBlock, $pointer);
		return $output;
	}

	/**
	 * replaces variables within a parsed string
	 *
	 * @param string $string the parsed string
	 * @param string $first the first character parsed in the string, which is the string delimiter (' or ")
	 * @param string $curBlock the current parser-block being processed
	 * @return string the original string with variables replaced
	 */
	protected function replaceStringVars($string, $first, $curBlock='')
	{
		$output = parent::replaceStringVars($string, $first, $curBlock);

		$output = $this->handleStringKey($output);

		return $output;
	}

	/**
	 * parses a variable
	 *
	 * @param string $in the string within which we must parse something
	 * @param int $from the starting offset of the parsed area
	 * @param int $to the ending offset of the parsed area
	 * @param mixed $parsingParams must be an array if we are parsing a function or modifier's parameters, or false by default
	 * @param string $curBlock the current parser-block being processed
	 * @param mixed $pointer a reference to a pointer that will be increased by the amount of characters parsed, or null by default
	 * @return string parsed values
	 */
	protected function parseVar($in, $from, $to, $parsingParams = false, $curBlock='', &$pointer = null)
	{
		$output = parent::parseVar($in, $from, $to, $parsingParams, $curBlock,$pointer);
		return $output;
	}

	/**
	 * parses a constant variable (a variable that doesn't contain another variable) and preprocesses it to save runtime processing time
	 *
	 * @param string $key the variable to parse
	 * @param string $curBlock the current parser-block being processed
	 * @return string parsed variable
	 */
	protected function parseVarKey($key, $curBlock) {
    // We first check if the $var is starting with dwoo keyword ($dwoo should be processed with default process)
    if(preg_match("/^dwoo\./i",$key)) {
      return parent::parseVarKey($key, $curBlock);
    }
    
		// We transform the var as string to be analized: $var => 'var'
		$key = "'".preg_replace('/\$/i','',$key)."'";
		$output = $this->handleStringKey($key);
		return $output;
	}

	/*
	 *	generateEvaluate
	 *
	 */
	protected function generateEvaluate($var,$params) {
		// We generate the output
		$output = "TemplateManager::evaluate(".$var.",array(";
		for($i = 0 ; $i < count($params) ; $i++) {
			if($i > 0) { $output .= ", "; }

			if(preg_match("/^\[.*\]$/i",$params[$i])) {
				// In case we have bracet, we need to evaluate the content
				$stringVar = preg_replace("/^\[(.*)\]$/i",'${1}',$params[$i]);
				if(preg_match("/^([0-9]+)$/i",$stringVar,$matches)) {
					// In case the content is an integer, we have an array index
					$output .= $matches[1];
				}
				else {
					// Default use case (associative array key)
					$output .= $this->handleStringKey("'".$stringVar."'");
				}
			}
			else {
				$output .= "\"".$params[$i]."\"";
			}
		}
		$output .= "))";

		return $output;
	}

	/*
	 *	handleStringKey
	 */
	protected function handleStringKey($key) {

		$output = $key;

		if(preg_match("/^\'([a-z0-9\.\[\]]*)\'$/i",$output,$matches)) {
			// We first look at all parts of the string key
			if(preg_match_all("/(\[?[a-z0-9\]]+\]?)/i",$matches[1],$params)) {
				// We get the list of items
				$parameters = array();
				$currentValue = "";
				$openedBracet = 0;
				foreach($params[0] as $param) {

					if(preg_match("/^\[[a-z0-9\]]+$/i",$param)) {
						// We have beginning of associative key [aaa
						$openedBracet++;
					}
					else if($openedBracet > 0) {
						$currentValue .= ".";
					}

					// We have end of associative key bbb]
					$openedMatches = array();
					$openedBracet -= preg_match_all("/\]/i",$param,$openedMatches);

					$currentValue .= $param;

					if($openedBracet == 0) {
						// We are not in associative key or it has been ended.
						// We add the value to parameters and reset the current value.
						$parameters[] = $currentValue;
						$currentValue = "";
					}
				}

				//$var = "\$this->scope[\"".array_shift($parameters)."\"]";
				$var = "\$this->scope";
				$output = $this->generateEvaluate($var,$parameters);
			}
		}

		return $output;
	}

}
