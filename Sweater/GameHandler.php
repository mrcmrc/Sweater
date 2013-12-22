<?php

// TODO: Perhaps set a sending/receiving mail limit?

namespace Sweater;
use Silk;
use Silk\Exceptions;
use Sweater\Handlers;

trait GameHandler {
	
	use Handlers\Inventory;
	
	public $arrXtHandlers = [
		// EPFHandler
		'f#epfga' => 'handleEPFGetAgentStatus',
		'f#epfgf' => 'handleEPFGetFieldOpStatus',
		'f#epfgr' => 'handleEPFGetPoints',
		// BuddyHandler
		'b#ba' => 'handleBuddyAccept',
		'b#bf' => 'handleBuddyFind',
		'b#br' => 'handleBuddyRequest',
		'b#gb' => 'handleGetBuddies',
		'b#rb' => 'handleBuddyRemove',
		// IglooHandler
		'g#af' => 'handleAddFurniture',
		'g#ag' => 'handleUpdateFloor',
		'g#au' => 'handleUpdateIglooType',
		'g#cr' => 'handleCloseIgloo',
		'g#gf' => 'handleGetFurniture',
		'g#gm' => 'handleGetIglooDetails',
		'g#go' => 'handleGetOwnedIgloos',
		'g#gr' => 'handleGetIglooList',
		'g#or' => 'handleOpenIgloo',
		'g#um' => 'handleUpdateMusic',
		'g#ur' => 'handleSaveIglooFurniture',
		// InventoryHandler
		'i#ai' => 'handleAddItem',
		'i#gi' => 'handleGetItems',
		// NavigationHandler
		'j#jp' => 'handleJoinPlayer',
		'j#jr' => 'handleJoinRoom',
		'j#js' => 'handleJoinServer',
		// MailHandler
		'l#md' => 'handleDeleteMail',
		'l#mdp' => 'handleDeleteMailPlayer',
		'l#mg' => 'handleGetMail',
		'l#ms' => 'handleSendMail',
		'l#mst' => 'handleStartMail', // This is received when a user first joins the server
		// MessageHandler
		'm#sm' => 'handleSendMessage',
		// IgnoreHandler
		'n#an' => 'handleAddIgnore', 
		'n#rn' => 'handleRemoveIgnore',
		'n#gn' => 'handleGetIgnoreList',
		// ModerationHandler
		'o#k' => 'handleKick',
		// PuffleHandler
		'p#pip' => 'handlePufflePip',
		'p#pir' => 'handlePufflePir',
		'p#ir' => 'handlePuffleIsResting',
		'p#ip' => 'handlePuffleIsPlaying',
		'p#pw' => 'handlePuffleWalk',
		'p#pf'   =>  'handlePuffleFeedFood',
		'p#phg'   =>  'handlePuffleClick',
		'p#pr'    =>  'handlePuffleRest',
		'p#pp'   =>  'handlePufflePlay',
		'p#pt'    =>  'handlePuffleFeed',
		'p#pm'  =>  'handlePuffleMove',
		'p#pb'   =>  'handlePuffleBath',
		'p#pg' => 'handleGetPuffle',
		'p#pgu' => 'handleGetPuffleUser',
		'p#pm' => 'handlePuffleMove',
		'p#pn' => 'handleAdoptPuffle',
		// Standard/System?
		's#upc'	=>	'handleUpdatePlayerArt',
		's#uph'	=>	'handleUpdatePlayerArt',
		's#upf'	=>	'handleUpdatePlayerArt',
		's#upn'	=>	'handleUpdatePlayerArt',
		's#upb'	=>	'handleUpdatePlayerArt',
		's#upa'	=>	'handleUpdatePlayerArt',
		's#upe'	=>	'handleUpdatePlayerArt',
		's#upl'	=>	'handleUpdatePlayerArt',
		's#upp'	=>	'handleUpdatePlayerArt',
		// StampHandler
		'st#gps' => 'handleGetPlayersStamps',
		'st#gsbcd' => 'handleGetStampBookCoverDetails',
		// ToyHandler
		't#at' => 'handleAddToy',
		't#rt' => 'handleRemoveToy',
		// PlayerHandler
		'u#h' => 'handleHeartBeat',
		'u#glr' => 'handleGetLatestRevision',
		'u#gp' => 'handleGetPlayer',
		'u#sa' => 'handleSendAction',
		'u#sb' => 'handleSnowBall',
		'u#se' => 'handleSendEmote',
		'u#sf' => 'handleSendFrame',
		'u#sg' => 'handleSendTourGuide',
		'u#sj' => 'handleSendJoke',
		'u#sp' => 'handleSendPosition',
		'u#ss' => 'handleSendSafeMessage',
		// MinigameHandler
		'gz' => 'handleGameStatus',
		'm' => 'handleMovePuck'
	];
	
