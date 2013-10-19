<?php

namespace Sweater;
use Sweater\Utils;
use Silk;
use Silk\Exceptions;

class Server extends Silk\ServerBase {
	
	use GameHandler, LoginHandler;
	
	public $objDatabase = null;
	private $objRoomManager;
	private static $arrPuffleStatistics;
	
	function __construct($intServerID, $blnUpdate){
		$this->readConfiguration($intServerID);
		$this->createSocket();
		$this->bindSocket($this->arrServer['Port']);
		$this->startListening(5);
		$strSystem = substr(PHP_OS, 0, 3);
		$strSystem = strtoupper($strSystem);
		if($strSystem != 'WIN'){
			$arrSignals = [SIGTERM, SIGINT, SIGHUP];
			$arrCallback = [$this, 'handleSignal'];
			$this->registerSignals($arrSignals, $arrCallback);
		} else {
			Silk\Logger::Log('Skipping signal registration - Windows doesn\'t support PCNTL', Silk\Logger::Warn);
		}
		if($blnUpdate === true){
			$this->updateCrumbs();
		}
		try {
			$strAddress = $this->arrConfig['Database']['Address'];
			$strUsername = $this->arrConfig['Database']['Username'];
			$strPassword = $this->arrConfig['Database']['Password'];
			$strDatabase = $this->arrConfig['Database']['Database'];
			$this->objDatabase = new CPDatabase('mysql:host=' . $strAddress . ';dbname=' . $strDatabase, $strUsername, $strPassword);
		} catch(\PDOException $objException){
			throw new Exceptions\StartupException($objException->getMessage());
		}
		if($this->arrServer['Type'] === 'Game'){
			$this->objRoomManager = new RoomManager();
			$this->updateStats();
		} else {
			$this->handleUpdateServerList();
		}
	}

	function handleData($strData, $objClient){
		$arrData = explode($this->arrConfig['Packets']['Delimiter'], $strData);
		array_pop($arrData);
		foreach($arrData as $strData){
			Silk\Logger::Log('Received data: ' . $strData);
			$strSubstring = substr($strData, 0, 1);
			$blnPossibleXML = $strSubstring == '<' ? true : false;
			$blnPossibleGame = $strSubstring == '%' ? true : false;
			if($blnPossibleXML === false && $blnPossibleGame === false){
				throw new Exceptions\HandlingException('Bad client detected!');
			}
			$blnPossibleXML ?
			$this->handleXMLData($strData, $objClient):
			$this->handleGameData($strData, $objClient);
		}
		unset($arrData);
	}
	
	function handleSignal($intSignal){
		switch($intSignal){
			case SIGINT:
			case SIGTERM:
				echo chr(8), chr(8);
				Silk\Logger::Log('Shutting down', Silk\Logger::Warn);
				socket_close($this->resSocket);
			break;
			case SIGHUP:
				Silk\Logger::Log('Restarting server', Silk\Logger::Warn);
				socket_close($this->resSocket);
				$this->objDatabase = null;
				$this->readConfiguration($intServerID);
				$this->updateCrumbs();
				try {
					$strAddress = $this->arrConfig['Database']['Address'];
					$strUsername = $this->arrConfig['Database']['Username'];
					$strPassword = $this->arrConfig['Database']['Password'];
					$strDatabase = $this->arrConfig['Database']['Database'];
					$this->objDatabase = new CPDatabase('mysql:host=' . $strAddress . ';dbname=' . $strDatabase, $strUsername, $strPassword);
				} catch(PDOException $objException){
					throw new Exception($objException->getMessage());
				}
				if($this->arrServer['Type'] === 'Game'){
					$this->updateStats();
				} else {
					$this->handleUpdateServerList();
				}
				$this->createSocket();
				$this->bindSocket($this->arrServer['Port']);
				$this->startListening(5);
			break;
		}
		exit();
	}
	
	function handleServerStatus(){
		Silk\Logger::Log('Ticking seems to be working!');
	}
	
