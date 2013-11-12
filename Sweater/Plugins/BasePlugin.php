<?php

namespace Sweater\Plugins;
use Sweater;

abstract class BasePlugin implements Interfaces\PluginInterface {
	
	private $arrXtHandlers = []; // Method mapping
	private $arrXMLHandlers = []; // Method mapping
	
	protected $arrDependencies = []; // Plugin names
	protected $intVersion = 0; // Plugin version
	protected $strAuthor = 'N/A'; // Author's name
	
	public $blnConstructor = false; // Call a 'handleConstruction' on startup?
	public $blnGame = false; // Traffic game packets through plugin via 'handleGamePacket'?
	public $blnLogin = false; // Traffic login packets through plugin via 'handleLoginPacket'?
	
	protected $objServer; // Required
	
	public function __construct(Sweater\Server $objServer){
		$this->objServer = $objServer;
	}
	
	protected function addCustomXtHandler($strHandle, $strHandler){
		$this->arrXtHandlers[$strHandle] = $strHandler;
	}
	
	protected function addCustomXMLHandler($strHandle, $strHandler){
		$this->arrXMLHandlers[$strHandle] = $strHandler;
	}
	
	protected function addDependencies(Array $arrPlugins){
		foreach($arrPlugins as $strPlugin){
			array_push($this->arrDependencies, $strPlugin);
		}
	}
	
	public function getDependencies(){
		return $this->arrDependencies;
	}
	
	public function getPluginInfo($blnPackets = false){
		$arrPlugin = [
			'Author' => $this->strAuthor,
			'Game' => $this->blnGame,
			'Login' => $this->blnLogin,
			'Packets' => $blnPackets ? $this->arrPackets : null,
			'Version' => $this->intVersion
		];
		return $arrPlugin;
	}
	
	public function handleGamePacket(Array $arrData){
		list($arrPacket, $objClient) = $arrData;
		$strHandle = $arrPacket[2];
		if(array_key_exists($strHandle, $this->arrXtHandlers)){
			$strHandler = $this->arrXtHandlers[$strHandle];
			call_user_func([$this, $strHandler], $arrPacket, $objClient);
		}
	}
	
	public function handleLoginPacket(Array $arrData){
		list($arrPacket, $objClient) = $arrData;
		$strHandle = $arrPacket['body']['@attributes']['action'];
		if(array_key_exists($strHandle, $this->arrXMLHandlers)){
			$strHandler = $this->arrXMLHandlers[$strHandle];
			call_user_func([$this, $strHandler], $arrPacket, $objClient);
		}
	}
	
	// Over-ride this function
	public function handleConstruction(){}
	
}

?>
