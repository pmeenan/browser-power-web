<?php
include 'tests.inc.php';
require('lib/browser.php');
$browser = new Browser();
$testInfo = array('UA' => $_SERVER['HTTP_USER_AGENT'],
                  'Browser' => $browser->getBrowser(),
                  'Browser Version' => $browser->getVersion(),
                  'Platform' => $browser->getPlatform());
if (isset($_REQUEST['label']))
  $testInfo['label'] = $_REQUEST['label'];
$id = TestCreate($testInfo);
if ($id !== false) {
  $ret = array('result' => 200, 'id' => $id);
  json_response($ret);
} else {
  header("HTTP/1.0 404 Not Found");
}
?>