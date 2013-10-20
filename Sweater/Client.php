<?php

namespace Sweater;
use Silk;

// TODO: Perhaps make a private field containing objParent's CPDatabase object
class Client extends Silk\ClientBase {
	
	// TODO: Make these private
	// NOTICE: Encapsulation is now being used - PLEASE USE THE ENCAPSULATION METHODS
	// ANOTHER NOTICE: The encapsulation methods are invisisble
	public $arrInventory; // Item field
	public $arrBuddies, $arrRequests; // Buddy fields
	public $arrFurniture, $arrIgloos; // Igloo fields
	public $arrIgnores; // Ignore field
	public $arrPostcards; // Mail field
	public $arrWalking; // Walking field - used for determining the walk status of player puffles
	public $blnModerator; // Moderator field
	public $intAge; // Player's age field
	public $intCoins; // Coin field
	public $intExtRoom, $intIntRoom; // Room information fields
	public $intPlayer, $strNickname, $strUsername; // Player fields
	public $intX, $intY, $intFrame; // Player room detail fields
	public $intRank; // Rank field
	
	private $objGameInstance;
	private $objDatabase;
	private $strRandomKey; // Random key field
	
	// For all the lazy people out there (like me!)
	function __call($strName, $arrArguments){
		$strType = substr($strName, 0, 3);
		switch($strType){
			case 'get':
				$strProperty = substr($strName, 3);
				if(property_exists($this, 'int' . $strProperty)) return $this->{'int' . $strProperty};
				if(property_exists($this, 'str' . $strProperty)) return $this->{'str' . $strProperty};
				if(property_exists($this, 'arr' . $strProperty)) return $this->{'arr' . $strProperty};
				if(property_exists($this, 'bln' . $strProperty)) return $this->{'bln' . $strProperty};
			break;
			case 'set':
				$strProperty = substr($strName, 3);
				list($strValue) = $arrArguments;
				if(property_exists($this, 'int' . $strProperty)) return $this->{'int' . $strProperty} = $strValue;
				if(property_exists($this, 'str' . $strProperty)) return $this->{'str' . $strProperty} = $strValue;
				if(property_exists($this, 'arr' . $strProperty)) return $this->{'arr' . $strProperty} = $strValue;
				if(property_exists($this, 'bln' . $strProperty)) return $this->{'bln' . $strProperty} = $strValue;
			break;
		}
	}
	
	function addCoins($intCoins){
		$this->intCoins += $intCoins;
		$this->updateColumn('Coins', $this->intCoins);
	}
	
	// NOTE: Furniture items are keys in the furniture array because the values of said furniture keys are the amount of said furniture the player owns
	function addFurniture($intFurniture){
		$intAmount = isset($this->arrFurniture[$intFurniture]) ? ++$this->arrFurniture[$intFurniture] : 1;
		$intCost = $this->objParent->arrFurniture[$intFurniture]['Cost'];
		$intCoins = $this->getCoins();
		if($intCost > $intCoins){
			return $this->sendError(401);
		}
		$this->arrFurniture[$intFurniture] = $intAmount;
		$this->intCoins = $intCoins - $intCost;
		$strFurniture = json_encode($this->arrFurniture);
		$this->updateColumn('Furniture', $strFurniture);
		$this->updateColumn('Coins', $this->intCoins);
		$this->sendData('%xt%af%' . $this->intIntRoom . '%' . $intFurniture . '%' . $this->intCoins . '%');
	}

	function addItem($intItem){
		if(!isset($this->objParent->arrItems[$intItem])){
			$this->sendError(402); // Invalid item
			return;
		}
		$intCoins = $this->intCoins;
		$intCost = $this->objParent->arrItems[$intItem]['Cost'];
		if(in_array($intItem, $this->arrInventory)){
			$this->sendError(400);
			return;
		}
		if($intCost > $intCoins){
			$this->sendError(401);
			return;
		}
		$this->arrInventory[] = $intItem;
		$this->intCoins = $intCoins - $intCost;
		$intCoins = $this->intCoins;
		$strItems = json_encode($this->arrInventory);
		$this->updateColumn('Inventory', $strItems);
		$this->updateColumn('Coins', $intCoins);
		$this->sendData('%xt%ai%' . $this->intIntRoom . '%' . $intItem . '%' . $intCoins . '%');
	}
	
