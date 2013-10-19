<?php

interface BotMethods {
	
	public function buildPlayerString();
	public function addListener($strPacket, $mixCallback);
	public function handleData($strPacket, $objClient);
	public function sendMessage($strMessage, $objClient);

}

?>