	function updateStats(){
		$intServer = $this->arrServer['ID'];
		$intClients = sizeof($this->arrClients);
		$this->objDatabase->updateStats($intServer, $intClients);
	}
	
	function registerSignals($arrSignals, $arrCallback){
		if(!is_array($arrSignals)) return;
		if(empty($arrSignals)) return;
		if(!is_array($arrCallback)) return;
		if(empty($arrCallback)) return;
		list($strObject, $strMethod) = $arrCallback;
		if(method_exists($strObject, $strMethod) === false) return;
		foreach($arrSignals as $cntSignal){
			$blnSuccess = pcntl_signal($cntSignal, $arrCallback);
			if($blnSuccess === false){
				Silk\Logger::Log('Error registering signal ' . $cntSignal, Silk\Logger::Warn);
			}
		}
		Silk\Logger::Log('Signals registered');
	}
	
	function removeClient($resSock){
		foreach($this->arrClients as $intIndex=>$objClient){
			if($objClient->resSocket == $resSock){
				unset($this->arrClients[$intIndex]);
				if($this->arrServer['Type'] == 'Game'){
					Silk\Logger::Log('Removing disconnecting client from rooms');
					$this->objRoomManager->removeFromRooms($objClient);
					if(isset($objClient->intPlayer)){
						unset($this->arrClientsByID[$objClient->intPlayer]);
					}
					if(!empty($objClient->arrBuddies)){
						foreach($objClient->arrBuddies as $intBuddy){
							if($this->getOnlineStatus($intBuddy)){
								$this->arrClientsByID[$intBuddy]->sendData('%xt%bof%-1%' . $objClient->intPlayer . '%');
							}
						}
					}
					$intIgloo = $objClient->intPlayer + 1000;
					if($this->objRoomManager->getIglooStatus($intIgloo)){
						$this->objRoomManager->closeIgloo($intIgloo);
					}
					$arrWalking = $objClient->getWalking();
					if(isset($arrWalking['Walking'])){
						Silk\Logger::Log('Removing puffle', Silk\Logger::Debug);
						$intPuffle = $arrWalking['Walking'];
						$objClient->updateClothing('Hand', 0);
						$this->objDatabase->updatePuffleColumn($intPuffle, 'Walking', 0);
					}
					$this->updateStats();
				}
				socket_close($objClient->resSocket);
				Silk\Logger::Log('Client disconnected');
				break;
			}
		}
	}
	
