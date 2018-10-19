<?php
/*
 * Copyright(c) 2009 limitlink,Inc. All Rights Reserved. http://limitlink.jp/ 文字コード UTF-8
 */
class Helper {
	function selector($name, $option, $item = '', $attribute = '', $id = '', $addBlank = false, $addOptions = null, $blankDisp = '') {
		$string = ($addBlank ? "<option>".$blankDisp."</option>" : "");
		$string .= ($addOptions ? $addOptions : "");
		if (is_array ( $option ) && count ( $option ) > 0) {
			foreach ( $option as $key => $value ) {
				if ($key == $item) {
					$selected = ' selected="selected"';
				} else {
					$selected = '';
				}
				$string .= sprintf ( '<option value="%s"%s>%s</option>', $key, $selected, $value );
			}
		}
		$selector = sprintf ( '<select id="%s" name="%s"%s>%s</select>', ($id != '' ? $id : $name), $name, $attribute, $string );
		return $selector;
	}

	function selectorColor($name, $option, $item = '', $attribute = '', $id = '', $addBlank = false, $addOptions = null, $blankDisp = '') {
		$string = ($addBlank ? "<option>".$blankDisp."</option>" : "");
		$string .= ($addOptions ? $addOptions : "");
		if (is_array ( $option ) && count ( $option ) > 0) {
			foreach ( $option as $key => $value ) {
				if ($key == $item) {
					$selected = ' selected="selected"';
					$selectedStyle = ' style="background:'.$value[1].';color:$value[2]"';
				} else {
					$selected = '';
				}
				$string .= sprintf ( '<option value="%s"%s style="background:%s; color:%s;">%s</option>', $key, $selected, $value[1], $value[2], $value[0]);
			}
		}
		$selector = sprintf ( '<select id="%s" name="%s"%s onchange="App.changeColor(this)"%s>%s</select>', ($id != '' ? $id : $name), $name, $attribute, $selectedStyle, $string );
		return $selector;
	}

	function option($begin, $end, $item = '', $addBlank = false) {
		$option = '';
		for($i = $begin; $i <= $end; $i ++) {
			$value = $i;
			if ($value == $item && strlen ( $item ) > 0) {
				$selected = ' selected="selected"';
			} else {
				$selected = '';
			}
			$option .= sprintf ( '<option value="%d"%s>%d</option>', $value, $selected, $value );
		}
		return ($addBlank ? "<option></option>" : "") . $option;
	}
	function checkbox($name, $value, $item, $label, $caption, $attribute = '') {
		if ($value == $item) {
			$checked = ' checked="checked"';
		} else {
			$checked = '';
		}
		$checkbox = '<input type="checkbox" name="%s" id="%s" value="%s"%s%s /><label for="%s">%s</label>';
		$checkbox = sprintf ( $checkbox, $name, $label, $value, $checked, $attribute, $label, $caption );
		return $checkbox;
	}
	function radio($name, $value, $item, $label, $caption, $attribute = '') {
		if ($value == $item) {
			$checked = ' checked="checked"';
		} else {
			$checked = '';
		}
		$radio = '<input type="radio" name="%s" id="%s" value="%s"%s%s /><label for="%s">%s</label>';
		$radio = sprintf ( $radio, $name, $label, $value, $checked, $attribute, $label, $caption );
		return $radio;
	}
	function attribute($attribute, $value, $string) {
		if ($value == $string) {
			$attribute = ' ' . $attribute . '="' . $attribute . '"';
		} else {
			$attribute = '';
		}
		return $attribute;
	}
	function resizeImage($image, $maxwidth = 200, $maxheight = 150) {
		$tag = '';
		$size = @getimagesize ( $image );
		if ($size [0] > $maxwidth && $size [1] > $maxheight) {
			if ($size [1] / $size [0] < $maxheight / $maxwidth) {
				$tag = 'width:' . $maxwidth . 'px;';
			} else {
				$tag = 'height:' . $maxheight . 'px;';
			}
		} elseif ($size [0] > $maxwidth) {
			$tag = 'width:' . $maxwidth . 'px;';
		} elseif ($size [1] > $maxheight) {
			$tag = 'height:' . $maxheight . 'px;';
		}
		if (strlen ( $tag ) > 0) {
			$tag = ' style="' . $tag . '"';
		}
		return $tag;
	}
	function multisort($data, $sortkey, $desc = '') {
		if (is_array ( $data ) && count ( $data ) > 0) {
			foreach ( $data as $key => $row ) {
				$array [$key] = $row [$sortkey];
			}
			if ($desc == 'desc') {
				$desc = SORT_DESC;
			} else {
				$desc = SORT_ASC;
			}
			array_multisort ( $array, $desc, $data );
		}
		return $data;
	}
	function numpad2($num) {
		return sprintf ( '%02d', $num );
	}

