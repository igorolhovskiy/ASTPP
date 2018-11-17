<?php if ( ! defined('CRON')) {
	exit('CLI script access allowed only');
}

/*
|--------------------------------------------------------------------------
| CRON Configuration
|--------------------------------------------------------------------------
*/

$astpp_config = parse_ini_file("/var/lib/astpp/astpp-config.conf");
$_SERVER['HTTP_HOST'] = $astpp_config['base_url'];
$config['SERVER_NAME'] 		= $astpp_config['base_url'];	// Your web site url
$config['CRON_TIME_LIMIT']	= 0;								// 0 = no time limit
$config['argv']			= array("LowBalance"=>"lowbalance/low_balance",
					"Lowcredit"=>"lowcreditlimit/low_creditlimit",
					"UpdateBalance" => "updateBalance/GetUpdateBalance",
						"CurrencyUpdate" => "currencyupdate/update_currency",
					"GenerateInvoice" => "generateInvoice/getInvoiceData",
					"FeedBack"=>"feedback/customer_feedback_result/TRUE",
					"BroadcastEmail" => "broadcastemail/broadcast_email",
					"AlertThreshold" => "alertthreshold/alert_threshold"
	);
$config['CRON_BETA_MODE']	= false;							// Beta Mode (useful for blocking submissions for testing)

?>
