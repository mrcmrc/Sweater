<?php

namespace Silk;
use Silk\Interfaces;

@date_default_timezone_set(date_default_timezone_get()); // Using the system's time

// TODO: Add colors
class Logger implements Interfaces\LogTypes {

	static function Log($strText, $strType = self::Info){
		echo '[', date('h\:i\:s'), '][', strtoupper($strType), '] > ', $strText, chr(10);
		if($strType == self::Error){
			if(function_exists('posix_kill') === false){
				exit();
			} else {
				$intProcess = posix_getpid();
				posix_kill($intProcess, SIGINT);
			}
		}
	}
	
}

?>
