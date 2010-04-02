<?php
/*
* copyright Ryan Day 2010
* http://ryanday.org
* dual licensed gpl and mit
*/

date_default_timezone_set('America/Los_Angeles');

class chat_module extends default_module{
	protected $clients = array();

	public function sanitize_data($data){
		$data = @json_decode($data,true);
		if(!$data || !is_array($data)){
			$data = array();
		}
		return $data;
	}

	//do not implement unless you intend on using this event
	//protected function event_loop(){
		//use this to triggering additional responses optionally at the end of every read loop
	//}

	protected function event_open($data,$client){
		$this->clients[$client] = array('name'=>$client);
		//send back user id to client - cannot do this until web socket handshake is complete there should be another state call later for "open" to module interaction
		//response::me(json_encode(array('event'=>'auth','client'=>$client)));
		return true;
	}

	protected function event_close($data,$client){
		if(isset($this->clients[$client])){
			unset($this->clients[$client]);
		}

		$this->serveUserList();
	}

	protected function event_message($data,$client){
		$cmd = get($data,'cmd');
		switch($cmd){
			case "chat":
				if($message = get($data,'data')){
					$data = $this->chat_response($message);
					response::all(json_encode(array('event'=>'chat','data'=>$data)));
				}
				break;
			case "name":
				$name = preg_replace("/[^a-zA-Z0-9-._]/",'',get($data,'name'));
				if($name){
					$this->clients[$client]['name'] = $name;
				} else {
					response::me(array('event'=>'name','error'=>'invalid name','data'=>$data));
					break;
				}
			case "userlist":
				$this->serveUserList();
				break;
			default:
				response::me(json_encode(array('event'=>'command_error','data'=>$data)));
		}
	}

	//----------------

	private function serveUserList(){
		$data = array('event'=>'userlist','data'=>$this->clients);

		response::all(json_encode($data),true);

		$data['client'] = response::$current_socket_key;

		response::me(json_encode($data));
	}

	//----------------

	private function chat_response($message){
		$client = response::$current_socket_key;
		if(isset($this->clients[$client]['name'])){
			$client = $this->clients[$client]['name'];
		}

		return array(
				'date'=>date("m-d-Y g:i:s"),
				'client'=>$client,
				'message'=>htmlentities($message,ENT_QUOTES)
			);
	}
}
