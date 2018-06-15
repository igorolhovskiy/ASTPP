INSERT INTO system
( name, display_name, value, field_type, comment, timestamp, reseller_id, brand_id, group_title) VALUES
('mpay24_status', 'Mpay24', '0', 'enable_disable_option', 'Set enable to add paypal as payment gateway option',
  NULL, 0, 0, 'mpay24');
INSERT INTO system
( name, display_name, value, field_type, comment, timestamp, reseller_id, brand_id, group_title) VALUES
('mpay24_mode', 'Mpay24 Mode', '1', 'mpay24_mode', 'Set mpay24 mode. Sandbox for testing',
  NULL, 0, 0, 'mpay24');
INSERT INTO system
( name, display_name, value, field_type, comment, timestamp, reseller_id, brand_id, group_title) VALUES
('mpay24_merchant_id', 'Mpay24 Merchant ID', '', 'default_system_input', 'Set mpay24 merchant ID',
  NULL, 0, 0, 'mpay24');
INSERT INTO system
( name, display_name, value, field_type, comment, timestamp, reseller_id, brand_id, group_title) VALUES
('mpay24_soap_password', 'Mpay24 Soap password', '', 'default_system_input', 'Set mpay24 Soap password',
  NULL, 0, 0, 'mpay24');