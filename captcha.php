<?php
session_start();
mt_srand();

function randomString($len, $charset)
{
  
  $charset_max_index = strlen($charset) - 1;
  $str="";
  for ($c=0; $c<$len; $c++)
  {
    $str .= $charset[mt_rand(0, $charset_max_index)];
  }
  return($str);
}

$charset = "ABCDEFGHJKLMNPRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789";
$num_chars = 6;
$ttf = "Ubuntu-B.ttf"; //Schriftart
$ttfsize = 20; //Schriftgrösse
$horizontal_margin = 3;
$horizontal_min_distance = 4;
$horizontal_max_distance = 15;
$vertical_margin=3;
$vertical_max_offset=15;

unset($_SESSION['captcha_text']);
$text = randomString($num_chars, $charset);
$_SESSION['captcha_text'] = $text;

$char_max_height=0;
$char_max_width=0;
//calc image size:
for($c=0; $c<strlen($charset); $c++)
{
  $char_bbox = imagettfbbox ( $ttfsize , 0 , $ttf , $charset[$c]);
  $char_min_x = min( array($char_bbox[0], $char_bbox[2], $char_bbox[4], $char_bbox[6]) );
  $char_max_x = max( array($char_bbox[0], $char_bbox[2], $char_bbox[4], $char_bbox[6]) );
  $char_min_y = min( array($char_bbox[1], $char_bbox[3], $char_bbox[5], $char_bbox[7]) );
  $char_max_y = max( array($char_bbox[1], $char_bbox[3], $char_bbox[5], $char_bbox[7]) );
  $char_height = abs( $char_max_y - $char_min_y );
  if (strpos("gjpqy", $charset[$c]) != False)
    $char_height = round($char_height* 1.5);
  $char_max_width  = max(abs( $char_max_x - $char_min_x ),$char_max_width);
  $char_max_height = max($char_height,$char_max_height);
}

$img_height = $vertical_margin*2 + $vertical_max_offset + $char_max_height;
$img_width = $horizontal_margin*2 + ($char_max_width + $horizontal_max_distance) * $num_chars;


//calc character positions
$char_pos=array();
$curr_x = $horizontal_margin - $horizontal_min_distance;
for($c=0; $c<$num_chars; $c++)
{
  $angle = mt_rand(-35,35);
  $char_bbox = imagettfbbox ( $ttfsize , $angle , $ttf , $text[$c]);
  $char_min_x = min( array($char_bbox[0], $char_bbox[2], $char_bbox[4], $char_bbox[6]) );
  $char_max_x = max( array($char_bbox[0], $char_bbox[2], $char_bbox[4], $char_bbox[6]) );
  $char_min_y = min( array($char_bbox[1], $char_bbox[3], $char_bbox[5], $char_bbox[7]) );
  $char_max_y = max( array($char_bbox[1], $char_bbox[3], $char_bbox[5], $char_bbox[7]) );
  $char_width  = abs( $char_max_x - $char_min_x );
  $char_height = abs( $char_max_y - $char_min_y );
  $curr_x += mt_rand($horizontal_min_distance,$horizontal_max_distance);
  $x = $curr_x;
  $curr_x += $char_width;
  $y = $char_height + $vertical_margin + mt_rand(0,$vertical_max_offset);
  $char_pos[] = array("x"=>$x, "y"=>$y, "angle"=>$angle);
}

$img = imagecreate($img_width, $img_height);  
$background = imagecolorallocate($img, 0xf2, 0xf2, 0xf2);
$black = imagecolorallocate($img, 0, 0, 0);
$gray = imagecolorallocate($img, 60, 60, 60);
$yellow = imagecolorallocate($img, 255,255,0);
$red = imagecolorallocate($img, 255,0,0);
$white = imagecolorallocate($img, 255,255,255);
$green = imagecolorallocate($img, 7, 255, 20);
$blue = imagecolorallocate($img, 12, 0, 255);  
imagefill($img,0,0,$background);
imagerectangle($img,10,10,$img_width*0.8,$img_height*0.8,$yellow);
imagerectangle($img, $img_width*0.1,$img_height*0.1,$img_width*0.9,$img_height*0.7, $blue);
imagerectangle($img, $img_width*0.1+1,$img_height*0.1+1,$img_width*0.9+1,$img_height*0.7+1, $blue);

//$color = ImageColorAllocate($img, 0, 0, 0); //Farbe
$t_x = 0;
$t_y_middle = 35;
for($c=0; $c<$num_chars; $c++)
{
  imagettftext($img, $ttfsize, $char_pos[$c]["angle"], $char_pos[$c]["x"], $char_pos[$c]["y"], imagecolorallocate($img, mt_rand(10,65),mt_rand(10,65),mt_rand(10,65)), $ttf, $text[$c]);
}

for ($c=0; $c<3; $c++)
{
  imageline($img,mt_rand(0,$img_width),mt_rand(0,$img_height),mt_rand(0,$img_width),mt_rand(0,$img_height),$red);
  imageline($img,mt_rand(0,$img_width),mt_rand(0,$img_height),mt_rand(0,$img_width),mt_rand(0,$img_height),$green);
}

header("Cache-Control: no-cache, must-revalidate");
header('Content-type: image/png');
imagepng($img);
imagedestroy($img);
?>
