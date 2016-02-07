<?php
/*
 * This work is hereby released into the Public Domain.
 * To view a copy of the public domain dedication,
 * visit http://creativecommons.org/licenses/publicdomain/ or send a letter to
 * Creative Commons, 559 Nathan Abbott Way, Stanford, California 94305, USA.
 *
 */

require_once "artichow/BarPlot.class.php";

function fmt_num($n) {
  $s=sprintf("%d",$n);
  while (preg_match('/\d\d\d\d/',$s)) $s=preg_replace('/(.*\d)(\d\d\d)/','$1,$2',$s);
  return $n>0?'$'.$s:'0';
}

function make_color($fld,$hex) {
  if ($fld && isset($_GET[$fld])) $hex=$_GET[$fld];
  if (preg_match('/([0-9a-fA-F]{6})([\-\|])([0-9a-fA-F]{6})/',$hex,$p)) return new awLinearGradient(make_color(NULL,$p[1]),make_color(NULL,$p[3]),($p[2]=='-'?90:0));
  $op=0;
  if (preg_match('/([0-9a-fA-F]{6})(-(\d+))?/',$hex,$p)) {
    $hex=$p[1];
    if (isset($p[2]) && $p[2]) $op=$p[3];
  }
  $rgb=sscanf($hex,'%02x%02x%02x');
  return new awColor($rgb[0],$rgb[1],$rgb[2],$op);
}

function set_font(&$obj,$fld) {
  if (isset($_GET[$fld])) {
    if (preg_match('/^(\d\d?)?(b|i|bi|ib)?(-|$)/',$_GET[$fld],$p)) {
      $s=$p[1];
      if (!$s) $s=10;
      $at=isset($p[2])?$p[2]:'';
      $font=strstr($at,'b')?(strstr($at,'i')?new TuffyBoldItalic($s):new TuffyBold($s)):(strstr($at,'i')?new TuffyItalic($s):new Tuffy($s));
      $obj->setFont($font,make_color($fld,'000000'));
    } else {
      $obj->setColor(make_color($fld,'000000'));
    }
  }
}

function get_val($fld,$val=NULL) {
  return isset($_GET[$fld])?$_GET[$fld]:$val;
}

$wd=isset($_GET['width'])?$_GET['width']:300;
$ht=isset($_GET['height'])?$_GET['height']:120;

$graph = new Graph($wd,$ht);
//$graph->title->set('Sales Performance');

$values = split(',',$_GET['data1']);
$v1max=max($values);

$group = new PlotGroup;

$plot = new BarPlot($values, 1, 2);
$plot->setBarColor(make_color('bar1_color','80FFFF-25'));
$plot->setBarSpace(0);

$group->add($plot);

$values = split(',',$_GET['data2']);
$v2max=max($values);

$plot = new BarPlot($values, 2, 2);
$plot->setBarColor(make_color('bar2_color','FFE080-25'));
//$plot->setBarColor(new LightOrange(25));
$plot->setBarSpace(0);

$group->add($plot);
//$group->axis->bottom->setLabelText(Array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'));
$group->axis->bottom->setLabelText(Array('J','F','M','A','M','J','J','A','S','O','N','D'));
set_font($group->axis->bottom->label,'month_font');
set_font($group->axis->left->label,'amount_font');
//$group->axis->bottom->label->setFont(new Tuffy(isset($_GET['month_label_size'])?$_GET['month_label_size']:10));
$group->grid->setBackgroundColor(make_color('grid_bg','FFFFFF'));
$group->setPadding(get_val('pad_left',54),get_val('pad_right'),get_val('pad_top',7),get_val('pad_bottom'));


$gmax=max($v1max,$v2max)*1.1;
$pwr=1;
while ($gmax>=80) {
  $gmax/=10;
  $pwr*=10;
}
if ($gmax>=40) $gstep=10;
else if ($gmax>=20) $gstep=5;
else if ($gmax>=12) $gstep=2.5;
else $gstep=2;
$gct=floor(1+$gmax/$gstep);

$group->setYMin(0);
$group->setYMax($pwr*$gstep*$gct);
$group->axis->left->setLabelNumber($gct+1);
$group->axis->left->label->setCallbackFunction('fmt_num');
$group->axis->left->label->move(4,0);

$graph->border->hide();
$graph->add($group);
$graph->setBackgroundColor(make_color('graph_bg','FFFFFF'));
$graph->draw();

?>