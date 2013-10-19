<?php

namespace Sweater\Interfaces;
use Sweater;

interface StandardRoomManager {
	
	public function __construct();
	public function addIglooUser($intRoom, Sweater\Client $objClient, Array $arrCoordinates = [0, 0]);
	public function addUser($intRoom, Sweater\Client $objClient, Array $arrCoordinates = [0, 0]);
	public function createRoom($intRoom);
	public function existsRoom($intRoom);
	public function getIglooStatus($intIgloo);
	public function getInternal($intRoom);
	public function getUserCount($intRoom);
	public function getUsers($intRoom);
	public function removeFromRooms(Sweater\Client $objClient);
	public function sendXt();
	
}

?>
