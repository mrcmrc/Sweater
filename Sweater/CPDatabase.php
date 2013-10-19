<?php

// TODO: Perhaps implement getPuffleColumn(s) method(s)

namespace Sweater;
use Silk;

final class CPDatabase extends \PDO {

	private function getColumn($mixPlayer, $strColumn){
		$strWhere = is_numeric($mixPlayer) ? 'ID' : 'Username';
		$strQuery = "SELECT $strColumn FROM `users` WHERE $strWhere = :Player";
		try {
			$objStatement = $this->prepare($strQuery);
			$objStatement->bindValue(':Player', $mixPlayer);
			$objStatement->execute();
			$objStatement->bindColumn($strColumn, $mixResult);
			$objStatement->fetch(\PDO::FETCH_BOUND);
			$objStatement->closeCursor();
			return $mixResult;
		} catch(PDOException $objException){
			Silk\Logger::Log($objException->getMessage(), Silk\Logger::Warn);
		}
	}
	
	private function getColumns($intPlayer, Array $arrColumns){
		$strColumns = implode(', ', $arrColumns);
		$strQuery = "SELECT $strColumns FROM `users` WHERE ID = :Player";
		try {
			$objStatement = $this->prepare($strQuery);
			$objStatement->bindValue(':Player', $intPlayer);
			$objStatement->execute();
			$arrColumns = $objStatement->fetch(\PDO::FETCH_ASSOC);
			$objStatement->closeCursor();
		} catch(PDOException $objException){
			Silk\Logger::Log($objException->getMessage(), Silk\Logger::Warn);
		}
		return $arrColumns;
	}
	
	public function adoptPuffle($intPlayer, Array $arrPuffle){
		list($strPuffle, $intPuffle) = $arrPuffle;
		$strQuery = 'INSERT INTO `Puffles` (`Owner`, `Name`, `Type`) VALUES (:Owner, :Name, :Type)';
		$objStatement = $this->prepare($strQuery);
		$objStatement->bindValue(':Owner', $intPlayer, \PDO::PARAM_INT);
		$objStatement->bindValue(':Name', $strPuffle);
		$objStatement->bindValue(':Type', $intPuffle, \PDO::PARAM_INT);
		$objStatement->execute();
		$objStatement->closeCursor();
	}
	
	public function changePuffleStats($intPuffle, $strStatistic, $intNumber, $blnIncrement = true){
		try {
			$strQuery = "SELECT $strStatistic FROM `Puffles` WHERE ID = :Puffle";
			$objStatement = $this->prepare($strQuery);
			$objStatement->bindValue(':Puffle', $intPuffle);
			$objStatement->execute();
			$arrColumns = $objStatement->fetch(\PDO::FETCH_ASSOC);
			$objStatement->closeCursor();
			$intStatisticValue = $arrColumns[$strStatistic];
			$blnIncrement ? $intStatisticValue += $intNumber : $intStatisticValue -= $intNumber;
			$strQuery = "UPDATE `Puffles` SET $strStatistic = :Value WHERE ID = :Puffle";
			$objStatement = $this->prepare($strQuery);
			$objStatement->bindValue(':Value', $intStatisticValue);
			$objStatement->bindValue(':Puffle', $intPuffle);
			$objStatement->execute();
			$objStatement->closeCursor();
		} catch(PDOException $objException){
			Silk\Logger::Log($objException->getMessage(), Silk\Logger::Warn);
		} 
	}
	
	public function getBuddies($intPlayer){
		try {
			$strQuery = 'SELECT Buddies FROM `users` WHERE ID = :Player';
			$objStatement = $this->prepare($strQuery);
			$objStatement->bindValue(':Player', $intPlayer);
			$objStatement->execute();
			$objStatement->bindColumn('Buddies', $strBuddies);
			$objStatement->fetch(\PDO::FETCH_BOUND);
			$objStatement->closeCursor();
			$arrBuddies = json_decode($strBuddies, true);
			return $arrBuddies;
		} catch(PDOException $objException){
			Silk\Logger::Log($objException->getMessage(), Silk\Logger::Warn);
		}
	}
	
