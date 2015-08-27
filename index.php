<html>
<head>
</head>
<body>
<?php
include 'tests.inc.php';
$compare = '';
if (isset($_REQUEST['tests']) && preg_match('/^[a-zA-Z0-9-]+$/', $_REQUEST['tests']))
  $compare = "{$_REQUEST['tests']},";
$tests = array();
$pending = array();
$results = glob(__DIR__ . '/results/*');
foreach ($results as $r) {
  if (!is_dir($r) && preg_match('/(?<id>[a-zA-Z0-9-]+)\.state$/', $r, $matches)) {
    $pending[$matches['id']] = json_decode(file_get_contents($r), true);
  }
}
foreach ($results as $r) {
  if (is_dir($r)) {
    $files = glob("$r/*");
    foreach ($files as $file) {
      if (preg_match('/(?<id>[a-zA-Z0-9-]+)\.log$/', $file, $matches)) {
        $cache = isset($pending[$matches['id']]) ? false : true;
        $testData = TestGetData($file, $cache);
        if (count($testData['actions']) > 0 && isset($testData['started'])) {
          $testData['id'] = $matches['id'];
          $tests[$testData['started']] = $testData;
        }
      }
    }
  }
}

ksort($tests);

echo '<table>';
echo '<tr><th></th><th></th><th>Browser</th><th>Test</th><th>Notes</th><th>Started</th><th>Run Time</th></tr>';
foreach ($tests as $test) {
  echo '<tr><td>';
  if (strlen($compare) && count($test['data']))
    echo "<a href=\"view.php?tests=$compare{$test['id']}\">Add to Comparison</a>";
  echo '</td><td>';
  if (count($test['data']))
    echo "<a href=\"view.php?tests={$test['id']}\">View Test Data</a>";
  echo "</td><td>{$test['platform']} {$test['browser']} {$test['version']}</td>";
  echo "<td>{$test['list']}</td>";
  echo "<td>{$test['notes']}</td>";
  echo "<td>" . date('M j Y, g:i a',$test['started']) . "</td>";
  $elapsed = round(($test['finished'] - $test['started']) / 60);
  $hr = intval($elapsed / 60);
  $min = str_pad($elapsed - ($hr * 60), 2, '0', STR_PAD_LEFT);
  $runTime = "$elapsed min ($hr:$min)";
  if (isset($pending[$test['id']]))
    $runTime .= ' - running';
  echo "<td>$runTime</td>";
  echo '</tr>';
}
echo '</table>';
?>
</body>
</html>
