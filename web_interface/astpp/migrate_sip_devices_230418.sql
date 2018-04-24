ALTER TABLE sip_devices ADD COLUMN forward enum('Off', 'Always', 'Not Registered', 'No Answer') DEFAULT 'Off',
  ADD COLUMN forward_to varchar(50);