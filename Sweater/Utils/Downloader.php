<?php

namespace Sweater\Utils;

class Downloader {
	
	static function AsyncDownload($arrFiles){
		if(!is_array($arrFiles)) return false;
		$intFiles = sizeof($arrFiles);
		$arrCurl = array();
		$resMulti = curl_multi_init();
		for($intIndex = 0; $intIndex < $intFiles; $intIndex++){
			$strURL = $arrFiles[$intIndex];
			$arrCurl[$intIndex] = curl_init($strURL);
			curl_setopt($arrCurl[$intIndex], CURLOPT_RETURNTRANSFER, true);
			curl_multi_add_handle($resMulti, $arrCurl[$intIndex]);
		}
		do {
			curl_multi_exec($resMulti, $intRunning);
		} while($intRunning > 0);

		$arrData = array();
		for($intIndex = 0; $intIndex < $intFiles; $intIndex++){
			$arrData[$arrFiles[$intIndex]] = curl_multi_getcontent($arrCurl[$intIndex]);
		}
		unset($arrCurl);
		return $arrData;
	}
		
}

?>
