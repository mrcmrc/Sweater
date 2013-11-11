<?php

namespace Sweater\Plugins;
use Sweater;
use Sweater\Exceptions;
use Silk;

class Bot extends BasePlugin {
	
	const intPlayerId = 0;
	const strPlayerName = 'Bot';
	const intMembership = 1;
	const intColor = 14;
	const intHead = 413;
	const intFace = 0;
	const intNeck = 0;
	const intBody = 0;
	const intHand = 0;
	const intFeet = 0;
	const intFlag = 0;
	const intPhoto = 0;
	const intX = 100;
	const intY = 100;
	const intFrame = 1;
	const intModerator = 1;
	const intRank = 876;
	
	protected $intVersion = 0.5;
	protected $strAuthor = 'Arthur';
	
	public $blnConstructor = true;
	public $blnGame = true;

	// Over-ride functions
	
	public function handleConstruction(){
		$this->addCustomHandler('j#jr', 'handleJoinRoom');
	}
	
	private function buildPlayerData(){
		$arrPlayer = [
			self::intPlayerId,
			self::strPlayerName,
			self::intMembership,
			self::intColor,
			self::intHead,
			self::intFace,
			self::intNeck,
			self::intBody,
			self::intHand,
			self::intFeet,
			self::intFlag,
			self::intPhoto,
			self::intX,
			self::intY,
			self::intFrame,
			self::intModerator,
			self::intRank
		];
		$strPlayer = implode('|', $arrPlayer);
		return $strPlayer;
	}
	
	public function handleJoinRoom(Array $arrPacket, Sweater\Client $objClient){
		Silk\Logger::Log('Bot: user has joined room');
		$this->refreshPlayerInstance($objClient);
	}
	public function refreshPlayerInstance(Sweater\Client $objClient){
		$objClient->sendXt('rp', $objClient->getIntRoom(), self::intPlayerId);
		$objClient->sendXt('ap', $objClient->getIntRoom(), $this->buildPlayerData());
	}
	
	public function sendMessage($strMessage, Sweater\Client $objClient){
		$this->refreshPlayerInstance($objClient);
		$objClient->sendXt('sm', $objClient->getIntRoom(), self::intPlayerId, $strMessage);
	}
	
}

?>
