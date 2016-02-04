<?php
function __autoload($class) {
  require('../'.str_replace('\\', '/', $class).'.php');
}
require("../functions.php");
if(isset($_GET['url']) && preg_match("/\.php$/", $_GET['url'])) require("../".$_GET['url']);
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Bot Tester</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet" integrity="sha256-7s5uDGW3AHqw6xtJmNNtr+OBRJUlgkNJEo78P4b0yRw= sha512-nNo+yCHEyn0smMxSswnf/OnX6/KwJuZTlNZBjauKhTK0c+zT+q5JOCx0UFhXQ6rJR9jg6Es8gPuD2uZcYDLqSw==" crossorigin="anonymous">
  </head>
  <body onload="textAreaAdjust(document.getElementById('input'))" style="padding-top: 2rem; padding-bottom: 2rem">
    <script>
      function textAreaAdjust(o) {
        o.style.height = "1px";
        o.style.height = (25+o.scrollHeight)+"px";
      }
    </script>
    <div class="container">
      <?php if(isset($_GET['url'])) echo '<pre>'.htmlspecialchars(file_get_contents("../".$_GET['url'])).'</pre>'; ?>
      <form method="post" action="#output">
        <textarea id="input" onkeyup="textAreaAdjust(this)" style="overflow: hidden; width: 100%; max-width: 100%; font-family: monospace;" name="code"><?php if(isset($_POST['code'])) echo $_POST['code']; ?></textarea><br>
        <input class="btn btn-default" type="submit">
      </form>
      <?php if(isset($_POST['code'])) {  ?> <pre id="output" style="margin-top: 2rem;"><?php } if(isset($_POST['code'])) echo eval($_POST['code']).'</pre>'; ?>
    </div>
  </body>

</html>
