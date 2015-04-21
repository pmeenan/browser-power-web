<?php
include 'tests.inc.php';
if (isset($_REQUEST['test'])) {
  $id = $_REQUEST['test'];
  $test = TestGetState($id);
  if ($test !== false) {
    // see if we have anything to log from the last task
    if (isset($_REQUEST['log'])) {
      $data = json_decode($_REQUEST['log'], true);
      if (isset($data) && is_array($data))
        TestLog($id, TEST_DATA, $data);
    }
    $task = TestGetNextTask($test);
  }
}

if (isset($task) && $task !== false) {
  TestSaveState($id, $test);
  TestLog($id, TEST_ACTION, $task);
  $ret = array('result' => 200, 'task' => $task);
  json_response($ret);
} else {
  header("HTTP/1.0 404 Not Found");
}
?>