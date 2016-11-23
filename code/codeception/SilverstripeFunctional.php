<?php
namespace ssautesting\Helper;

use Codeception\Lib\ModuleContainer;
// here you can define custom actions
// all public methods declared in helper class will be available in $I

class SilverstripeFunctional extends \Codeception\Module
{
	public function __construct(ModuleContainer $moduleContainer, $config = null)
	{
		//$this->I = $moduleContainer->getModule("WebDriver");
		parent::__construct($moduleContainer, $config);
		$this->I = $this->moduleContainer->getModule("WebDriver");
	}

    
    public function getElements($css) {
        return $this->I->_findElements($css);
    }

	/**
	 * Alias method for nice readability
	 *
	 * @return void
	 */
	public function amOnHome()
	{
		$this->I->amOnPage('/');
	}

	/**
	 * Alias method for nice readability
	 *
	 * @return void
	 */
	public function amAtHome()
	{
		$this->amOnHome();
	}

	/**
	 * Wrapper method to navigate to /admin and login.
	 *
	 * @param string $email 
	 * @param string $password 
	 * @return void
	 */
	public function loginToAdmin($email, $password)
	{
		$this->I->amOnPage('/admin');
		$this->I->see('Log In');
		$this->loginWith("admin", "admin");
	}

	/**
	 * Performs a log in. By defafult it will look for specific fields with attributes name=Email
	 * and name=Password.
	 *
	 * @param string $email 
	 * @param string $password 
	 * @return void
	 */
	public function loginWith($email, $password)
	{
		$this->I->fillField('Email', $email);
		$this->I->fillField('Password', $password);
		$this->I->click("action_dologin");
	}

	/**
	 * Waits for jQuery to load. This prevents certain calls being invalid due to jQuery being undefined.
	 *
	 * @param int $maxTime - The maximum wait time
	 * @return void
	 */
	public function waitForJQuery($maxTime = 10)
	{
		$this->I->waitForJS("return (jQuery !== undefined)", $maxTime);
	}

	/**
	 * Waits for jQuery to not be active. This is done when data is being retrieved or posted via
	 *
	 * @param int $maxTime - The maximum wait time
	 * @return void
	 */
	public function waitForAjax($maxTime = 10)
	{
		$this->I->waitForJS('return jQuery.active == 0', $maxTime);
	}

	/**
	 * Wait for an element to be rendered in the DOM. Can be used in tandem with @method waitForElementToAppear
	 *
	 * @param string $selector - The CSS selector to look for.
	 * @param int $maxTime - The maximum wait time
	 * @return void
	 */
	public function waitForElement($selector, $maxTime = 10)
	{
		$i = 0;
		while($this->I->executeJS("return jQuery('$selector').length > 0;") == false)
		{
			if($i >= $maxTime) { return false; }
			sleep(2);
			$i++;
		}
		return true;
	}

	/**
	 * Waits for an element to disappear. Will return a boolean value dpeending on whether or not
	 * the element is no longer visible in the browser so the developer can handle situations
	 * accordingly.
	 *
	 * @param string $selector - The CSS selector to look for
	 * @param int $maxTime - The maximum wait time
	 * @return void
	 */
	public function waitForElementToDisappear($selector, $maxTime = 10)
	{
		$i = 0;
		while($this->I->executeJS("return jQuery('$selector').is(':visible');")) {
			if($i >= $maxTime) { return false; }
			sleep(2);
			$i++;
		}
		return true;
	}

	/**
	 * Waits for an element to appear. Will return a boolean value depending on whether or not the
	 * element is visible, in order for the developer to handle situations accordingly.
	 *
	 * @param type $selector - The CSS selector to look for
	 * @param int $maxTime - The maximum wait time
	 * @return bool
	 */
	public function waitForElementToAppear($selector, $maxTime = 10)
	{
		$i = 0;
		while($this->I->executeJS("return !jQuery('$selector').is(':visible');")) {
			if($i >= $maxTime) { return false; }
			sleep(2);
			$i++;
		}
		return true;
	}

	/**
	 * Halt executing thread for a specified quantity of seconds
	 *
	 * @param int $seconds 
	 * @return void
	 */
	public function sleep($seconds)
	{
		sleep($seconds);
	}

	/**
	 * Click the given option from the chosen dropdown. The index starts at 0,
	 * so the 10th element in the list would be element number 9. The first element
	 * would be element number 0.
	 *
	 * @param mixed[int|string] $index 
	 * @param int $index 
	 * @return void
	 */
	public function clickChosenDropdown($fieldName, $contains, $index = 0)
	{
		$chosenXPath = "//div[contains(@class,'chosen-container')][contains(@id, '{$fieldName}_chosen')]";
		$this->I->click(['xpath' => $chosenXPath]);
		$this->waitForAjax();
		$this->waitForChosenDropdown("$fieldName");

		if(is_int($contains)) {
			$this->I->click(['xpath' => $chosenXPath."/div/ul/li[$contains]"]);
		}
		else {
			if($index !== 0) {
				$this->I->click(['xpath' => $chosenXPath."/div/ul/li[contains(.,'{$contains}')][{$index}]"]);
			}
			else {
				$this->I->click(['xpath' => $chosenXPath."/div/ul/li[contains(.,'{$contains}')]"]);
			}
		}
	}

	/**
	 * Waits for the chosen dropdown to appear
	 *
	 * @param string $name 
	 * @return void
	 */
	public function waitForChosenDropdown($name)
	{
		$this->waitForElementToAppear("div[id*=\"{$name}_chosen\"] ul.chosen-results li");
	}
}
