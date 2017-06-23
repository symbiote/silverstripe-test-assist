<?php

/**
 * Display query information
 *
 * @author <marcus@symbiote.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class QueryDisplayFilter implements RequestFilter {
	
	/**
	 *
	 * @var DevMySQLDatabase
	 */
	public $database;
	
	/**
	 * Should we always output query information?
	 *
	 * @var boolean
	 */
	public $displayData = true;
	
	/**
	 * How many queries is considered too many?
	 *
	 * @var int
	 */
	public $queryThreshold = 5;
	
	
	public function postRequest(\SS_HTTPRequest $request, \SS_HTTPResponse $response, \DataModel $model) {
		if (defined('PROXY_CACHE_GENERATING') || isset($GLOBALS['__cache_publish']) || strpos($request->getURL(), 'admin/') !== false) {
			return;
		}
		$this->database = Db::getConn();
		
		$queries = $this->database->queryRecord;
		$dupes = $this->database->getDuplicateQueries();
		
		$str =  "\n<!-- Total queries: " . count($queries) . "-->\n";
		$str .= "\n<!-- Duplicate queries: " . count($dupes) . "-->\n";
		$b = $response->getBody();
		
		if (strpos($b, '</html>')) {
			if (count($queries) > $this->queryThreshold) {
				// add a floating div with info about the stuff
				
				$buildQueryList = function ($source, $class) {
					$html = '';
					foreach ($source as $sql => $info) {
						$html .= "\n<p class='$class' style='display: none; border-top: 1px dashed #000;'>$info->count : $info->query</p>\n";
						if ($info->source) {
							$html .= "\n<p class='$class' style='color: #a00; display: none; '>Last called from $info->source</p>\n";
						}
					}
					return $html;
				};
				
				$html = $buildQueryList($queries, 'debug-query');
				$html .= $buildQueryList($dupes, 'debug-dupe-query');

				$div = '<div id="query-stat-debugger" '
					. 'style="position: fixed; bottom: 0; right: 0; border: 2px solid red; background: #fff; '
					. 'font-size: 8px; font-family: sans-serif; width: 100px; z-index: 2000; padding: 1em;'
					. 'overflow: auto; max-height: 500px;">'
					. '<p id="debug-all-queries-list">Total of ' . count($queries) . ' queries</p>'
					. '<p id="debug-dupe-queries-list">Total of ' . count($dupes) . ' duplicates</p>'
					. $html
					. '<script>'
					. 'jQuery("#debug-all-queries-list").click(function () {'
					. 'var elems = jQuery(this).parent().find(".debug-query");'
					. 'jQuery(this).parent().css("width", "40%");'
					. 'elems.toggle();'
					. '}); '
					. 'jQuery("#debug-dupe-queries-list").click(function () {'
					. 'var elems = jQuery(this).parent().find(".debug-dupe-query");'
					. 'jQuery(this).parent().css("width", "40%");'
					. 'elems.toggle();'
					. '}); '
					. ''
					. ''
					. '</script>'
					
					. '</div>';
				
				$b = str_replace('</body>', "$div</body>", $b);
			}
			
			$b = str_replace('</html>', "$str</html>", $b);
			$response->setBody($b);
		}
	}

	public function preRequest(\SS_HTTPRequest $request, \Session $session, \DataModel $model) {
		
	}

}
