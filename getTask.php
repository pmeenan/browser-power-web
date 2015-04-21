<?php
include 'tests.inc.php';
if (isset($_REQUEST['test'])) {
  $id = $_REQUEST['test'];
  $test = TestGetState($id);
  if ($test !== false) {
    $task = TestGetNextTask($test);
  }
}

if (isset($task) && $task !== false) {
  TestSaveState($id, $test);
  $ret = array('result' => 200, 'task' => $task);
  json_response($ret);
} else {
  header("HTTP/1.0 404 Not Found");
}
?>