	public $arrClientsByID = [];
	public $arrItems = [];
	public $arrRooms = [];
	public $strGameStatus;
	
	function getClientByID($intPlayer){
		$objClient = $this->arrClientsByID[$intPlayer];
		return $objClient;
	}
	
	function getOnlineStatus($intPlayer){
		$blnOnline = isset($this->arrClientsByID[$intPlayer]);
		return $blnOnline;
	}
	
	function handleAddFurniture(Array $arrData, Client $objClient){
		$intFurniture = $arrData[4];
		if(!is_numeric($intFurniture) || !isset($this->arrFurniture[$intFurniture])){
			return $objClient->sendError(410);
		}
		$objClient->addFurniture($intFurniture);
	}
	
	function handleAddIgnore(Array $arrData, Client $objClient){
		$intPlayer = $arrData[4];
		if($intPlayer === $objClient->intPlayer) return;
		if($this->getOnlineStatus($intPlayer)){
			$arrIgnore = $objClient->arrIgnores;
			if(!in_array($arrIgnore, $intPlayer)){
				$arrIgnore[] = $intPlayer;
				$objClient->arrIgnores = $arrIgnore;
				$objClient->updateIgnores();
				$objClient->sendXt('an', $objClient->getIntRoom(), $intPlayer);
			}
		}
	}
	
	function handleAddToy(Array $arrData, Client $objClient){
		$intRoom = $objClient->getExtRoom();
		$this->objRoomManager->sendXt($intRoom, ['at', $objClient->getIntRoom(), $objClient->getPlayer()]);
	}
	
	function handleAdoptPuffle(Array $arrData, Client $objClient){
		$intPuffle = $arrData[4];
		$strPuffle = $arrData[5];
		if(is_numeric($intPuffle) && is_string($strPuffle)){
			$intUnique = mt_rand(1000) . $intPuffle; // Random integer to identify the puffle (this is only used once - it is not stored in the database)
			if($objClient->getCoins() < 800) return $objClient->sendError(401);
			$arrPuffle = [$strPuffle, $intPuffle];
			$this->objDatabase->adoptPuffle($objClient->getPlayer(), $arrPuffle);
			$objClient->delCoins(800);
			$strPuffle = $intUnique . implode('|', $arrPuffle) . '|100|100|100';
			$objClient->sendXt('pn', $objClient->getIntRoom(), $objClient->getCoins(), $strPuffle);
			$this->handleGetPuffleUser([4 => $objClient->getPlayer()], $objClient);
		}
	}
	
	/* Buddy methods - These can be improved */
	
	function handleBuddyAccept(Array $arrData, Client $objClient){
		$intPlayer = $arrData[4];
		$intBuddies = sizeof($objClient->arrBuddies);
		$blnOnline = $this->getOnlineStatus($intPlayer);
		$blnExist = $this->objDatabase->playerExists($intPlayer);
		if($blnOnline === false && $blnExist === false) return;
		if(!in_array($intPlayer, $objClient->arrRequests)) return;
		if($intBuddies >= 100) return $objClient->sendError(901);
		$arrBuddies = $this->objDatabase->getBuddies($intPlayer);
		if(!empty($arrBuddies)){
			if(in_array($arrBuddies, $objClient->getPlayer())) return;
		}
		$arrBuddies[] = $objClient->intPlayer;
		$this->objDatabase->setBuddies($intPlayer, $arrBuddies);
		if($blnOnline) $this->arrClientsByID[$intPlayer]->arrBuddies = $arrBuddies;
		// Time to add them to $objClient
		$strUsername = $this->objDatabase->getUsername($intPlayer);
		if(!in_array($intPlayer, $objClient->arrBuddies)){
			$objClient->arrBuddies[] = $intPlayer;
			$this->objDatabase->setBuddies($objClient->getPlayer(), $objClient->arrBuddies);
		}
		if($blnOnline){
			$this->arrClientsByID[$intPlayer]->sendXt('ba', $objClient->getIntRoom(), $objClient->getPlayer(), $objClient->getUsername());
		}
		$objClient->sendXt('ba', $objClient->getIntRoom(), $intPlayer, $strUsername);
		unset($objClient->arrRequests[$intPlayer]);
	}
	
