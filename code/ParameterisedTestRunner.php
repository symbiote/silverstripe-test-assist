<?php

/**
 * A test runnner that accepts several parameters for setting things
 * like the test reporter to use, the 
 * @author Marcus Nyeholt <marcus@symbiote.com.au>
 *
 */
class ParameterisedTestRunner extends TestRunner
{
	// overridden on the base due to private declaration in TestRunner
	private static $default_reporter;
	
	private static $allowed_actions = array(
		'module',
		'only',
		'coverage/module/$ModuleName' => 'coverageModule',
		'coverage' => 'coverageAll',
	);
	
	/**
	 * The list of modules we're testing. Captures info for code-coverage
	 *
	 * @var array
	 */
	protected $moduleList = array();
	
	/**
	 * Override the default reporter with a custom configured subclass.
	 *
	 * @param string $reporter
	 */
	static function set_reporter($reporter) {
		if (is_string($reporter)) $reporter = new $reporter;
		self::$default_reporter = $reporter;
	}

	function init() {
		parent::init();
		if (!self::$default_reporter) self::set_reporter(Director::is_cli() ? 'CliDebugView' : 'DebugView');
	}
	
	/**
	 * 
	 * Somewhat messy and god-method, but this override exists because
	 * 
	 * a) we want to change the configuration of items from the user-set TESTING_CONFIG 
	 * variable if it exists
	 * 
	 * b) PhpUnitWrapper is referenced with explicit class settings, rather than allowing us to 
	 * override as we like (to change code coverage behaviour). 
	 * 
	 * @global type TESTING_CONFIG
	 * @global type $TESTING_CONFIG
	 * @global type $databaseConfig
	 * @param type $classList
	 * @param type $coverage
	 * @throws Exception
	 */
	function runTests($classList, $coverage = false) {
		global $TESTING_CONFIG;

		$startTime = microtime(true);
		Config::inst()->update('Director', 'environment_type', 'dev');

		if (isset($TESTING_CONFIG['database']) && $TESTING_CONFIG['database'] != 'silverstripe_testing') {
            if (class_exists("Multisites")) {
                Multisites::inst()->resetCurrentSite();
            }
			global $databaseConfig;
			$newConfig = $databaseConfig;
			$newConfig = array_merge($databaseConfig, $TESTING_CONFIG);
			$newConfig['memory'] = isset($TESTING_CONFIG['memory']) ? $TESTING_CONFIG['memory'] : true;
			
			$newDbName = $TESTING_CONFIG['database'];
			
			$type = isset($newConfig['type']) ? $newConfig['type'] : 'MySQL';
			Debug::message("Connecting to new $type database ${TESTING_CONFIG['database']} as defined by testing config");
			DB::connect($newConfig);
			if (!DB::getConn()->databaseExists($newDbName)) {
				DB::getConn()->createDatabase($newDbName);
			}
			if (!DB::getConn()->selectDatabase($newDbName)) {
				throw new Exception("Could not find database to use for testing");
			}
			
			if ($newConfig['memory']) {
				Debug::message("Using in memory database");
			}

			$dbadmin = new DatabaseAdmin();
			if (isset($_REQUEST['clear']) && $_REQUEST['clear'] == 0) {
				
			} else {
				$dbadmin->clearAllData();
			}

			if (!(isset($_REQUEST['build']) && $_REQUEST['build'] == 0)) {
				Debug::message("Executing dev/build as requested");
				$dbadmin->doBuild(true);
			}
		}

		// XDEBUG seem to cause problems with test execution :-(
		if(function_exists('xdebug_disable')) xdebug_disable();
		
		ini_set('max_execution_time', 0);		
		
		$this->setUp();
		
		// Optionally skip certain tests
		$skipTests = array();
		if($this->request->getVar('SkipTests')) {
			$skipTests = explode(',', $this->request->getVar('SkipTests'));
		}
		
		$abstractClasses = array();
		foreach($classList as $className) {
			// Ensure that the autoloader pulls in the test class, as PHPUnit won't know how to do this.
			class_exists($className);
			$reflection = new ReflectionClass($className);
			if ($reflection->isAbstract()) {
				array_push($abstractClasses, $className);
			}
		}
		
		$classList = array_diff($classList, $skipTests);
		
		// run tests before outputting anything to the client
		$suite = new PHPUnit_Framework_TestSuite();
		natcasesort($classList);
		foreach($classList as $className) {
			// Ensure that the autoloader pulls in the test class, as PHPUnit won't know how to do this.
			class_exists($className);
			$suite->addTest(new SapphireTestSuite($className));
		}

		// Remove the error handler so that PHPUnit can add its own
		restore_error_handler();

		// CUSTOMISATION
		if (Director::is_cli()) {
			if ($reporterClass = $this->request->requestVar('reporter')) {
				$clazz = $reporterClass;
			} else if (isset($TESTING_CONFIG['reporter'])) {
				$clazz = $TESTING_CONFIG['reporter'];
			} else { 
				$clazz = "CliTestReporter";
			}
		} else {
			$clazz = "SapphireTestReporter";
		}
		// END CUSTOMISATION
		
        // CUSTOMISATION
		$outputFile = null;
		if ($TESTING_CONFIG['logfile']) {
			$outputFile = BASE_PATH . '/'. $TESTING_CONFIG['logfile'];
		}
        
		$reporter = new $clazz($outputFile);
		$default = self::$default_reporter;

		self::$default_reporter->writeHeader("Sapphire Test Runner");
		if (count($classList) > 1) { 
			self::$default_reporter->writeInfo("All Tests", "Running test cases: " . implode(",", $classList));
		} else {
			self::$default_reporter->writeInfo($classList[0], "");
		}
		
		$results = new PHPUnit_Framework_TestResult();		
		$results->addListener($reporter);

		if($coverage === true) {
			$coverer = $this->getCodeCoverage();
			$results->setCodeCoverage($coverer);
			$suite->run($results);
			$writer = new PHP_CodeCoverage_Report_HTML();
			$writer->process($coverer, Director::baseFolder() . '/test-assist/html/code-coverage-report');
		} else {
			$suite->run($results);
		}
		
		if(!Director::is_cli()) echo '<div class="trace">';
		
        if (method_exists($reporter, 'writeResults')) {
            $reporter->writeResults($outputFile);
        } else {
            $reporter->flush();
        }
		
		// END CUSTOMISATION

		$endTime = microtime(true);
		if(Director::is_cli()) echo "\n\nTotal time: " . round($endTime-$startTime,3) . " seconds\n";
		else echo "<p>Total time: " . round($endTime-$startTime,3) . " seconds</p>\n";
		
		if(!Director::is_cli()) echo '</div>';
		
		// Put the error handlers back
		Debug::loadErrorHandlers();
		
		if(!Director::is_cli()) self::$default_reporter->writeFooter();
		
		$this->tearDown();
		
		// Todo: we should figure out how to pass this data back through Director more cleanly
		if(Director::is_cli() && ($results->failureCount() + $results->errorCount()) > 0) exit(2);
	}
	
