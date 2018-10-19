<?php
/*
 * kagen
 * 文字コード UTF-8
 */

class Utility {
	// this function is the alternative of "array_column" php >=5.5
	function flatarray($arr, $key, $distinct = false) {
		$result = array();
		foreach ($arr as  $elm) {
			if (isset($elm[$key])) {
				if (!$distinct || !in_array($elm[$key], $result)) {
					$result[] = $elm[$key];
				}
			}
		}
		return $result;
	}

	function assembarray($arr, $key, $valkey, $overwrite = false) {
		$result = array();
		foreach ($arr as  $elm) {
			if (isset($elm[$key]) && isset($elm[$valkey])) {
				if ($overwrite || !isset($result[$elm[$key]])) {
					$result[$elm[$key]] = $elm[$valkey];
				}
			}
		}
		return $result;
	}

	function logVar($arr) {
		error_log(var_export($arr, true));
	}

	function flatten_array_val($arr) {
		$fltarr = array();
		foreach($arr as $key => $value) {
			if (!is_array($value)) {
				$fltarr[] = $value;
			} else {
				$fltarr = array_merge($fltarr, $this->flatten_array_val($value));
			}
		}
		return $fltarr;
	}

	function array_intersect_flatten (array $array1, array $array2) {
		$flt_arr1 = $this->flatten_array_val($array1);
		$flt_arr2 = $this->flatten_array_val($array2);
		return array_intersect($flt_arr1,$flt_arr2);
	}

	function color_inverse($color){
		$color = str_replace('#', '', $color);
		if (strlen($color) != 6){ return '000000'; }
		$rgb = '';
		for ($x=0;$x<3;$x++){
			$c = 255 - hexdec(substr($color,(2*$x),2));
			$c = ($c < 0) ? 0 : dechex($c);
			$rgb .= (strlen($c) < 2) ? '0'.$c : $c;
		}
		return '#'.$rgb;
	}

}

?>