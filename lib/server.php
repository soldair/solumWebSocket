<?php
/*
* copyright Ryan Day 2010
* http://ryanday.org
* dual licensed gpl and mit
*/
class server{
	private static $config;
	private static $initalized;

	public static $clients = array();
	public static $sockets = array();

	public static function init(&$sockets,&$clients){
		if(self::$initalized){

			return;

		}

		$sockets =& self::$sockets;
		$clients =& self::$clients;

		self::$initalized = 1;

		// Allow the script to hang around waiting for connections.
		set_time_limit(0);
		
		// Turn on implicit output flushing so output gets sent imediately
		ob_implicit_flush();

		$DEBUG = true;
		define('DEBUG',$DEBUG);

		self::require_libs();
		self::load_config();

		if(issetArg('help') || issetArg('?')) {

			self::show_help();
			exit();

		}

		if(!($modules = self::config('modules'))){

			exit("[FATAL ERROR] no modules loaded in the config.\n");

		}

		foreach($modules as $m){
			module::load($m);
		}
	}

	public static function config($k){
		return get(self::$config,$k);
	}

	private static function load_config(){
		$config = snarf_scope(SERVER_ROOT.'/config.php');
		
		$config['address'] = $address = getArg('a', 'address', get($config,'ip','0.0.0.0'));

		$port = get($config,'port',10000);
		
		if(strpos($address,':') !== false) {

			$parts = explode(':',$address);

			$address = $parts[0];

			$port = (int) $parts[1];
		}

		$config['port'] = getArg('p', 'port', $port);

		self::$config = $config;
	}

	private static function require_libs(){

		$lib_path = SERVER_ROOT.'/lib';

		require_once($lib_path.'/general.php');
		require_once($lib_path.'/module.php');
		require_once($lib_path.'/web_socket_protocol.php');

	}

	private static function show_help(){
?>
--------------------
args:
--debug	turn on debug output


-a or --address	ip address

		default address is 0.0.0.0

		the port may be appended to the ip address
		if you precede it with a ":"

		example: -a127.0.0.1


-p or --port	the port to listen to

		example: -p10000


--------------------
address: <?php echo self::config('address')?>

port: <?php echo self::config('port')?>

--------------------


<?php
	}
}