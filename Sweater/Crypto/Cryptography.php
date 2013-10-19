<?php

namespace Sweater\Crypto;

trait Cryptography {
	
	function encryptPassword($strPassword, $strRandomKey){
		$strEncrypt = $this->swapHash($strPassword);
		$strEncrypt .= $strRandomKey;
		$strEncrypt .= 'Y(02.>\'H}t":E1';
		$strEncrypt = md5($strEncrypt);
		$strEncrypt = $this->swapHash($strEncrypt);
		return $strEncrypt;
	}
	
	function generateRandomString(){
		$strAlphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$strCharacters = '`~!@#$%^&*()_+{}|:"<>?[]\;\',./';
		$intAlpha = strlen($strAlphabet);
		$intChar = strlen($strCharacters);
		$strRandom = '';
		for($intLoops = 0; $intLoops < 10; $intLoops++){
			$intRandom = mt_rand(0, 1);
			$intCase = mt_rand(0, 1);
			$intSubstring = mt_rand(0, $intRandom ? $intAlpha : $intChar);
			$strChar = substr($intRandom ? $strAlphabet : $strCharacters, $intSubstring, 1);
			if($intRandom == 1 && $intCase == 1) $strChar = strtolower($strChar);
			$strRandom .= $strChar;
		}
		return $strRandom;
	}
	
	function swapHash($strHash){
		$strSwapped = substr($strHash, 16, 16);
		$strSwapped .= substr($strHash, 0, 16);
		return $strSwapped;
	}

	
}


?>
