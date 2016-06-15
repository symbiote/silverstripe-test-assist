# SilverStripe Australia Testing Extensions Module

Adds several helpers to the base SilverStripe testing framework to simplify
hooking the testing process into CI servers such as Jenkins, by parameterising
several configuration options. 

Additionally, the module provides the `SsauSeleniumTestCase` which provides a 
simpler API for writing Selenium powered tests. 

## Selenium

To run just the selenium tests, a commandline such as 

```
php framework/cli-script.php dev/tests/module/ssautesting \ 
  flush=1 build=1 selenium_host=127.0.0.1 browser=firefox \
  test_url=http://my.silverstripe.site/ test_type=SsauSeleniumTestCase SkipTests=ssauseleniumtestcase \
  admin_user=admin admin_pass=admin
```

Note: The trailing slash in the URL is _important_!

should get you going. Note that you will _need_ to have [selenium server](http://www.seleniumhq.org/download/) 
running for this to work. A command such as the following will start selenium server in a virtual
framebuffer, meaning the windows don't launch all over your screen!

```
#!/bin/sh
/usr/bin/xvfb-run -e /var/log/selenium/xvfb-selenium.log -n 10 \
  -s "-screen 10 1024x768x8" \
  java -jar /home/path/to/programs/selenium-server-standalone-2.39.0.jar  \
  -port 4444 -log /var/log/selenium/server.log 
```

However it can be useful to run the selenium server directly from the commandline to debug why 
a test has failed. 

## Diagnostic tools

Swap from using MySQLDatabase to DevMySQLDatabase in your DB config

```yml
---
Name: dev_filters
---
Injector:
  RequestProcessor:
    properties:
      filters: 
        - %$QueryDisplayFilter
        - %$RequestTimerFilter
```


## Codeception

To hook codeception up for your project, you will need to create a 
codeception.yml config file at the top level of your project. An examples of 
this can be found in `ssautesting/sample-config`

**codeception.yml** defines the paths of modules to be included in the test runs

Within your module, you can then create a namespaced project specific set of 
tests to be included in that top level path. 

* mkdir modulename/codeception
* cd modulename/codeception
* ../../vendor/bin/codecept bootstrap --namespace modulenamespace
* mv codeception.yml codeception.dist.yml
* touch .gitignore

Note that 'modulenamespace' can be anything, as long as it's a valid PHP 
namespace string

Next, create a new `codeception.yml` file that contains _just_ your local
environment codeception configuration; this will typically be the local URL
for developer testing, ie

```
modules:
    config:
        WebDriver:
            url: http://project.clients.sslocal
            browser: chrome 

```

Update `modulename/codeception/tests/functional.suite.xml` and add a couple of 
modules

```
class_name: FunctionalTester
modules:
    enabled:
        - \transportapi\Helper\Functional
        - WebDriver # new
        - \ssautesting\Helper\SilverstripeFunctional # new

```

Update `modulename/codeception/tests/_bootstrap.php` to include the 
SilverstripFunctional helper

```
<?php
// This is global bootstrap for autoloading
include_once 'ssautesting/code/codeception/SilverstripeFunctional.php';
```


Now, add the following to .gitignore

```
codeception.yml
/tests/_output/
```

Include your module in the top level `codeception.yml`

```
include:
  - modulename/codeception

```

And lastly, start writing tests! In `modulename/codeception/tests/functional/FirstTestCept.php`

```
<?php

use \Codeception\Util\Locator;

$I = new \modulenamespace\FunctionalTester($scenario);

$I->wantTo("Test the homepage");
$I->amOnPage("/");
$I->see("Home");

```

From the top level of the project

`$  ./vendor/bin/codecept run`

