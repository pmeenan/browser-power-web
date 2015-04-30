<html>
<head>
<style type="text/css">
.chart {
  width: 600px;
  height: 400px;
}
</style>
</head>
<body>
<?php
include 'tests.inc.php';
echo "<p><a href=\"index.php?tests={$_REQUEST['tests']}\">Add more tests to the comparison</a></p>";
$tests = array();
$urls = array();
$actions = array();
$charts = array();
$configs = array();
$metrics = array('cpu' => "CPU Cores Used", 'W' => "Average Watts");
$ids = explode(',', $_REQUEST['tests']);
foreach ($ids as $id) {
  $file = TestGetFilePath($id, false);
  if ($file) {
    $data = TestGetData($file);
    if (count($data['actions']) > 0 && isset($data['started'])) {
      ProcessData($data);
      foreach ($data['urls'] as $url => $acts) {
        if (!isset($urls[$url]))
          $urls[$url] = $url;
        foreach ($acts as $action => $a) {
          if (!isset($actions[$action]))
            $actions[$action] = $action;
        }
      }
      $tests[] = $data;
    }
  }
}

$div_count = 0;
foreach ($actions as $action) {
  $a = str_replace(' ', '', $action);
  foreach ($metrics as $metric => $description) {
    echo "<h1 id=\"{$a}_$metric\">$action - $description</h1>";
    foreach ($urls as $url) {
      $div_count++;
      echo "<h2 id=\"section_$div_count\"><a href=\"$url\">$url</a></h2>";
      $div = "chart_{$a}_{$metric}_$div_count";
      echo "<div class=\"chart\" id=\"$div\"></div>";
      $label = "$description for $action for $url";
      $chart = array('div' => $div, 'label' => $label, 'data' => array());
      // count the max number of occurrences for the given url/metric/action across all the tests
      $max = 0;
      foreach ($tests as $test) {
        if (isset($test['urls'][$url][$action]))
          $max = max($max, count($test['urls'][$url][$action]));
      }
      // set up the header row of the data table
      $row = array('Configuration');
      for ($i = 0; $i < $max; $i++)
        $row[] = 'Load #' . ($i + 1);
      $chart['data'][] = $row;
      
      // create a row for each configuration
      foreach ($tests as $test) {
        $row = array($test['platform'] . ' ' . $test['browser']);
        for ($i = 0; $i < $max; $i++)
          $row[] = isset($test['urls'][$url][$action][$i][$metric]) ? $test['urls'][$url][$action][$i][$metric] : 0;
        $chart['data'][] = $row;
      }
      $charts[] = $chart;
    }
  }
}
?>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">
  google.load('visualization', '1.1', {'packages':['bar']});
  google.setOnLoadCallback(drawCharts);
  function drawCharts() {
    <?php
    foreach ($charts as $chart) {
      $id = $chart['div'];
      echo "var {$id}_data = google.visualization.arrayToDataTable([";
      $first = true;
      foreach ($chart['data'] as $row) {
        if ($first) {
          $first = false;
          echo "\n[";
        } else {
          echo ",\n[";
        }
        $firstCol = true;
        foreach ($row as $col) {
          if (!is_numeric($col))
            $col = "'$col'";
          if ($firstCol) {
            echo $col;
            $firstCol = false;
          } else {
            echo ",$col";
          }
        }
        echo ']';
      }
      echo "\n]);\n";
      
      echo "var {$id}_chart = new google.charts.Bar(document.getElementById('$id'));\n";
      echo "{$id}_chart.draw({$id}_data, {chart: {title: '{$chart['label']}'}});\n";
    }
    ?>
  }
</script>
</body>
</html>

<?php
/**
* Group the power and CPU data by URL
* 
* @param mixed $data
*/
function ProcessData(&$data) {
  $data['urls'] = array();
  foreach($data['data'] as $entry) {
    if (isset($entry['elapsed_time']) &&
        $entry['elapsed_time'] > 0 &&
        isset($entry['url']) &&
        isset($entry['action'])) {
      $elapsed = $entry['elapsed_time'];
      $url = $entry['url'];
      $action = $entry['action'];
      if (!isset($data['urls'][$url]))
        $data['urls'][$url] = array();
      if (!isset($data['urls'][$url][$action]))
        $data['urls'][$url][$action] = array();
      $e = array();
      if (isset($entry['cpu_time']))
        $e['cpu'] = $entry['cpu_time'] / $elapsed;
      if (isset($entry['Processor Joules']))
        $e['W'] = $entry['Processor Joules'] / $elapsed;
      if (count($e))
        $data['urls'][$url][$action][] = $e;
    }
  }
}
?>