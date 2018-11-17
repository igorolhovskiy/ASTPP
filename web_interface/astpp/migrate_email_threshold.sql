// Email Alerts for Administrator if client reach some threshold in balance
ALTER TABLE accounts ADD COLUMN alert_threshold_status tinyint DEFAULT 0 COMMENT '0:inactive, 1:active',
  ADD COLUMN alert_threshold_value decimal(10, 2) DEFAULT 0,
  ADD COLUMN alert_threshold_flag tinyint DEFAULT 0 COMMENT '0: not sent yet, 1: alert was sent';

INSERT INTO default_templates (name, subject, template, last_modified_date)
  VALUES ('email_alert_threshold',
  'Email Alerts for Administrator if client reach some threshold in balance',
  'Hi Admin,

This is a quick notification about the low balance + credit limit of #AMOUNT# in account #ACCOUNT#. Please take into account this information.

Thanks,
#COMPANY_NAME#',
  now());