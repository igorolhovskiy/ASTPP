ALTER TABLE subscriber ADD COLUMN `accountcode` varchar(20) NOT NULL,
  ADD COLUMN `pricelist_id` int(11) NOT NULL DEFAULT '0',
  ADD COLUMN `channel_limit` int(5) DEFAULT NULL,
  ADD COLUMN `effective_caller_id_name` varchar(50) NOT NULL,
  ADD COLUMN `effective_caller_id_number` varchar(50) NOT NULL,
  ADD COLUMN `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  ADD COLUMN `last_modified_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  ADD COLUMN `reseller_id` int(4) NOT NULL,
  ADD COLUMN `status` int(11) NOT NULL DEFAULT '0';