INSERT INTO default_templates (name, subject, template, last_modified_date)
  VALUES ('email_detail_cdr',
  'Detail CDR to invoice #INVOICE_NUMBER# from #INVOICE_DATE#',
  'Hi #NAME#,<br> This is detail report to invoice #INVOICE_NUMBER# from #INVOICE_DATE#.<p>Please visit on our website #COMPANY_NAME# or contact to our support at #COMPANY_EMAIL# Thanks, #COMPANY_NAME#</p>',
  now());