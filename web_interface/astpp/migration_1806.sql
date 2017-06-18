INSERT INTO system
(
 ,name
 ,display_name
 ,value
 ,field_type
 ,comment
 ,timestamp
 ,reseller_id
 ,brand_id
 ,group_title
)
VALUES
(
 ,'automatic_invoice' -- name - VARCHAR(48)
 ,'Automatic invoice flag' -- display_name - VARCHAR(255) NOT NULL
 ,'0' -- value - VARCHAR(255)
 ,'default_system_input' -- field_type - VARCHAR(250) NOT NULL
 ,'' -- comment - VARCHAR(255)
 ,NOW() -- timestamp - DATETIME
 ,0 -- reseller_id - INT(11) NOT NULL
 ,0 -- brand_id - INT(11) NOT NULL
 ,'global' -- group_title - VARCHAR(15) NOT NULL
);
INSERT INTO system
(
 ,name
 ,display_name
 ,value
 ,field_type
 ,comment
 ,timestamp
 ,reseller_id
 ,brand_id
 ,group_title
)
VALUES
(
 ,'decimalpoints' -- name - VARCHAR(48)
 ,'Decimal Points' -- display_name - VARCHAR(255) NOT NULL
 ,'2' -- value - VARCHAR(255)
 ,'default_system_input' -- field_type - VARCHAR(250) NOT NULL
 ,'Set decimal points to use through out system' -- comment - VARCHAR(255)
 ,NOW() -- timestamp - DATETIME
 ,0 -- reseller_id - INT(11) NOT NULL
 ,0 -- brand_id - INT(11) NOT NULL
 ,'global' -- group_title - VARCHAR(15) NOT NULL
);