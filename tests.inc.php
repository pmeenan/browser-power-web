<?php
require_once('util.inc.php');

const TEST_STARTED = 'STARTED';
const TEST_ACTION = 'ACTION';
const TEST_DATA = 'ACTION_DATA';

function TestCreate($test) {
  $id = date('ymd') . '-' . md5(uniqid(rand(), true));
  $path = TestGetFilePath($id, true);
  if (TestLog($id, TEST_STARTED, $test)) {
    if (TestSaveState($id, $test) === false)
      $id = false;
  } else {
    $id = false;
  }
    
  return $id;
}

function TestLog($id, $event, $info) {
  $ret = false;
  $logFile = TestGetFilePath($id, $event == TEST_STARTED);
  if ($logFile !== false) {
    $ret = error_log(gmdate('Y/m/d H:i:s - ') . time() . ' - ' . json_encode($info) . "\n", 3, $logFile);
  }
  return $ret;
}

function TestGetFilePath($id, $create) {
  $path = false;
  if (preg_match('/^(?<year>[0-9][0-9])(?<month>[0-9][0-9])(?<day>[0-9][0-9])-(?<hash>[0-9a-z]+)$/i', $id, $matches)) {
    $path = __DIR__ . "/results/{$matches['year']}/{$matches['month']}/{$matches['day']}";
    if ($create && !is_dir($path))
      mkdir($path, 0777, true);
    if (is_dir($path)) {
      $path .= "/{$matches['hash']}.log";
      if (!$create && !is_file($path) && !is_file("$path.gz"))
        $path = false;
    } else {
      $path = false;
    }
  }
  return $path;
}

function TestGetState($id) {
  $state = false;
  $path = TestGetFilePath($id, false);
  if ($path != false && is_file("$path.state")) {
    $state = json_decode(file_get_contents("$path.state"), true);
    if (!isset($state))
      $state = false;
  }
  return $state;
}

function TestSaveState($id, $test) {
  $ret = false;
  $path = TestGetFilePath($id, true);
  if ($path !== false) {
    if (file_put_contents("$path.state", json_encode($test)) === false)
      $ret = true;
  }
  return $ret;
}

function TestGetNextTask(&$test) {
  $task = false;
  $step = isset($test['last_step']) ? $test['last_step'] + 1 : 0;
  $tasks = TestLoadTasks();
  if (is_array($tasks) && count($tasks)) {
    $step = $step % count($tasks);
    $task = $tasks[$step];
    $test['last_step'] = $step;
  }
  return $task;
}

function TestLoadTasks() {
  $tasks = array();
  $lines = file(__DIR__ . '/tests/default.txt');
  foreach ($lines as $line) {
    $task = TestParseTask($line);
    if (is_array($task)) {
      $task['index'] = count($tasks);
      $tasks[] = $task;
    }
  }
  return $tasks;
}

function TestParseTask($line) {
  $line = trim($line);
  if (strlen($line)) {
    $task = @json_decode($line, true);
    if (!isset($task) || !is_array($task)) {
      $task = array('url' => $line);
    }
  }
  if (!isset($task['scroll']))
    $task['scroll'] = true;
  if (!isset($task['wait']))
    $task['wait'] = 60;
  return $task;
}
?>
