ALTER TABLE `dids` ADD `localization_id` int(3) NULL;

CREATE TABLE `fraud_limits_counters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) NOT NULL,
  `limit_key` varchar(32) NOT NULL,
  `limit_value` varchar(32) NULL DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `accounts` ADD `daily_limit` DECIMAL(20,5);
ALTER TABLE `accounts` ADD `single_call_limit` DECIMAL(20,5);

ALTER TABLE timezone ADD COLUMN php_timezone_string varchar(255) DEFAULT NULL;
UPDATE timezone SET php_timezone_string = 'Europe/Berlin' WHERE id = 28;