	function handleBuddyRemove(Array $arrData, Client $objClient){
		$intTarget = $arrData[4];
		$arrBuddies = $objClient->arrBuddies;
		if(in_array($intTarget, $arrBuddies)){
			$intKey = array_search($intTarget, $arrBuddies);
			unset($arrBuddies[$intKey]);
			$objClient->arrBuddies = array_values($arrBuddies);
			$objClient->updateBuddies();
		}
		if($this->objDatabase->playerExists($intTarget)){
			$arrBuddies = $this->objDatabase->getBuddies($intTarget);
			if(in_array($objClient->intPlayer, $arrBuddies)){
				$intKey = array_search($objClient->intPlayer, $arrBuddies);
				unset($arrBuddies[$intKey]);
				$arrBuddies = array_keys($arrBuddies);
				$this->objDatabase->setBuddies($intTarget, $arrBuddies);
			}
			if($this->getOnlineStatus($intTarget)){
				$this->arrClientsByID[$intTarget]->sendXt('rb', $objClient->getIntRoom(), $objClient->getPlayer(), $objClient->getUsername());
			}
		}
		$objClient->sendXt('rb', $objClient->getIntRoom(), $intTarget, $this->objDatabase->getUsername($intTarget));
	}
	
	function handleBuddyFind(Array $arrData, Client $objClient){
		$intTarget = $arrData[4];
		if(!$this->getOnlineStatus($intTarget)) return;
		$intRoom = $this->arrClientsByID[$intTarget]->getExtRoom();
		$objClient->sendXt('bf', $objClient->getIntRoom(), $intRoom);
	}
	
	function handleBuddyRequest(Array $arrData, Client $objClient){
		$intTarget = $arrData[4];
		$intBuddies = sizeof($objClient->arrBuddies);
		if($intBuddies >= 100) return $objClient->sendError(901);
		if($intTarget == $objClient->intPlayer) return;
		$blnOnline = $this->getOnlineStatus($intTarget);
		if($blnOnline === false) return;
		// TODO: Implement ignored detection
		$objTarget = $this->getClientByID($intTarget);
		$intTargetBuddies = sizeof($objTarget->arrBuddies);
		if(!empty($objTarget->arrRequests) && in_array($objClient->intPlayer, $objTarget->arrRequests)) return; // Already requested
		if($intTargetBuddies >= 150) return;
		$objTarget->sendXt('br', $objClient->getIntRoom(), $objClient->getPlayer(), $objClient->getUsername());
		$objTarget->arrRequests[] = $objClient->intPlayer;
	}
	
	function handleCloseIgloo(Array $arrData, Client $objClient){
		$intIgloo = $arrData[4];
		if($intIgloo == $objClient->intPlayer){
			$this->arrRooms[$intIgloo + 1000]['Open'] = false;
		}
	}
	
	function handleDeleteMail(Array $arrData, Client $objClient){
		$intPostcard = $arrData[4]; // The postcard's unique ID
		$arrPostcards = $objClient->arrPostcards;
		foreach($arrPostcards as $intUnique => $arrPostcard){
			if($intUnique == $intPostcard){
				unset($arrPostcards[$intUnique]);
				break;
			}
		}
		$objClient->arrPostcards = $arrPostcards;
		$this->objDatabase->setPostcards($objClient->getPlayer(), $arrPostcards);
		$objClient->sendXt('md', -1, $intPostcard);
	}

	function handleDeleteMailPlayer(Array $arrData, Client $objClient){
		$intPlayer = $arrData[4];
		if($intPlayer == $objClient->intPlayer) return;
		$arrPostcards = $objClient->arrPostcards;
		foreach($arrPostcards as $intPostcard => $strPostcard){
			if($arrPostcard['From']['ID'] == $intPlayer){
				unset($arrPostcards[$intPostcard]);
			}
		}
		$this->objDatabase->setPostcards($objClient->getPlayer(), $arrPostcards);
		$objClient->arrPostcards = $arrPostcards;
		$objClient->sendXt('mdp', -1, sizeof($arrPostcards));
	}
	
