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

  //make a get request using curl to sendbox

  public function get_api_response_by_curl($url)
  {
	  $handle = curl_init();
	  curl_setopt($handle, CURLOPT_URL, $url);
	  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
	  $output = curl_exec($handle);
	  curl_close($handle);
	  return $output;
  }
  //make request to sendbox with header

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


  //all sendbox endpoints
  public function get_sendbox_api_url($url_type)
  {
	  if ('delivery_quote' == $url_type) {
		  $url = 'https://live.sendbox.co/shipping/shipment_delivery_quote';
	  } elseif ('countries' == $url_type) {
		  $url = 'https://api.sendbox.co/auth/countries?page_by={' . '"per_page"' . ':264}';
	  } elseif ('states' == $url_type) {
		  $url = 'https://api.sendbox.co/auth/states';
	  } elseif ('shipments' == $url_type) {
		  $url = 'https://live.sendbox.co/shipping/shipments';
	  } elseif('payment' == $url_type){
      $url = 'https://live.sendbox.co/payments/profile';
	  }
	  elseif ('item_type' == $url_type) {
		  $url = 'https://api.sendbox.ng/v1/item_types';
	  } elseif ('profile' == $url_type) {
		  $url = 'https://live.sendbox.co/oauth/profile';
	  }else {
		  $url = '';
	  }
	  return $url;
  }



}

class sendbox_ControllerSaleOrder extends ControllerSaleOrder{ 

