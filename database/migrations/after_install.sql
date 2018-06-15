CREATE TABLE astpp.invoice_templates (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(50) NOT NULL DEFAULT '',
  subject varchar(250) NOT NULL,
  head_template mediumtext NOT NULL,
  details_template mediumtext NOT NULL,
  total_template mediumtext NOT NULL,
  group_calls_template mediumtext NOT NULL,  
  footer_template mediumtext NOT NULL,
  last_modified_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (id)
)
ENGINE = INNODB
CHARACTER SET utf8
COLLATE utf8_general_ci;

INSERT INTO menu_modules (menu_label, module_name, module_url, menu_title, menu_image, menu_subtitle, priority)
  VALUES('Invoice Templates',	'templates',	'invoices/templates/template_list/',	'Configuration','TemplateManagement.png','0',	80.4);
SET @lastID := LAST_INSERT_ID();
UPDATE userlevels set module_permissions = CONCAT(module_permissions,',',@lastID) WHERE userlevelid = -1;

CREATE TABLE astpp.invoice_template_vars (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(50) DEFAULT NULL,
  query text DEFAULT NULL,
  comment varchar(255) DEFAULT NULL,
  PRIMARY KEY (id)
)
ENGINE = INNODB
CHARACTER SET utf8
COLLATE utf8_general_ci;

INSERT INTO invoice_template_vars
(
  id
 ,name
 ,query
 ,comment
)
VALUES
(
  0 -- id - INT(11) NOT NULL
 ,'countcdr' -- name - VARCHAR(50)
 ,'select count(*) from cdrs where accountid = {$accounts.id}' -- query - TEXT
 ,'sample custom variable' -- comment - VARCHAR(255)
);