	// TODO: Get actual medals/points whatever
	function handleEPFGetPoints(Array $arrData, Client $objClient){
		$objClient->sendXt('epfgr', -1, 0, 0);
	}
	
	// TODO: Implement actually getting the user's field op status?
	function handleEPFGetFieldOpStatus(Array $arrData, Client $objClient){
		$objClient->sendXt('epfgf', -1, 1);
	}
	
	function handleEPFGetAgentStatus(Array $arrData, Client $objClient){
		$mixAgentStatus = 1;
		$objClient->sendXt('epfga', -1, $mixAgentStatus);
	}
	
	function handleGameData($strData, Client $objClient){
		$arrData = explode('%', $strData);
		unset($arrData[0]);
		$arrData = array_values($arrData);
		array_pop($arrData);
		$strType = $arrData[1];
		$strHandler = $arrData[2];
		if($strType == 's'){ // Standard packet
			if(isset($this->arrXtHandlers[$strHandler])){
				$strMethod = $this->arrXtHandlers[$strHandler];
				if(method_exists($this, $strMethod) === false){
					Silk\Logger::Log('Missing standard handler for ' . $strMethod . ' (' . $strHandler . ')!', Silk\Logger::Warn);
				} else {
					call_user_func([$this, $strMethod], $arrData, $objClient);
				}
			} else {
			Silk\Logger::Log('Unknown packet received: ' . $strData, Silk\Logger::Warn);
			}
		} elseif($strType == 'z'){
			Silk\Logger::Log('Game packet received: ' . $strData, Silk\Logger::Debug);
		}
		foreach($this->arrPlugins as $objPlugin){
			if($objPlugin->blnGame){
				$objPlugin->handleGamePacket([$arrData, $objClient]);
			}
		}
		print_r($arrData);
		unset($arrData);
	}
	
	function handleGameStatus(Array $arrData, Client $objClient){
		$strStatus = $this->strGameStatus ? $this->strGameStatus : '0%0%0%0';
		$objClient->sendXt('gz', $objClient->getIntRoom(), $strStatus);
	}

	function handleGetBuddies(Array $arrData, Client $objClient){
		$strBuddies = $objClient->getBuddies();
		$objClient->sendXt('gb', -1, $strBuddies);
	}
	
	function handleGetFurniture(Array $arrData, Client $objClient){
		$strFurniture = $objClient->getFurniture();
		$objClient->sendXt('gf', $objClient->getIntRoom(), $strFurniture);
	}
	
	function handleGetIglooDetails(Array $arrData, Client $objClient){
		$intPlayer = $arrData[4];
		if($this->objDatabase->playerExists($intPlayer)){
			$strIgloo = $this->objDatabase->getPlayerIgloo($intPlayer);
			$objClient->sendXt('gm', $objClient->getIntRoom(), $strIgloo);
		}
	}
	
	function handleGetIglooList(Array $arrData, Client $objClient){
		$strIgloos = '';
		$arrRooms = $this->arrRooms;
		foreach($arrRooms as $intRoom=>$arrRoom){
			$blnOpen = isset($arrRoom['Open']) && $arrRoom['Open'] === true ? true : false;
			if($intRoom > 1000 && $blnOpen){
				$intRoom -= 1000;
				$strIgloos .= '%' . $intRoom . '|' . $this->arrClientsByID[$intRoom]->strUsername;
			}
		}
		$blnIgloos = strlen($strIgloos) > 1;
		if($blnIgloos) $strIgloos = substr($strIgloos, 1);
		$blnIgloos ?
		// The following lines are correct :-)
		$objClient->sendXt('gr', $objClient->getIntRoom(), $strIgloos):
		$objClient->sendXt('gr', $objClient->getIntRoom());
	}
	
	function handleGetIgnoreList(Array $arrData, Client $objClient){
		$strIgnore = $objClient->getIgnores();
		$objClient->sendXt('gn', -1, $strIgnore);
	}
	
	function handleGetLatestRevision(Array $arrData, Client $objClient){
		$objClient->sendXt('glr', -1, 4815); // I don't know what these numbers are for, so feel free to change em up
	}
	
	function handleGetMail(Array $arrData, Client $objClient){
		$strPostcards = $objClient->getPostcards();
		$objClient->sendXt('mg', -1, $strPostcards);
	}
	
