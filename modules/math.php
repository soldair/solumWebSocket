<?php
/*
* copyright Ryan Day 2010
* http://ryanday.org
* dual licensed gpl and mit
*/

//why would anyone want to do math over a socket... for testing =P
class math_module extends default_module{

	protected function event_message($data,$client){
		$return = array('error'=>true,'data'=>false);
		$is_equation = "/^[0-9^.+*\/%()-]+$/";
		if(preg_match($is_equation,$data)){
			$data = escapeshellcmd($data);
			$answer = trim(`php -r "echo $data;" 2>/dev/null`);

			if($answer){
				$return['error'] = preg_match($is_equation,$answer)?false:true;
			}
			$return['data'] = $answer;
		}

		response::me(json_encode($return));
	}

}