/* Insert template data */
INSERT INTO invoice_templates
(
  id
 ,name
 ,subject
 ,head_template
 ,details_template
 ,total_template
 ,group_calls_template
 ,footer_template
 ,last_modified_date
)
VALUES
(
  0 -- id - INT(11) NOT NULL
 ,'default' -- name - VARCHAR(50) NOT NULL
 ,'Default template' -- subject - VARCHAR(250) NOT NULL
 ,'<table style="width: 100%; padding: 40px 93px 100px 50px;" border="0" cellspacing="1" align="left">
<tbody>
<tr>
<td style="width: 100%;"><img src="{$logo}" alt="" /></td>
</tr>
</tbody>
</table>
<table style="width: 100%; padding-right: 93px; padding-left: 60px;" cellspacing="1">
<tbody>
<tr>
<td style="width: 100%;">
<table>
<tbody>
<tr>
<td style="width: 100%; font-size: 14px; color: #525252; font-family: arial; line-height: 22px;">{$invoice_conf.company_name}</td>
</tr>
<tr>
<td style="width: 100%; font-size: 14px; color: #525252; font-family: arial; line-height: 22px;">{$invoice_conf.address}</td>
</tr>
<tr>
<td style="width: 100%; font-size: 14px; color: #525252; font-family: arial; line-height: 22px;">{$invoice_conf.city}</td>
</tr>
<tr>
<td style="width: 100%; font-size: 14px; color: #525252; font-family: arial; line-height: 22px;">{$invoice_conf.province}</td>
</tr>
<tr>
<td style="width: 100%; font-size: 14px; color: #525252; font-family: arial; line-height: 22px;">{$invoice_conf.country}</td>
</tr>
<tr>
<td style="width: 100%; font-size: 14px; color: #525252; font-family: arial; line-height: 23px;">{$invoice_conf.zipcode}</td>
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>
<table style="width: 100%; padding-right: 60px; padding-left: 80px;" border="0" cellspacing="0">
<tbody>
<tr style="width: 100%;">
<td style="width: 68%; font-size: 14px; color: #525252; font-family: arial; line-height: 22px; text-align: right;">Kundennummer: {$accounts.number}</td>
</tr>
<tr style="width: 100%;">
<td style="width: 100%; font-size: 14px; color: #525252; font-family: arial; line-height: 22px; text-align: right;">Rechnungsnummer: {$invoices.invoice_prefix}{$invoices.invoiceid}</td>
</tr>
<tr style="width: 100%;">
<td style="width: 100%; text-align: right; font-size: 14px; color: #525252; font-family: arial; line-height: 22px;">Datum: {$from_date}</td>
</tr>
<tr style="width: 100%;">
<td style="width: 100%; text-align: right; font-size: 14px; color: #525252; font-family: arial; line-height: 22px;">F&auml;lligkeit: {$due_date}</td>
</tr>
<tr style="width: 100%; line-height: 30px;">
<td style="width: 100%; text-align: right; font-size: 14px; color: #fff; font-family: arial; line-height: 30px;">Total Due: {$sub_total}</td>
</tr>
</tbody>
</table>' -- head_template - MEDIUMTEXT NOT NULL
 ,'<table style="width: 100%; padding-right: 60px; padding-left: 60px; padding-bottom: 60px; border-collapse: collapse;" border="0" cellspacing="1">
<thead>
<tr>
<td style="width: 60%; font-size: 15px; color: #333333; font-weight: bold; font-family: arial; line-height: 22px; padding-bottom: 30px;" colspan="2"><strong>Lieferungen &amp; Leistungen</strong></td>
</tr>
<tr>
<td style="width: 50%; font-size: 14px; color: #525252; font-family: arial; border-bottom: 2px solid #808080; line-height: 22px;">Beschreibung</td>
<td style="width: 50%; font-size: 14px; border-bottom: 2px solid #808080; color: #525252; font-family: arial; line-height: 22px;" align="right">Betrag</td>
</tr>
</thead>
<tbody>
<tr>
<td style="width: 50%; font-size: 14px; padding-top: 5px; color: #525252; font-family: arial; line-height: 22px;">{$invoice_details[{$i}].description}</td>
<td style="width: 50%; font-size: 14px; padding-top: 5px; color: #525252; font-family: arial; line-height: 22px;" align="right">{$currency} {$invoice_details[{$i}].debit}</td>
</tr>
</tbody>
</table>' -- details_template - MEDIUMTEXT NOT NULL
 ,'<div style="margin-right: 60px; margin-left: 60px;">
<table style="width: 100%; padding-bottom: 30px;" cellspacing="0">
<tfoot>
<tr style="height: 40px;">
<td style="height: 40px; border-top: 2px solid black; width: 50%; font-size: 14px; color: #333333; text-align: left; font-family: arial; line-height: 40px;"><strong>Gesamt brutto</strong></td>
<td style="height: 40px; border-top: 2px solid black; width: 50%; font-size: 14px; color: #333333; font-family: arial; line-height: 40px; text-align: right;"><strong>{$currency} {$sub_total}</strong></td>
</tr>
</tfoot>
<thead>
<tr style="height: 25px;">
<td style="color: #525252; height: 25px; width: 50%; font-size: 14px; font-family: arial; line-height: 25px;">Zwischensumme netto</td>
<td style="color: #525252; height: 25px; width: 50%; font-size: 14px; font-family: arial; line-height: 25px; text-align: right;">{$currency} {$total_sum}</td>
</tr>
</thead>
<tbody>
<tr style="height: 30px;">
<td style="color: #525252; height: 30px; width: 50%; font-size: 14px; font-family: arial; line-height: 30px;">{$invoice_details_tax[{$i}].description}</td>
<td style="color: #525252; height: 30px; width: 50%; font-size: 14px; font-family: arial; line-height: 30px; text-align: right;">{$currency} {$invoice_details_tax[{$i}].total_vat}</td>
</tr>
</tbody>
</table>
</div>' -- total_template - MEDIUMTEXT NOT NULL
 ,'<div style="margin-right: 60px; margin-left: 60px;">
<table style="width: 100%; padding-top: 40px; padding-bottom: 30px;" cellspacing="0">
<thead>
<tr>
<td style="width: 100%; font-size: 16px; font-weight: bold; color: #525252; font-family: arial; border-bottom: 2px solid #808080; line-height: 22px;" colspan="4">Group Calls</td>
</tr>
<tr>
<td style="width: 10%; font-size: 14px; color: #525252; font-family: arial; border-bottom: 2px solid #808080; line-height: 22px;">Pos</td>
<td style="width: 20%; font-size: 14px; border-bottom: 2px solid #808080; color: #525252; font-family: arial; line-height: 22px;">Min:Sec</td>
<td style="width: 50%; font-size: 14px; border-bottom: 2px solid #808080; color: #525252; font-family: arial; line-height: 22px;">Group name</td>
<td style="width: 20%; font-size: 14px; border-bottom: 2px solid #808080; color: #525252; font-family: arial; line-height: 22px;">Amount</td>
</tr>
</thead>
<tbody>
<tr>
<td style="width: 10%; font-size: 14px; color: #525252; font-family: arial; border-bottom: 2px solid #808080; line-height: 22px;">{$group_calls[{$i}].num}</td>
<td style="width: 20%; font-size: 14px; border-bottom: 2px solid #808080; color: #525252; font-family: arial; line-height: 22px;">{$group_calls[{$i}].total_seconds}</td>
<td style="width: 50%; font-size: 14px; border-bottom: 2px solid #808080; color: #525252; font-family: arial; line-height: 22px;">{$group_calls[{$i}].group_calls_name}</td>
<td style="width: 20%; font-size: 14px; border-bottom: 2px solid #808080; color: #525252; font-family: arial; line-height: 22px;">{$group_calls[{$i}].total_debit}</td>
</tr>
</tbody>
</table>
</div>' -- group_calls_template - MEDIUMTEXT NOT NULL
 ,'<table class="page_footer" align="center">
<tbody>
<tr>
<td style="width: 50%; font-size: 12px; text-align: center; color: #333333; font-family: arial; line-height: 22px;">Consertis GmbH | Am Gestade 3 | 1010 Wien</td>
</tr>
<tr>
<td style="width: 50%; font-size: 12px; text-align: center; color: #333333; font-family: arial; line-height: 22px;">Tel.: +43 1 235 12 00, Fax DW -40 | E-Mail: office@consertis.at | Web: www.consertis.at</td>
</tr>
<tr>
<td style="width: 50%; font-size: 12px; padding-bottom: 20px; text-align: center; color: #333333; font-family: arial; line-height: 22px;">FN 428591g | UID: ATU 69303504 | IBAN: AT23 3200 0000 1040 6411 | BIC: RLNWATWW</td>
</tr>
</tbody>
</table>
<div><img style="width: 100%; margin-bottom: 40px;" src="{$strip}" /></div>' -- footer_template - MEDIUMTEXT NOT NULL
 ,NOW() -- last_modified_date - DATETIME NOT NULL
);

