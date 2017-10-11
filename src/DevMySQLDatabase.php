<?php

namespace Symbiote\TestAssist;


use SilverStripe\Control\Director;
use SilverStripe\ORM\Connect\MySQLDatabase;
use stdClass;


/**
 * Custom dev version of the database that will record details of queries
 *
 * @author <marcus@symbiote.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class DevMySQLDatabase extends MySQLDatabase {
	public $queryRecord = array();
	public $duplicateQueries = array();
	
	public $allQueries = array();
	
	public $userCode = array('mysite');
	
	public function query($sql, $errorLevel = E_USER_ERROR) {
		$this->recordQuery($sql);
		return parent::query($sql, $errorLevel);
	}
    
    public function preparedQuery($sql, $parameters, $errorLevel = E_USER_ERROR) {
        $this->recordQuery($sql);
        return parent::preparedQuery($sql, $parameters, $errorLevel);
    }
    
    protected function recordQuery($sql) {
        $query = new stdClass;
		$query->query = $sql;
		$query->source = '';
		$query->count = 0;
		
		$trace = $this->userCaller();
		if ($trace) {
			$query->source = 'Line ' . $trace['line'] . ' in ' . $trace['file'];
		}
		
		$this->queryRecord[] = $query;

		if (isset($this->allQueries[$sql])) {
			$cur = isset($this->duplicateQueries[$sql]) ? $this->duplicateQueries[$sql] : $query;
			if (!isset($cur->count)) {
				$cur->query = $sql;
				$cur->count = 0;
			}

			$cur->count = $cur->count + 1;
			if ($cur->count > 2 && !isset($cur->source)) {
				// lets see where it's coming from
				if ($trace) {
					$cur->source = 'Line ' . $trace['line'] . ' in ' . $trace['file'];
				}
				
			}
			$this->duplicateQueries[$sql] = $cur;
		}
		
		// mark as having executed this query
		$this->allQueries[$sql] = true;
    }
	
	public function getDuplicateQueries() {
		$actualDupes = array();
		
		foreach ($this->duplicateQueries as $sql => $info) {
			if ($info->count > 1) {
				$actualDupes[$sql] = $info;
			}
		}
		return $actualDupes;
	}
	
	
	protected function userCaller() {
		$bt = debug_backtrace(2);
		if (!isset($bt[5])) {
			return;
		}
		
		$base = Director::baseFolder();
		for ($i = 2, $c = count($bt); $i < $c; $i++) {
			$history = $bt[$i];
			if (!isset($history['file'])) {
				continue;
			}
			$file = trim(str_replace($base, '', $history['file']), '/');
			$bits = explode('/', $file);
			if (in_array($bits[0], $this->userCode) && $i < 15) {
				if(!isset($history['class'])) $history['class'] = '';
				if(!isset($history['type'])) $history['type'] = '';
				return $history;
			}
		}
	}
}
