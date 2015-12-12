<?php
  if (!isset($_REQUEST['type'])) {
    echo "-1";
    return;
  }
  if ($_FILES["files"]["error"] > 0) {
    echo $_FILES["file"]["error"];
    echo "0";
  } else {
    if ($_REQUEST["type"] == "mp4") {
      if (file_exists("08/" . $_FILES["file"]["name"])) {
        echo "1";
      } else {
        move_uploaded_file($_FILES["file"]["tmp_name"], "08/".$_FILES["file"]["name"]);
        echo "08/" . $_FILES["file"]["name"];
        $command = "./mtn  -P -h 0 -c 1 -r 1 -w 256 -g 0 -j 80 -b 0.80 -D 12 -L 4:2 -k 000000 -F FFFFFF:12:tahomabd.ttf:FFFFFF:000000:10 -i -t ";
        exec($command."08/".$_FILES["file"]["name"], $result);
      }
    } else if ($_REQUEST["type"] == "rom") {
      move_uploaded_file($_FILES["file"]["tmp_name"], "install/update.zip");
      echo "install/update.zip";
    } else if ($_REQUEST["type"] == "apk") {
      move_uploaded_file($_FILES["file"]["tmp_name"], "install/KTVBox.apk");
      echo "install/KTVBox.apk";
    } else if ($_REQUEST["type"] == "image") {
      if (file_exists("image/" . $_FILES["file"]["name"]))
      {
        echo "1";
      } else {
        move_uploaded_file($_FILES["file"]["tmp_name"], "image/".$_FILES["file"]["name"]);
        echo "image/" . $_FILES["file"]["name"];
      }
    } else if ($_REQUEST["type"] == "avatar"){
      move_uploaded_file($_FILES["file"]["tmp_name"], "avatar/".$_FILES["file"]["name"]);
      echo "avatar/" . $_FILES["file"]["name"];
    } else if ($_REQUEST["type"] == "fm"){
      move_uploaded_file($_FILES["file"]["tmp_name"], "fm/".$_FILES["file"]["name"]);
      echo "fm/" . $_FILES["file"]["name"];
    } else if ($_REQUEST["type"] == "lyric"){
      move_uploaded_file($_FILES["file"]["tmp_name"], "lyric/".$_FILES["file"]["name"]);
      echo "lyric/" . $_FILES["file"]["name"];
    } else {
      echo "-2";
      exit;
    }
  }
?>