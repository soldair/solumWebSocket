<?php
/*
* copyright Ryan Day 2010
* http://ryanday.org
* dual licensed gpl and mit
*/

//haystack needle is not my fav but its common

function get($arr, $key, $def = null) {
	if(isset($arr[$key]) && !empty($arr[$key])) {
		return $arr[$key];
	}
	
	return $def;
}

function extractable($arr,$keys){
	$out = array();
	foreach($keys as $k){
		$out[$k] = get($arr,$k);
	}
	return $out;
}

function issetArg($k) {
	$a = getArgs();
	return isset($a[$k]);
}

function getArg($short, $long, $def = null) {
	$args = getArgs();
	$r = get($args, $short, false);
	if($r === false) {
		if($long) {
			$r = get($args, $long, $def);
		} else {
			$r = $def;
		}
	}
	
	return $r;
}

function getArgs() {
	static $args;
	global $argv;

	if($args != null) {
		return $args;
	}

	$args = array();
	$state = 'looking_for_arg';
	$holding_key = false;
	for($i = 1, $count = count($argv); $i < $count; $i++) {
		$v = $argv[$i];
	
		if($state === "looking_for_arg") {
			if($v) {
				if(strpos($v,'-') === 0) {
					if(strlen($v) > 2) {
						if($v[1] === '-') {
							//suport --anynumberofcharkeys
							$holding_key = ltrim($v,'-');
							$args[$holding_key] = '';
							$state = 'looking_for_value';
						} else {
							$key = $v[1];
							$value = substr($v,2);
							$args[$key] = $value;
						}
					} else {
						$holding_key = ltrim($v,'-');
						$args[$holding_key] = '';
						$state = 'looking_for_value';
					}
				} else {
					$args[] = $v;
				}
			}
		} else if($state === "looking_for_value") {
			$args[$holding_key] = $v;
			$state = "looking_for_arg";
		}
	}
	
	return $args;
}

function snarf_scope($file){
	@include($file);
	return get_defined_vars();
}


function parse_headers($headers){
	$lines = explode("\r\n",$headers);
	$out = array();
	foreach($lines as $l){
		if(($pos = strpos($l,':')) !== false){
			$out[substr($l,0,$pos)] = trim(substr($l,$pos+1));
		} else {
			$out[] = $l;
		}
	}
	return $out;
}