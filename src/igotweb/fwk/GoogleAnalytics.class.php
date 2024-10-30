<?php

/**
 *	Class: GoogleAnalytics
 *	Version: 0.2
 *	This class handle specific actions for Google Analytics application.
 */
namespace igotweb_wp_mp_links\igotweb\fwk;
 
class GoogleAnalytics {
	
	public $account;
	public $url;
	public $parameters;
	public $domain;

	public function __construct($url = NULL) {
		global $request;
		
		$this->parameters = array();
		$this->account = $request->getConfig("googleAnalyticsAccount");
		$this->domain = $request->getConfig("googleAnalyticsDomain");
		
		if(isset($url)) {
			$this->url = $url;	
		}
	}
	
	public function setAccount($account) {
		$this->account = $account;	
	}
	
	public function addParameter($name,$value) {
		$this->parameters[$name] = $value;
	}
	
	public function getURLParameters() {
		$paramStr = "";
		foreach($this->parameters as $key=>$value) {
			if($paramStr == "") {
				$paramStr .= "?";	
			}
			else {
				$paramStr .= "&";	
			}
			$paramStr .= $key."=".$value;	
		}
		return $paramStr;
	}
	
	public function setUrl($url) {
		$this->url = $url;
	}
	
	public function setDomain($domain) {
		$this->domain = $domain;	
	}
	
	/*
	 *	generateScript
	 *	This method generates the script to be used for Google Analytics tracking.
	 *	By default it track the current page.
	 *
	 *	parameters:
	 *		- options : 
	 */
	public function generateScript($options = array()) {
		// We do not generate the script if no account is defined.
		if(!isset($this->account)) {
			return;	
		}
		
		echo "<!-- Google Analytics -->";
		echo "<script>";
		echo "(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){";
		echo "(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),";
		echo "m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)";
		echo "})(window,document,'script','//www.google-analytics.com/analytics.js','ga');";

		echo "ga('create', '".$this->account."', 'auto'";
		
		if(isset($this->domain) && $this->domain != "") {
			echo ", {";
			echo "cookieDomain: '".$this->domain."',";
			echo "legacyCookieDomain: '".$this->domain."'";
			echo "}";
		}
		echo"); ";
		
		if(!isset($options["disableTrackPage"]) || !$options["disableTrackPage"]) {
			if(isset($this->url) && $this->url!="") {
				echo "ga('send', 'pageview', '".$this->url.$this->getURLParameters()."');";
			}
			else {
				echo "	ga('send', 'pageview');";
			}
		}
		
		echo "</script>";
		echo "<!-- End Google Analytics -->";
	}

}

?>
