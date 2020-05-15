ALTER TABLE timezone ADD COLUMN php_timezone_string varchar(255) DEFAULT NULL;
UPDATE timezone SET php_timezone_string = 'Europe/Berlin' WHERE id = 28;