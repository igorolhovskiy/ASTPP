<?php
/**
 * Get sip device status
 */
//Include file
include (FCPATH."../fs/lib/fusion.eventsocket.php");
include (FCPATH."../fs/lib/astpp.sipstatus.php");
error_reporting(0);

class Sipdevice_Status extends MX_Controller
{
	function __construct()
	{
		parent::__construct();
	}

	function index() {
		$username = $this->input->get('username');
		if (!empty($this->session->userdata['username']) && !empty($username)) {
			$sip_device_to_search = $username;
			if (get_device_status($sip_device_to_search)) {
				$result = array('success' => true, 'state' => 1);
			} else {
				$result = array('success' => true, 'state' => 0);
			}
		} else {
			$result = array('success' => false);
		}

		echo json_encode($result);
	}
}