	function handleGetOwnedIgloos(Array $arrData, Client $objClient){
		$strIgloos = $objClient->getOwnedIgloos();
		$objClient->sendXt('go', $objClient->getIntRoom(), $strIgloos);
	}
	
	function handleGetPlayer(Array $arrData, Client $objClient){
		$intTarget = $arrData[4];
		if($this->objDatabase->playerExists($intTarget)){
			$strPlayer = $this->objDatabase->getPlayer($intTarget);
			$objClient->sendXt('gp', $objClient->getIntRoom(), $strPlayer);
		}
	}
	
	// TODO: Finish
	function handleGetPlayersStamps(Array $arrData, Client $objClient){
		$intPlayer = $arrData[4];
		$strStamps = '';
		$objClient->sendXt('gps', $objClient->getIntRoom(), $strStamps);
	}
	
	function handleGetPuffle(Array $arrData, Client $objClient){
		$intPlayer = $arrData[4];
		if($this->objDatabase->playerExists($intPlayer)){
			if(is_numeric($intPlayer)){
				$strPuffles = $this->objDatabase->getPuffles($intPlayer);
				$objClient->sendXt('pg', $objClient->getIntRoom(), $strPuffles);
			}
		}
	}
	
	function handleGetPuffleUser(Array $arrData, Client $objClient){
		$intPlayer = isset($arrData[4]) ? $arrData[4] : $objClient->intPlayer; // I don't know
		if($this->objDatabase->playerExists($intPlayer)){
			if(is_numeric($intPlayer)){
				$strPuffles = $this->objDatabase->getPuffles($intPlayer);
				$objClient->sendXt('pgu', $objClient->getIntRoom(), $strPuffles);
			}
		}
	}
	
	function handleGetStampBookCoverDetails(Array $arrData, Client $objClient){
		$intPlayer = $arrData[4];
		$strStampBook = '';
		$objClient->sendXt('gsbcd', -1, $strStampBook);
	}
	
	// This is for keeping the player connected, sorta like a ping-pong
	function handleHeartBeat(Array $arrData, Client $objClient){
		$objClient->sendXt('h', -1);
	}

	function handleJoinPlayer(Array $arrData, Client $objClient){
		$intRoom = $arrData[4];
		$intX = isset($arrData[5]) ? $arrData[5] : 0;
		$intY = isset($arrData[6]) ? $arrData[6] : 0;
		if($intRoom < 1000) $intRoom += 1000;
		$this->objRoomManager->removeFromRooms($objClient);
		if(!$this->objRoomManager->existsRoom($intRoom)){
			$this->objRoomManager->createRoom($intRoom);
		}
		$this->objRoomManager->addIglooUser($intRoom, $objClient, [$intX, $intY], true);
	}
	
	function handleJoinRoom(Array $arrData, Client $objClient){
		$intRoom = $arrData[4];
		$intX = $arrData[5];
		$intY = $arrData[6];
		$blnExists = $this->objRoomManager->existsRoom($intRoom);
		if($blnExists == false) return;
		$this->objRoomManager->removeFromRooms($objClient);
		$this->objRoomManager->addUser($intRoom, $objClient);
	}
	
	function handleJoinServer(Array $arrData, Client $objClient){
		$intAge = $objClient->getAge();
		$strStamps = '';
		// TODO: Implement sending true statistical values (EPF, tour guide, moderator)
		$objClient->sendXt('js', -1, 0, 1, $objClient->getModerator() ? 1 : 0);
		$objClient->sendXt('gps', -1, $strStamps);
		$objClient->sendXt('lp', -1, $objClient->buildPlayerString(), $objClient->getCoins(), 0, 1440, time(), $intAge, 1000, 233, '', 7);
		$this->objDatabase->updatePuffleStatistics($objClient->getPlayer(), $objClient);
		$this->objDatabase->updateColumn($objClient->getPlayer(), 'LastLogin', time());
		// TODO: add randomization
		$this->handleJoinRoom([4 => 100, 0, 0], $objClient);
	}
	
	function handleKick(Array $arrData, Client $objClient){
		$intPlayer = $arrData[4];
		if($this->getOnlineStatus($intPlayer) && $objClient->getModerator()){
			$this->arrClientsByID[$intPlayer]->sendError(5);
			$this->removeClient($this->arrClientsByID[$intPlayer]->resSocket);
		}
	}
	
