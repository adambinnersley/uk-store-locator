CREATE TABLE IF NOT EXISTS `stores` (
  `id` smallint(6) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  `address` text,
  `postcode` varchar(10) NOT NULL,
  `lat` float(10,4) NOT NULL,
  `lng` float(10,4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `distance` (`lat`,`lng`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;