<?php
function json_response($response) {
  header("Content-type: application/json; charset=utf-8");
  header("Cache-Control: no-cache, must-revalidate");
  header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

  if (version_compare(phpversion(), '5.4.0') >= 0) {
    echo json_encode($response, JSON_PRETTY_PRINT);
  } else {
    echo json_encode($response);
  }
}  
?>
