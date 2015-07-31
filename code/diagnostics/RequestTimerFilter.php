<?php

/**
 * @author <marcus@silverstripe.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class RequestTimerFilter implements RequestFilter {
	private $start;
	
	public function postRequest(\SS_HTTPRequest $request, \SS_HTTPResponse $response, \DataModel $model) {
		$time = sprintf('%.3f ms', microtime(true) - $this->start);
		$response->addHeader('X-SilverStripe-Time', $time);
		
		$b = $response->getBody();
		
		if (strpos($b, '</html>')) {
			$b = str_replace('</html>', "\n<!-- Generated in $time -->\n</html>", $b);
			$response->setBody($b);
		}
	}
		
	public function preRequest(\SS_HTTPRequest $request, \Session $session, \DataModel $model) {
		$this->start = microtime(true);
	}

}
