<?php

class Sendbox_Shipping_API{
    //make a post to sendbox api using curl.
    public function post_on_api_by_curl($url, $data, $api_key)
    {
        $ch = curl_init($url);
        // Setup request to send json via POST.
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:' . $api_key));
        // Return response instead of printing.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Send request.
        $result = curl_exec($ch);
        curl_close($ch);
        // Print response.
        return $result;
	} 

	public function get_api_response_with_header($url, $request_headers){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
        $season_data = curl_exec($ch);
        if (curl_errno($ch)) {
            print "Error: " . curl_error($ch);
            exit();
        }
        // Show me the result
        curl_close($ch);
        return $season_data;

    }

 ///sendox endpoint
	public function get_sendbox_api_url($url_type)
    {
        if ('delivery_quote' == $url_type) {
            $url = 'https://live.sendbox.co/shipping/shipment_delivery_quote';
        } elseif ('countries' == $url_type) {
            $url = 'https://api.sendbox.co/auth/countries?page_by={' . '"per_page"' . ':264}';
        } elseif ('states' == $url_type) {
            $url = 'https://api.sendbox.co/auth/states';
        } elseif ('shipments' == $url_type) {
            $url = 'https://api.sendbox.ng/v1/merchant/shipments';
        } elseif ('item_type' == $url_type) {
            $url = 'https://api.sendbox.ng/v1/item_types';
        } elseif ('profile' == $url_type) {
            $url = 'https://live.sendbox.co/oauth/profile';
        }else {
            $url = '';
        }
        return $url;
    }
}

class ModelExtensionShippingSendbox extends Model {
	
	function getQuote($address) {
		$api_key = $this->checkauth();
		$this->load->language('extension/shipping/sendbox');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('shipping_sendbox_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		if (!$this->config->get('shipping_sendbox_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}
		
		
		$this->load->model('checkout/order');
			$this->load->model('localisation/country');
			$country_info = $this->model_localisation_country->getCountry($this->config->get('config_country_id'));	
			
			$origin_country = $country_info['name'];
			$this->load->model('localisation/zone');
			$zone_info = $this->model_localisation_zone->getZone($this->config->get('config_zone_id'));
			
			$origin_state = $zone_info['name'];
			//$destination_state_code = "ABV";
			$destination_state = $address['zone'];
			//var_dump($address);
			$destination_country =$address['country'];
			//var_dump($destination_country);
			//$destination_country_code = "NG";
			$weight = ($this->cart->getWeight());
			$sendbox_obj = new Sendbox_Shipping_API(); 
			$url = $sendbox_obj->get_sendbox_api_url('delivery_quote');
			$payload_data = new stdClass(); 
			$payload_data->destination_state= $destination_state;
			$payload_data->destination_country = $destination_country;
			$payload_data->origin_country = $origin_country;
			$payload_data->origin_state = $origin_state;
			$payload_data->weight = $weight;
			$payload_data->destination_post_code = $address['postcode'];
			$quotes_data = json_encode($payload_data);
           // var_dump($quotes_data);
			//get api key one long yarns like that but we go rough am
			//make call to profile
			
			$data = $this->get_data()->row;
			
		$sendbox_quotes_res = $sendbox_obj->post_on_api_by_curl($url, $quotes_data, $api_key);
		$sendbox_quotes_obj = json_decode($sendbox_quotes_res);
		//var_dump($sendbox_quotes_obj);
		$sendbox_quotes = $sendbox_quotes_obj->max_quoted_fee;
	
	
		$method_data = array();
		if ($status) { 
		

		
			$quote_data = array();
			//var_dump($sendbox_quotes);
			

			$quote_data['sendbox'] = array(
				'code'         => 'sendbox.sendbox',
				'title'        => $this->language->get('text_description'),
				'cost'         => $sendbox_quotes,
				//'tax_class_id' => $this->config->get('shipping_flat_tax_class_id'),
				//'text'         => $this->currency->format($this->tax->calculate($this->config->get('shipping_flat_cost'), $this->config->get('shipping_flat_tax_class_id'), $this->config->get('config_tax')), $this->session->data['currency'])
			   'text' => $this->currency->format($sendbox_quotes, $this->session->data['currency'])
			);

			$method_data = array(
				
				'code'       => 'sendbox',
				'title'      => $this->language->get('text_title'),
				'quote'      => $quote_data,
				'sort_order' => $this->config->get('shipping_sendbox_sort_order'),
				'error'      => false
			);
		}
		
		return $method_data;
	} 
	public function checkauth(){
		$sendbox_obj = new Sendbox_Shipping_API();
		$data = $this->get_data()->row;
		$auth_header = $data['auth_token'];
		$type = "application/json";

		$request_headers = array(
			"Content-Type: " .$type,
			"Authorization: " .$auth_header,
		); 

		$profile_url = $sendbox_obj->get_sendbox_api_url('profile');
		$profile_res = $sendbox_obj->get_api_response_with_header($profile_url,$request_headers);
		$profile_obj = json_decode($profile_res);
		//var_dump($profile_obj, property_exists($profile_obj, 'title'));

		
		if(isset($profile_obj->title)){
			//make a new request to oauth
		
			$s_url = 'https://live.sendbox.co/oauth/access/access_token/refresh?';
			$app_id = $data["sendbox_app_id"];
			
			$client_secret = $data['sendbox_client_secret'];
			$refresh_token = $data['refresh_token'];
			$url_oauth = $s_url.'app_id='.$app_id.'&client_secret='.$client_secret;
			$type = "application/json";

			$headers = array(
				"Content-Type: " .$type,
				"Refresh-Token: " .$refresh_token,
			); 
			//var_dump($url_oauth);
			$oauth_res = $sendbox_obj->get_api_response_with_header($url_oauth,$headers);
			$oauth_obj = json_decode($oauth_res);
			if(isset($oauth_obj->access_token)){
                $new_auth = $oauth_obj->access_token;
			}
			if(isset($oauth_obj->refresh_token)){
				$new_refresh = $oauth_obj->refresh_token;
			}
			$obj = $this->get_auth("sendbox_app_id", $app_id);
			
			$id = $obj->row['id'];
			$values = "auth_token='".$new_auth."', refresh_token='".$new_refresh."'";
			$this->update_data($id, $values);
			$auth_header = $new_auth;
		}
		else{
			$auth_header = $data['auth_token'];
		}

		return $auth_header;
	}




	private function update_data($id, $values){
        //$id = $this->get_data($key);
        
    //    $query = "UPDATE `".DB_PREFIX."sendbox` SET `sendbox_values` =`".$values."` WHERE `".DB_PREFIX."sendbox`.`id` = ".$id.";";
        $query = "UPDATE " . DB_PREFIX . "sendbox SET " . $values . " WHERE id = ". $id . ";";
       // var_dump($query);
        $exec = $this->db->query($query);
       return $exec;
    } 

//get from db
private function get_data(){
	$query = $this->db->query("SELECT * FROM " . DB_PREFIX ."sendbox ORDER BY ID DESC LIMIT 1;");
	return $query;

}


private function get_auth($key, $value){
	$query = $this->db->query("SELECT * FROM " . DB_PREFIX ."sendbox WHERE ". $key . "='". $value . "' ORDER BY ID DESC LIMIT 1;");
	return $query;

}




}
