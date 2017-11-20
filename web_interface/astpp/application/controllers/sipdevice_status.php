<?php
/**
 * Get sip device status
 */
//Include file
include (FCPATH."../../freeswitch/fs/lib/fusion.eventsocket.php");
include (FCPATH."../../freeswitch/fs/lib/astpp.sipstatus.php");

class Sipdevice_Status extends MX_Controller
{
	function Getbalance()
	{
		parent::__construct();
	}

	function index() {
		if (isset($_REQUEST['username'])) {
			$sip_device_to_search = $_REQUEST['username'];
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