<?
/*
* copyright Ryan Day 2010
* http://ryanday.org
* dual licensed gpl and mit
*/
class default_module{
	public function __construct(){

	}

	public function handle_event($event,$data,$client){
		if(DEBUG){
			echo "[DEBUG] handle event $event in ".__CLASS__."\n";
		}

		$data = $this->sanitize_data($data);

		$event_handler = 'event_'.$event;
		if(is_callable(array($this,$event_handler))){
			return $this->$event_handler($data,$client);
		}
		return false;
	}

	//? decode_data
	public function sanitize_data($data){
		return $data;
	}

	/*
	protected function event_loopstart(){
		//this is for 
	}
	*/

	protected function event_open($data,$client){
		echo "event_open called ".__CLASS__."\n";
		//i am interested in this client
		return true;
	}

	protected function event_close($data,$client){
		echo "event_close called ".__CLASS__."\n";
	}

	protected function event_message($data,$client){
		response::me('hello world, i am the default module!');
	}

	protected function event_error($data,$client){
		echo "event_error called ".__CLASS__."\n";

	}

	public function __destruct(){
		//clean up any module specific data defined outside the class
	}
}
