<?php

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\Dev\TestOnly;
use SilverStripe\CMS\Controllers\CMSPageEditController;
use SilverStripe\Dev\FunctionalTest;

/**
 * @package test-assist
 * @subpackage tests
 */
class TestAssistSiteTreeTest extends FunctionalTest {
	protected $usesDatabase = true;

	/**
	 * Need this off for testGetCMSFields() so if the user is redirected away
	 * from the CMS page, it's detected as a permission issue or similar.
	 */
	protected $autoFollowRedirection = false;

	protected $pages = array();

	protected $oldErrorLevel;

	public function setUpOnce() 
	{
		parent::setUpOnce();

		// Error levels (ensure we catch strict / notice errors)
		$this->oldErrorLevel = error_reporting(E_ALL & ~(E_DEPRECATED));
	}

	public function setUp()
	{
		parent::setUp();

		// Create pages / objects as ADMIN
		if (!$this->pages) {
			// NOTE(Jake): Allow use of "Controller::curr()" in onBeforeWrite.
			$stubController = new Controller;
			$stubController->setRequest(new HTTPRequest(
				'POST', 
				'', 
				// Get vars
				array(
				), 
				// Post Vars
				array(
					// NOTE(Jake): I'm doing this as MediaAttribute expects `url` to be set in onBeforeWrite
					'url' => ''
				)
			));
            $stubController->pushCurrent();
			$this->logInWithPermission('ADMIN');

			$pagesTypes = ClassInfo::subclassesFor(SiteTree::class);

			// Support Multisites
			if (class_exists('Site')) {
				$site = Site::create();
				$site->Title = 'Site';
				$site->IsDefault = true;
				$site->write();
				$site->doPublish();
				unset($pagesTypes['Site']);
			}

			// Support MediaAwesome

			// NOTE(Jake): This currently gets "Invalid MediaHolder" error. Further fixes required
			//			   to support this module.

			/*if (class_exists('MediaAttribute')) {
				singleton('MediaAttribute')->requireDefaultRecords();
			}
			if (class_exists('MediaType')) {
				singleton('MediaType')->requireDefaultRecords();
			}
			if (class_exists('MediaHolder') && isset($pagesTypes['MediaHolder'])) {
				$page = MediaHolder::create();
				$page->Title = $page->class.' Test Page';
				$page->write();
				$page->doPublish();
				$mediaHolderID = $page->ID;
				$this->pages[] = $page;
			}
			if (class_exists('MediaPage') && isset($pagesTypes['MediaPage']) && $mediaHolderID) {
				$page = MediaPage::create();
				$page->Title = $page->class.' Test Page';
				$page->ParentID = $mediaHolderID;
				$page->write();
				$page->doPublish();
				$this->pages[] = $page;
			}*/
			unset($pagesTypes['MediaHolder']);
			unset($pagesTypes['MediaPage']);

			// Setup ErrorPage
			if (isset($pagesTypes[ErrorPage::class])) {
				// 404 Page
				$page = ErrorPage::create();
				$page->Title = $page->class.' Test 404 Page';
				$page->ErrorCode = 404;
				$page->write();
				$page->doPublish();
				$this->pages[] = $page;

				// 500 Page
				$page = ErrorPage::create();
				$page->Title = $page->class.' Test 404 Page';
				$page->ErrorCode = 500;
				$page->write();
				$page->doPublish();
				$this->pages[] = $page;
				unset($pagesTypes[ErrorPage::class]);
			}

			// User should not be able to create SiteTree objects.
			unset($pagesTypes[SiteTree::class]);

			// Workaround pages that didn't implement 'TestOnly'
			unset($pagesTypes['SitemapPageTest_Unviewable']); // Sitemap module

			// Don't bother testing framework pages
			unset($pagesTypes[RedirectorPage::class]);
			unset($pagesTypes[VirtualPage::class]);

			// Handle the rest
			foreach ($pagesTypes as $class) {
				if (ClassInfo::classImplements($class, TestOnly::class)) {
					continue;
				}
				// ie. Ignore CalendarEvent
				if (!$class::config()->can_be_root) {
					continue;
				}
				$page = $class::create();
				$page->Title = $class.' Test Page';
				if ($page instanceof HomePage) {
					$page->URLSegment = 'home';
				}
				$page->write();
				$page->doPublish();
				$this->pages[] = $page;
			}

			// Revert
			$this->logInAs(0);
			$stubController->popCurrent();
		}
	}

	public function tearDown() 
	{
		parent::tearDown();

		// Revert to default error level
		error_reporting($this->oldErrorLevel);
	}

	public function testGetCMSFields() 
	{
		$editPageLink = singleton(CMSPageEditController::class)->Link('show');

		// Required to have permission to view the page
		$this->logInWithPermission('ADMIN');

		foreach ($this->pages as $page) {
			// Visit page in CMS
			$response = $this->get(Controller::join_links($editPageLink, $page->ID));
			$statusCode = $response->getStatusCode();
			$this->assertEquals(200, $statusCode, $statusCode == 302 ? 'User probably doesn\'t have permission (canView()) #'.$page->ID.' '.$page->ClassName.'.' : '');
		}

		// Logout
		$this->logInAs(0);
	}

	// NOTE(Jake): When tested against a Symbiote project, I was getting: "LogicException: No ListFilterSet configured on Page #2"
	//			   Not sure how to naively create pages for special cases... might need a special fixture setup?
	//
	/*public function testSiteTreeFrontend() 
	{
		$editPageLink = singleton('CMSPageEditController')->Link('show');

		foreach ($this->pages as $page) {
			// Visit page in CMS
			$class = get_class($page);
			$response = $this->get($page->Link());
			$this->assertEquals(200, $response->getStatusCode(), $class.' should return 200.');
			$this->assertNotEquals('', $response->getBody(), $class.' should return a non-empty response in body.');
		}
	}*/
}
