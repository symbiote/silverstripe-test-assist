<?php

namespace Symbiote\TestAssist;


use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Session;
use SilverStripe\Control\RequestFilter;



/**
 * @author <marcus@symbiote.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class RequestTimerFilter implements RequestFilter {
	private $start;
	
	public function postRequest(HTTPRequest $request, HTTPResponse $response) {
		$time = sprintf('%.3f ms', microtime(true) - $this->start);
		$response->addHeader('X-SilverStripe-Time', $time);
		
		$b = $response->getBody();
		
		if (strpos($b, '</html>')) {
			$b = str_replace('</html>', "\n<!-- Generated in $time -->\n</html>", $b);
			$response->setBody($b);
		}
	}
		
	public function preRequest(HTTPRequest $request) {
		$this->start = microtime(true);
	}

}
