# SilverStripe Australia Testing Extensions Module

**This version is required for projects running early SS 3.1 versions and lower.**

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
