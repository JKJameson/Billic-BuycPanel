<?php
class BuycPanel {
	public $settings = array(
		'orderform_vars' => array('ipaddress'),
		'description' => 'Create cPanel licenses with buycpanel.com.',
	);
	
	function suspend($array) {
		global $billic, $db;
		$service = $array['service'];
		$data = $this->curl('cancel.php?currentip='.urlencode($service['domain']));
		$data = json_decode($data);
		if($data->success==1) {
			if (isset($data->faultstring)) {
				return 'BuycPanel Error: '.$data->faultstring;
			}
			return true;
		} else {
			if (isset($data->faultstring) && $data->faultstring=='There was an error during your order, please contact customer support.') { // Crap API... doesn't tell us the license is already terminated -.-
				return true;
			}
			return 'BuycPanel Error: '.$data->result;
		}
	}
	
	function unsuspend($array) {
		global $billic, $db;
		$service = $array['service'];
		$billic->email(get_config('billic_companyemail'), 'Reactivate buycpanel Licence: '.$service['domain'], $service['domain']);
		return true;
	}
	
	function terminate($array) {
		global $billic, $db;
		$service = $array['service'];
		return true;
	}
	
	function create($array) {
		global $billic, $db;
		$vars = $array['vars'];
		$service = $array['service'];
		$plan = $array['plan'];
		$user_row = $array['user'];
	    
		if (!$this->isIPAddress($service['domain'])) {
			return 'Domain must be an IP address';
		}
		$data = $this->curl('order.php?domain='.urlencode($service['domain']).'&serverip='.urlencode($service['domain']).'&ordertype=10');
		$data = json_decode($data);
		if($data->success==1) {
			if (isset($data->faultstring)) {
				return 'BuycPanel Error: '.$data->faultstring;
			}
			return true;
		} else {
			return 'BuycPanel Error: '.$data->result;
		}
	}
	
	function curl($action) {
		global $billic, $db;
		$options = array(
			CURLOPT_URL				=> 'https://www.buycpanel.com/api/'.$action.'&login='.urlencode(get_config('buycpanel_user')).'&key='.urlencode(get_config('buycpanel_apikey')).'&test=0',
			CURLOPT_RETURNTRANSFER	=> true,
			CURLOPT_HEADER			=> false,
			CURLOPT_FOLLOWLOCATION	=> true,
			CURLOPT_ENCODING		=> "",
			CURLOPT_USERAGENT		=> "Curl",
			CURLOPT_AUTOREFERER		=> true,
			CURLOPT_CONNECTTIMEOUT	=> 2,
			CURLOPT_TIMEOUT			=> 60,
			CURLOPT_MAXREDIRS		=> 3,
			CURLOPT_SSL_VERIFYHOST	=> 0,
			CURLOPT_SSL_VERIFYPEER	=> false,
			//CURLOPT_VERBOSE			=> 1,
		);
		$ch = curl_init();
		curl_setopt_array($ch, $options);
		$data = curl_exec($ch);
		$data = trim($data);
		return $data;
	}
	
	function isIPAddress($ip) {
		global $billic, $db;
		return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
	}
	
	function ordercheck($array) {
		global $billic, $db;
		$vars = $array['vars'];
		if (!$this->isIPAddress($vars['ipaddress'])) {
			$billic->error('Invalid IP Address', 'ipaddress');
		}
		return $vars['ipaddress']; // return the domain for the service to be called
	}
	
	function settings($array) {
		global $billic, $db;
		if (empty($_POST['update'])) {
			echo '<form method="POST"><input type="hidden" name="billic_ajax_module" value="BuycPanel"><table class="table table-striped">';
			echo '<tr><th>Setting</th><th>Value</th></tr>';
			echo '<tr><td>BuycPanel User Email</td><td><input type="text" class="form-control" name="buycpanel_user" value="'.safe(get_config('buycpanel_user')).'" style="width: 100%"></td></tr>';
			echo '<tr><td>BuycPanel API Key</td><td><input type="text" class="form-control" name="buycpanel_apikey" value="'.safe(get_config('buycpanel_apikey')).'" style="width: 100%"></td></tr>';
			echo '<tr><td colspan="2" align="center"><input type="submit" class="btn btn-default" name="update" value="Update &raquo;"></td></tr>';
			echo '</table></form>';
		} else {
			if (empty($billic->errors)) {
				set_config('buycpanel_user', $_POST['buycpanel_user']);
				set_config('buycpanel_apikey', $_POST['buycpanel_apikey']);
				$billic->status = 'updated';
			}
		}
	}
}
