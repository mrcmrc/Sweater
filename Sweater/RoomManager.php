<?php

namespace Sweater;
use Sweater\Interfaces;
use Silk;

class RoomManager extends Silk\Manager implements Interfaces\StandardRoomManager {
	
	private $arrRooms;
	
	public function __construct(){
		$strRooms = file_get_contents('Crumbs/Rooms.json');
		$arrRooms = json_decode($strRooms, true);
		$arrRoomData = [];
		foreach($arrRooms as $intRoom => $arrRoom){
			if($intRoom < 900) $arrRoomData[$intRoom] = $arrRoom;
		}
		$this->arrRooms = $arrRoomData;
		$intRooms = sizeof($this->arrRooms);
		Silk\Logger::Log('RoomManager initialized - ' . $intRooms . ' rooms');
	}
	
	public function addIglooUser($intRoom, Client $objClient, Array $arrCoordinates = [0, 0]){
		list($intX, $intY) = $arrCoordinates;
		$intInternal = $this->getInternal($intRoom);
		$this->arrRooms[$intRoom]['Users'][] = $objClient;
		$objClient->setFrame(1);
		$objClient->setExtRoom($intRoom);
		$objClient->setIntRoom($intInternal);
		$this->sendXt($intRoom, ['ap', $intInternal, $objClient->buildPlayerString()]);
		$objClient->setX($intX);
		$objClient->setY($intY);
		$objClient->sendXt('jp', $intInternal, $intRoom);
		$blnEmpty = $this->getUserCount($intRoom) < 1;
		$blnEmpty ? $objClient->sendXt('jr', $intInternal, $intRoom):
		$objClient->sendXt('jr', $intInternal, $intRoom, $this->buildRoomString($intRoom));
	}
	
	public function addUser($intRoom, Client $objClient, Array $arrCoordinates = [0, 0]){
		list($intX, $intY) = $arrCoordinates;
		$intInternal = $this->getInternal($intRoom);
		$this->arrRooms[$intRoom]['Users'][] = $objClient;
		$objClient->setFrame(1);
		$objClient->setExtRoom($intRoom);
		$objClient->setIntRoom($intInternal);
		$this->sendXt($intRoom, ['ap', $intInternal, $objClient->buildPlayerString()]);
		$objClient->setX($intX);
		$objClient->setY($intY);
		$blnUnique = $intRoom > 900;
		if($blnUnique){
			$objClient->sendXt('jg', $intInternal, $intRoom);
		} else {
			$blnEmpty = $this->getUserCount($intRoom) < 1;
			$blnEmpty ? $objClient->sendXt('jr', $intInternal, $intRoom):
			$objClient->sendXt('jr', $intInternal, $intRoom, $this->buildRoomString($intRoom));
		}
	}
	
	private function buildRoomString($intRoom){
		$arrClients = $this->arrRooms[$intRoom]['Users'];
		$strRoom = '';
		foreach($arrClients as $objClient){
			$strRoom .= '%' . $objClient->buildPlayerString();
		}
		return substr($strRoom, 1);
	}
	
	public function createRoom($intRoom){
		$intInternal = function() use ($intRoom) {
			$arrRoom = str_split($intRoom);
		$intInternal = 0;
			foreach($arrRoom as $intChar){
				$intInternal += ord($intChar);
			}
			return ($intInternal += (floor(9875 / 3)));
		};
		$this->arrRooms[$intRoom] = [
			'Internal' => $intInternal(),
			'Open' => false,
			'Users' => []
		];
		Silk\Logger::Log('Room created', Silk\Logger::Debug);
	}
	
	public function existsRoom($intRoom){
		Silk\Logger::Log('Handling room existance check');
		return isset($this->arrRooms[$intRoom]);
	}
	
	public function getIglooStatus($intIgloo){
		if(!isset($this->arrRooms[$intIgloo])) return false;
		if($this->arrRooms[$intIgloo]['Open'] === false) return false;
		return true;
	}
	
	public function getInternal($intRoom){
		return $this->arrRooms[$intRoom]['Internal'];
	}
	
	public function getUserCount($intRoom){
		return array_key_exists('Users', $this->arrRooms[$intRoom]) ? sizeof($this->arrRooms[$intRoom]['Users']) : 0;
	}
	
	public function getUsers($intRoom){
		return array_key_exists('Users', $this->arrRooms[$intRoom]) ? $this->arrRooms[$intRoom]['Users'] : [];
	}
	
	public function removeFromRooms(Client $objClient){
		$blnExist = isset($this->arrRooms[$objClient->intExtRoom]['Users']);
		if($blnExist){
			$arrUsers = $this->arrRooms[$objClient->intExtRoom]['Users'];
			if(!empty($arrUsers)){
				$intKey = array_search($objClient, $arrUsers);
				unset($arrUsers[$intKey]);
				$this->sendXt($objClient->getExtRoom(), ['rp', $objClient->getIntRoom(), $objClient->getPlayer()]);
				$this->arrRooms[$objClient->intExtRoom]['Users'] = $arrUsers;
			}
		}
	}
	
	public function sendXt(){
		$arrArguments = func_get_args();
		list($intRoom, $arrStrings) = $arrArguments;
		$strPacket = '%xt%' . implode('%', $arrStrings) . '%';
		$this->sendPacket($intRoom, $strPacket);
	}

	private function sendPacket($intRoom, $strPacket){
		$arrUsers = $this->getUsers($intRoom);
		foreach($arrUsers as $objUser){
			$objUser->sendData($strPacket);
		}
	}
	
}

?>
