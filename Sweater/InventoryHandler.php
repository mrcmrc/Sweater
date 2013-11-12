<?php

namespace Sweater;
use Silk;

trait InventoryHandler {

	public function handleAddItem(Array $arrData, Client $objClient){
		$intItem = $arrData[4];
		$objClient->addItem($intItem);
	}
	
	public function handleGetItems(Array $arrData, Client $objClient){
		$strItems = $objClient->getItems();
		$objClient->sendXt('gi', -1, $strItems);
	}
	
	// TODO: Implement actually getting player's awards
	public function handleQueryPlayersAwards(Array $arrData, Client $objClient){
		$strAwards = '';
		$objClient->sendXt('qpa', -1, $strAwards);
	}
	
	//TODO: Implement actually getting player's pins
	public function handleQueryPlayersPins(Array $arrData, Client $objClient){
		$intPlayer = $arrData[4];
		$strPins = '';
		$objClient->sendXt('qpp', -1, $strPins);
	}
	
}

?>
