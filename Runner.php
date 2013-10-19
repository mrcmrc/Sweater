<?php

namespace Sweater;
use Silk;
use Silk\Exceptions;

spl_autoload_register(function($strClass){
	$strClass = str_replace('\\', '/', $strClass);
	include $strClass . '.php';
});

$vodExit = function($strMessage){
	echo $strMessage, chr(10), exit();
};

try {
	@list($strFile, $mixServerID) = $argv;
	if(!$mixServerID) $vodExit('Server ID not specified');
	$blnUpdate = array_search('--update-crumbs', $argv) > -1;
	$objServer = new Server($mixServerID, $blnUpdate);
} catch(Exceptions\StartupException $objException){
	 Silk\Logger::Log($objException->getMessage(), Silk\Logger::Error);
}

Silk\Logger::Log('Looping for incoming connections');
while(true){
	$objServer->acceptClients();
}

?>
