<?php
include 'tests.inc.php';
if (isset($_REQUEST['test'])) {
  $id = $_REQUEST['test'];
  $test = TestGetState($id);
  if ($test !== false) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data) && is_array($data))
      TestLog($id, TEST_DATA, $data);
  }
}
?>