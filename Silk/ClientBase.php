<?php

namespace Silk;

abstract class ClientBase {
	
	protected $floAddress;
	protected $objParent;
	public $resSocket = null;
	
	function __construct($resSock, $objParent){
		$this->resSocket = $resSock;
		$this->objParent = $objParent;
		$blnSuccess = socket_getpeername($this->resSocket, $this->floAddress);
		if(!$blnSuccess){
			$this->objParent->removeClient($this->resSocket);
			Logger::Log('Couldn\'t obtain the IP of a client. Removing.', Logger::Warn);
		}
	}
	
	public function getAddress(){
		return $this->floAddress;
	}
	
	// TODO: Maybe implement throwing a custom exception ?
	public function sendData($strData, $blnChunk = false, $intWritten = 0, $intIterations = 0){
		Logger::Log('Outgoing data: ' . $strData, Logger::Debug);
		if($blnChunk === false){
			$strData .= $this->objParent->getDelimiter();
		} else {
			if($intIterations > 3){
				$this->objParent->removeClient($this->resSocket);
				Logger::Log('Failed to completely send data a client', Logger::Warn);
			}
			$strData = substr($strData, $intWritten);
		}
		$intData = strlen($strData);
		$mixSend = socket_send($this->resSocket, $strData, $intData, 0);
		if($mixSend === false){
			$this->objParent->removeClient($this->resSocket);
			return false;
		}
		if($intData != $mixSend){
			$this->sendData($strData, true, $mixSend, $intIterations++);
		}
	}
	
}

?>
