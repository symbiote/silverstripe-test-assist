<?php

namespace Symbiote\TestAssist;


use PHPUnit_Util_Log_JUnit;
use PHPUnit_Framework_TestSuite;
use PHPUnit_Framework_Test;
use Exception;
use PHPUnit_Framework_AssertionFailedError;
use Symbiote\TestAssist\PhingPHPUnit3ResultFormatter;


/**
 * $Id: XMLPHPUnit2ResultFormatter.php 142 2007-02-04 14:06:00Z mrook $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://phing.info>.
 */

if (file_exists('PHPUnit/Util/Log/JUnit.php')) {
	require_once 'PHPUnit/Util/Log/JUnit.php';
}

/**
 * Prints XML output of the test to a specified Writer
 *
 * @author Michiel Rook <michiel.rook@gmail.com>
 * @version $Id: XMLPHPUnit2ResultFormatter.php 142 2007-02-04 14:06:00Z mrook $
 * @package phing.tasks.ext.phpunit
 * @since 2.1.0
 */
class PhingXMLPHPUnit3ResultFormatter extends PhingPHPUnit3ResultFormatter
{
	/**
	 * @var PHPUnit_Util_Log_XML
	 */
	private $logger = NULL;

	function __construct()
	{
		$this->logger = new PHPUnit_Util_Log_JUnit(null, true);
		$this->logger->setWriteDocument(false);
	}

	function getExtension()
	{
		return ".xml";
	}

	function getPreferredOutfile()
	{
		return "testsuites";
	}

	function startTestSuite(PHPUnit_Framework_TestSuite $suite)
	{
		parent::startTestSuite($suite);

		$this->logger->startTestSuite($suite);
	}

	function endTestSuite(PHPUnit_Framework_TestSuite $suite)
	{
		parent::endTestSuite($suite);

		$this->logger->endTestSuite($suite);
	}

	function startTest(PHPUnit_Framework_Test $test)
	{
		parent::startTest($test);

		$this->logger->startTest($test);
	}

	function endTest(PHPUnit_Framework_Test $test, $time)
	{
		parent::endTest($test, $time);

		$this->logger->endTest($test, $time);
	}

	function addError(PHPUnit_Framework_Test $test, Exception $e, $time)
	{
		parent::addError($test, $e, $time);

		$this->logger->addError($test, $e, $time);
	}

	/**
	 * Risky test.
	 *
	 * @param PHPUnit_Framework_Test $test
	 * @param Exception              $e
	 * @param float                  $time
	 * @since  Method available since Release 3.8.0
	 */
	function addRiskyTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
		// Stub out to support PHPUnit 3.8
	}

	function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time)
	{
		parent::addFailure($test, $e, $time);

		$this->logger->addFailure($test, $e, $time);
	}

	function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time)
	{
		parent::addIncompleteTest($test, $e, $time);

		$this->logger->addIncompleteTest($test, $e, $time);
	}

	function endTestRun()
	{
		parent::endTestRun();

		if ($this->out)
		{
			$this->out->write($this->logger->getXML());
			$this->out->close();
		}
	}

	function writeResults($fname)
	{
		file_put_contents($fname, $this->logger->getXML());
	}
}
