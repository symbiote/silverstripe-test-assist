<?php

/**
 * 
 * @author <marcus@symbiote.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class SymbioteSeleniumTestCase extends PHPUnit_Extensions_SeleniumTestCase {
	
	public static $protected_urls = array();
	
	/**
	 * We need to disabling backing up of globals to avoid overriding
	 * the few globals SilverStripe relies on, like $lang for the i18n subsystem.
	 * 
	 * @see http://sebastian-bergmann.de/archives/797-Global-Variables-and-PHPUnit.html
	 */
	protected $backupGlobals = FALSE;
	
	private static $test_user = 'admin';
	private static $test_pass = 'admin';
	private static $test_browser = 'firefox';
	private static $test_url = 'http://localhost/silverstripe';
	
	protected $productionSafe = true;
	
	private $user;
	private $pass;

	protected function setUp() {
		// testing a remote system, so assume user/pass is provided
		if (isset($_GET['selenium_host'])) {
			$this->setHost($_GET['selenium_host']);
		}
		
		$this->user = isset($_GET['user']) ? $_GET['user'] : Config::inst()->get('SymbioteSeleniumTestCase', 'test_user');
		$this->pass = isset($_GET['pass']) ? $_GET['pass'] : Config::inst()->get('SymbioteSeleniumTestCase', 'test_pass');
		
		$browser = isset($_GET['browser']) ? $_GET['browser'] : Config::inst()->get('SymbioteSeleniumTestCase', 'test_browser');
		$url = isset($_GET['test_url']) ? $_GET['test_url'] : Config::inst()->get('SymbioteSeleniumTestCase', 'test_url');

		$this->setBrowser($browser);
		$this->setBrowserUrl($url);

		if (!$this->productionSafe) {
			// do a few checks to ensure we're okay to go ahead. 
			foreach (self::$protected_urls as $protected) {
				if (strpos($url, $protected) !== false) {
					$this->markTestSkipped();
					return;
				}
			}
		}
	}
	
	
	protected function waitForElementPresent($css, $timeout = 10) {
		if (strpos($css, 'css=') === false && strpos($css, 'id=') === false && strpos($css, 'name=') === false) {
			$css = "css=$css";
		}

		for ($second = 0;; $second++) {
			if ($second >= $timeout) {
				return $this->fail("timeout");
			}
			try {
				if ($this->isElementPresent($css)) {
					break;
				}
			} catch (Exception $e) {
				
			}
			sleep(1);
		}
	}


	/**
	 * Repeatedly perform some function until the given selector doesn't exist in the page any more
	 * 
	 * @param string $selector
	 * @param closure $callback
	 */
	protected function doUntilNoMore($selector, $callback) {
		$done = false;
		$timeout = 5;
		
		for ($second = 0;; $second++) {
			if ($second >= $timeout) {
				$done = true;
				break;
			}
			try {
				if ($this->isElementPresent($selector)) {
					break;
				}
			} catch (Exception $e) {
				
			}
			usleep(300); 
		}
		
		// our recursion breaker
		if ($done) {
			return true;
		}
		
		// execute whatever it is we're trying to do
		$callback($this, $selector);

		// and execute again
		return $this->doUntilNoMore($selector, $callback);
	}
	
	public function loginToCms($user = null, $pass = null) {
		if (!$user) {
			$user = 'admin';
		}
		$this->loginTo('admin/pages', $user, $pass);
		$this->waitForElementPresent('.cms-content-header-info');
		// element present 'cms-content-header-info'
	}
	
	public function loginTo($url, $user = null, $pass = null) {
		$encoded = urlencode($url);
		
		if (!$pass) {
			$dets = $this->getUserDetails($user);
			if (!$dets) {
				throw new Exception("User $user not provided");
			}
			$user = $dets['user'];
			$pass = $dets['pass'];
		}

		$user = $user ? $user : $this->user;
		$pass = $pass ? $pass : $this->pass;
			
		$this->open("Security/login?BackURL=$encoded");
		$this->type("id=MemberLoginForm_LoginForm_Email", $user);
		$this->type("id=MemberLoginForm_LoginForm_Password", $pass);
		$this->click("id=MemberLoginForm_LoginForm_action_dologin");
		$this->waitForPageToLoad("15000");
		$this->open($url);
	}
	
	protected function getUserDetails($user) {
		$u = "{$user}_user";
		$p = "{$user}_pass";
		
		$username = isset($_GET[$u]) ? $_GET[$u] : Config::inst()->get('SymbioteSeleniumTestCase', $u);
		$password = isset($_GET[$p]) ? $_GET[$p] : Config::inst()->get('SymbioteSeleniumTestCase', $p);
		
		if (!$username) {
			return false;
		}
		return array('user'=>$username, 'pass'=>$password);
	}
	
	public function logout() {
		$this->open('Security/logout');
	}
	
	protected function getEditObjectID() {
		$editUrl = $this->getLocation();
		$idValue = $this->getEval(" '" . $editUrl . "'.substring('" . $editUrl . "'.indexOf('item/')+5, '" . $editUrl . "'.length); ");
		return $idValue;
	}
	
	protected function getAttributeValue($selector, $attribute) {
		return $this->getEval('window.jQuery("' . $selector . '").attr("' . $attribute . '");');
	}


	////
	// MODEL ADMIN
	////

	protected function openModelAdmin($controller) {
		$this->click("css=#Menu-$controller > a > span.text");
		$this->waitForElementPresent("div.cms-content.$controller");
		$this->waitForElementPresent("div#cms-content-tools-ModelAdmin");
	}
	
	protected function modelAdminTab($dataType) {
		$this->click("css=li.tab-$dataType a");
		$this->waitForElementPresent("form[action$='$dataType/EditForm']");
	}
	
	protected function filterModelAdmin($textOptions = array(), $selectOptions = array(), $listOptions = array()) {
		$this->waitForElementPresent('#Form_SearchForm_action_search');
		
		foreach ($textOptions as $field => $value) {
			$this->typeKeys("id=Form_SearchForm_q-$field", $value);
		}
		
		foreach ($selectOptions as $field => $value) {
			$this->selectChosenList($field, $value, true, 'SearchForm_q', '-'); 
		}
		
		foreach ($listOptions as $field => $value) {
			$this->selectChosenList($field, $value, false, 'SearchForm_q', '-');
		}

		$this->clickAndWait('css=#Form_SearchForm_action_search');
	}
	
	protected function modelAdminSelectRow($col, $content) {
		$this->clickAt("css=td.col-$col:contains($content)");
		$this->waitForElementPresent('#Form_ItemEditForm_SecurityID');
	}

	protected function modelAdminAdd() {
		$this->click("css=div.ss-gridfield-buttonrow-before a[data-icon=add]");
		$this->waitForElementPresent('#Form_ItemEditForm_SecurityID');
	}

	protected function modelAdminSave() {
		$this->click('css=button[name=action_doSave]');
		// waiting for ajax request to 
		$this->waitForElementPresent('css=p.message.good:contains("Saved")');
		
		// remove that element! 
		$this->runScript("jQuery('p.message.good:contains(\"Saved\")').remove();");
	}
	
	protected function modelAdminDelete() {
		$this->click('css=button[name=action_doDelete]');

		$deleteConfirmed = $this->getConfirmation();
		$baseSsDelete = $this->getConfirmation();
	}
	
	protected function enterText($field, $value) {
		$fieldSelector = "css=div#$field input[type=text]";
		$this->type($fieldSelector, $value);
	}

	protected function selectChosenList($field, $entry, $single = true, $form = 'ItemEditForm', $fieldSep = '_') {
		$selector = $single ? 'a.chzn-single' : 'ul.chzn-choices';
		
		$fieldSelector = "#Form_{$form}{$fieldSep}{$field}_chzn";

		$this->clickAt("css=$fieldSelector $selector", "");
		$this->clickAt("css=$fieldSelector div.chzn-drop li:contains($entry)", "");
	}

	protected function clearCheckboxSet($field) {
		$div = "div#$field";
		
		$func = function ($testCase, $selector) {
			// click at the selector, which _should_ deselect it
			$testCase->click($selector);
		};

		$this->doUntilNoMore("css=$div input:checked", $func);
	}
	
	/**
	 * Deselects all items, then selects the specified items
	 * 
	 * @param string $field
	 * @param array $itemLabels
	 */
	protected function selectCheckboxSet($field, $itemLabels, $clearFirst = true) {
		if ($clearFirst) {
			$this->clearCheckboxSet($field);
		}
		
		$div = "div#$field";
		
		foreach ($itemLabels as $label) {
			$this->clickAt("css=$div li label:contains($label)");
		}
	}

	////
	// SECURITY THINGS
	///
	
	
	protected function deleteUser($emailAddress) {
		$this->open('admin/security');
		$this->click('css=th.col-Actions button');
		$this->waitForElementPresent('css=#filter-Members-Email');
		
		$this->type('css=#filter-Members-Email', $emailAddress);
		$this->click('css=#action_filter_Member_Actions');
		sleep(1);
		
		if ($this->isElementPresent('css=td.col-Email:contains(' . $emailAddress .')')) {
			$this->chooseOkOnNextConfirmation();
			$this->click('css=#Form_EditForm_Members button.gridfield-button-delete');
			$this->getConfirmation();
		}
		sleep(1);
	}
	
	protected function createUser($user, $email, $pass, $group = null, $fields = null) {
		$this->open('admin/security');
		$this->click("css=div.ss-gridfield-buttonrow-before a[data-icon=add]");
		$this->waitForElementPresent('#Form_ItemEditForm_SecurityID');
		
		list($first, $last) = explode(' ', $user);
		$this->type('css=#Form_ItemEditForm_FirstName', $first);
		$this->type('css=#Form_ItemEditForm_Surname', $last);
		$this->type('css=#Form_ItemEditForm_Email', $email);
		$this->type('css=#Password-_Password', $pass);
		$this->type('css=#Password-_ConfirmPassword', $pass);
		
		if ($group) {
			$this->clickAt('css=#Form_ItemEditForm_DirectGroups_chzn');
			$this->clickAt('css=#Form_ItemEditForm_DirectGroups_chzn li:contains(' . $group . ')');
		}
		
		$this->click('css=#Form_ItemEditForm_action_doSaveAndQuit');
		$this->waitForElementPresent('css=#Form_EditForm_HeaderFieldImport-users');
	}
	
	protected function createGroup($groupName) {
		$this->open('admin/security');
		$this->click('css=a.ui-tabs-anchor:contains(Groups)');
		
		if (!$this->isElementPresent('css=td.col-Breadcrumbs:contains(' . $groupName . ')')) {
			$this->click("css=div.ss-gridfield-buttonrow-before a[data-icon=add]:contains('Add Group')");
			$this->waitForElementPresent('css=#Form_ItemEditForm_Title');
			$this->type('css=#Form_ItemEditForm_Title', $groupName);
			$this->click('css=#Form_ItemEditForm_action_doSaveAndQuit');
			$this->waitForElementPresent('css=#Form_EditForm_HeaderFieldImport-groups');
		}
	}
	
	
	////
	// PAGES THINGS
	////
	