ALTER TABLE astpp.invoice_templates ADD COLUMN page1_template mediumtext AFTER head_template,
                                      ADD COLUMN page2_template mediumtext AFTER page1_template;
UPDATE astpp.invoice_templates it set
  it.head_template = '<table style="width: 100%; padding: 40px 93px 100px 50px;" border="0" cellspacing="1" align="left">
<tbody>
<tr>
<td style="width: 100%;"><img src="{$logo}" alt="" /></td>
</tr>
</tbody>
</table>',
  it.page1_template = '<table style="width: 100%; padding-right: 93px; padding-left: 60px;" cellspacing="1">
<tbody>
<tr>
<td style="width: 100%;">
<table>
<tbody>
<tr>
<td style="width: 100%; font-size: 14px; color: #525252; font-family: arial; line-height: 22px;">{$invoice_conf.company_name}</td>
</tr>
<tr>
<td style="width: 100%; font-size: 14px; color: #525252; font-family: arial; line-height: 22px;">{$invoice_conf.address}</td>
</tr>
<tr>
<td style="width: 100%; font-size: 14px; color: #525252; font-family: arial; line-height: 22px;">{$invoice_conf.city}</td>
</tr>
<tr>
<td style="width: 100%; font-size: 14px; color: #525252; font-family: arial; line-height: 22px;">{$invoice_conf.province}</td>
</tr>
<tr>
<td style="width: 100%; font-size: 14px; color: #525252; font-family: arial; line-height: 22px;">{$invoice_conf.country}</td>
</tr>
<tr>
<td style="width: 100%; font-size: 14px; color: #525252; font-family: arial; line-height: 23px;">{$invoice_conf.zipcode}</td>
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>
<table style="width: 100%; padding-right: 60px; padding-left: 80px;" border="0" cellspacing="0">
<tbody>
<tr style="width: 100%;">
<td style="width: 68%; font-size: 14px; color: #525252; font-family: arial; line-height: 22px; text-align: right;">Kundennummer: {$accounts.number}</td>
</tr>
<tr style="width: 100%;">
<td style="width: 100%; font-size: 14px; color: #525252; font-family: arial; line-height: 22px; text-align: right;">Rechnungsnummer: {$invoices.invoice_prefix}{$invoices.invoiceid}</td>
</tr>
<tr style="width: 100%;">
<td style="width: 100%; text-align: right; font-size: 14px; color: #525252; font-family: arial; line-height: 22px;">Datum: {$from_date}</td>
</tr>
<tr style="width: 100%;">
<td style="width: 100%; text-align: right; font-size: 14px; color: #525252; font-family: arial; line-height: 22px;">F&auml;lligkeit: {$due_date}</td>
</tr>
<tr style="width: 100%; line-height: 30px;">
<td style="width: 100%; text-align: right; font-size: 14px; color: #fff; font-family: arial; line-height: 30px;">Total Due: {$sub_total}</td>
</tr>
</tbody>
</table>
<p>&nbsp;</p>
<table style="width: 100%; padding-right: 60px; padding-left: 60px; padding-bottom: 60px; border-collapse: collapse;" border="0" cellspacing="1">
<thead>
<tr>
<td style="width: 60%; font-size: 15px; color: #333333; font-weight: bold; font-family: arial; line-height: 22px; padding-bottom: 30px;" colspan="2"><strong>Lieferungen &amp; Leistungen</strong></td>
</tr>
<tr>
<td style="width: 50%; font-size: 14px; color: #525252; font-family: arial; border-bottom: 2px solid #808080; line-height: 22px;">Beschreibung</td>
<td style="width: 50%; font-size: 14px; border-bottom: 2px solid #808080; color: #525252; font-family: arial; line-height: 22px;" align="right">Betrag</td>
</tr>
</thead>
<tbody foreach="$invoice_details" class="myclass">
<tr>
<td style="width: 50%; font-size: 14px; padding-top: 5px; color: #525252; font-family: arial; line-height: 22px;">{$invoice_details[{$i}].description}</td>
<td style="width: 50%; font-size: 14px; padding-top: 5px; color: #525252; font-family: arial; line-height: 22px;" align="right">{$currency} {$invoice_details[{$i}].debit}</td>
</tr>
</tbody>
</table>
<p>&nbsp;</p>
<div style="margin-right: 60px; margin-left: 60px;">
<table style="width: 100%; padding-bottom: 30px;" cellspacing="0">
<tfoot>
<tr style="height: 40px;">
<td style="height: 40px; border-top: 2px solid black; width: 50%; font-size: 14px; color: #333333; text-align: left; font-family: arial; line-height: 40px;"><strong>Gesamt brutto</strong></td>
<td style="height: 40px; border-top: 2px solid black; width: 50%; font-size: 14px; color: #333333; font-family: arial; line-height: 40px; text-align: right;"><strong>{$currency} {$sub_total}</strong></td>
</tr>
</tfoot>
<thead>
<tr style="height: 25px;">
<td style="color: #525252; height: 25px; width: 50%; font-size: 14px; font-family: arial; line-height: 25px;">Zwischensumme netto</td>
<td style="color: #525252; height: 25px; width: 50%; font-size: 14px; font-family: arial; line-height: 25px; text-align: right;">{$currency} {$total_sum}</td>
</tr>
</thead>
<tbody foreach="$invoice_details_tax">
<tr style="height: 30px;">
<td style="color: #525252; height: 30px; width: 50%; font-size: 14px; font-family: arial; line-height: 30px;">{$invoice_details_tax[{$i}].description}</td>
<td style="color: #525252; height: 30px; width: 50%; font-size: 14px; font-family: arial; line-height: 30px; text-align: right;">{$currency} {$invoice_details_tax[{$i}].total_vat}</td>
</tr>
</tbody>
</table>
</div>',
  it.page2_template = '<div style="margin-right: 60px; margin-left: 60px;">