	public function getProduct($product_id) {
		$query = $this->db->query("SELECT DISTINCT *, pd.name AS name, p.image, m.name AS manufacturer, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special, (SELECT points FROM " . DB_PREFIX . "product_reward pr WHERE pr.product_id = p.product_id AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "') AS reward, (SELECT ss.name FROM " . DB_PREFIX . "stock_status ss WHERE ss.stock_status_id = p.stock_status_id AND ss.language_id = '" . (int)$this->config->get('config_language_id') . "') AS stock_status, (SELECT wcd.unit FROM " . DB_PREFIX . "weight_class_description wcd WHERE p.weight_class_id = wcd.weight_class_id AND wcd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS weight_class, (SELECT lcd.unit FROM " . DB_PREFIX . "length_class_description lcd WHERE p.length_class_id = lcd.length_class_id AND lcd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS length_class, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating, (SELECT COUNT(*) AS total FROM " . DB_PREFIX . "review r2 WHERE r2.product_id = p.product_id AND r2.status = '1' GROUP BY r2.product_id) AS reviews, p.sort_order FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id) WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");
		//var_dump($product_id);
		if ($query->num_rows) {
			//var_dump($query->row['weight']);
			return $query->row['weight'];
		}
	}

	public function wallet_balance(){
		$api_key = $this->checkauth();
		$sendbox_obj = new Sendbox_Shipping_API();
		$auth_header = $api_key;
		$type = "application/json";

		$request_headers = array(
			"Content-Type: " .$type,
			"Authorization: " .$auth_header,
		); 
		$payment_url = $sendbox_obj->get_sendbox_api_url('payment');
		$payment_res = $sendbox_obj->get_api_response_with_header($payment_url,$request_headers);
		$payment_obj = json_decode($payment_res);
		 $wallet_balance = $payment_obj->funds;
		 return $wallet_balance;
		
	}

	public function get_quotes(){
		$api_key = $this->checkauth();
	$this->load->language('sale/order');
		$this->load->model('sale/order');

		//make a call to get quotes
		$this->load->model('localisation/country');
		$country_info = $this->model_localisation_country->getCountry($this->config->get('config_country_id'));	
		
		$this->load->model('localisation/zone');
		$zone_info = $this->model_localisation_zone->getZone($this->config->get('config_zone_id'));
		$order_id = $this->request->get['order_id'];
		$order_details = $this->model_sale_order->getOrder($order_id);
		$products = $this->model_sale_order->getOrderProducts($order_id);

		$weight = 0;
		foreach ($products as $product) {
			$item_name = $product['name'];
			$total_value = $product['total'];
			$quantity = ($product['quantity']);

			$mee_weight = $this->getProduct($product["product_id"]);
			
			$weight+=$mee_weight * $quantity;
			//var_dump($weight);
		}
		
		//get quotes first to get coirour_id
		$origin_country = $country_info['name'];
		$origin_state = $zone_info['name']; 
		//$origin_city = $this->config->
		$destination_country = $order_details['shipping_country'];
		$destination_state = $order_details['shipping_zone'];
		$destination_city = $order_details['shipping_city'];
		//var_dump($order_details);
		$sendbox_obj = new Sendbox_Shipping_API();
		$url = $sendbox_obj->get_sendbox_api_url('delivery_quote');
		$payload_data = new stdClass(); 
		$payload_data->destination_state= $destination_state;
		$payload_data->destination_country = $destination_country;
		$payload_data->origin_country = $origin_country;
		$payload_data->destination_city = $destination_city;
		$payload_data->origin_state = $origin_state;
		$payload_data->weight = $weight;
		$data = $this->get_data()->row;
		$payload_data->origin_city = $data["sendbox_city"];
		//$api_key = $data['auth_token'];

		//var_dump($api_key);
		//$api_key = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VybmFtZSI6ImpoYWFzdHJ1cDIxQGdtYWlsLmNvbSIsImF1ZCI6WyJzZW5kYm94LmRpc2NvdmVyeSIsInNlbmRib3gucGF5bWVudHMiLCJzZW5kYm94LnJldmlld3MiLCJzZW5kYm94LmF1dGgiLCJzZW5kYm94LnNoaXBwaW5nIiwic2VuZGJveC5jb21tcyIsInNlbmRib3guZXNjcm93Il0sImlzcyI6InNlbmRib3guYXV0aCIsInBob3RvIjpudWxsLCJwcm9maWxlcyI6W3siZG9tYWluIjoic2VuZGJveC5zaGlwcGluZyIsImlkIjoiMTAzODMiLCJwZXJtIjpbInVzZXJfY2FuX3JlYWQiLCJ1c2VyX2Nhbl93cml0ZSIsInVzZXJfY2FuX2RlbGV0ZSJdLCJmcmFuY2hpc2VfaWQiOm51bGx9LHsiZG9tYWluIjoic2VuZGJveC5wYXltZW50cyIsImlkIjoiNWQwZDZkZTg3Y2IzMWMwMDAxYzlkMzJkIiwicGVybSI6WyJ1c2VyX2Nhbl9yZWFkIiwidXNlcl9jYW5fd3JpdGUiLCJ1c2VyX2Nhbl9kZWxldGUiXSwiZnJhbmNoaXNlX2lkIjpudWxsfSx7ImRvbWFpbiI6InNlbmRib3guZXNjcm93IiwiaWQiOiI1ZDBkNmRlOGEwNjZiZDAwMDE4YjQwNTIiLCJwZXJtIjpbInVzZXJfY2FuX3JlYWQiLCJ1c2VyX2Nhbl93cml0ZSIsInVzZXJfY2FuX2RlbGV0ZSJdLCJmcmFuY2hpc2VfaWQiOm51bGx9LHsiZG9tYWluIjoic2VuZGJveC5yZXZpZXdzIiwiaWQiOiI1ZDBkNmRlOGE1OGFjZDAwMDExZTEyMzciLCJwZXJtIjpbInVzZXJfY2FuX3JlYWQiLCJ1c2VyX2Nhbl93cml0ZSIsInVzZXJfY2FuX2RlbGV0ZSJdLCJmcmFuY2hpc2VfaWQiOm51bGx9LHsiZG9tYWluIjoic2VuZGJveC5jb21tcyIsImlkIjoiNWRhNWU2MzIxNWI1ZmMwMDAxZGU5YTE5IiwicGVybSI6WyJ1c2VyX2Nhbl9yZWFkIiwidXNlcl9jYW5fd3JpdGUiLCJ1c2VyX2Nhbl9kZWxldGUiXSwiZnJhbmNoaXNlX2lkIjpudWxsfSx7ImRvbWFpbiI6InNlbmRib3guZGlzY292ZXJ5IiwiaWQiOiI1ZGU0ZTkyMmEwMjY0ZjAwMGU4ZDE1ODkiLCJwZXJtIjpbInVzZXJfY2FuX3JlYWQiLCJ1c2VyX2Nhbl93cml0ZSIsInVzZXJfY2FuX2RlbGV0ZSJdLCJmcmFuY2hpc2VfaWQiOm51bGx9XSwibmFtZSI6IkFkZWpva2UgIiwicGhvbmUiOiIrMjM0ODEwMjk3NzE4MSIsImV4cCI6MTU4MjEzMTA4OSwiZW1haWwiOiJqaGFhc3RydXAyMUBnbWFpbC5jb20iLCJ1aWQiOiI1ZDBkNmRlODZkNWVlYTAwMDExMTQ2NjkifQ.Ld9YWopFbfes-E6cCXuf1wp7m3io3F9tNO-JdvO3Bww";
		
		//var_dump($payload_data);
		$quotes_data = json_encode($payload_data);
		//var_dump($quotes_data);

		$sendbox_quotes_res = $sendbox_obj->post_on_api_by_curl($url, $quotes_data, $api_key);
		$sendbox_quotes_obj = json_decode($sendbox_quotes_res);
		$quotes_rates = $sendbox_quotes_obj->rates;
		//var_dump($sendbox_quotes_obj);
		return $quotes_rates;
	} 

	public function get_present_url(){
		$url_obj = $_SERVER;
		$query = $url_obj['QUERY_STRING'];
		
		$act_url = HTTP_SERVER.'index.php?'.$query;
		
		return $act_url;
	}

	

	
	public function preRender( $template_buffer, $template_name,&$data ) {
		if ($template_name != 'sale/order_info.twig') {
		   return parent::preRender( $template_buffer, $template_name, $data );

		}
		if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
			
			if (isset($this->request->post['rate'])) {
				
				
				//var_dump($this->request->post['rate']);
				// var_dump("['selected_courier_id']");
				// var_dump($_POST['selected_courier_id']);

				$shipment_obj = $this->book_shipment($selected_courier_id=$this->request->post['rate']);
				if (isset($shipment_obj->transaction)){
					$message = "insufficient funds login to your sendbox account and top up your wallet";
                  echo "<script type='text/javascript'>alert('$message');</script>";
				}
				
				if(isset($shipment_obj->code)){
					$story = "Sucssfully booked! your tracking code for this shipment is ".$shipment_obj->code;
					echo "<script type='text/javascript'>alert('$story');</script>";
				}

			}
				}

		 // add additional or modified controller variables
		 $this->load->language('sale/order');
		 $this->load->model('sale/order');

		  // modify the the template, to use the additional or modified variables
		  $this->load->helper( 'modifier' );
		$rates = $this->get_quotes();
		//var_dump($rates);
		//die();
		$option_string = "";
		foreach ($rates as $rates_id => $rates_values){
			$rates_names = $rates_values->name;
			$rates_fee   = $rates_values->fee;
			$rates_id   = $rates_values->courier_id;
			$option_string.='<input name="rate" type ="radio" data-courier-price = '.$rates_fee.' value='.$rates_id.'> '.$rates_names.' <br/> </input>';
		}

       // $this->book_shipment();

		  $search = '<div class="row">';
		  $add = '
		  <div class="col-md-4">
		  <div class="panel panel-default">
          <div class="panel-heading">
            <h3 class="panel-title"><i class="fa fa-truck"></i>Ship with Sendbox</h3>
		  </div>
		  <form name="shipment-form" method="POST" id="shipment-form" action="'.$this->get_present_url().'">
		  <table class="table">
		
            <tbody>
              <tr>
               
                <td> Wallet Balance : ₦'.$this->wallet_balance().'</td>
              </tr>
            
			  <tr>
			  <td> 

			  <div id="rates">
			  <p>Select a courier </p>

			  <label>'.$option_string.'</label>
			</div>
			  </td>
			  </tr>
			  
			  <tr>
			  <td>
              <p id="fee">Fee: ₦ 0.00</p>
			  </td>
			  
			  </tr>
             
			 <tr> 
			 <td>
			 <button class="btn btn-primary sendbox-shipping-add" id="ship-btn"> Request Shipment</button><script src="'.HTTP_SERVER.'/view/javascript/sendbox.js"></script>
			</td>
			</tr>
		
            </tbody>
		  </table>
		  </form>
        </div>
		
		 
		 </div> 
		  ';
			//$this->document->addScript('view/javascript/sendbox/js/sendbox.js');
			
		  $template_buffer = Modifier::modifyStringBuffer( $template_buffer,$search,$add,'after');
				// call parent preRender method
			return parent::preRender( $template_buffer, $template_name, $data );
			
	}


