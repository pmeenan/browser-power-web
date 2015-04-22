<?php
// Go through and stop any tests that haven't connected in the last 30 minutes
$files = glob(__DIR__ . '/results/*.state');
$now = time();
foreach ($files as $file) {
  $state = json_decode(file_get_contents($file), true);
  if (isset($state) && isset($state['last_update'])) {
    if ($now - $state['last_update'] > 1800)
      unlink($file);
  }
}
?>