<table style="width: 100%; padding-top: 40px; padding-bottom: 30px;" cellspacing="0">
<thead>
<tr>
<td style="width: 100%; font-size: 16px; font-weight: bold; color: #525252; font-family: arial; border-bottom: 2px solid #808080; line-height: 22px;" colspan="4">Group Calls</td>
</tr>
<tr>
<td style="width: 10%; font-size: 14px; color: #525252; font-family: arial; border-bottom: 2px solid #808080; line-height: 22px;">Pos</td>
<td style="width: 20%; font-size: 14px; border-bottom: 2px solid #808080; color: #525252; font-family: arial; line-height: 22px;">Min:Sec</td>
<td style="width: 50%; font-size: 14px; border-bottom: 2px solid #808080; color: #525252; font-family: arial; line-height: 22px;">Group name</td>
<td style="width: 20%; font-size: 14px; border-bottom: 2px solid #808080; color: #525252; font-family: arial; line-height: 22px;">Amount</td>
</tr>
</thead>
<tbody foreach="$group_calls">
<tr>
<td style="width: 10%; font-size: 14px; color: #525252; font-family: arial; border-bottom: 2px solid #808080; line-height: 22px;">{$group_calls[{$i}].num}</td>
<td style="width: 20%; font-size: 14px; border-bottom: 2px solid #808080; color: #525252; font-family: arial; line-height: 22px;">{$group_calls[{$i}].total_seconds}</td>
<td style="width: 50%; font-size: 14px; border-bottom: 2px solid #808080; color: #525252; font-family: arial; line-height: 22px;">{$group_calls[{$i}].group_calls_name}</td>
<td style="width: 20%; font-size: 14px; border-bottom: 2px solid #808080; color: #525252; font-family: arial; line-height: 22px;">{$group_calls[{$i}].total_debit}</td>
</tr>
</tbody>
</table>
</div>';
ALTER TABLE astpp.invoice_templates DROP COLUMN details_template, DROP COLUMN total_template, DROP COLUMN group_calls_template;