	public function getLoginKey($intPlayer){
		try {
			$strQuery = 'SELECT LoginKey FROM `users` WHERE ID = :Player';
			$objStatement = $this->prepare($strQuery);
			$objStatement->bindValue(':Player', $intPlayer);
			$objStatement->execute();
			$objStatement->bindColumn('LoginKey', $strLoginKey);
			$objStatement->fetch(\PDO::FETCH_BOUND);
			$objStatement->closeCursor();
			return $strLoginKey;
		}
		catch(\PDOException $objException){
			Silk\Logger::Log($objException->getMessage(), Silk\Logger::Warn);
		}
	}

	public function getPlayer($intPlayer){
		$arrPlayer = $this->getColumns($intPlayer, ['Username', 'Color', 'Head', 'Face', 'Neck', 'Body', 'Hand', 'Feet', 'Flag', 'Photo']);
		$strPlayer = $intPlayer;
		$strPlayer .= '|' . $arrPlayer['Username'];
		$strPlayer .= '|' . 1;
		$strPlayer .= '|' . $arrPlayer['Color'];
		$strPlayer .= '|' . $arrPlayer['Head'];
		$strPlayer .= '|' . $arrPlayer['Face'];
		$strPlayer .= '|' . $arrPlayer['Neck'];
		$strPlayer .= '|' . $arrPlayer['Body'];
		$strPlayer .= '|' . $arrPlayer['Hand'];
		$strPlayer .= '|' . $arrPlayer['Feet'];
		$strPlayer .= '|' . $arrPlayer['Flag'];
		$strPlayer .= '|' . $arrPlayer['Photo'] . '|';
		unset($arrPlayer);
		return $strPlayer;
	}
	
	public function getPlayerIgloo($intPlayer){
		$strIgloo = $intPlayer;
		$strIgloo .= '%' . $this->getColumn($intPlayer, 'Igloo');
		$strIgloo .= '%' . $this->getColumn($intPlayer, 'Music');
		$strIgloo .= '%' . $this->getColumn($intPlayer, 'Floor');
		$strIgloo .= '%' . $this->getColumn($intPlayer, 'RoomFurniture');
		return $strIgloo;
	}
	
	public function getPostcards($intPlayer){
		try {
			$strQuery = 'SELECT Postcards FROM `users` WHERE ID = :Player';
			$objStatement = $this->prepare($strQuery);
			$objStatement->bindValue(':Player', $intPlayer);
			$objStatement->execute();
			$objStatement->bindColumn('Postcards', $strPostcards);
			$objStatement->fetch(\PDO::FETCH_BOUND);
			$objStatement->closeCursor();
			$arrPostcards = json_decode($strPostcards, true);
			return $arrPostcards;
		} catch(PDOException $objException){
			Silk\Logger::Log($objException->getMessage(), Silk\Logger::Warn);
		}
	}
	
	public function getPuffleColumn($intPuffle, $strColumn){
		try {
			$strQuery = "SELECT $strColumn FROM `Puffles` WHERE ID = :Puffle";
			$objStatement = $this->prepare($strQuery);
			$objStatement->bindValue(':Puffle', $intPuffle);
			$objStatement->execute();
			$objStatement->bindColumn($strColumn, $mixResult);
			$objStatement->fetch(\PDO::FETCH_BOUND);
			$objStatement->closeCursor();
			return $mixResult;
		} catch(PDOException $objException){
			Silk\Logger::Log($objException->getMessage(), Silk\Logger::Warn);
		}
	}
	
