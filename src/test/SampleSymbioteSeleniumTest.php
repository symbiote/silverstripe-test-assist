<?php

/**
 * php framework/cli-script.php dev/tests/SampleSymbioteSeleniumTest flush=1 build=1 selenium_host=127.0.0.1 browser=firefox 
 * test_url=http://testing.demos.dev/ test_type=SymbioteSeleniumTestCase SkipTests=symbioteseleniumtestcase admin_user=admin admin_pass=admin
 *
 * @author <marcus@symbiote.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class SampleSymbioteSeleniumTest extends SymbioteSeleniumTestCase {
	public function testCmsLogin() {
		$this->loginToCms();
		
		$this->waitForElementPresent('a.profile-link');
	}
}
