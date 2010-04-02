<?php
/*
* copyright Ryan Day 2010
* http://ryanday.org
* dual licensed gpl and mit
*/


//   http://www.whatwg.org/specs/web-socket-protocol/
class web_socket_protocol{
	public static function is_handshake($str){
		return strpos($str,"GET / HTTP/1.1\r\nUpgrade: WebSocket\r\nConnection: Upgrade\r\n") === 0;
	}

	public static function handshake($handshake){
		$headers = parse_headers($handshake);
		$ws_location = self::location_from_host($headers['Host']);

		///TODO update handshake to comply with new SEC-* websocket changes

		return "HTTP/1.1 101 Web Socket Protocol Handshake\r\n"
		."Upgrade: WebSocket\r\n"
		."Connection: Upgrade\r\n"
		."WebSocket-Origin: {$headers['Origin']}\r\n"
		."WebSocket-Location: $ws_location\r\n\r\n";
	}

	public static function read_message($s){

		$frame_type_str = socket_read($s,1);

		$message = '';

		$frame_type = ord($frame_type_str);
		if (($frame_type & 0x80) == 0x80){
			# The payload length is specified in the frame.
			# Read and discard
			$len = web_socket_protocol::payload_length($s);
			if($len){
				 $message = socket_read($s,$len);
			}
		} else {
			# The payload is delimited with \xff.c
			$data = '';
			$chr = '';
			do{
				$chr = socket_read($s,1);
				if($chr != "\xff"){
					$data .= $chr;
				}

			}while($chr && $chr != "\xff");

			if($frame_type == 0x00){
				# I SHOULD ONLY ACCEPT THE DATA if the frame type is a null byte
				$message = $data;
			} else {
				# Discard data of other types.
				if(DEBUG){
					echo "[DEBUG] frame type is not a  null byte! TRASH\n";
				}
			}
		}

		return $message;
	}

	public static function payload_length($s){
		$length = 0;
		$max = 5000;
		while(true){
			$b_str = socket_read($s,1);
			$b = ord($b_str);
			$length = $length * 128 + ($b & 0x7f);
			if (($b & 0x80) == 0){
				break;
			}
			if(!$max){
				if(DEBUG){
					echo "[DEBUG] payload length ran for 5000 chars i probably have an issue. this should have been framed with a len specified before the message\n";
				}
				break;
			}
			$max--;
		}
		return $length;
	}

	public static function frame_response($response){
		//added str_replace to sanitize response / frame injection
		return "\x00".str_replace("\xff",'',$response)."\xff";
	}

	private static function location_from_host($host){
		$loc = 'ws://';
	
		$url = parse_url($host);
	
		$host = get($url,'host');
		$port = get($url,'port');
	
		$loc .= $host;
		if ($port){
			$loc .= ':'.$port;
		}

		///TODO - fix this uri
		$uri = '/';
	
		return $loc.$uri;
	}
}