	function buildPlayerString(){
		$arrPlayer = [
			$this->intPlayer,
			$this->strNickname, 
			1, // Not exactly sure what this is for, but I think it's a language setting
			$this->intColor,
			$this->intHead,
			$this->intFace,
			$this->intNeck,
			$this->intBody,
			$this->intHand,
			$this->intFeet,
			$this->intFlag,
			$this->intPhoto,
			$this->intX,
			$this->intY, 
			$this->intFrame ? $this->intFrame : 1,
			1, // Uh?
			$this->intRank * 146, // Rank multiplied by 146
		];
		$strPlayer = implode('|', $arrPlayer);
		return $strPlayer;
	}
	
	function clearWalking(){
		$this->arrWalking = [];
	}
	
	function delCoins($intCoins){
		$this->intCoins -= $intCoins;
		$this->updateColumn('Coins', $this->intCoins);
	}
	
	function getBuddies(){
		$arrBuddies = $this->arrBuddies;
		$strBuddies = '';
		if(empty($arrBuddies)) return;
		foreach($arrBuddies as $intBuddy){
			$strBuddies .= $intBuddy . '|' . $this->objDatabase->getUsername($intBuddy) . '|';
			if($this->objParent->getOnlineStatus($intBuddy)){
				$this->objParent->arrClientsByID[$intBuddy]->sendData('%xt%bon%-1%' . $this->intPlayer . '%');
				$strBuddies .= '1%';
			} else {
				$strBuddies .='0%';
			}
		}
		return $strBuddies;
	}
	
	function getFurniture(){
		$strFurniture = '';
		foreach($this->arrFurniture as $intAmount=>$intFurniture){
			$strFurniture .= '%' . $intAmount . '|' . $intFurniture;
		}
		$strFurniture = substr($strFurniture, 1);
		return $strFurniture;
	}
	
	function getIgnores(){
		$arrIgnores = $this->arrIgnores;
		$strIgnores = '';
		if(empty($arrIgnores)) return $strIgnores;
		foreach($arrIgnores as $intIgnore){
			$strIgnores .= $intIgnore . '|' . $this->objDatabase->getUsername($intIgnore) . '%';
		}
		return $strIgnores;
	}

	function getItems(){
		$arrItems = $this->arrInventory;
		if(empty($arrItems)) return;
		$strItems = implode('%', $arrItems);
		return $strItems;
	}
	
	function getOwnedIgloos(){
		if(empty($this->arrIgloos)) return;
		$strIgloos = implode('|', $this->arrIgloos);
		return $strIgloos;
	}
	
	function getPostcards(){
		$arrPostcards = $this->arrPostcards;
		$strPostcards = '';
		if(empty($arrPostcards)) return;
		$arrPostcards = array_reverse($arrPostcards);
		foreach($arrPostcards as $arrPostcard){
			$strPostcards .= '%' . $arrPostcard['From']['Name'] . '|' . $arrPostcard['From']['ID'] . '|' . $arrPostcard['ID'] . '|' . $arrPostcard['Message'] . '|' . $arrPostcard['Timestamp'] . '|' . $arrPostcard['Unique'];
		}
		$strPostcards = substr($strPostcards, 1);
		return $strPostcards;
	}
	
	function getWalking(){
		return $this->arrWalking;
	}
	
	function sendError($intError){
		$this->sendData('%xt%e%-1%' . $intError . '%');
	}
	
