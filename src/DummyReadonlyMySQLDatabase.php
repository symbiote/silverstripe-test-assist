<?php

namespace Symbiote\TestAssist;


use SilverStripe\ORM\Connect\MySQLDatabase;
use Exception;


/**
 * Description of DummyReadonlyMySQLDatabase
 *
 * @author marcus
 */
class DummyReadonlyMySQLDatabase extends MySQLDatabase {
	public $writeQueries = array('insert','update','delete','replace', 'drop', 'create', 'truncate');
	
	public function query($sql, $errorLevel = E_USER_ERROR) {
		if (in_array(strtolower(substr($sql,0,strpos($sql,' '))), $this->writeQueries)) {
			throw new Exception("Attempted to write to readonly database");
		}
		return parent::query($sql, $errorLevel);
	}
}