ALTER TABLE accounts ADD COLUMN invoice_template_id int DEFAULT 1;
ALTER TABLE cdrs MODIFY calltype ENUM('STANDARD','DID','FREE','CALLINGCARD', 'PACKAGE', 'PACKAGE+') DEFAULT 'STANDARD';

ALTER TABLE accounts ADD COLUMN general_field1 varchar(255),
  ADD COLUMN general_field2 varchar(255),
  ADD COLUMN general_field3 varchar(255),
  ADD COLUMN general_field4 varchar(255),
  ADD COLUMN general_field5 varchar(255);

  INSERT INTO system
(
 name
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
 'automatic_invoice' -- name - VARCHAR(48)
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
 name
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
 'decimalpoints' -- name - VARCHAR(48)
 ,'Decimal Points' -- display_name - VARCHAR(255) NOT NULL
 ,'2' -- value - VARCHAR(255)
 ,'default_system_input' -- field_type - VARCHAR(250) NOT NULL
 ,'Set decimal points to use through out system' -- comment - VARCHAR(255)
 ,NOW() -- timestamp - DATETIME
 ,0 -- reseller_id - INT(11) NOT NULL
 ,0 -- brand_id - INT(11) NOT NULL
 ,'global' -- group_title - VARCHAR(15) NOT NULL
);


UPDATE rent_products_types rpt set name = 'Recur' WHERE id = 2;
UPDATE rent_products_types rpt set name = 'Always' WHERE id = 3;

alter TABLE invoice_details ADD COLUMN count int DEFAULT 0;
ALTER TABLE invoice_details ADD INDEX(invoiceid), ADD INDEX(item_type);
ALTER TABLE cdrs ADD INDEX(callstart);
ALTER TABLE cdrs ADD INDEX(accountid);

ALTER TABLE dids ADD COLUMN is_ported tinyint(1) NOT NULL DEFAULT 0;

INSERT INTO default_templates (name, subject, template, last_modified_date)
  VALUES ('email_detail_cdr',
  'Detail CDR to invoice #INVOICE_NUMBER# from #INVOICE_DATE#',
  'Hi #NAME#,<br> This is detail report to invoice #INVOICE_NUMBER# from #INVOICE_DATE#.<p>Please visit on our website #COMPANY_NAME# or contact to our support at #COMPANY_EMAIL# Thanks, #COMPANY_NAME#</p>',
  now());

  ALTER TABLE sip_devices ADD COLUMN forward enum('Off', 'Always', 'Not Registered', 'No Answer') DEFAULT 'Off',
  ADD COLUMN forward_to varchar(50);


ALTER TABLE invoice_templates ADD COLUMN type enum ('I', 'R') NOT NULL DEFAULT 'I' COMMENT 'I => Invoice R=> Receipt' AFTER id;
INSERT INTO menu_modules
(
  menu_label
 ,module_name
 ,module_url
 ,menu_title
 ,menu_image
 ,menu_subtitle
 ,priority
)
VALUES
(
 'Receipt Templates' -- menu_label - VARCHAR(25) NOT NULL
 ,'templates' -- module_name - VARCHAR(25) NOT NULL
 ,'invoices/templates/template_list/R' -- module_url - VARCHAR(100) NOT NULL
 ,'Configuration' -- menu_title - VARCHAR(20) NOT NULL
 ,'TemplateManagement.png' -- menu_image - VARCHAR(25) NOT NULL
 ,'0' -- menu_subtitle - VARCHAR(20) NOT NULL
 ,80.4 -- priority - FLOAT NOT NULL
);
SELECT id FROM menu_modules WHERE menu_label = 'Receipt Templates' LIMIT 1 INTO @menu_id;
UPDATE userlevels SET module_permissions = '1,2,4,5,3,8,9,13,14,15,16,17,18,19,20,21,22,25,26,27,28,7,29,30,45,38,39,40,41,42,43,44,48,49,53,54,55,56,66,68,69,77,78,100,101,102,103,104,105' WHERE userlevelid = -1;
INSERT INTO invoice_templates SELECT '' AS id, 'R' AS type, 'default' AS name, subject, head_template,
  page1_template, '' AS page2_template, footer_template,last_modified_date FROM invoice_templates WHERE name = 'default';
ALTER TABLE accounts ADD COLUMN receipt_template_id int(11) DEFAULT 5 AFTER invoice_template_id;