	protected function getCodeCoverage() {
		$coverage = new PHP_CodeCoverage();
		
		$filter = $coverage->filter();

		$filter->addFileToBlacklist(Director::baseFolder() .'/mysite/local.conf.php');
		$filter->addDirectoryToBlacklist(Director::baseFolder() .'/mysite/scripts');

		$modules = $this->moduleDirectories();

		foreach(TestRunner::config()->coverage_filter_dirs as $dir) {
			if($dir[0] == '*') {
				$dir = substr($dir, 1);
				foreach ($modules as $module) {
					$filter->addDirectoryToBlacklist(BASE_PATH . "/$module/$dir");
				}
			} else {
				$filter->addDirectoryToBlacklist(BASE_PATH . '/' . $dir);
			}
		}
		
		// whitelist for specific modules
		foreach ($this->moduleList as $directory) {
			$filter->addFileToBlacklist($directory .'/_config.php');
			$filter->addDirectoryToWhitelist($directory . '/code');
			$filter->addDirectoryToWhitelist($directory . '/src');
		}

		$filter->addFileToBlacklist(__FILE__, 'PHPUNIT');

		return $coverage;
	}

	/**
	 * Run tests for one or more "modules".
	 * A module is generally a toplevel folder, e.g. "mysite" or "framework".
	 * 
	 * @OVERRIDE
	 * 
	 * Over-ridden to allow selection of specific test type if specified on the command line
	 * 
	 */
	public function module($request, $coverage = false) {
		self::use_test_manifest();
		$classNames = array();
		$moduleNames = explode(',', $request->param('ModuleName'));
		
		$testClassParent = $request->getVar('test_type');
		if (!$testClassParent) {
			$testClassParent = 'SapphireTest';
		}
		
		$ignored = array('functionaltest', 'phpsyntaxtest');

		foreach($moduleNames as $moduleName) {
			$classesForModule = ClassInfo::classes_for_folder($moduleName);
			$this->moduleList[] = Director::baseFolder() . DIRECTORY_SEPARATOR . $moduleName;
			if($classesForModule) {
				foreach($classesForModule as $className) {
					if(class_exists($className) && is_subclass_of($className, $testClassParent)) {
						if(!in_array($className, $ignored))
							$classNames[] = $className;
					}
				}
			}
		}

		$this->runTests($classNames, $coverage);
	}
	
	/**
	 * Run only a single test class or a comma-separated list of tests
	 */
	public function only($request, $coverage = false) {
		self::use_test_manifest();
		if($request->param('TestCase') == 'all') {
			$this->all();
		} else {
			$testClassParent = $request->getVar('test_type');
			if (!$testClassParent) {
				$testClassParent = 'SapphireTest';
			}

			$classNames = explode(',', $request->param('TestCase'));
			foreach($classNames as $className) {
				if(!class_exists($className) || !is_subclass_of($className, $testClassParent)) {
					user_error("TestRunner::only(): Invalid TestCase '$className', cannot find matching class",
						E_USER_ERROR);
				}
			}
			
			$this->runTests($classNames, $coverage);
		}
	}

	/**
	 * @OVERRIDE
	 * 
	 * Overridden to prevent deletion of custom defined tmpdb
	 * 
	 * @global type $TESTING_CONFIG
	 */
	function tearDown() {
		global $TESTING_CONFIG;
		if (!isset($TESTING_CONFIG['database'])) {
			parent::tearDown();
		}
		if (PHP_SAPI != 'cli') {
			DB::set_alternative_database_name(null);
		}
	}

	/**
	 * Copied from PHPUnitWrapper
	 * 
	 * @return array
	 */
	protected function moduleDirectories() {
		$files = scandir(BASE_PATH);
		$modules = array();
		foreach($files as $file) {
			if(is_dir(BASE_PATH . "/$file") && (file_exists(BASE_PATH . "/$file/_config.php") || is_dir(BASE_PATH . "/$file/_config"))) {
				$modules[] = $file;
			}
		}
		return $modules;
	}
}