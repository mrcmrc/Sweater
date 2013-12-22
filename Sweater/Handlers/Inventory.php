<?php

namespace Sweater\Handlers;
use Sweater;

trait Inventory {
	
	public function handleAddItem(Array $arrData, Sweater\Client $objClient){
		$intItem = $arrData[4];
		
		if(is_numeric($intItem)){
			$objClient->addItem($intItem);
		}
	}
	
	public function handleGetItems(Array $arrData, Sweater\Client $objClient){
		$strItems = $objClient->getItems();
		
		$objClient->sendXt('gi', -1, $strItems);
	}
	
}

?>