	function updateCrumbs(){
		Silk\Logger::Log('Updating crumbs..');
		$strFloors = $this->arrConfig['Crumbs']['Floors'];
		$strFurniture = $this->arrConfig['Crumbs']['Furniture'];
		$strIgloos = $this->arrConfig['Crumbs']['Igloos'];
		$strItems = $this->arrConfig['Crumbs']['Items'];
		$strRooms = $this->arrConfig['Crumbs']['Rooms'];
		$arrData = Utils\Downloader::AsyncDownload([$strFloors, $strFurniture, $strIgloos, $strItems, $strRooms]);
		$arrFloors = json_decode($arrData[$strFloors], true);
		$arrFurniture = json_decode($arrData[$strFurniture], true);
		$arrIgloos = json_decode($arrData[$strIgloos], true);
		$arrItems = json_decode($arrData[$strItems], true);
		$arrRooms = json_decode($arrData[$strRooms], true);
		if(is_dir('Crumbs') === false){
			mkdir('Crumbs');
		}
		// Floors
		$arrOrgFloors = [];
		foreach($arrFloors as $arrFloor){
			$arrOrgFloors[$arrFloor['igloo_floor_id']] = [
				'Cost' => $arrFloor['cost'],
				'Name' => $arrFloor['label']
			];
		}
		$strFloors = json_encode($arrOrgFloors);
		$resFloors = fopen('Crumbs/Floors.json', 'w');
		fwrite($resFloors, $strFloors);
		fclose($resFloors);
		// Furniture (TODO: Implement is_member and type)
		$arrOrgFurniture = [];
		foreach($arrFurniture as $arrFurni){
			$arrOrgFurniture[$arrFurni['furniture_item_id']] = [
				'Cost' => $arrFurni['cost'],
				'Name' => $arrFurni['label']
			];
		}
		$strFurniture = json_encode($arrOrgFurniture);
		$resFurniture = fopen('Crumbs/Furniture.json', 'w');
		fwrite($resFurniture, $strFurniture);
		fclose($resFurniture);
		// Igloos
		$arrOrgIgloos = [];
		foreach($arrIgloos as $intIgloo=>$arrIgloo){
			$arrOrgIgloos[$intIgloo] = [
				'Cost' => $arrIgloo['cost'],
				'Name' => $arrIgloo['name']
			];
		}
		$strIgloos = json_encode($arrOrgIgloos);
		$resIgloos = fopen('Crumbs/Igloos.json', 'w');
		fwrite($resIgloos, $strIgloos);
		fclose($resIgloos);
		// Items
		$arrOrgItems = [];
		$strType = function($intType){
			switch($intType){
				case 1: return 'Color';
				case 2: return 'Head';
				case 3: return 'Face';
				case 4: return 'Neck';
				case 5: return 'Body';
				case 6: return'Hand';
				case 7: return 'Feet';
				case 8: return 'Flag';
				case 9: return 'Photo';
				case 10: return 'Other';
				default: return 'Other';
			}
		};
		foreach($arrItems as $arrItem){
			$arrOrgItems[$arrItem['paper_item_id']] = [
				'Name' => $arrItem['label'], 
				'Cost' => $arrItem['cost'], 
				'Member' => $arrItem['is_member'] ? 'true' : 'false', 
				'Type' => $strType($arrItem['type'])
			];
		}
		$strJson = json_encode($arrOrgItems);
		$resItems = fopen('Crumbs/Items.json', 'w');
		fwrite($resItems, $strJson);
		fclose($resItems);
		// Rooms - There has to be a better way to do this..
		$arrOrgRooms = [];
		$blnGame = function($intRoomID){
			include('Crumbs/Rooms.php');
			$blnExist = isset($arrRooms[$intRoomID]);
			$blnGame = $blnExist ? $arrRooms[$intRoomID]['game'] : 'false';
			return $blnGame;
		};
		$intInternal = function($intRoomID){
			include('Crumbs/Rooms.php');
			$blnExist = isset($arrRooms[$intRoomID]);
			$intInternal = $blnExist ? $arrRooms[$intRoomID]['intid'] : -1;
			return $intInternal;
		};
		foreach($arrRooms as $intRoom => $arrRoom){
			$arrOrgRooms[$intRoom] = [
				'Game' => $blnGame($intRoom),
				'Internal' => $intInternal($intRoom),
				'Member' => $arrRoom['is_member'] ? 'true' : 'false',
				'MaxUsers' => $arrRoom['max_users']
			];
		}
		$strJson = json_encode($arrOrgRooms);
		$resRooms = fopen('Crumbs/Rooms.json', 'w');
		fwrite($resRooms, $strJson);
		fclose($resRooms);
		$strFloors = file_get_contents('Crumbs/Floors.json');
		$strFurniture = file_get_contents('Crumbs/Furniture.json');
		$strIgloos = file_get_contents('Crumbs/Igloos.json');
		$strItems = file_get_contents('Crumbs/Items.json');
		$strRooms = file_get_contents('Crumbs/Rooms.json');
		$arrFloors = json_decode($strFloors, true);
		$arrFurniture = json_decode($strFurniture, true);
		$arrIgloos = json_decode($strIgloos, true);
		$arrItems = json_decode($strItems, true);
		$arrRooms = json_decode($strRooms, true);
		$this->arrFloors = $arrFloors;
		$this->arrFurniture = $arrFurniture;
		$this->arrIgloos = $arrIgloos;
		$this->arrItems = $arrItems;
		self::$arrPuffleStatistics = $this->arrConfig['Puffles'];
		Silk\Logger::Log('Crumbs updated');
	}
	
}

?>