	function handleMovePuck(Array $arrData, Client $objClient){
		$intPlayer = $arrData[4];
		// Probably wrong - I think some of these are for the scores of both teams and the rest are the puck's coordinates
		// ^ However they still work!
		$intW = $arrData[5];
		$intX = $arrData[6];
		$intY = $arrData[7];
		$intZ = $arrData[8];
		$strStatus = $intW . '%' . $intX . '%' . $intY . '%' . $intZ;
		$this->objRoomManager->sendXt($objClient->getExtRoom(), ['zm', $objClient->getIntRoom(), $intPlayer, $strStatus]);
		$this->strGameStatus = $strStatus;
	}
	
	function handleOpenIgloo(Array $arrData, Client $objClient){
		$intIgloo = $arrData[4];
		if($intIgloo == $objClient->intPlayer){
			$this->arrRooms[$intIgloo + 1000]['Open'] = true;
		}
	}
	
	function handlePuffleBath(Array $arrData, Client $objClient){
		$intPuffle = $arrData[4];
		$objClient->delCoins(5);
		$this->objRoomManager->sendXt($objClient->getExtRoom(), ['pb', $objClient->getIntRoom(), $objClient->getCoins(), $intPuffle]);
		$this->handlePuffleStatChange($intPuffle, 'Bath');
		$this->handleGetPuffle([4 => $objClient->getPlayer()], $objClient);
	}
	
	function handlePuffleFeed(Array $arrData, Client $objClient){
		$intPuffle = $arrData[4];
		$intAction = $arrData[5];
		$objClient->delCoins(10);
		$this->objRoomManager->sendXt($objClient->getExtRoom(), ['pt', $objClient->getIntRoom(), $objClient->getCoins(), $intPuffle, $intAction]);
		$this->handlePuffleStatChange($intPuffle, 'Food');
		$this->handleGetPuffle([4 => $objClient->getPlayer()], $objClient);
	}
	
	function handlePuffleFeedFood(Array $arrData, Client $objClient){
		$intPuffle = $arrData[4];
		$objClient->delCoins(10);
		$this->objRoomManager->sendXt($objClient->getExtRoom(), ['pf', $objClient->getIntRoom(), $objClient->getCoins(), $intPuffle]);
		$this->objDatabase->handlePuffleStatChange($intPuffle, 'Food');
		$this->handleGetPuffle([4 => $objClient->getPlayer()], $objClient);
	}
	
	function handlePuffleMove(Array $arrData, Client $objClient){
		$intPuffle = $arrData[4];
		$intX = $arrData[5];
		$intY = $arrData[6];
		$this->objRoomManager->sendXt($objClient->getExtRoom(), ['pm', $objClient->getIntRoom(), $intPuffle, $intX, $intY]);
	}
	
	function handlePufflePip(Array $arrData, Client $objClient){
		$intPuffle = $arrData[4];
		$this->objRoomManager->sendXt($objClient->getExtRoom(), ['pip', $objClient->getIntRoom(), $intPuffle, $arrData[5], $arrData[6]]);
	}
	
	function handlePufflePir(Array $arrData, Client $objClient){
		$intPuffle = $arrData[4];
		$this->objRoomManager->sendXt($objClient->getExtRoom(), ['pir', $objClient->getIntRoom(), $intPuffle, $arrData[5], $arrData[6]]);
	}
	
	function handlePufflePlay(Array $arrData, Client $objClient){
		$intPuffle = $arrData[4];
		$this->objRoomManager->sendXt($objClient->getExtRoom(), ['pp', $objClient->getIntRoom(), $intPuffle]);
		$this->objDatabase->changePuffleStats($intPuffle, 'Rest', 10, false);
		$this->handleGetPuffle([4 => $objClient->getPlayer()], $objClient);
	}
	
	function handlePuffleWalk(Array $arrData, Client $objClient){
		$intPuffle = $arrData[4];
		$intWalk = $arrData[5];
		$intItem = 750;
		$intItem += $this->objDatabase->getPuffleColumn($intPuffle, 'Type');
		$this->handleUpdatePlayerArt([2 => 's#upa', 4 => $intItem], $objClient);
		$objClient->sendXt('pw', $objClient->getIntRoom(), $intPuffle, $intWalk);
		$objClient->setWalking($intPuffle);
		$this->objDatabase->updatePuffleColumn($intPuffle, 'Walking', 1);
	}
	
