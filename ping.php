<?php
include 'tests.inc.php';
if (isset($_REQUEST['test'])) {
  $id = $_REQUEST['test'];
  $test = TestGetState($id);
  if ($test !== false) {
    TestLog($id, TEST_PING, array());
  }
}
?>