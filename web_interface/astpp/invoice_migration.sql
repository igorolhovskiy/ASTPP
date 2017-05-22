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