ALTER TABLE invoice_templates ADD COLUMN type enum ('I', 'R') NOT NULL DEFAULT 'I' COMMENT 'I => Invoice R=> Receipt' AFTER id
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
UPDATE userlevels SET module_permissions = CONCAT(module_permissions, ',', @menu_id) WHERE  userlevelid = -1;
INSERT INTO invoice_templates SELECT '' AS id, 'R' AS type, 'default' AS name, subject, head_template,
  page1_template, '' AS page2_template, footer_template,last_modified_date FROM invoice_templates WHERE name = 'default';
ALTER TABLE accounts ADD COLUMN receipt_template_id int(11) DEFAULT 5 AFTER invoice_template_id;