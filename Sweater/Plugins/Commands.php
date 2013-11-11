<?php

namespace Sweater\Plugins;
use Sweater;
use Sweater\Exceptions;
use Silk;

class Commands extends BasePlugin {
	
	protected $arrDependencies = ['Bot'];
	protected $intVersion = 0.5;
	protected $strAuthor = 'Arthur';
	
	public $blnConstructor = true;
	public $blnGame = true;
	
	private $arrCommands = [
		'AC' => 'handleAddCoins',
		'AF' => 'handleAddFurniture',
		'AI' => 'handleBuyInventory',
		'NICK' => 'handleNicknameChange',
		'PING' => 'handlePing',
		'UI' => 'handleUpdateIgloo'
	];
	
	private $objBot;
	
	// Over-ride functions
	
	public function handleConstruction(){
		$this->addCustomXtHandler('m#sm', 'handlePlayerMessage');
		$this->objBot = $this->objServer->arrPlugins['Bot'];
	}
	
	private function handleAddCoins(Array $arrArguments, Sweater\Client $objClient){
		Silk\Logger::Log('Adding coins!');
		list($intCoins) = $arrArguments;
		if(is_numeric($intCoins) && $intCoins < 5001 && $objClient->getCoins() < 1000000){
			$objClient->addCoins($intCoins);
			$objClient->sendXt('zo', $objClient->getIntRoom(), $objClient->getCoins());
		}
	}
	
	private function handleAddFurniture(Array $arrArguments, Sweater\Client $objClient){
		list($intFurniture) = $arrArguments;
		if(array_key_exists($intFurniture, $this->arrFurniture)){
			$objClient->addFurniture($intFurniture);
		}
	}
	
	private function handleBuyInventory(Array $arrArguments, Sweater\Client $objClient){
		Silk\Logger::Log('Purchashing item!');
		list($intItem) = $arrArguments;
		if(array_key_exists($intItem, $this->objServer->arrItems)){
			$objClient->addItem($intItem);
		}
	}
	
	private function handleNicknameChange(Array $arrArguments, Sweater\Client $objClient){
		if($objClient->getModerator()){
			list($strUsername) = $arrArguments;
			$objClient->setNickname($strUsername);
			$intRoom = $objClient->getExtRoom();
			$blnIgloo = $intRoom > 1000;
			$strMethod = $blnIgloo ? 'handleJoinPlayer' : 'handleJoinRoom';
			$this->objServer->$strMethod([4 => $intRoom, 0, 0], $objClient);
		}
	}
	
	private function handlePing(Array $arrArguments, Sweater\Client $objClient){
		$this->objBot->sendMessage('Pong', $objClient);
	}
	
	private function handleUpdateIgloo(Array $arrArguments, Sweater\Client $objClient){
		list($intIgloo) = $arrArguments;
		$objClient->updateIgloo($intIgloo);
	}
	
	// Parses message to get commands argument(s), and calls command handler
	// This shouldn't require any editing
	protected function handlePlayerMessage(Array $arrPacket, Sweater\Client $objClient){
		$strMessage = $arrPacket[5];
		$blnCommand = substr($strMessage, 0, 1) == '!';
		if($blnCommand){
			$strStripped = substr($strMessage, 1);
			$blnArguments = strpos($strStripped, ' ') > -1;
			$arrArguments = [];
			if($blnArguments){
				$arrArguments = explode(' ', $strStripped);
				$strCommand = $arrArguments[0];
				unset($arrArguments[0]);
				$arrArguments = array_values($arrArguments);
				unset($arrFixed);
			} else {
				$strCommand = $strStripped;
			}
			$strCommand = strtoupper($strCommand);
			if(array_key_exists($strCommand, $this->arrCommands)){
				$strHandler = $this->arrCommands[$strCommand];
				call_user_func([$this, $strHandler], $arrArguments, $objClient);
			}
		}
	}
	
}

?>
