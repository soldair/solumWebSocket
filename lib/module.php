<?php
/*
* copyright Ryan Day 2010
* http://ryanday.org
* dual licensed gpl and mit
*/
require_once(MODULE_PATH.'/default.php');

class module{

	private static $modules = array();
	private static $module_data = array();
	private static $module_data_default = array('clients'=>array());

	public static function load($name){

		if(DEBUG){
			echo "[DEBUG] loading module $name\n";
		}

		$path = MODULE_PATH.'/'.$name.'.php';

		if(file_exists($path)){

			require_once($path);

			$class = self::module_class($name);

			if(class_exists($class)){

				$module = new $class();

				if($module instanceof default_module){

					self::$module_data[$class] = self::$module_data_default;
					self::$modules[$class] = $module;
					return $module;

				} else {

					exit("[FATAL ERROR] module $class does not extend default_module\n");

				}

			} else {

				exit("[FATAL ERROR] cannot find module class $class\n");

			}
		} else {

			exit("[FATAL ERROR] cannot find module by name $name\n");

		}
		return false;
	}

	public static function dispatch_event($event,$data,$sock){
		if(DEBUG){
			echo "[DEBUG] dispatch event '$event' ".count(self::$modules)."\n";
		}
		foreach(self::$modules as $class=>$m){
			$res = false;
			$accepted_sock = isset(self::$module_data[$class]['clients'][$sock]);
			if($accepted_sock || $event == 'open'){
				echo 'sock: '.$sock."\n";
				$res = $m->handle_event($event,$data,$sock);
			} else {
				if(DEBUG){
					echo "[DEBUG] did not attempt to handle event $event, ($class,$sock)\n";
					return;
				}
			}
			switch($event){
				case "open":
					if($res){
						if(DEBUG){
							echo "[DEBUG] adding $sock to the approved clients list for module $class\n";
						}
						self::add_client($class,$sock,1);
					} else {
						if(DEBUG){
							echo "[DEBUG] no res from handle_event for 'open'\n";
						}
					}
					break;
				case 'close':
					self::remove_client($class,$sock);
					break;
			}
		}
	}

	public static function remove_module($class){
		if(isset(self::$modules[$class])){

			unset(self::$modules[$class]);
			unset(self::$module_data[$class]);

		}
	}

	public static function add_client($class,$sock,$data = 1){
		self::$module_data[$class]['clients'][$sock] = $data;
	}

	public static function remove_client($class,$sock){
		if(isset(self::$module_data[$class]['clients'][$sock])){
			unset(self::$module_data[$class]['clients'][$sock]);
		}
	}

	private static function module_class($name){
		return $name.'_module';
	}
}
