<?php
require_once('util.inc.php');

const TEST_STARTED = 'STARTED';
const TEST_ACTION = 'ACTION';
const TEST_DATA = 'DATA';
const TEST_PING = 'PING';

function TestCreate($test) {
  $browser = isset($test['Browser']) ? str_replace(" ", "", $test['Browser']) : 'UnknownBrowser';
  $os = isset($test['Platform']) ? str_replace(" ", "", $test['Platform']) : 'UnknownOS';
  $name = isset($test['list']) ? str_replace(' ', '', $test['list']) : 'unknown';
  $id = date('ymd') . "-$name-$os-$browser-" . md5(uniqid(rand(), true));
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
    $info['log'] = $event;
    $ret = error_log(gmdate('Y/m/d H:i:s - ') . time() . ' - ' . json_encode($info) . "\n", 3, $logFile);
  }
  return $ret;
}

function TestGetFilePath($id, $create) {
  $path = false;
  if (preg_match('/^(?<year>[0-9][0-9])(?<month>[0-9][0-9])(?<day>[0-9][0-9])(-(?<list>[^\-]*))?-(?<os>[^\-]+)-(?<browser>[^\-]+)-(?<hash>[0-9a-z]+)$/i', $id, $matches)) {
    $path = __DIR__ . "/results/{$matches['year']}-{$matches['month']}";
    if ($create && !is_dir($path))
      mkdir($path, 0777, true);
    if (is_dir($path)) {
      $path .= "/$id.log";
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
  if ($path != false) {
    $path = __DIR__ . "/results/$id.state";
    if (is_file($path)) {
      $state = json_decode(file_get_contents($path), true);
      if (!isset($state))
        $state = false;
    }
  }
  return $state;
}

function TestSaveState($id, $test) {
  $ret = false;
  $path = TestGetFilePath($id, true);
  if ($path !== false) {
    $test['last_update'] = time();
    if (!isset($test['first_update']))
      $test['first_update'] = $test['last_update'];
    $test['update_count'] = isset($test['update_count']) ? $test['update_count'] + 1 : 1;
    if (file_put_contents(__DIR__ . "/results/$id.state", json_encode($test)) !== false)
      $ret = true;
  }
  return $ret;
}

function TestGetNextTask(&$test) {
  $task = false;
  $step = isset($test['last_step']) ? $test['last_step'] + 1 : 0;
  $tasks = TestLoadTasks($test);
  if (is_array($tasks) && count($tasks)) {
    $step = $step % count($tasks);
    $task = $tasks[$step];
    $test['last_step'] = $step;
  }
  return $task;
}

function TestLoadTasks($test) {
  $tasks = array();
  $list = isset($test['list']) ? $test['list'] : 'default';
  $lines = file(__DIR__ . "/tests/{$test['list']}.txt");
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
    $task['wait'] = 20;
  return $task;
}

$global_test_notes = null;
function TestGetData($file, $saveCache = false) {
  global $global_test_notes;
  if (!isset($global_test_notes) && is_file('./notes.ini'))
    $global_test_notes = parse_ini_file('./notes.ini');
    
  if (is_file("$file.cache")) {
    $test = json_decode(file_get_contents("$file.cache"), true);
  } else {
    $test = array('file' => $file, 'actions' => array(), 'data' => array());
    $lines = file($file);
    foreach($lines as $line) {
      if (preg_match('/^[^-]+ - (?<time>[0-9]+) - (?<json>{.*})$/', trim($line), $matches)) {
        $time = intval($matches['time']);
        $data = json_decode($matches['json'], true);
        if (isset($data['log']))
          $type = $data['log'];
        elseif (isset($data['UA']))
          $type = TEST_STARTED;
        else
          $type = TEST_ACTION;
        if (!isset($test['started']) || $time < $test['started'])
          $test['started'] = $time;
        if (!isset($test['finished']) || $time > $test['finished'])
          $test['finished'] = $time;
          
        if ($type == TEST_STARTED) {
          $test['platform'] = $data['Platform'];
          $test['browser'] = $data['Browser'];
          $test['version'] = $data['Browser Version'];
          $test['list'] = isset($data['list']) ? $data['list'] : 'default';
        } elseif ($type == TEST_ACTION) {
          $test['actions'][] = $data;
        } elseif ($type == TEST_DATA) {
          $test['data'][] = $data;
        }
      }
    }
    if ($saveCache)
      file_put_contents("$file.cache", json_encode($test));
  }
  $id = basename($file, '.log');
  $test['notes'] = isset($global_test_notes[$id]) ? $global_test_notes[$id] : '';
  return $test;
}
?>