	function addtime($strtime, $straddtime, $dropSeconds = false){

		$oprt1 = "";
		$oprt2 = "";
		$oprt = "+";

		if(substr($strtime,0,1) == "-") {
			$oprt1 = "-";
			$strtime = substr($strtime, 1);
		}

		if(substr($straddtime,0,1) == "-") {
			$oprt2 = "-";
			$straddtime = substr($straddtime, 1);
		}

		if($oprt1 == "-" && $oprt2 != "-"){
			$temp = $strtime;
			$strtime = $straddtime;
			$straddtime = $temp;
		}elseif ($oprt1 != "-" && $oprt2 == "-"){
			$oprt = "-";
		}

		if($strtime == null || $strtime == '' || $strtime === 0){
			$strtime = '00:00:00';
		}
		if($straddtime == null || $straddtime == '' || $straddtime === 0){
			$straddtime = '00:00:00';
		}

		if (preg_match ( '/^[0-9]+:([0-5][0-9])$/', $strtime )) {
			$strtime = $strtime.":00";
		}

		if (preg_match ( '/^[0-9]+:([0-5][0-9])$/', $straddtime )) {
			$straddtime = $straddtime.":00";
		}


		if (!preg_match ( '/^[0-9]+:([0-5][0-9]):([0-5][0-9])$/', $strtime ) || !preg_match ( '/^[0-9]+:([0-5][0-9]):([0-5][0-9])$/', $straddtime )) {
			return false;
		}

		$arytime = explode(":", $strtime);
		$aryaddtime = explode(":", $straddtime);

		$timeinsec = $arytime[0]*60*60+$arytime[1]*60+$arytime[2];
		$addtimeinsec = $aryaddtime[0]*60*60+$aryaddtime[1]*60+$aryaddtime[2];

		if ($oprt == "+") {
			$timeinsec += $addtimeinsec;
		} elseif ($oprt == "-") {
			$timeinsec -= $addtimeinsec;
		}
		$resulttime = sprintf("%02d:%02d:%02d", floor($timeinsec/3600), ($timeinsec/60)%60, $timeinsec%60);

		return ($dropSeconds?$this->dropsecond($resulttime):$resulttime);
	}

	function nicetime($time, $format = 'G:i') {
		if ($time == "00:00" || $time == "00:00:00" || $time == null) {
			return "";
		} elseif (preg_match('/^(([0-1][0-9])|([2][0-3])):([0-5][0-9]):([0-5][0-9])$/', $time)) {
			return date($format, strtotime($time));
		} else {
			return date($format, $time);
		}
	}

	function nicedatetime($time, $format = 'Y-m-d H:i') {
		if ($time == "0000-00-00 00:00" || $time == "0000-00-00 00:00:00" || $time == "0000/00/00 00:00:00" || $time == null) {
			return "";
		} else {
			return date($format, strtotime($time));
		}
	}

	function dropsecond($time) {
		if (preg_match ( '/^[0-9]+:([0-5][0-9])$/', $time )) {
			return $time;
		} elseif (preg_match ( '/^[0-9]+:([0-5][0-9]):([0-5][0-9])$/', $time )) {
			$arytime = explode(":", $time);
			return $arytime[0].":".$arytime[1];
		}else{
			return "00:00";
		}
	}

	function time2sec($time) {
		sscanf($time, "%d:%d:%d", $hours, $minutes, $seconds);
		return isset($seconds) ? $hours * 3600 + $minutes * 60 + $seconds : $hours * 3600 + $minutes * 60;
	}

	function makeClickableLinks($s) {
		return preg_replace('@((https?://|file:///)[^\n\r]+)@', '<a href="$1" target="_blank">$1</a>', $s);
	}
}
?>