	public function getPuffles($intPlayer){
		if(is_numeric($intPlayer)){
			$strQuery = 'SELECT ID, Name, Type, Health, Hunger, Rest, Walking FROM `Puffles` WHERE `Owner` = :Owner';
			try {
				$objStatement = $this->prepare($strQuery);
				$objStatement->bindValue(':Owner', $intPlayer, \PDO::PARAM_INT);
				$objStatement->execute();
				$arrPuffles = $objStatement->fetchAll(\PDO::FETCH_NUM);
				$objStatement->closeCursor();
				$strPuffles = '';
				foreach($arrPuffles as $arrPuffle){
					$intWalking = $arrPuffle[6];
					if($intWalking == 0) $strPuffles .= '%' . join('|', $arrPuffle);
				}
				$strPuffles = substr($strPuffles, 1);
				return $strPuffles;
			} catch(PDOException $objException){
				Silk\Logger::Log($objException->getMessage(), Silk\Logger::Warn);
			}
		}
	}
	
	public function getPuffleString($intPuffle){
		if(is_numeric($intPuffle)){
			$strQuery = 'SELECT ID, Name, Type, Health, Hunger, Rest FROM `Puffles` WHERE ID = :Puffle';
			try {
				$objStatement = $this->prepare($strQuery);
				$objStatement->bindValue(':Puffle', $intPuffle, \PDO::PARAM_INT);
				$objStatement->execute();
				$arrPuffle = $objStatement->fetch(\PDO::FETCH_NUM);
				print_r($arrPuffle);
				$strPuffle = join('|', $arrPuffle);
				return $strPuffle;
			} catch(PDOException $objException){
				Silk\Logger::Log($objException->getMessage(), Silk\Logger::Warn);
			}
		}
	}
	
	// Should be only used once for every client instance
	public function getRow($strUsername){
		if(is_string($strUsername)){
			$strQuery = 'SELECT * FROM `users` WHERE Username = :Player';
			try {
				$objStatement = $this->prepare($strQuery);
				$objStatement->bindValue(':Player', $strUsername);
				$objStatement->execute();
				$arrPlayer = $objStatement->fetch(\PDO::FETCH_ASSOC);
				$objStatement->closeCursor();
				return $arrPlayer;
			} catch(PDOException $objException){
				Silk\Logger::Log($objException->getMessage(), Silk\Logger::Warn);
			}
		}
	}
	
	public function getServerPopulation(){
		$strQuery = 'SELECT * FROM `Stats`';
		try {
			$objStatement = $this->prepare($strQuery);
			$objStatement->execute();
			$arrServers = $objStatement->fetchAll(\PDO::FETCH_ASSOC);
			$objStatement->closeCursor();
		} catch(PDOException $objException){
			Silk\Logger::Log($objException->getMessage(), Silk\Logger::Warn);
		}
		$strServers = '';
		foreach($arrServers as $arrServer){
			$intPopulation = $arrServer['Population'];
			if($intPopulation > 279) $intBars = 6; // Full
			elseif($intPopulation > 208) $intBars = 5;
			elseif($intPopulation > 124) $intBars = 4;
			elseif($intPopulation > 62) $intBars = 3;
			elseif($intPopulation > 24) $intBars = 2;
			else $intBars = 1;
			// 101,5|102,3 are examples of what this may look like
			$strServers .= $arrServer['ID'] . ',' . $intBars . '|';
		}
		return $strServers;
	}
	
	public function getUsername($intPlayer){
		try {
			$strQuery = 'SELECT Username FROM `users` WHERE ID = :Player';
			$objStatement = $this->prepare($strQuery);
			$objStatement->bindValue(':Player', $intPlayer);
			$objStatement->execute();
			$objStatement->bindColumn('Username', $strUsername);
			$objStatement->fetch(\PDO::FETCH_BOUND);
			$objStatement->closeCursor();
			return $strUsername;
		} catch(PDOException $objException){
			Silk\Logger::Log($objException->getMessage(), Silk\Logger::Warn);
		}
	}
	
	public function playerExists($mixPlayer){
		$strWhere = is_numeric($mixPlayer) ? 'ID' : 'Username';
		try {
			$strQuery = "SELECT ID FROM `users` WHERE $strWhere = :Player";
			$objStatement = $this->prepare($strQuery);
			$objStatement->bindValue(':Player', $mixPlayer);
			$objStatement->execute();
			$intRows = $objStatement->rowCount();
			$objStatement->closeCursor();
			return $intRows > 0;
		} catch(PDOException $objException){
			Silk\Logger::Log($objException->getMessage(), Silk\Logger::Warn);
		}
	}
	