	// TODO: Add security measures
	function handlePuffleRest(Array $arrData, Client $objClient){
		$intPuffle = $arrData[4];
		$this->objRoomManager->sendXt($objClient->getExtRoom(), ['pr', $objClient->getIntRoom(), $intPuffle]);
		$this->objDatabase->changePuffleStats($intPuffle, 'Health', 16);
		$this->objDatabase->changePuffleStats($intPuffle, 'Rest', 20);
		$this->handleGetPuffle([4 => $objClient->getPlayer()], $objClient);
		$objClient->delCoins(5);
	}
	
	function handlePuffleStatChange($intPuffle, $strType){
		$arrSets = self::$arrPuffleStats[$strType]['Set'];
		$arrRanges = self::$arrPuffleStats[$strType]['Range'];
		foreach($arrSets as $strSet => $intValue){
			$arrRange = range($arrRanges[$strSet], 100);
			$intStat = $this->objDatabase->getPuffleColumn($intPuffle, $strSet);
			if(in_array($intStat, $arrRange) && $intHealth != 100){
				$this->objDatabase->changePuffleStats($intPuffle, $strSet, $intValue - $intStat);
			} else {
				$this->objDatabase->changePuffleStats($intPuffle, $strSet, $intValue);
			}
		}
	}
	
	function handleRemoveIgnore(Array $arrData, Client $objClient){
		$intPlayer = $arrData[4];
		if(in_array($intPlayer, $objClient->arrIgnores)){
			$intIndex = array_search($intPlayer, $objClient->arrIgnores);
			unset($objClient->arrIgnores[$intIndex]);
			$objClient->updateIgnores(true);
			$objClient->sendXt('rn', $objClient->getIntRoom(), $intPlayer);
		}
	}
	
	function handleSaveIglooFurniture(Array $arrData, Client $objClient){
		$strFurniture = '';
		// This is unfortunately the most efficient way
		foreach($arrData as $intIndex => $strItem){
			if($intIndex > 3) $strFurniture .= $strItem . ',';
		}
		$objClient->updateColumn('RoomFurniture', $strFurniture);
	}
	
	function handleSendAction(Array $arrData, Client $objClient){
		$intAction = $arrData[4];
		$this->objRoomManager->sendXt($objClient->getExtRoom(), ['sa', $objClient->getIntRoom(), $objClient->getPlayer(), $intAction]);
	}
	
	function handleSendEmote(Array $arrData, Client $objClient){
		$intEmote = $arrData[4];
		$this->objRoomManager->sendXt($objClient->getExtRoom(), ['se', $objClient->getIntRoom(), $objClient->getPlayer(), $intEmote]);
	}
	
	function handleSendFrame(Array $arrData, Client $objClient){
		$intFrame = $arrData[4];
		$this->objRoomManager->sendXt($objClient->getExtRoom(), ['sa', $objClient->getIntRoom(), $objClient->getPlayer(), $intFrame]);
	}
	
	function handleSendJoke(Array $arrData, Client $objClient){
		$intJoke = $arrData[4];
		$this->objRoomManager->sendXt($objClient->getExtRoom(), ['sj', $objClient->getIntRoom(), $objClient->getPlayer(), $intJoke]);
	}
	
	function handleSendMail(Array $arrData, Client $objClient){
		$intPlayer = $arrData[4];
		$intPostcard = $arrData[5];
		if($intPlayer == $objClient->intPlayer) return;
		if($this->objDatabase->playerExists($intPlayer)){
			$arrPostcard = [
				'From' => [
					'ID' => $objClient->intPlayer,
					'Name' => $objClient->strUsername
				],
				'ID' => $intPostcard,
				'Message' => '', // Probably don't need this
				'Timestamp' => time(),
				'Unique' => $objClient->intPlayer . sizeof($objClient->arrPostcards)
			];
			$strPostcard = $arrPostcard['From']['Name'] . '%' . $arrPostcard['From']['ID'] . '%' . $arrPostcard['ID'] . '%%' . $arrPostcard['Timestamp'] . '%' . $arrPostcard['Unique'];
			$objClient->delCoins(10);
			$arrMail = $this->objDatabase->getPostcards($intPlayer);
			$arrMail[$arrPostcard['Unique']] = $arrPostcard;
			unset($arrPostcard);
			$this->objDatabase->setPostcards($arrMail);
			if($this->getOnlineStatus($intPlayer)){
				$this->arrClientsByID[$intPlayer]->sendXt('mr', -1, $strPostcard);
			}
			$objClient->sendXt('ms', -1, $objClient->getExtRoom(), $objClient->getCoins(), 1);
		}
	}
	
