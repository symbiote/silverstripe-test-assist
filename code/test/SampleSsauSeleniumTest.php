<?php

/**
 * 
 *
 * @author <marcus@silverstripe.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class SampleSsauSeleniumTest extends SsauSeleniumTestCase {
	public function testCmsLogin() {
		$this->loginToCms();
		
		$this->waitForElementPresent('a.profile-link');
	}
}