	function setClient(Array $arrData){
		$this->arrBuddies = json_decode($arrData['Buddies'], true);
		$this->arrFurniture = json_decode($arrData['Furniture'], true);
		$this->arrIgloos = json_decode($arrData['Igloos'], true);
		$this->arrIgnores = json_decode($arrData['Ignores'], true);
		$this->arrInventory = json_decode($arrData['Inventory'], true);
		$this->arrPostcards = json_decode($arrData['Postcards'], true);
		$this->arrWalking = [];
		$this->blnModerator = $arrData['Moderator'] ? true : false;
		$this->intCoins = $arrData['Coins'];
		$this->intColor = $arrData['Color'];
		$this->intHead = $arrData['Head'];
		$this->intFace = $arrData['Face'];
		$this->intNeck = $arrData['Neck'];
		$this->intHand = $arrData['Hand'];
		$this->intBody = $arrData['Body'];
		$this->intFeet = $arrData['Feet'];
		$this->intFlag = $arrData['Flag'];
		$this->intPhoto = $arrData['Photo'];
		$this->intPlayer = $arrData['ID'];
		$this->intRank = $arrData['Rank'];
		$this->strNickname = $this->strUsername = $arrData['Username'];
		$intNow = time();
		$intOld = $arrData['RegisteredTime'];
		$intSub = $intNow - $intOld;
		$intAge = $intSub / 86400 % 7;
		$this->intAge = $intAge;
		
		$this->objParent->arrClientsByID[$this->intPlayer] = $this;
		$this->objDatabase = $this->objParent->objDatabase;
	}
	
	function sendXt(){
		$arrStrings = func_get_args();
		$strPacket = '%xt%' . implode('%', $arrStrings) . '%';
		$this->sendData($strPacket);
	}
	
	function setWalking($intPuffle){
		$this->arrWalking = ['Walking' => $intPuffle];
	}
	
	function updateBuddies(){
		$strBuddies = json_encode($this->arrBuddies);
		$this->updateColumn('Buddies', $strBuddies);
	}
	
	function updateClothing($strType, $intItem){
		$strMethod = 'set' . $strType;
		$this->$strMethod($intItem);
		$this->updateColumn($strType, $intItem);
	}
	
	function updateColumn($strColumn, $mixValue){
		$this->objDatabase->updateColumn($this->intPlayer, $strColumn, $mixValue);
	}
	
	function updateFloor($intFloor){
		if(isset($this->objParent->arrFloors[$intFloor])){
			$intCost = $this->objParent->arrFloors[$intFloor]['Cost'];
			if($intCost > $this->intCoins) return $this->sendError(401);
			$this->intCoins -= $intCost;
			$this->updateColumn('Floor', $intFloor);
			$this->updateColumn('Coins', $this->intCoins);
			if($this->intExtRoom == $this->intPlayer + 1000){
				$this->sendData('%xt%ag%' . $this->intIntRoom . '%' . $intFloor . '%' . $this->intCoins . '%');
			}
		}
	}
	
	function updateIgloo($intIgloo){
		if(!isset($this->objParent->arrIgloos[$intIgloo])) return;
		$intCost = $this->objParent->arrIgloos[$intIgloo]['Cost'];
		if($intCost > $this->intCoins) return $this->sendError(401);
		if(empty($this->arrIgloos)) $this->arrIgloos = [];
		if(!in_array($intIgloo, $this->arrIgloos)){
			$this->arrIgloos[] = $intIgloo;
			$strIgloos = json_encode($this->arrIgloos);
			$this->updateColumn('Igloos', $strIgloos);
		}
		$this->updateColumn('RoomFurniture', '');
		$this->updateColumn('Igloo', $intIgloo);
		$this->updateColumn('Floor', 0);
		$this->updateColumn('Coins', $this->intCoins);
		$this->intCoins -= $intCost;
		if($this->intExtRoom == $this->intPlayer + 1000){
			$this->sendData('%xt%au%' . $this->intIntRoom . '%' . $intIgloo . '%' . $this->intCoins . '%');
			$this->objParent->handleGetIglooDetails([4 => $this->intPlayer], $this);
			$this->objParent->handleJoinPlayer([4 => $this->intPlayer + 1000], $this); // Automatically re-join the igloo to receive the update
		}
	}
	
	function updateIgnores($blnRemove = false){
		if($blnRemove) $this->arrIgnores = array_keys($this->arrIgnores);
		$strIgnores = json_encode($this->arrIgnores);
		$this->updateColumn('Ignores', $strIgnores);
	}
	
	function updateMusic($intMusic){
		$this->updateColumn('Music', $intMusic);
	}

}

?>