	public function book_shipment($selected_courier_id){
		$api_key = $this->checkauth();
		$this->load->language('sale/order');
		$this->load->model('sale/order');

		$this->load->model('localisation/country');
		$country_info = $this->model_localisation_country->getCountry($this->config->get('config_country_id'));	
		
		$this->load->model('localisation/zone');
		$zone_info = $this->model_localisation_zone->getZone($this->config->get('config_zone_id'));
		$order_id = $this->request->get['order_id'];
		$order_details = $this->model_sale_order->getOrder($order_id);
		$products = $this->model_sale_order->getOrderProducts($order_id);
		//var_dump($products);
		$weight = 0;
	
		foreach ($products as $product) {
			$item_name = $product['name'];
			$total_value = $product['total'];
			$quantity = ($product['quantity']);

			$mee_weight = $this->getProduct($product["product_id"]);
			
			$weight+=$mee_weight * $quantity;
			//var_dump($weight);
		}
		
		//get quotes first to get coirour_id
		$origin_country = $country_info['name'];
		$origin_state = $zone_info['name']; 
		$origin_city =    $this->config->get('config_address');
		$destination_country = $order_details['shipping_country'];
		$destination_state = $order_details['shipping_zone'];
		$data = $this->get_data()->row;
	

		//$data = $this->get_data()->row;
		$origin_name = $data['sendbox_username'];
		$origin_phone = $data['sendbox_phone'];
		$origin_email = $data["sendbox_email"];
		$origin_street = $this->config->get('config_address');
		$destination_name = $order_details['firstname'] ." ".$order_details['lastname'];
		$destination_street = $order_details['shipping_address_1'];
		$destination_phone = $order_details['telephone'];
		$destination_email = $order_details['email'];
		$destination_city = $order_details['shipping_city'];
		$items_lists       = [];
		$items_data = new stdClass();
		$items_data->name             = $item_name;
		$items_data->quantity          = $quantity;
	    $items_data->value             = $total_value;
		$items_data->package_size_code = 'medium';
		$items_data->item_type_code = 'other';
		$items_data->weight = $weight;
		array_push($items_lists, $items_data);
		$items = $items_lists; 
		$date = new DateTime();
		$date->modify('+1 day');
		$pickup_date = $date->format('c');
		 
		//var_dump($items_lists);
		//MAKE CALL TO SHIPMENT
		$sendbox_obj = new Sendbox_Shipping_API();
		$shipment_url = $sendbox_obj->get_sendbox_api_url('shipments');
		$payload_data = new stdClass(); 
		$payload_data->selected_courier_id = $selected_courier_id;
		$payload_data->destination_name = $destination_name;
		$payload_data->destination_state= $destination_state;
		$payload_data->destination_country = $destination_country;
		$payload_data->destination_phone = $destination_phone;
		$payload_data->destination_street = $destination_street;
		$payload_data->destination_city = $destination_city;
		$payload_data->origin_name = $origin_name;
		$payload_data->origin_city = $data["sendbox_city"];
		$payload_data->origin_phone =$origin_phone;
		$payload_data->origin_country = $origin_country;
		$payload_data->origin_state = $origin_state;
		$payload_data->origin_street = $origin_street;
		$payload_data->weight = $weight;
		$payload_data->items = $items;
		$payload_data->payment_option_code   = 'prepaid';
		$payload_data->channel_code = "mobile_web"; 
		$payload_data->pickup_date = $pickup_date;
		$payload_data->deliver_priority_code = 'next_day';
		$payload_data->incoming_option_code = $data['sendbox_pickup_types'];
		
		$shipment_data = json_encode($payload_data);
		//var_dump($shipment_data);
		//var_dump($selected_courier_id);

		$shipment_res = $sendbox_obj->post_on_api_by_curl($shipment_url, $shipment_data, $api_key);
		$shipment_res_obj = json_decode($shipment_res);
		return $shipment_res_obj;
	
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
			var_dump($url_oauth);
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
        //var_dump($query);
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
