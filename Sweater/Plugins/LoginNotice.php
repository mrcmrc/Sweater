<?php

namespace Sweater\Plugins;
use Sweater;
use Sweater\Exceptions;
use Silk;

class LoginNotice extends BasePlugin {
	
	protected $intVersion = 0.5;
	protected $strAuthor = 'Arthur';
	
	public $blnConstructor = true;
	public $blnLogin = true;

	// Over-ride functions
	
	public function handleConstruction(){
		$this->addCustomXMLHandler('login', 'handleLoginNotification');
	}
	
	public function handleLoginNotification(Array $arrPacket, Sweater\Client $objClient){
		$strUser = $arrPacket['body']['login']['nick'];
		Silk\Logger::Log('Someone\'s attempting to login with the username \'' . $strUser . '\'');
	}
	
}

?>
