<?php
include 'tests.inc.php';
require('lib/browser.php');
$browser = new Browser();
$test = array('UA' => $_SERVER['HTTP_USER_AGENT'],
                  'Browser' => $browser->getBrowser(),
                  'Browser Version' => $browser->getVersion(),
                  'Platform' => $browser->getPlatform());
if (isset($_REQUEST['label']))
  $test['label'] = $_REQUEST['label'];
//$test['list'] = 'no video';
$test['list'] = 'video';
if (isset($_REQUEST['list']) && preg_match('/^[a-zA_Z0-9\- ]+$/', $_REQUEST['list']))
  $test['list'] = $_REQUEST['list'];
$id = TestCreate($test);
if ($id !== false) {
  $ret = array('result' => 200, 'id' => $id);
  json_response($ret);
} else {
  header("HTTP/1.0 404 Not Found");
}
?>