	public function setBuddies($intPlayer, Array $arrBuddies){
		try {
			$strBuddies = json_encode($arrBuddies);
			$strQuery = 'UPDATE `users` SET Buddies = :Buddies WHERE ID = :Player';
			$objStatement = $this->prepare($strQuery);
			$objStatement->bindValue(':Buddies', $strBuddies);
			$objStatement->bindValue(':Player', $intPlayer);
			$objStatement->execute();
			$objStatement->closeCursor();
		} catch(PDOException $objException){
			Silk\Logger::Log($objException->getMessage(), Silk\Logger::Warn);
		}
	}
	
	public function setPostcards($intPlayer, Array $arrPostcards){
		try {
			$strPostcards = json_encode($arrPostcards);
			$strQuery = 'UPDATE `users` SET Postcards = :Postcards WHERE ID = :Player';
			$objStatement = $this->prepare($strQuery);
			$objStatement->bindValue(':Postcards', $strPostcards);
			$objStatement->execute();
			$objStatement->closeCursor();
		} catch(PDOException $objException){
			Silk\Logger::Log($objException->getMessage(), Silk\Logger::Warn);
		}
	}
	
	public function updatePuffleStatistics($mixPlayer, Client $objClient){
		$strQuery = "SELECT ID, Name, Health, Hunger, Rest, Type FROM `Puffles` WHERE Owner = :Player";
		try {
			$objStatement = $this->prepare($strQuery);
			$objStatement->bindValue(':Player', $mixPlayer);
			$objStatement->execute();
			$arrPuffles = $objStatement->fetchAll(\PDO::FETCH_ASSOC);
			$objStatement->closeCursor();
			$intRows = $objStatement->rowCount();
			if($intRows !== 0){
				$intRand = mt_rand(0, 4);
				$intTime = strtotime('-5 days');
				$intLastLogin = $this->getColumn($mixPlayer, 'LastLogin');
				if($intLastLogin !== null){
					$intSubtract = $intLastLogin - $intTime;
					$blnMajor = $intSubtract < 0;
				} else {
					$blnMajor = false;
				}
				if($intRand === 2 ^ $blnMajor){
					Silk\Logger::Log('Updating puffles', Silk\Logger::Debug);
					foreach($arrPuffles as $arrPuffle){
						$intPuffle = $arrPuffle['ID'];
						$intHealth = $arrPuffle['Health'];
						$intHunger = $arrPuffle['Hunger'];
						$intRest = $arrPuffle['Rest'];
						$intMin = $blnMajor ? 25 : 0;
						$intMax = $blnMajor ? 45 : 15;
						$intHealth = $intHealth - mt_rand($intMin, $intMax);
						$intHunger = $intHunger - mt_rand($intMin, $intMax);
						$intRest = $intRest - mt_rand($intMin, $intMax);
						$strHealth = "UPDATE `Puffles` SET Health = $intHealth WHERE ID = :Puffle";
						$strHunger = "UPDATE `Puffles` SET Hunger = $intHunger WHERE ID = :Puffle";
						$strRest = "UPDATE `Puffles` SET Rest = $intRest WHERE ID = :Puffle";
						// The following is in the order it is because some drivers may be dicks if closeCursor isn't called before executing another statement
						$objHealth = $this->prepare($strHealth);
						$objHealth->bindValue(':Puffle', $intPuffle);
						$objHealth->execute();
						$objHealth->closeCursor();
						$objHunger = $this->prepare($strHunger);
						$objHunger->bindValue(':Puffle', $intPuffle);
						$objHunger->execute();
						$objHunger->closeCursor();
						$objRest = $this->prepare($strRest);
						$objRest->bindValue(':Puffle', $intPuffle);
						$objRest->execute();
						$objRest->closeCursor();
					}
				}
				$objStatement = $this->prepare($strQuery);
				$objStatement->bindValue(':Player', $mixPlayer);
				$objStatement->execute();
				$arrPuffles = $objStatement->fetchAll(\PDO::FETCH_ASSOC);
				$objStatement->closeCursor();
				foreach($arrPuffles as $arrPuffle){
					$intHealth = $arrPuffle['Health'];
					$intPuffle = $arrPuffle['ID'];
					$intType = $arrPuffle['Type'];
					$strName = $arrPuffle['Name'];
					if($intHealth < 5){
						$strQuery = "DELETE FROM `Puffles` WHERE ID = :Puffle";
						$objStatement = $this->prepare($strQuery);
						$objStatement->bindValue(':Puffle', $intPuffle);
						$objStatement->execute();
						$objStatement->closeCursor();
						// TODO: Implement sending the player some mail here
						$intPostcard = function() use ($intType){
							switch($intType){
								case 0: return 100;
								case 1: return 101;
								case 2: return 102;
								case 3: return 103;
								case 4: return 104;
								case 5: return 105;
								case 6: return 106;
							}
						};
						$strPostcards = $this->getColumn($mixPlayer, 'Postcards');
						$arrPostcards = json_decode($strPostcards, true);
						$arrPostcard = [
							'From' => [
								'ID' => $intPuffle,
								'Name' => $strName
							],
							'ID' => $intPostcard(),
							'Message' => $strName,
							'Timestamp' => time(),
							'Unique' => $mixPlayer . sizeof($arrPostcards)
						];
						$arrPostcards[$arrPostcard['Unique']] = $arrPostcard;
						$strPostcards = json_encode($arrPostcards);
						$this->updateColumn($mixPlayer, 'Postcards', $strPostcards);
						$strPostcard = $arrPostcard['From']['Name'] . '%' . $arrPostcard['From']['ID'] . '%' . $arrPostcard['ID'] . '%%' . $arrPostcard['Timestamp'] . '%' . $arrPostcard['Unique'];
						$objClient->arrPostcards = $arrPostcards;
						$objClient->sendXt('mr', -1, $strPostcard);
					}
				}
						
			}
		} catch(PDOException $objException){
			Silk\Logger::Log($objException->getMessage(), Silk\Logger::Warn);
		}
	}
	
