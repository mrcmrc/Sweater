<?php

include 'Extended/Plugins/Bot/Interfaces/BotMethods.php';

abstract class Bot implements BotMethods {
	
	private $arrListeners; // Contains all listening callbacks
	protected $arrPlayer; // Pseduo-player array
	protected $objParent; // Parent object
	protected $strName; // Bot name
	protected $strPlayer; // Pseudo-player string
	
	function __construct($strName, $objParent){
		$this->objParent = $objParent;
		$arrServer = $this->objParent->getServer();
		$this->strName = $arrServer['Bot'];
		$this->arrPlayer = $this->objParent->getConfig()['Bots'][$this->strName];
		if(!$this->arrPlayer) throw new BotException('\'' . $this->strName . '\' is an invalid bot name');
		$this->buildPlayerString();
	}
	
	public function addListener($strHandler, $mixCallback){
		if(!isset($this->arrListeners[$strHandler])){
			$this->arrListeners[$strHandler] = $mixCallback;
		}
	}
	
	public function buildPlayerString(){
		$arrPlayer = [
			0, // ID
			$this->strName,
			1, // Not exactly sure what this is for, but I think it's a language setting
			$this->arrPlayer['Color'],
			$this->arrPlayer['Head'],
			$this->arrPlayer['Face'],
			$this->arrPlayer['Neck'],
			$this->arrPlayer['Body'],
			$this->arrPlayer['Hands'],
			$this->arrPlayer['Feet'],
			$this->arrPlayer['Flag'],
			$this->arrPlayer['Photo'],
			mt_rand(100, 200),
			mt_rand(100, 200), 
			1, // Frame
			1, // Uh?
			6 * 146,
		];
		$strPlayer = implode('|', $arrPlayer);
		$this->strPlayer = $strPlayer;
	}
	
	public function getPlayerString(){
		return $this->strPlayer;
	}
	
	public function handleData($strPacket, $objClient){
		$arrData = explode('%', $strPacket);
		unset($arrData[0]);
		$arrData = array_values($arrData);
		array_pop($arrData);
		$strHandler = $arrData[2];
		if(isset($this->arrListeners[$strHandler]) && method_exists($this, $this->arrListeners[$strHandler])){
			call_user_method_array($this->arrListeners[$strHandler], $this, [$arrData, $objClient]);
		}
		unset($arrData);
	}
	
	public function sendMessage($strMessage, $objClient){
		$objClient->sendData('%xt%sm%' . $objClient->intIntRoom . '%0%' . $strMessage . '%');
	}

}

?>
