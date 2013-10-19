<?php

class Mittsies extends Bot {
	
	function handleKick($arrData, $objClient){
		$intPlayer = $arrData[4];
		if($intPlayer == 0){
			$this->sendMessage('You can\'t kick me!', $objClient);
		}
	}
	
	// Perhaps handle commands here instead? :P
	function handleMessage($arrData, $objClient){}
	
}

?>
