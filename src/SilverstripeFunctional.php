<?php

namespace Symbiote\TestAssist;

use Codeception\Lib\ModuleContainer;
use Codeception\Module;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class SilverstripeFunctional extends Module
{
    public $I;

    /**
     * These can be set depending on better buttons or not
     *
     * @var string
     */
    protected $saveButton = 'action_doSave';

    public function __construct(ModuleContainer $moduleContainer, $config = null)
    {
        // var_dump($moduleContainer->getModule('WebDriver')->_getConfig());
        //$this->I = $moduleContainer->getModule("WebDriver");
        parent::__construct($moduleContainer, $config);
        $this->I = $this->moduleContainer->getModule("WebDriver");

        if (isset($config['use_better_buttons']) && $config['use_better_buttons']) {
            $this->saveButton = 'action_save';
        }
    }
    
    public function getModuleReference($module)
    {
        $m = $this->moduleContainer->getModule($module);
        return $m;
    }

    public function haveLoginDetailsFor($user = null)
    {
        $u = "{$user}_user";
        $p = "{$user}_pass";

        $config = $this->_getConfig();

        if (isset($config[$u]) && isset($config[$p])) {
            return true;
        }
        return false;
    }

    public function loginToPage($page = "/", $user = 'user') {
        $u = "{$user}_user";
        $p = "{$user}_pass";

        $config = $this->_getConfig();
        if (!isset($config[$u])) {
            $me = get_class($this);
            throw new \Exception("Please specify modules.config.$me.$u in your local codeception.yml");
        }

        $username = $config[$u];
        $password = $config[$p];

        $this->I->amOnPage('/Security/login?BackURL=' .urlencode($page));

        $this->I->see('Log in');
        $this->loginWith($username, $password);
    }

    public function loginToAdminAs($user = null)
    {
        $u = "{$user}_user";
        $p = "{$user}_pass";

        $config = $this->_getConfig();

        $username = $config[$u];
        $password = $config[$p];

        // return array('user'=>$username, 'pass'=>$password);
        $this->I->amOnPage('/admin');
        $this->I->see('Log in');
        $this->loginWith($username, $password);
    }

    public function canVerify($element)
    {
        try {
            $this->I->seeElement($element);
        } catch (\PHPUnit_Framework_AssertionFailedError $f) {
            return false;
        }
        return true;
    }

    public function canVerifyInDOM($element)
    {
        try {
            $this->I->seeElementInDOM($element);
            // } catch (\PHPUnit_Framework_AssertionFailedError $f) {
        } catch (\Exception $f) {
            return false;
        }
        return true;
    }

    public function clickCMSMenuItemFor($item, $subItem = null)
    {
        // cms-menu-list
        // $this->I->click(['xpath' => $chosenXPath."/div/ul/li[contains(.,'{$contains}')][{$index}]"]);
        // <li class="children current opened" id="Menu-PTV" title="PTV">

        if ($this->canVerify(['xpath' => "//ul[contains(@class,'cms-menu-list')]/li[not(contains(@class,'opened'))][@title='{$item}']"])) {
            $this->I->click(['xpath' => "//ul[contains(@class,'cms-menu-list')]/li[not(contains(@class,'opened'))][@title='{$item}']"]);
        }

        if ($subItem == null) {
            return;
        }

        $this->I->click(['xpath' => "//ul[contains(@class,'cms-menu-list')]/li[@title='{$item}']/ul/li/a/span[contains(@class,'text')][contains(.,'{$subItem}')]"]);
    }

    public function getElements($css)
    {
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
        $this->I->click("action_doLogin");
    }

    public function logout()
    {
        $this->I->amOnPage('Security/logout');
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
        $i        = 0;
        $selector = str_replace("'", "\\'", $selector);
        while ($this->I->executeJS("return jQuery('$selector').length > 0;") == false) {
            if ($i >= $maxTime) {
                throw new \Exception("Could not find $selector"); 
            }
            sleep(1);
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
        while ($this->I->executeJS("return jQuery('$selector').is(':visible');")) {
            if ($i >= $maxTime) {
                throw new \Exception("$selector is still visible");
            }
            sleep(1);
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
        while ($this->I->executeJS("return !jQuery('$selector').is(':visible');")) {
            if ($i >= $maxTime) {
                throw new \Exception("$selector did not appear");
            }
            sleep(1);
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
        // $chosenXPath = "//div[contains(@class,'chosen-container')][contains(@id, '{$fieldName}_chosen')]";
        $chosenXPath = "//div[contains(@id, '{$fieldName}_chzn')][contains(@class,'chzn-container')]";
        $this->I->click(['xpath' => $chosenXPath]);
        $this->waitForAjax();
        $this->waitForChosenDropdown($fieldName);

        if (is_int($contains)) {
            $this->I->click(['xpath' => $chosenXPath."/div/ul/li[$contains]"]);
        } else {
            if ($index !== 0) {
                $this->I->click(['xpath' => $chosenXPath."/div/ul/li[contains(.,'{$contains}')][{$index}]"]);
            } else {
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
        $this->waitForElementToAppear("div[id*=\"{$name}_chzn\"] ul.chzn-results li");
    }

    /**
     * Repeatedly perform some function until the given selector doesn't exist in the page any more
     *
     * @param string $selector
     * @param closure $callback
     */
    public function doUntilNoMore($selector, $callback)
    {
        $done    = false;
        $timeout = 3;

        for ($second = 0;; $second++) {
            if ($second >= $timeout) {
                $done = true;
                break;
            }

            if ($this->canVerifyInDOM($selector)) {
                break;
            }
            usleep(300);
        }

        // our recursion breaker
        if ($done) {
            return true;
        }

        // execute whatever it is we're trying to do
        $callback($this, $selector);
        $this->waitForAjax();

        // and execute again
        return $this->doUntilNoMore($selector, $callback);
    }

    public function openModelAdmin($controller)
    {
        $this->I->click(['css' => '#Menu-$controller > a > span.text']);
        $this->waitForElement("div.cms-content.$controller");
        $this->waitForElement("div#cms-content-tools-ModelAdmin");
    }

    public function clickModelAdminTab($dataType)
    {
        $this->I->click(['css' => ".cms-tabset-nav-primary li[class*='$dataType'] a"]);
        $this->waitForElement("form[action$='$dataType/EditForm']");
    }

    public function clickModelAdminRootTab($tab)
    {
        $this->I->click(['xpath' => '//div[@id="Root"]/ul/li/a[@id="tab-Root_'.$tab.'"]']);
        $this->waitForElement('.ui-state-active #tab-Root_'.$tab.'');
    }

    public function clickModelAdminAddButton()
    {
        $this->I->click(['css' => "div.ss-gridfield-buttonrow-before a[data-icon=add]"]);
        $this->waitForElement('#Form_ItemEditForm_SecurityID');
    }

    public function clickModelAdminSaveButton()
    {
        $this->waitForElement("button[name=$this->saveButton]");

        $this->I->click(['css' => "button[name=$this->saveButton]"]);
        usleep(200000);
        // waiting for ajax request to
        $this->waitForElement('p.message.good:contains("Saved")');

        // remove that element!
        $this->I->executeJS("jQuery('p.message.good:contains(\"Saved\")').remove();");
    }

    public function clickModelAdminCreateButton()
    {
        $this->I->click(['xpath' => '//button[@type="submit"][@name="action_doAdd"]/span[@class="ui-button-text"][contains(.,"Create")]']);
        // waiting for ajax request to
        $this->waitForAjax();
    }

    public function clickModelAdminDeleteButton()
    {
        $this->I->click(['css' => 'button[name=action_doDelete]']);
        $this->waitForAjax();
    }

    public function clickModelAdminPublishButton()
    {
        $this->I->click(['xpath' => '//button[@type="submit"][@name="action_publish"]']);
        $this->waitForAjax();
    }

    public function upload($fieldName, $filePath)
    {
        $xpath = "//div[contains(@class,'ss-uploadfield')][label[contains(.,'{$fieldName}')]]/div/div/div/label[contains(@class,'ss-uploadfield-fromcomputer')]/span/input";
        $this->I->attachFile($xpath, $filePath);
    }

    /**
     * Creates the given hierarchy, skipping pages that already exist. 
     * 
     * @param type $hierarchyStr
     */
    public function createPageHierarchy($hierarchyStr, $depth = 0)
    {
        // short circuit
        if ($depth > 5) {
            throw new Exception("Probably a loop");
        }

        $this->I->amOnPage('admin/pages/');
        $this->waitForAjax();

        $pageAndTypes = explode('/', $hierarchyStr);

        $parentPage = null;

        $pagesHierarchy = [];

        foreach ($pageAndTypes as $pt) {
            $bits  = explode('|', $pt);
            $title = $bits[0];
            $type  = isset($bits[1]) ? $bits[1] : 'Page';

            $path = '//li[a/span[contains(@class,"text")]/span[contains(@class,"item")][contains(.,"'.$title.'")]]';

            $good = false;
            
            if ($this->canVerify(['xpath' => $path])) {
                // can we expand?
                $good = true;
                if ($this->canVerify(['xpath' => $path . '[contains(@class,"jstree-closed")]'])) {
                    $this->I->click(['xpath' => $path . '/ins']);
                    $this->waitForAjax();
                }
            }

            if (!$good) {
                // need to create
                $this->createPage($type, $title, $parentPage);
                // try again
                return $this->createPageHierarchy($hierarchyStr, $depth + 1);
            }

            $this->I->click(['xpath' => $path]);
            $this->waitForAjax();

            $parentPage = $title;
            $pagesHierarchy[] = $title;
        }
    }

    public function createPage($pageType, $pageTitle, $parentPage = null)
    {
        // add new or add page?
        if ($this->canVerify(['css' => 'a.cms-content-addpage-button'])) {
            $this->I->click(['css' => 'a.cms-content-addpage-button']);
        } else {
            $this->I->click(['xpath' => "//div[@class='cms-actions-row']/a[contains(@data-icon,'add')]/span[contains(.,'Add new')]"]);
        }
        
        $this->waitForAjax();

        if ($parentPage) {
            $last = '';
            if (is_string($parentPage)) {
                $parentPage = [$parentPage];
            }

            $last = array_pop($parentPage);

            if (!$this->canVerify(['xpath' => '//span[@class="treedropdownfield-title"]/span[contains(.,"'.$last.'")]'])) {
                $this->I->click(['xpath' => '//span[@class="treedropdownfield-title"]']);

                $this->waitForAjax();

                foreach ($parentPage as $title) {
                    if ($this->canVerify(['xpath' => '//li[contains(@class,"jstree-closed")][a/span[@class="item"][contains(.,"'.$title.'")]]'])) {
                        $clickPath = '//li[contains(@class,"jstree-closed")][a/span[@class="item"][contains(.,"'.$title.'")]]/ins';
                        $this->I->click(['xpath' => $clickPath]);
                        $this->waitForAjax();
                    }
                }

                $clickPath = '//li/a/span[contains(@class,"item")][contains(.,"'.$last.'")]';
                $this->I->click(['xpath' => $clickPath]);
                $this->waitForAjax();
            }
        }

        $this->I->click(['xpath' => '//input[@name="PageType"][@value="'.$pageType.'"]']);
        $this->clickModelAdminCreateButton();

        $this->I->fillField('Title', $pageTitle);
        $this->waitForAjax();
        usleep(500);

        $this->clickModelAdminSaveButton();
    }

    public function populatePage($fieldData = array())
    {
        /*
          $fieldData = [
          FieldName => [
          'Type' => FieldType,
          'Value' => FieldValue
          ]
          ]
         */
        if (count($fieldData) > 0) {
            foreach ($fieldData as $field => $data) {
                if (!isset($data['Type'])) {
                    continue;
                }

                switch (strtolower($data['Type'])) {
                    case "radio":
                        $this->I->selectOption('form input[name='.$field.']', $data['Value']);
                        break;
                    case "text":
                    default:
                        $this->I->fillField($field, $data['Value']);
                        break;
                }

                // Just make sure that we're don't really encounter any race issues and such with trying to access fields
                // which aren't available yet
                $this->waitForAjax();
                usleep(500);
            }
        }
    }

    /**
     * Opens a page in the site tree. Takes either a string for a top level page, or an array
     * of pages to expand.
     * @param string|array $titlePath
     * @param boolean $returnNotException
     *              if you only want details of the page that _wasn't_ found, pass in true. 
     * @return void
     */
    public function openPageInSiteTree($titlePath, $returnNotException = false)
    {
        $this->I->amOnPage('admin/pages/');
        $this->waitForAjax();

        if (is_string($titlePath)) {
            $path = '//li/a/span[contains(@class,"text")]/span[contains(@class,"item")][contains(.,"'.$titlePath.'")]';
            if ($this->canVerify(['xpath' => $path])) {
                $this->I->click(['xpath' => $path]);
                return;
            }
            throw new \Exception('Unable to find page');
        }

        if (!is_array($titlePath)) {
            throw new \Exception('Open Page expects a string (the title of the page) or an array of nested page titles');
            return;
        }

        $actualPage = array_pop($titlePath);

        foreach ($titlePath as $title) {
            $pathToNode = '//li[a/span[contains(@class,"text")]/span[contains(@class,"item")][contains(.,"'.$title.'")]]';
            if ($this->canVerify(['xpath' => $pathToNode . '[contains(@class,"jstree-closed")]'])) {
                $clickPath = $pathToNode . '/ins';


                $this->I->click(['xpath' => $clickPath]);
                $this->waitForAjax();
            } 
        }

        $clickPath = '//li/a/span[contains(@class,"text")]/span[contains(@class,"item")][contains(.,"'.$actualPage.'")]';
        if (!$returnNotException) {
            $this->I->click(['xpath' => $clickPath]);
        }

        $this->waitForElementToAppear('#Form_EditForm_Title');
    }
}
