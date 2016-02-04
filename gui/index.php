<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if(!isset($_GET['type'])) $_GET['type'] = 'Message';
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Telegram Bot Sender</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet" integrity="sha256-7s5uDGW3AHqw6xtJmNNtr+OBRJUlgkNJEo78P4b0yRw= sha512-nNo+yCHEyn0smMxSswnf/OnX6/KwJuZTlNZBjauKhTK0c+zT+q5JOCx0UFhXQ6rJR9jg6Es8gPuD2uZcYDLqSw==" crossorigin="anonymous">
    <!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="https://cdn.rawgit.com/twbs/bootstrap/v4-dev/dist/js/bootstrap.js"></script> -->

  </head>
  <body style="padding-top: 2rem; padding-bottom: 2rem" onresize="menu()" onload="menu()">
    <script>
        function menu() {
            if(window.innerWidth<992) document.getElementById('mode').className = 'btn-group-vertical';
            else document.getElementById('mode').className = 'btn-group btn-group-justified';
        }
    </script>
    <div class="container">
      <form action="#" method="post">
        <fieldset class="form-group">
          <label for="token">Token:</label>
            <div class="input-group">
              <input class="form-control" type="password" id="token" name="token" <?php
                if(array_key_exists('token', $_GET)) echo 'value="'.ucfirst($_GET['token']).'"';
              ?> >
              <span class="input-group-btn">
                <button type="button" class="btn btn-default" id="basic-addon2">Not working yet.</button>
              </span>
            </div>
        </fieldset>
        <div class="form-group">
          <div id="mode" class="btn-group btn-group-justified" role="group" style="width: 100%">
            <?php
              $types = json_decode(file_get_contents("../types.json"), true);
              $classes = [];
              foreach($types as $key => $value) {
                if(gettype($value) == 'array') array_push($classes, $key);
              }

              foreach($classes as $value) {
                $type = ($value == $_GET['type'])?'primary':'default';
                echo '<a href="?type='.$value.(isset($_GET['token'])?'&token='.$_GET['token']:'').'" class="btn btn-'.$type.'">'.$value.'</a>';
              }
            ?>
          </div>
        </div>
        <?php
        foreach($types as $key => $value) {
          if(gettype($value) != 'array') {
            printOption($key, $value);
          }
        }
        foreach($types[$_GET['type']] as $key => $value) {
          printOption($key, $value);
        }
        function printOption($key, $value) {
          echo '<fieldset class="form-group">';
          echo '  <label for="'.$key.'">'.$value.':</label>';
          echo '  <input class="form-control" autocomplete="off" type="text" id="'.$key.'" name="'.$key.'">';
          echo '</fieldset>';
        }
        ?>
        <input type="submit" class="btn btn-default">
      </form>

      <?php if($_POST):

        function __autoload($class) {
          require('../'.str_replace('\\', '/', $class).'.php');
        }
        require('../functions.php');

        $bot = new out\Bot('https://api.telegram.org/bot'.$_POST['token'].'/');
        unset($_POST['token']);

        $class = 'out\\'.$_GET['type'];

        $object = new ReflectionClass($class);
        $update = new \out\Update($_POST, $object->getDefaultProperties()['method']);
        $result = $bot->send($update, true);
        echo '<hr>Update: <pre>'.json_encode($update, JSON_PRETTY_PRINT).'</pre>';
        echo '<hr>Result: <pre>'.json_encode($result, JSON_PRETTY_PRINT).'</pre>';

        endif;
      ?>

    </div>
  </body>
</html>
