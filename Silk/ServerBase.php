<?php

// TODO: Stop usage of $this->returnSocketError considering it seems to always return 'Success' when called

namespace Silk;
use Silk\Exceptions;

abstract class ServerBase {
	
	protected $arrClients = [];
	protected $arrConfig;
	protected $arrServer;
	protected $intClientLimit = 0;
	protected $intLastCheck;
	protected $strClientObject;
	protected $strServerObject;
	protected $resSocket = null;
	
	abstract function handleData($strData, $objClient);
	
	function acceptClient(){
		$intClients = sizeof($this->arrClients);
		if($this->intClientLimit != 0 && $intClients >= $this->intClientLimit){
			throw new Exceptions\AcceptException('Client limit exceeded!');
		} else {
			$resSock = socket_accept($this->resSocket);
			$objClient = new $this->strClientObject($resSock, $this);
			if($objClient){
				$this->arrClients[] = $objClient;
				Logger::Log('Client connected from ' . $objClient->getAddress());
			}
		}
	}
	
	function acceptClients(){
		if(function_exists('pcntl_signal_dispatch')) pcntl_signal_dispatch();
		$arrSockets = $this->getSockets();
		$arrWrite = null;
		$arrExcept = null;
		$intSockets = @socket_select($arrSockets, $arrWrite, $arrExcept, null);
		if($intSockets < 1) return;
		if(in_array($this->resSocket, $arrSockets)){
			try {
				$this->acceptClient();
			} catch(Exceptions\AcceptException $objException){
				Logger::Log($objException->getMessage(), Logger::Warn);	
			}
			unset($arrSockets[0]);
		}
		foreach($arrSockets as $resSock){
			socket_recv($resSock, $strData, 8192, 0);
			if($strData == null){
				$this->removeClient($resSock);
				continue;
			}
			$objClient = $this->getClientBySock($resSock);
			try {
				$this->handleData($strData, $objClient);
			} catch(Exceptions\HandlingException $objException){
				Logger::Log($objException->getMessage(), Logger::Warn);
				$this->removeClient($resSock);
			}
		}
	}
	
	function bindSocket($intPort){
		if(!is_numeric($intPort)){
			throw new Exceptions\StartupException('Port is not numeric.');
		} elseif($intPort <= 1024){
			throw new Exceptions\StartupException('Please choose a port greater than 1024. Also, please don\'t run this, or anything, as root.');
		} elseif($resSock = null){
			throw new Exceptions\StartupException('Please create the socket first.');
		}
		$blnBind = socket_bind($this->resSocket, 0, $intPort);
		if(!$blnBind){
			throw new Exceptions\StartupException('Couldn\'t bind socket to port ' . $intPort . '. (' . $this->returnSocketError() . ')');
		}
	}
	
	function closeSocket(){
		if($this->resSocket == null){
			throw new Exceptions\StartupException('I\'m not able to close the socket because it hasn\'t been opened yet.');
		}
		socket_close($this->resSocket);
	}
	
