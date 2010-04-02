<?php
/*
* copyright Ryan Day 2010
* http://ryanday.org
* dual licensed gpl and mit
*/
error_reporting(E_ALL);

define('SERVER_ROOT',dirname(__FILE__));
define('MODULE_PATH', SERVER_ROOT.'/modules');
require(SERVER_ROOT.'/lib/server.php');

server::init($sockets,$clients);

if(($listen = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
	die("battle server: socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n");
}

socket_set_option($listen, SOL_SOCKET, SO_REUSEADDR, 1);

if(socket_bind($listen, server::config('address'), server::config('port')) === false) {
	die("battle server: socket_bind() failed: reason: " . socket_strerror(socket_last_error($listen)) . "\n");
}

if(socket_listen($listen, 5) === false) {
	die("battle server: socket_listen() failed: reason: " . socket_strerror(socket_last_error($listen)) . "\n");
}


$sockets[] = $listen;
$clients[] = null;

do {
	$read = $sockets;
	$write = null;
	$except = null;
	
	$r = socket_select($read, $write, $except, null);
	
	if($r === false) {
		echo "[ERROR] socket_select() failed, reason: " . socket_strerror(socket_last_error()) . "\n";
	} else if($r > 0) {
		
		foreach($read as $sock => $s) {
			
			$s = $read[$sock];

			
			if($s === $listen /*|| $s === $listen_policy*/) {
				if(($new = socket_accept($s)) === false) {
					echo "[ERROR] socket_accept() failed: reason: " . socket_strerror(socket_last_error($s)) . "\n";
				} else {
					if(false/*$s === $listen_policy*/) {
						if(DEBUG) {
							echo "[DEBUG] policy server: client connected...\n";
						}
					} else {
						if(DEBUG) {
							echo "[DEBUG] client connected...\n";
						}
					}
					
					$sockets[] = $new;
					$clients[] = false;

					if(DEBUG) {
						echo "[DEBUG] about to dispatch open event\n";
					}

					module::dispatch_event('open',false,count($sockets)-1,array());
				}
			} else {

				response::set_current_sock($sock,$s);

				//if the socket is not a websocket

				if($clients[$sock] != 'websocket'){

					$str = '';
					do {
						$buf = socket_read($s, 2048);
						if($buf === false) {
							echo "[ERROR] socket_read() failed: reason: " . socket_strerror(socket_last_error($s)) . "\n";

							module::dispatch_event('close',false,$sock,array());

							// close socket
							socket_close($s);
							unset($sockets[$sock]);
							unset($clients[$sock]);
							//array_splice($sockets, $sock, 1);
							//array_splice($clients, $sock, 1);
						}
						
						$str .= $buf;
					} while(strlen($buf) === 2048);//if buffer is full keep looping for more data
					$str = trim($str);

				} else {
					$str = web_socket_protocol::read_message($s);
				}

				if(DEBUG) {
					echo "[DEBUG] received: $str\n";
				}


				if($str == 'quit' || $str === "" || $str === "<policy-file-request/>") {
					
					if(DEBUG) {
						echo "[DEBUG] client $sock disconnecting...\n";
					}
					

					module::dispatch_event('close',false,$sock,array());

					// close socket
					socket_close($s);

					array_splice($sockets, $sock, 1);
					array_splice($clients, $sock, 1);

				} else if (!$clients[$sock] && web_socket_protocol::is_handshake($str)){

					if(DEBUG){
						echo "web socket request!\n";
					}

					response::me(web_socket_protocol::handshake($str));

					$clients[$sock] = 'websocket';//flag the client as a web socket client

				} else {

					///TODO create client_data and
					module::dispatch_event('message',$str,$sock,array());

				}
			}
		}
	}
} while(true);

socket_close($listen);
if($listen_policy){
	socket_close($listen_policy);
}




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

	public static function all($response_to_all,$not_me = false){
		global $clients;
		if($response_to_all) {
			if(DEBUG) {
				echo "[OUT] broadcasting message:\n$response_to_all\n\n";
			}
			
			foreach(self::get_sockets() as $i => $s) {
				//$not_me = ($not_me && $i != self::$current_socket_key);
				if($clients[$i] !== null) {
					self::write($s,$response_to_all);
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
		socket_write($s, $data, strlen($data));
	}

	private static function get_sockets(){
		return $GLOBALS['sockets'];
	}
}



//---------------------