	public function updateColumn($mixPlayer, $strColumn, $mixValue){
		$strWhere = is_numeric($mixPlayer) ? 'ID' : 'Username';
		$strQuery = "UPDATE `users` SET $strColumn = :Value WHERE $strWhere = :Player";
		try {
			$objStatement = $this->prepare($strQuery);
			$objStatement->bindValue(':Value', $mixValue);
			$objStatement->bindValue(':Player', $mixPlayer);
			$objStatement->execute();
			$objStatement->closeCursor();
		} catch(PDOException $objException){
			Silk\Logger::Log($objException->getMessage(), Silk\Logger::Warn);
		}
	}
	
	public function updatePuffleColumn($intPuffle, $strColumn, $mixValue){
		try {
			$strQuery = "UPDATE `Puffles` SET $strColumn = :Value WHERE ID = :Puffle";
			$objStatement = $this->prepare($strQuery);
			$objStatement->bindValue(':Value', $mixValue);
			$objStatement->bindValue(':Puffle', $intPuffle);
			$objStatement->execute();
			$objStatement->closeCursor();
		} catch(PDOException $objException){
			Silk\Logger::Log($objException->getMessage(), Silk\Logger::Warn);
		}
	}
	
	public function updateStats($intServer, $intClients){
		$strQuery = 'INSERT INTO `Stats` VALUES (:Server, :Clients) ON duplicate KEY UPDATE Population = :Clients;';
		try {
			$objStatement = $this->prepare($strQuery);
			$objStatement->bindValue(':Server', $intServer);
			$objStatement->bindValue(':Clients', $intClients);
			$objStatement->execute();
			$objStatement->closeCursor();
		} catch(PDOException $objException){
			Silk\Logger::Log($objException->getMessage(), Silk\Logger::Warn);
		}
	}
	
}

?>
