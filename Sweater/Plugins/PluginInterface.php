<?php

namespace Sweater\Plugins;

interface PluginInterface {
	
	public function getDependencies();
	public function getPluginInfo($blnPackets = false);
	public function handleGamePacket(Array $arrData);
	public function handleLoginPacket(Array $arrData);
	public function handleConstruction();
	
}

?>
