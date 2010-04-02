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
require(SERVER_ROOT.'/lib/response.php');

server::init($sockets,$clients);

if(($listen = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
	die("socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n");
}

socket_set_option($listen, SOL_SOCKET, SO_REUSEADDR, 1);

if(socket_bind($listen, server::config('address'), server::config('port')) === false) {
	die("socket_bind() failed: reason: " . socket_strerror(socket_last_error($listen)) . "\n");
}

if(socket_listen($listen, 5) === false) {
	die(" socket_listen() failed: reason: " . socket_strerror(socket_last_error($listen)) . "\n");
}


$sockets[] = $listen;
$clients[] = null;

do {
	$read = $sockets;
	$write = null;
	$except = null;


	
	$r = socket_select($read, $write, $except, null);
	
	if($r === false) {
		$err = socket_strerror(socket_last_error());
		echo "[ERROR] socket_select() failed, reason: $err\n";
		if($err == 'Success'){
			/**
			NOTE having issues replicating now..

			this bug happens when i connect to the app via browser and refresh. it seems to be environment based
			it tight loops flooding the log with socket warnings

			PHP 5.2.11 with Suhosin-Patch 0.9.7 (cli) (built: Sep 24 2009 12:40:58)
			*/
			var_dump($read);
			exit('bad socket error that doesnt happen locally! socket count: '.count($sockets));
		}
	} else if($r > 0) {

		foreach($read as $sock => $s) {
			/*
			an undocumented feature in php 5.3 is that $sock is the key in the sockets array
			in lesser versions the resulting read array is reindexed from 0
			as this wont work across the board ill have to array search =(
			*/
			//$s = $read[$sock];
			$sock = array_search($s,$sockets);

			
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

					unset($sockets[$sock]);
					unset($clients[$sock]);

					//array_splice($sockets, $sock, 1);
					//array_splice($clients, $sock, 1);

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
//---------------------
