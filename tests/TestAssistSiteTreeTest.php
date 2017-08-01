<?php

/**
 * @package test-assist
 * @subpackage tests
 */
class TestAssistSiteTreeTest extends FunctionalTest {
	protected $pages = array();

	protected $usesDatabase = true;

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

		$pageCount = SiteTree::get()->count();

		// If pages are already in the DB, use those instead.
		if ($pageCount > 0) {
			// todo(Jake): Allow config to exclude certain page types
			$this->pages = SiteTree::get();
		}

		// Create pages / objects as ADMIN
		if (!$this->pages && $pageCount == 0) {
			// NOTE(Jake): Allow use of "Controller::curr()" in onBeforeWrite.
			$stubController = new Controller;
			$stubController->setRequest(new SS_HTTPRequest(
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

			$pagesTypes = ClassInfo::subclassesFor('SiteTree');

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
			if (isset($pagesTypes['ErrorPage'])) {
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
				unset($pagesTypes['ErrorPage']);
			}

			// User should not be able to create SiteTree objects.
			unset($pagesTypes['SiteTree']);

			// Workaround pages that didn't implement 'TestOnly'
			unset($pagesTypes['SitemapPageTest_Unviewable']); // Sitemap module

			// Don't bother testing framework pages
			unset($pagesTypes['RedirectorPage']);
			unset($pagesTypes['VirtualPage']);

			// Handle the rest
			foreach ($pagesTypes as $class) {
				if (ClassInfo::classImplements($class, 'TestOnly')) {
					continue;
				}
				// ie. Ignore CalendarEvent
				if (!$class::config()->can_be_root) {
					continue;
				}
				$page = $class::create();
				$page->Title = $class.' Test Page';
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
		$editPageLink = singleton('CMSPageEditController')->Link('show');

		foreach ($this->pages as $page) {
			// Visit page in CMS
			$response = $this->get(Controller::join_links($editPageLink, $page->ID));
			$this->assertTrue($response->getStatusCode() == 200);
		}
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
