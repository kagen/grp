<?php
/*
 * Copyright(c) 2009 limitlink,Inc. All Rights Reserved.
 * http://limitlink.jp/
 * 文字コード UTF-8
 */
require_once('application/config.php');
require_once(DIR_VIEW.'header.php');
?>
<script type="text/javascript" src="<?=$root?>js/timerecorder.js"></script>
<script type="text/javascript" src="<?=$root?>js/qrcam/voice.js"></script>
<script type="text/javascript" src="<?=$root?>js/qrcam/llqrcode.js"></script>
<script type="text/javascript" src="<?=$root?>js/qrcam/webqr.js"></script>
<script type="text/javascript" src="<?=$root?>js/library/raphael-min.js"></script>
<script type="text/javascript" src="<?=$root?>js/gistfile1.js"></script>
<script type="text/javascript" src="<?=$root?>js/application.js"></script>

<link href="<?=$root?>css/clock.css" rel="stylesheet" type="text/css" />

<div class="header">
	<div class="headertitle">
		<a href="<?=$root?>index.php"><img src="<?=$root?>images/logo.gif" /></a>
		アウェイ建築評価ネット株式会社
	</div>
	<div class="clearer"></div>
</div>
<div id="mainbody">
<table>
<tr><td colspan="2" align="center">
<div id="outdiv">
</div>
<div class="graysmallradius" id="result"></div>
</td></tr>
</table>
</div>
<canvas id="qr-canvas" width="800" height="600" style="display:none"></canvas>
<script type="text/javascript">loadQrReader();</script>
<div class="clock">
<div id="Date"></div>
  <ul class="time">
      <li id="hours" class="time"></li>
      <li id="point" class="time">:</li>
      <li id="min" class="time"></li>
      <li id="point" class="time">:</li>
      <li id="sec" class="time"></li>
  </ul>
</div>
<?php if($_GET['nonews']!=1):?>
<div class="section">

<h3>お知らせ</h3>
<div id="news_list">
</div>
</div>
<?php endif;?>
<script type="text/javascript">
var paper = Raphael(130, 160, 110, 110);
var rect = paper.rect(10, 10, 90, 90);
rect.attr("fill", "none");
rect.attr("stroke", "red");
rect.attr("stroke-width", 4);
/*
var scanline = paper.path( "M10,10L60,10" );
scanline.attr("stroke", "red");
var scanmove = Raphael.animation({path: "M10,60L60,60"}, 2500).repeat(Infinity);
scanline.animate(scanmove);
*/

/**
*
*/
$(document).ready(function() {
//Create two variable with the names of the months and days in an array
getparams = App.getParams();
if(getparams.nonews!=1){
	ajax_send('/' + window.location.pathname.split('/')[1] + '/news/get.php',{'recorder_disp':1,'news_hide':0},function(response){$("#news_list").html(response)});
}

var monthNames = [ "1月", "2月", "3月", "4月", "5月", "6月", "7月", "8月", "9月", "10月", "11月", "12月" ];
var dayNames= ["日曜日","月曜日","火曜日","水曜日","木曜日","金曜日","土曜日"]
var currenttime = '<?php print date("F d, Y H:i:s", time())?>';

setInterval( function() {
	// Create a newDate() object
	//var newDate = new Date(currenttime);
	var newDate = new Date();
	// Extract the current date from Date object
	newDate.setDate(newDate.getDate());
	// Output the day, date, month and year
	$('#Date').html(newDate.getFullYear() + "年 " + monthNames[newDate.getMonth()] + newDate.getDate() + '日 （' + dayNames[newDate.getDay()] + '）');
},1000);

setInterval( function() {
	// Create a newDate() object and extract the seconds of the current time on the visitor's
	//var seconds = new Date(currenttime).getSeconds();
	var seconds = new Date().getSeconds();
	// Add a leading zero to seconds value
	$("#sec").html(( seconds < 10 ? "0" : "" ) + seconds);
	},1000);

setInterval( function() {
	// Create a newDate() object and extract the minutes of the current time on the visitor's
	//var minutes = new Date(currenttime).getMinutes();
	var minutes = new Date().getMinutes();
	// Add a leading zero to the minutes value
	$("#min").html(( minutes < 10 ? "0" : "" ) + minutes);
   },1000);

setInterval( function() {
	// Create a newDate() object and extract the hours of the current time on the visitor's
	//var hours = new Date(currenttime).getHours();
	var hours = new Date().getHours();
	// Add a leading zero to the hours value
	$("#hours").html(( hours < 10 ? "0" : "" ) + hours);
   }, 1000);
if(getparams.nonews!=1){
	setInterval(function(){
		ajax_send('/' + window.location.pathname.split('/')[1] + '/news/get.php',{'recorder_disp':1,'news_hide':0},function(response){$("#news_list").html(response)});
	   }, 600000);
	}
}
);
</script>