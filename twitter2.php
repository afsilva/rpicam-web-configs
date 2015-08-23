<?php
   define('BASE_DIR', dirname(__FILE__));
   require_once(BASE_DIR.'/config.php');
  
   if(isset($_GET['twitter'])) {
      sendTwitter($_GET['img'], $_GET['txt']); 
      exit();
   }

   $dSelect = "";
   $pFile = "";
   $tFile = "";
   $debugString = "";
   $previewSize = 640;
   $thumbSize = 96;
   $sortOrder = 1;
   $showTypes = 1;

 
   //send file to twitter
   function sendTwitter($img, $text) {

   $ch = curl_init();
   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
   curl_setopt($ch, CURLOPT_URL, 'https://mobilepicam-bean2.rhcloud.com/start.php');
   curl_setopt($ch, CURLOPT_POST, true);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
   curl_setopt($ch, CURLOPT_POSTFIELDS, array('img' => '@/var/www/media/'.$img.';filename=picam.jpg','txt' => "$text"));
   curl_setopt($ch, CURLOPT_VERBOSE, true);
   curl_setopt($ch,CURLOPT_USERAGENT,"Mozilla/5.0 (X11; Fedora; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.155 Safari/537.36");
   curl_setopt($ch, CURLOPT_HEADER, true);
   curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:') );
   curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
   curl_setopt($ch, CURLOPT_AUTOREFERER , true);
   curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
   curl_setopt( $ch, CURLOPT_COOKIESESSION, true );
   curl_setopt( $ch, CURLOPT_COOKIEJAR,  'cookies.txt' );
   curl_setopt( $ch, CURLOPT_COOKIEFILE, 'cookies.txt' );
   $data = curl_exec($ch);
   $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
   $header = substr($data, 0, $header_size);
   $body = substr($data, $header_size);
   preg_match("!\r\n(?:Location|URI): *(.*?) *\r\n!", $header, $m);
   $redirectURL = $m[1];
   $time = $_SERVER['REQUEST_TIME'];

   $hash = array();
   foreach (file('cookies.txt') as $row) { 
     list($domain, $ssl, $path, $notsure, $expire, $key, $value) = explode("\t", $row); 
     $hash["$key"] = $value;
     setcookie($key, $valeu, $time + 3600 * 30, 'twitter_test'); 
   } 
   //echo var_dump($hash);
   #preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $data, $matches);
   #$cookies = array();
   #foreach($matches[1] as $item) {
   #   $parts = explode("=",$item);
   #   $name = trim($parts[0]);
   #   setcookie($name, $parts[1], $time + 3600 * 30, 'twitter_test','mobilepicam-bean2.rhcloud.com');
   #}


   header("Location: $redirectURL");
   #print_r("Location: $redirectURL<br>");
   //print_r("$header");
   curl_close($ch);
   #echo $data;
   }

   //function to draw 1 file on the page
   function drawFile($f, $ts, $sel) {
      $fType = getFileType($f);
      $rFile = dataFilename($f);
      $fNumber = getFileIndex($f);
      $lapseCount = "";
      switch ($fType) {
         case 'v': $fIcon = 'video.png'; break;
         case 't': 
            $fIcon = 'timelapse.png';
            $lapseCount = '(' . count(findLapseFiles($f)). ')';
            break;
         case 'i': $fIcon = 'image.png'; break;
         default : $fIcon = 'image.png'; break;
      }
      $duration ='';
      if (file_exists(MEDIA_PATH . "/$rFile")) {
         $fsz = round ((filesize(MEDIA_PATH . "/$rFile")) / 1024);
         $fModTime = filemtime(MEDIA_PATH . "/$rFile");
         if ($fType == 'v') {
            $duration = ($fModTime - filemtime(MEDIA_PATH . "/$f")) . 's';
         }
      } else {
         $fsz = 0;
         $fModTime = filemtime(MEDIA_PATH . "/$f");
      }
      $fDate = @date('Y-m-d', $fModTime);
      $fTime = @date('H:i:s', $fModTime);
      $fWidth = max($ts + 4, 140);
      echo "<fieldset class='fileicon' style='width:" . $fWidth . "px;'>";
      if ($fsz > 0) echo "$fsz Kb $lapseCount $duration"; else echo 'Busy';
      echo "<br>$fDate<br>$fTime<br>";
      if ($fsz > 0) echo "<a title='$rFile' href='preview.php?preview=$f'>";
      echo "<img src='" . MEDIA_PATH . "/$f' style='width:" . $ts . "px'/>";
      if ($fsz > 0) echo "</a>";
      echo "<form method='get' action='twitter2.php'>";
      echo "<input type='hidden' name='img' value='$rFile'>";
      echo "<input type='hidden' name='twitter' value='1'>";
      echo "<input type='hidden' name='txt' value='#MobilePiCam Photo anderson.the-silvas.com'>";
      echo "<input type='submit' name='sub' value='Twitter'></form>";

      echo "</fieldset> ";
   }
   
   function getThumbnails() {
      global $sortOrder;
      global $showTypes;
      $files = scandir(MEDIA_PATH, $sortOrder - 1);
      $thumbnails = array();
      foreach($files as $file) {
         if($file != '.' && $file != '..' && isThumbnail($file)) {
            $fType = getFileType($file);
            if($showTypes == '1') {
               $thumbnails[] = $file;
            }
            elseif($showTypes == '2' && ($fType == 'i' || $fType == 't')) {
               $thumbnails[] = $file;
           }
            elseif($showTypes == '3' && ($fType == 'v')) {
               $thumbnails[] = $file; 
            }
         }
      }
      return $thumbnails;   
   }
   
   function diskUsage() {
      //Get disk data
      $totalSize = round(disk_total_space(BASE_DIR . '/' . MEDIA_PATH) / 1048576); //MB
      $currentAvailable = round(disk_free_space(BASE_DIR . '/' . MEDIA_PATH) / 1048576); //MB
      $percentUsed = round(($totalSize - $currentAvailable)/$totalSize * 100, 1);
      if ($percentUsed > 98)
         $colour = 'Red';
      else if ($percentUsed > 90)
         $colour = 'Orange';
      else
         $colour = 'LightGreen';
      echo '<div style="margin-left:5px;position:relative;width:300px;border:1px solid #ccc;">';
         echo "<span>Used:$percentUsed%  Total:$totalSize(MB)</span>";
         echo "<div style='z-index:-1;position:absolute;top:0px;width:$percentUsed%;background-color:$colour;'>&nbsp;</div>";
      echo '</div>';
   }
   
?>
<!DOCTYPE html>
<html>
   <head>
      <meta name="viewport" content="width=550, initial-scale=1">
      <title>RPi Cam Download</title>
      <link rel="stylesheet" href="css/style_minified.css" />
      <link rel="stylesheet" href="css/preview.css" />
      <link rel="stylesheet" href="<?php echo getStyle(); ?>" />
      <script src="js/style_minified.js"></script>
      <script src="js/script.js"></script>
   </head>
   <body>
      <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
         <div class="container">
            <div class="navbar-header">
               <a class="navbar-brand" href="<?php echo ROOT_PHP; ?>"><span class="glyphicon glyphicon-chevron-left"></span>Back - <?php echo CAM_STRING; ?></a>
            </div>
         </div>
      </div>
    
      <div class="container-fluid">
      <?php
         $thumbnails = getThumbnails();
         diskUsage();
         if ($debugString !="") echo "$debugString<br>";
         if(count($thumbnails) == 0) echo "<p>No videos/images saved</p>";
         else {
            foreach($thumbnails as $file) {
              drawFile($file, $thumbSize, $dSelect);
            }
         }
      ?>
      </div>
      
   </body>
</html>
