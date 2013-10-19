CREATE TABLE IF NOT EXISTS `Puffles` (
  `ID` mediumint(8) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Puffle ID',
  `Owner` int(11) unsigned NOT NULL COMMENT 'Owner''s player ID',
  `Name` tinytext NOT NULL COMMENT 'Puffle name',
  `Type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'Puffle type',
  `Hunger` tinyint(3) unsigned NOT NULL DEFAULT '100' COMMENT 'Puffle''s hunger status',
  `Health` tinyint(3) unsigned NOT NULL DEFAULT '100' COMMENT 'Puffle''s health status',
  `Rest` tinyint(3) unsigned NOT NULL DEFAULT '100' COMMENT 'Puffle''s rest status',
  `Walking` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Puffle''s walking status',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=latin1 COMMENT='Contains data regarding user puffles';

CREATE TABLE IF NOT EXISTS `Stats` (
  `ID` tinyint(3) unsigned NOT NULL COMMENT 'Server ID',
  `Population` smallint(5) unsigned NOT NULL COMMENT 'Server''s Population',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Server statistics';

CREATE TABLE IF NOT EXISTS `users` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `Username` char(12) NOT NULL COMMENT 'Nickname',
  `Password` char(40) NOT NULL COMMENT 'Password hash',
  `LoginKey` char(40) DEFAULT NULL COMMENT 'Used for logging into the game sevrer',
  `Active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `Status` tinytext NOT NULL COMMENT 'Contains the player''s online status',
  `RegisteredTime` int(11) NOT NULL COMMENT 'Unix timestamp',
  `Coins` int(10) unsigned NOT NULL DEFAULT '10000' COMMENT 'Player coins',
  `Color` smallint(5) unsigned NOT NULL DEFAULT '1' COMMENT 'Current color item',
  `Head` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Current head item',
  `Face` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Current face item',
  `Neck` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Current neck item',
  `Body` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Current body item',
  `Hand` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Current hand item',
  `Feet` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Current feet item',
  `Photo` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Current photo item',
  `Flag` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Current flag item',
  `Buddies` text NOT NULL COMMENT 'Player buddies',
  `Ignores` text NOT NULL COMMENT 'Player''s ignored list',
  `Inventory` text COMMENT 'Player inventory',
  `Igloo` tinyint(2) unsigned NOT NULL DEFAULT '1' COMMENT 'Current igloo ID',
  `Igloos` tinytext NOT NULL COMMENT 'Player''s owned igloos',
  `Music` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT 'Current music ID',
  `Floor` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'Current flooring ID',
  `RoomFurniture` text NOT NULL COMMENT 'Igloo furniture',
  `Furniture` text NOT NULL COMMENT 'Furniture inventory',
  `Postcards` text NOT NULL COMMENT 'Player postcards',
  `Moderator` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'Player''s moderator status',
  `Rank` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT 'Player''s rank (badge when multiplied by 146)',
  `LastLogin` int(11) unsigned DEFAULT NULL COMMENT 'Unix timestamp',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Username` (`Username`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1 COMMENT='This is the table in which all player data is stored.';
