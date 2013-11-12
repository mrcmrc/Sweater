<?php

namespace Sweater\Plugins\Interfaces;

interface PluginInterface {
	
	public function getDependencies();
	public function getPluginInfo($blnPackets = false);
	public function handleGamePacket(Array $arrData);
	public function handleLoginPacket(Array $arrData);
	public function handleConstruction();
	
}

?>
