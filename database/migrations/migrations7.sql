alter TABLE invoice_details ADD COLUMN count int DEFAULT 0;
ALTER TABLE invoice_details ADD INDEX(invoiceid), ADD INDEX(item_type);
ALTER TABLE cdrs ADD INDEX(callstart);
ALTER TABLE cdrs ADD INDEX(accountid);

ALTER TABLE dids ADD COLUMN is_ported tinyint(1) NOT NULL DEFAULT 0;