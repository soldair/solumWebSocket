<?php
/*
* copyright Ryan Day 2010
* http://ryanday.org
* dual licensed gpl and mit
*/


///TODO remove globals
class response{

	public static $current_socket;
	public static $current_socket_key;

	public static function me($response){
		if($response) {
			if(DEBUG) {
				echo "[OUT] responding:\n$response\n\n";
			}

			self::write(self::$current_socket,$response);
		}
	}

	public static function all($response_to_all){
		if($response_to_all) {
			if(DEBUG) {
				echo "[OUT] broadcasting message:\n$response_to_all\n\n";
			}
			
			foreach($GLOBALS['sockets'] as $i => $s) {
				
				if($GLOBALS['clients'] !== null) {
					self::write($s,$response_to_all);
				}
			}
		}
	}

	public static function all_but_me($response_to_all_but_me){
		if($response_to_all_but_me) {
			if(DEBUG) {
				echo "[OUT] everyone but me message:\n$response_to_all\n\n";
			}
			
			foreach($GLOBALS['sockets'] as $i => $s) {
				if($GLOBALS['clients'][$i] !== null && $i != self::$current_socket_key) {
					self::write($s,$response_to_all_but_me);
				}
			}
		}
	}

	public static function set_current_sock($sock,$s){
		self::$current_socket_key = $sock;
		self::$current_socket = $s;
	}

	public static function write($s,$data){
		if($GLOBALS['clients'][self::$current_socket_key] == 'websocket'){
			$data = web_socket_protocol::frame_response($data);
		}

		$bytes = @socket_write($s, $data, strlen($data));
		if($bytes === false){
			//client has disconnected
		}
	}

}