	function createSocket(){
		$resSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		socket_set_option($resSocket, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_set_nonblock($resSocket);
		if(!$resSocket){
			throw new Exceptions\StartupException('I wasn\'t able to create the socket for some reason. Please make sure the sockets extension is enabled and available.');
		}
		$this->resSocket = $resSocket;
	}
	
	function getClientBySock($resSock){
		foreach($this->arrClients as $objClient){
			if($objClient->resSocket == $resSock){
				return $objClient;
			}
		}
	}
	
	function getConfig(){
		return $this->arrConfig;
	}
	
	function getDelimiter(){
		return $this->arrConfig['Packets']['Delimiter'];
	}
	
	function getServer(){
		return $this->arrServer;
	}

	function getSockets(){
		$arrSockets = [];
		$arrSockets[0] = $this->resSocket;
		foreach($this->arrClients as $objClient){
			$arrSockets[] = $objClient->resSocket;
		}
		return $arrSockets;
	}
	
	function readConfiguration($mixServerID, $mixOther = null){
		if(!file_exists('Server.conf')){
			throw new Exceptions\StartupException('Couldn\'t find Server.conf');
		}
		$arrJson = file_get_contents('Server.conf');
		$arrConfig = json_decode($arrJson, true);
		if(!$arrConfig){
			throw new Exceptions\StartupException('Failed to decode Server.conf');
		}
		$this->arrConfig = $arrConfig;
		if(!isset($this->arrConfig['Servers'][$mixServerID]) &&
			!isset($this->arrConfig['Servers'][$mixServerID]['Type']) ||
			((!isset($this->arrConfig['Servers'][$mixServerID]['Port'])) &&
			(!is_numeric(@$this->arrConfig['Servers'][$mixServerID]['Port']))) ^
			!isset($this->arrConfig['Servers'][$mixServerID]['ClientLimit'])){
				throw new Exceptions\StartupException('Configuration file for the server is is invalid.');
			}
		$strExtendedNamespace = $this->arrConfig['Extended']['Namespace'];
		$strClientObject = $this->arrConfig['Extended']['ClientObject'];
		$strServerObject = $this->arrConfig['Extended']['ServerObject'];
		$this->strClientObject = $strExtendedNamespace . '\\' . $strClientObject;
		$this->strServerObject = $strExtendedNamespace . '\\' . $strServerObject;
		$this->setClientLimit($this->arrConfig['Servers'][$mixServerID]['ClientLimit']);
		$this->arrServer['ID'] = $mixServerID;
		foreach($this->arrConfig['Servers'][$mixServerID] as $mixIndex=>$mixValue){
			$this->arrServer[$mixIndex] = $mixValue;
		}
		if($mixOther != null){
			$blnArray = is_array($mixOther);
			if($blnArray === false){
				if(!isset($this->arrConfig[$mixOther])){
					throw new Exceptions\StartupException('Configuration file is missing key \'' . $mixOther . '\'');
				}
			} else {
				if(empty($mixOther)){
					throw new Exceptions\StartupException('$mixOther is an empty array');
				} else {
					foreach($mixOther as $mixObject=>$mixChildren){
						if(!isset($this->arrConfig[$mixObject])){
							throw new Exceptions\StartupException('Configuration file is missing key \'' . $mixObject . '\'');
						}
						foreach($mixChildren as $mixChild){
							if(!isset($this->arrConfig[$mixObject][$mixChild])){
								throw new Exceptions\StartupException('Configuration file is missing key \'' . $mixChild . '\' in \'' . $mixObject . '\'');
							}
						}
					}
				}
			}
		}
	}
	
	function reloadConfiguration(){
		$intServer = $this->arrServer['ID'];
		$this->readConfiguration($intServer);
	}
	
	// This is over-written in the Server class 
	function removeClient($resSock){
		foreach($this->arrClients as $intIndex=>$objClient){
			if($objClient->resSocket == $resSock){
				unset($this->arrClients[$intIndex]);
				socket_close($resSock);
				Logger::Log('Client disconnected');
			}
		}
	}
	
	function returnSocketError(){
		if($this->resSocket = null){
			throw new Exceptions\StartupException('The main socket hasn\'t been created yet, so I can\'t give you a reason as to why something having to do with it failed.');
		}
		$intError = socket_last_error($this->resSocket);
		$strError = socket_strerror($intError);
		return $strError;
	}
	
	function setClientLimit($intLimit){
		$this->intClientLimit = $intLimit;
	}

	function startListening($intBacklog){
		if($this->resSocket == null){
			throw new Exceptions\StartupException('Main socket hasn\'t been created yet, so I can\'t start listening.');
		}
		$blnListen = socket_listen($this->resSocket, $intBacklog);
		if(!$blnListen){
			throw new Exceptions\StartupException('Could not listen with a backlog of ' . $intBacklog . ' (' . $this->returnSocketError() . ')');
		}
	}

}

?>