//	protected function 
	
	protected function openPage($titlePath, $from = null) {
		if (!$from) {
			$this->open('admin/pages/');
		}
		
		$this->waitForElementPresent('css=ul.jstree-no-checkboxes');
		
		if ($this->isElementPresent('css=ul.jstree-no-checkboxes li.Root.jstree-closed')) {
			$this->click('css=ul.jstree-no-checkboxes li.Root.jstree-closed > ins.jstree-icon');
			usleep(200000);
		}
		
		if ($this->isElementPresent('css=li.class-Site.jstree-closed')) {
			$this->click('css=li.class-Site.jstree-closed > ins.jstree-icon');
			usleep(200000);
		}
		
		
		$segments = explode('/', $titlePath);
		$last = array_pop($segments);
		if (count($segments)) {
			foreach ($segments as $seg) {
				// find and open them
				if ($this->isElementPresent("css=li.jstree-closed a:contains($seg)")) {
					$this->click("css=li.jstree-closed a:contains($seg) > ins.jstree-icon");
					usleep(200000);
				}
			}
		}
		
		if ($this->isElementPresent("css=div.jstree li a:contains($last)")) {
			$this->click("css=div.jstree li a:contains($last)");
			$this->waitForElementPresent('css=#Form_EditForm_Title');
			$this->waitForValue("css=#Form_EditForm_Title", $last);
			return true;
		}
		return false;
	}
}