	function handleSendMessage(Array $arrData, Client $objClient){
		$strMessage = $arrData[5];
		$this->objRoomManager->sendXt($objClient->getExtRoom(), ['sm', $objClient->getIntRoom(), $objClient->getPlayer(), $strMessage]);
	}
	
	function handleSendPosition(Array $arrData, Client $objClient){
		$intX = $arrData[4];
		$intY = $arrData[5];
		$this->objRoomManager->sendXt($objClient->getExtRoom(), ['sp', $objClient->getIntRoom(), $objClient->getPlayer(), $intX, $intY]);
		$objClient->intX = $intX;
		$objClient->intY = $intY;
	}
	
	function handleSendSafeMessage(Array $arrData, Client $objClient){
		$intMessage = $arrData[4];
		$this->objRoomManager->sendXt($objClient->getExtRoom(), ['ss', $objClient->getIntRoom(), $objClient->getPlayer(), $intMessage]);
	}
	
	function handleSendTourGuide(Array $arrData, Client $objClient){
		$intGuide = $arrData[4];
		$this->objRoomManager->sendXt($objClient->getExtRoom(), ['sg', $objClient->getIntRoom(), $objClient->getPlayer(), $intGuide]);
	}
	
	function handleSnowBall(Array $arrData, Client $objClient){
		$intX = $arrData[4];
		$intY = $arrData[5];
		$this->objRoomManager->sendXt($objClient->getExtRoom(), ['sb', $objClient->getIntRoom(), $objClient->getPlayer(), $intX, $intY]);
	}
	
	function handleStartMail(Array $arrData, Client $objClient){
		$intPostcards = sizeof($objClient->arrPostcards);
		$objClient->sendXt('mst', -1, 0, $intPostcards);
	}
	
	function handleUpdateFloor(Array $arrData, Client $objClient){
		$intFloor = $arrData[4];
		$objClient->updateFloor($intFloor);
	}
	
	function handleUpdateIglooType(Array $arrData, Client $objClient){
		$intIgloo = $arrData[4];
		$objClient->updateIgloo($intIgloo);
	}
	
	function handleUpdateMusic(Array $arrData, Client $objClient){
		$intMusic = $arrData[4];
		$objClient->updateMusic($intMusic);
	}
	
	// FYI: Can be shortened
	function handleUpdatePlayerArt(Array $arrData, Client $objClient){
		$intItem = $arrData[4];
		$arrPuffles = range(750, 759);
		$arrItems = $objClient->getInventory();
		$arrWalking = $objClient->getWalking();
		$blnInventory = in_array($intItem, $arrItems);
		$blnPuffle = in_array($intItem, $arrPuffles);
		$blnRemove = $intItem == 0;
		$blnWalking = isset($arrWalking['Walking']);
		$strType = substr($arrData[2], 2);
		$arrItemTypes = [
			'upc'	=>	'Color',
			'uph'	=>	'Head',
			'upf'	=>	'Face',
			'upn'	=>	'Neck',
			'upb'	=>	'Body',
			'upa'	=>	'Hand',
			'upe'	=>	'Feet',
			'upl'	=>	'Flag',
			'upp'	=>	'Photo',
		];
		
		if($strType === false) return; // Invalid
		if(!$blnPuffle && !$blnInventory && !$blnRemove){
			Silk\Logger::Log('Invalid item', Silk\Logger::Debug);
			return;
		}
		if($blnWalking){
			$intPuffle = $arrWalking['Walking'];
			$objClient->clearWalking();
			$this->objDatabase->updatePuffleColumn($intPuffle, 'Walking', 0);
			$objClient->sendXt('pw', $objClient->getIntRoom(), $objClient->getPlayer(), $intPuffle, 0);
		}
		
		$this->objRoomManager->sendXt($objClient->getExtRoom(), [$strType, $objClient->getIntRoom(), $objClient->getPlayer(), $intItem]);
		$strType = $arrItemTypes[$strType];
		$objClient->updateClothing($strType, $intItem);
	}
	
}

?>
