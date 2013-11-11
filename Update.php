<?php

$strNewItems = "130000 - Orange Polo
130001 - Yellow Polo
130002 - Green Polo
130003 - Purple Polo
130004 - Grey Polo
130005 - Cotton Candy Scarf
130006 - Blue and Black Scarf
130007 - Dark Blue and Black Scarf
130008 - Green and Black Scarf
130010 - Red and Black Scarf
130011 - Yellow and Blue Scarf
130012 - Red MP3000
130015 - Green MP3000
130016 - Blue MP3000";

$arrNewItems = explode(chr(10), $strNewItems);

$intSize = filesize('Crumbs/Items.json');
$resFile = fopen('Crumbs/Items.json', 'w');
$strItems = fread($resFile, 90000);
fclose($resFile);

$arrItems = json_decode($strItems, true);
print_r($arrItems);

foreach($arrNewItems as $strItem){
	$arrSpaced = explode(' ', $strItem);
	list($intItem) = $arrSpaced;
	unset($arrSpaced[0]);
	unset($arrSpaced[1]);
	$arrSpaced = array_values($arrSpaced);
	$strName = implode(' ', $arrSpaced);
	echo 'Adding ', $strName, chr(10);
	$arrItems[$intItem] = [
		'Name' => $strName,
		'Cost' => 0,
		'Member' => 'false',
		'Type' => 'Other'
	];
}

$strItems = json_encode($arrItems);
$resFile = fopen('Crumbs/Items.json', 'w');
fwrite($resFile, $strItems);
fclose($resFile);

?>
