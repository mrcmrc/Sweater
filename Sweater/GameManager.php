<?php

namespace Sweater;
use Silk;

class GameManager extends Silk\Manager {
	
	private $arrGames;
	
	public function handlePacket(Array $arrPacket, Client $objClient){
		Silk\Logger::Log('Game packet received: ' . $arrPacket[2]);
	}
	
	public function createGame($intGame){
		$this->arrGames[$intGame] = new Object();
	}
	
	public function getGame($intGame){
		return $this->arrGames[$intGame];
	}
	
	public function getGamePlayers($intGame){
		return $this->arrGames[$intGame]['Players'];
	}
	
}

?>
