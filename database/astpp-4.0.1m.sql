ALTER TABLE `dids` ADD `localization_id` int(3) NULL;

CREATE TABLE `fraud_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) NOT NULL,
  `limit_key` varchar(32) NOT NULL,
  `limit_value` varchar(32) NULL DEFAULT NULL,
) ENGINE=InnoDB DEFAULT CHARSET=latin1;