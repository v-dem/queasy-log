[1mdiff --git a/.gitignore b/.gitignore[m
[1mindex 1b2ac07..1354909 100644[m
[1m--- a/.gitignore[m
[1m+++ b/.gitignore[m
[36m@@ -1,3 +1,4 @@[m
 /.idea/[m
 composer.lock[m
 vendor[m
[32m+[m[32m/clover.xml[m
[1mdiff --git a/composer.json b/composer.json[m
[1mindex 6c88c1f..57b3ba1 100644[m
[1m--- a/composer.json[m
[1m+++ b/composer.json[m
[36m@@ -21,7 +21,8 @@[m
     },[m
     "require-dev": {[m
         "php": ">=7.1.0",[m
[31m-        "phpunit/phpunit": "~7"[m
[32m+[m[32m        "phpunit/phpunit": "~7",[m
[32m+[m[32m        "ext-xdebug": "^2.6.0"[m
     },[m
     "autoload": {[m
         "psr-4": {[m
[36m@@ -32,6 +33,9 @@[m
         "psr-4": {[m
             "queasy\\log\\tests\\": "tests/"[m
         }[m
[32m+[m[32m    },[m
[32m+[m[32m    "scripts": {[m
[32m+[m[32m        "test": "phpunit --coverage-clover clover.xml"[m
     }[m
 }[m
 [m
[1mdiff --git a/phpunit.xml b/phpunit.xml[m
[1mindex 537d533..e983050 100644[m
[1m--- a/phpunit.xml[m
[1m+++ b/phpunit.xml[m
[36m@@ -8,12 +8,8 @@[m
          convertWarningsToExceptions="true"[m
          processIsolation="false"[m
          stopOnFailure="false"[m
[31m-         syntaxCheck="false"[m
          beStrictAboutTestsThatDoNotTestAnything="true"[m
[31m-         beStrictAboutOutputDuringTests="true"[m
[31m-         beStrictAboutTestSize="true"[m
[31m-         reportUselessTests="true"[m
[31m-         disallowTestOutput="true">[m
[32m+[m[32m         beStrictAboutOutputDuringTests="true">[m
     <testsuites>[m
         <testsuite name="queasy-log tests">[m
             <directory suffix=".php">./tests/</directory>[m
[1mdiff --git a/tests/LoggerTest.php b/tests/LoggerTest.php[m
[1mindex fea75bf..749d574 100644[m
[1m--- a/tests/LoggerTest.php[m
[1m+++ b/tests/LoggerTest.php[m
[36m@@ -1,15 +1,16 @@[m
 <?php[m
[31m-/**[m
[31m- * Created by PhpStorm.[m
[31m- * User: Home[m
[31m- * Date: 22.04.2019[m
[31m- * Time: 23:10[m
[31m- */[m
 [m
 namespace queasy\log;[m
 [m
[32m+[m[32muse Psr\Log\LogLevel;[m
[32m+[m[32muse PHPUnit\Framework\TestCase;[m
 [m
[31m-class LoggerTest extends \PHPUnit_Framework_TestCase[m
[32m+[m[32mclass LoggerTest extends TestCase[m
 {[m
 [m
[32m+[m[32m    public function testLevel2int()[m
[32m+[m[32m    {[m
[32m+[m[32m        $this->assertEquals(Logger::level2int(LogLevel::ALERT), 6);[m
[32m+[m[32m        $this->assertEquals(Logger::level2int('Dummy'), 0);[m
[32m+[m[32m    }[m
 }[m
