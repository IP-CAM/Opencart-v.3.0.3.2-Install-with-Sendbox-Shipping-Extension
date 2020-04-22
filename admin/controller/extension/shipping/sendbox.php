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
            $url = 'https://api.sendbox.ng/v1/merchant/shipments/delivery_quote';
        } elseif ('countries' == $url_type) {
            $url = 'https://api.sendbox.co/auth/countries?page_by={' . '"per_page"' . ':264}';
        }
        elseif('city' == $url_type){
            $url = 'https://api.sendbox.co/auth/cities?page_by=&filter_by={"state_code":"LOS"}';
        }
         elseif ('states' == $url_type) {
            $url = 'https://api.sendbox.co/auth/states?page_by={' . '"per_page"' . ':264}&filter_by={'.'"country_code"'.':"NG"'.'}';
        } elseif ('shipments' == $url_type) {
            $url = 'https://api.sendbox.ng/v1/merchant/shipments';
        } elseif ('item_type' == $url_type) {
            $url = 'https://api.sendbox.ng/v1/item_types';
        } elseif ('profile' == $url_type) {
            $url = 'https://api.sendbox.co/oauth/profile';
        }else {
            $url = '';
        }
        return $url;
    }

}

class ControllerExtensionShippingSendbox extends Controller { 
     
    private $error = array();

    public function index()
    { 
        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "sendbox (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            
            sendbox_app_id VARCHAR(225),
            sendbox_client_secret VARCHAR(225),
            sendbox_username VARCHAR(225),
            sendbox_email VARCHAR(225),
            sendbox_phone VARCHAR(225),
            sendbox_state VARCHAR(225),
            sendbox_city VARCHAR(225),
            sendbox_rates VARCHAR(225),
            sendbox_extra_fees VARCHAR(225),
            sendbox_status VARCHAR(225),
            sendbox_pickup_types VARCHAR(225),
            sendbox_geo_zone VARCHAR(225),
            refresh_token TEXT(1000),
            auth_token TEXT(2000),
            connected BOOLEAN
             ) ENGINE = INNODB;");
        $static_url = HTTP_SERVER."controller/extension/shipping/sendbox-webhook.php";
        $red_url = 'extension/shipping/sendbox';
        $server_obj = $_SERVER;
        $connected = false;
        if($this->request->server['REQUEST_METHOD'] == 'GET'){
            // $urlObj = $_GET;
            // if(isset($urlObj)){
            //     $app_id =preg_replace('/\s/','',$urlObj['app_id']);
            //     $client_secret = preg_replace('/\s/','', $urlObj['client_secret']); 
            //     if ($app_id !== '' && $client_secret !== ''){
            //         $obj = $this->get_data("sendbox_app_id", $app_id);
            //         $columns = array('sendbox_app_id', 'sendbox_client_secret');
    
            //         $values = array($app_id, $client_secret);
            //         if (empty($obj->row)){
            //             $this->save_data($columns, $values);
            //         }
            //         else{
            //             $id = $obj->row['id'];
            //             $values = "sendbox_app_id='". $app_id ."', sendbox_client_secret='".$client_secret . "'";
            //             $this->update_data($id, $values);
            //         };

            //         }
            // }

 
            $show_connect_box = False;
            $sendbox_url = null;
            $connected = False;
            $obj = $this->display_data()->row;
            if (!empty($obj)){
                if ($obj["sendbox_app_id"] && $obj["sendbox_client_secret"]){
                    
                    $app_id = $obj["sendbox_app_id"];
                    $client_secret = $obj["sendbox_client_secret"];
                    //var_dump($app_id, $client_secret);
                    $show_connect_box  = True;
                    $scopes= "profile"; 
                    $user_token = $server_obj["QUERY_STRING"];
                    parse_str($user_token, $output);
                    $token = $output['user_token'];
                    $_url = $server_obj["PHP_SELF"];
                    $domain = $server_obj["HTTP_HOST"];
                    $domain_name = $domain.$_url;
            
                    $http_scheme = $server_obj['HTTPS'];
                    $http_sch = "http://";
                    if ($http_scheme){
                        $http_sch = "https://";
                    }
            
                    $route = $output['route'];
                  
                    $state_params =  $http_sch.$domain_name.'?route='.$route.'&user_token='.$token.'&app_id='.$app_id.'&client_secret'.$client_secret;
                     
                     $new_state_params = str_replace('&', '$', $state_params);
                     
                    $sendbox_url = 'https://api.sendbox.co/oauth/access?app_id='.$app_id.'&scopes='.$scopes.'&redirect_url='.$static_url.'&state='.$new_state_params;

                    $data['sendbox_username'] = $obj["sendbox_username"];
                    $data['sendbox_email'] = $obj["sendbox_email"];
                    $data['sendbox_phone'] = $obj["sendbox_phone"];
                    $data['sendbox_city'] =$obj["sendbox_city"];
                    $connected = $obj["connected"];
                    $data['sendbox_state_select'] = $obj['sendbox_state']; 
        
    
                }
            }
            $data['show_connect_box'] = $show_connect_box;
            $data["sendbox_url"] = $sendbox_url;
            //var_dump($sendbox_url, $show_connect_box);
        } 

        //get present url
       /*  $url_obj = $_SERVER;
		$query = $url_obj['QUERY_STRING'];
		
        $act_url = HTTP_SERVER.'index.php?'.$query;
        
        var_dump($act_url); */

        //$static_url = "http://kidsthatcode.com.ng/webhook.php";
        $sendbox_obj = new Sendbox_Shipping_API();

        $this->load->language('extension/shipping/sendbox');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting'); 

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

            $this->model_setting_setting->editSetting('shipping_sendbox', $this->request->post);
            $obj = $this->display_data()->row;
            $app_id = null;
            $id = null;
            if (!empty($obj)){
                if ($obj["sendbox_app_id"] && $obj["sendbox_client_secret"]){
                    $app_id = $obj["sendbox_app_id"];
                    $id = $obj["id"];
                }
                else{
                    $id = $obj["id"];
                    $this->delete_data($id);
                    $id = null;
                }
            }

            if (isset($this->request->post['app_id']) && isset($this->request->post['client_secret'])){
                $app_id = trim($this->request->post['app_id'], " ");
                $client_secret = trim($this->request->post['client_secret'], " ");
                $columns = array('sendbox_app_id', "sendbox_client_secret");
                $values = array($app_id,  $client_secret);
                if ($id){
                    $this->delete_data($id);
                    $id = null;
                }
                $this->save_data($columns, $values);               
                 //after connecting, now get code and make a new url to get authtoke        

            }
            
            if ($app_id && $id){
                
                $values = "";
                if (isset($this->request->post['sendbox_username'])){
                    $sendbox_username = $this->request->post['sendbox_username'];
                    $values = $values . "sendbox_username='". $sendbox_username ."',"; 
                }
                if (isset($this->request->post['sendbox_phone'])){
                    $sendbox_phone = $this->request->post['sendbox_phone'];
                    $values = $values . "sendbox_phone='". $sendbox_phone ."',"; 
                }
                if (isset($this->request->post['sendbox_email'])){
                    $sendbox_email = $this->request->post['sendbox_email'];
                    $values = $values . "sendbox_email='". $sendbox_email ."',"; 
                }
                if (isset($this->request->post['sendbox_city_select'])){
                    $sendbox_city = $this->request->post['sendbox_city_select'];
                    $values = $values . "sendbox_city='". $sendbox_city ."',"; 
                }
                if (isset($this->request->post['sendbox_state_select'])){
                    $sendbox_state = $this->request->post['sendbox_state_select'];
                    $values = $values . "sendbox_state='". $sendbox_state ."',"; 
                }
                if (isset($this->request->post['sendbox_pickup_types'])){
                    $sendbox_pickup_types = $this->request->post['sendbox_pickup_types'];
                    $values = $values . "sendbox_pickup_types='". $sendbox_pickup_types ."',"; 
                }
                $values = rtrim($values, ',');
                $this->update_data($id, $values);

                $red_url = "marketplace/extension";
            }
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link($red_url, 'user_token=' . $this->session->data['user_token']. '&type=shipping', true));

        }			
		
        //displaying the values back to the user

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['keys'])) {
            $data['error_keys'] = $this->error['keys'];
        } else {
            $data['error_keys'] = '';
        } 

        //load the breadcrumbs

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_shipping'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'].'&type=shipping', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/shipping/sendbox', 'user_token=' . $this->session->data['user_token'], true)
        ); 

        $data['action'] = $this->url->link('extension/shipping/sendbox', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true);
        $data['sendbox_status'] = $this->config->get('shipping_sendbox_status');
        $data['shipping_sendbox_geo_zone_id'] = $this->config->get('shipping_sendbox_geo_zone_id');
        
        if (isset($this->request->post['shipping_sendbox_geo_zone_id'])) {
            $data['shipping_sendbox_geo_zone_id'] = $this->request->post['shipping_sendbox_geo_zone_id'];
        }

        if (isset($this->request->post['shipping_sendbox_status'])) {
            $data['shipping_sendbox_status'] = $this->request->post['shipping_sendbox_status'];
        }

        $display = $this->display_data()->row; 
        // $data['sendbox_username'] = $display['sendbox_username'];
        // $data['sendbox_phone'] = $display['sendbox_phone'];
        // $data['sendbox_email'] = $display['sendbox_email'];
        // $data['sendbox_city'] = $display['sendbox_city'];
        // $data['sendbox_state'] = $display['sendbox_state'];


                        
        $get_code = "";
        $query = parse_str($server_obj["QUERY_STRING"], $get_code);
        $get_code = $query.$_GET["code"];
        if ($get_code){
            $s_url = 'https://api.sendbox.co/oauth/access/access_token?';
            $url_oauth = $s_url.'app_id='.$app_id.'&redirect_url='.$static_url.'&client_secret='.$client_secret.'&code='.$get_code.'';
            $oauth_res = $sendbox_obj->get_api_response_by_curl($url_oauth);
            $oauth_obj = json_decode($oauth_res);
            //print_r($oauth_obj->refresh_token);
            $oauth_token = ""; 
            $refresh_token = ""; 
            
            if(isset($oauth_obj->access_token)){
                $oauth_token = $oauth_obj->access_token;
            }
            if(isset($oauth_obj->refresh_token)){
                $refresh_token = $oauth_obj->refresh_token;
            }
            
           // var_dump($app_id);
            if($oauth_token  != null && $refresh_token !=null){
                $obj = $this->get_data("sendbox_app_id", $app_id);
                $id = $obj->row['id'];
                //var_dump($id);
                //var_dump($obj);
    
                $values = "auth_token='". $oauth_token ."', refresh_token='".$refresh_token . "', connected=" . true . "";
                $this->update_data($id, $values);
                $connected = true;
            }
    
            //var_dump($oauth_obj);
            
            $oauth = "";
            if(isset($oauth_obj->access_token)){
                 $oauth = $oauth_obj->access_token;
            }
    
            //FINALLY CALL PROFILE FOR PROFILE DETAILS
    
            if( $oauth != null){
                $auth_header = $oauth;
                $type = "application/json";
        
                $request_headers = array(
                    "Content-Type: " .$type,
                    "Authorization: " .$auth_header, 
                );
                $profile_url = $sendbox_obj->get_sendbox_api_url('profile');
                $profile_res = $sendbox_obj->get_api_response_with_header($profile_url,$request_headers);
        
        
                $profile_obj = json_decode($profile_res);
                $sendbox_username = $profile_obj->name;
                $sendbox_phone =$profile_obj->phone;
                $sendbox_email = $profile_obj->email;
                //save into the db
                $obj = $this->get_data("sendbox_app_id", $app_id);
                $id = $obj->row['id'];
                $values = "sendbox_username='". $sendbox_username ."', sendbox_phone='".$sendbox_phone . "', sendbox_email='" .$sendbox_email."'";
                $data["sendbox_city_select"] = $obj->row["sendbox_city"];
                $this->update_data($id, $values);
                
                $data['sendbox_username'] = $sendbox_username;
                $data['sendbox_email'] = $sendbox_email;
                $data['sendbox_phone'] = $sendbox_phone;    

            }    
        } 
            $data['sendbox_country'] = "Nigeria";

            if(isset($this->request->post['sendbox_username'])){
                $data['sendbox_username'] = $this->request->post['sendbox_username'];
            }
        
            if(isset($this->request->post['sendbox_phone'])){
                $data['sendbox_phone'] = $this->request->post['sendbox_phone'];
            }
    
            if(isset($this->request->post['sendbox_email'])){
                $data['sendbox_email'] = $this->request->post['sendbox_email'];
            }



        //getstates 
        $data["connected"] = $connected;
        $state_url = $sendbox_obj->get_sendbox_api_url('states');
        $nigeria_states = $sendbox_obj->get_api_response_by_curl($state_url);
        $sendbox_state = json_decode($nigeria_states)->results;

          $data['sendbox_state'] =$sendbox_state;
         if(isset($this->request->post['sendbox_state_select'])){
            $data['sendbox_state_select'] = $this->request->post['sendbox_state_select'];
        }

        //get cities

        $city_url = $sendbox_obj->get_sendbox_api_url('city');
        $nigeria_cities = $sendbox_obj->get_api_response_by_curl($city_url);
        $sendbox_city = json_decode($nigeria_cities)->results;
        // var_dump($sendbox_city);
        
        //$nigeria_states = $this->config->get('sendbox_state');
       
       // $data['sendbox_city'] =$sendbox_city;
        if(isset($this->request->post['sendbox_city_select'])){
            $data['sendbox_city_select'] = $this->request->post['sendbox_city_select'];
        }



        $this->load->model('localisation/zone');
		$zone_info = $this->model_localisation_zone->getZone($this->config->get('config_zone_id'));

        //getname,phone and email..to get this, we make a call kinda a long process but we move
        //get the add_id and client secret then build a sendbox ur        

        if(isset($this->request->post['sendbox_store_address'])){
            $data['sendbox_store_address'] = $this->request->post['sendbox_store_address'];
        }else{
            $data['sendbox_store_address'] =  $this->config->get('config_address');
        }

        if (isset($this->request->post['shipping_sendbox_sort_order'])) {
			$data['shipping_sendbox_sort_order'] = $this->request->post['shipping_sendbox_sort_order'];
		} else {
			$data['shipping_sendbox_sort_order'] = $this->config->get('shipping_sendbox_sort_order');
        }

         if(isset($this->request->post['sendbox_rates'])){
            $data['senbox_rates'] = $this->request->post['sendbox_rates'];
        }else{
            $data['sendbox_rates'] = array('minimum', 'maximum');
        }


        //$data['sendbox_pickup_types'] =$sendbox_pickup_types;

        if(isset($this->request->post['sendbox_pickup_types'])){

            $data['senbox_pickup_types'] = $this->request->post['sendbox_pickup_types'];
        }else{
            $data['sendbox_pickup_types'] = array('pickup', 'drop_off');
        
        } 

        if(isset($this->request->post['sendbox_country'])){
            $data['sendbox_country'] = $this->request->post['sendbox_country'];
        }else{
            $data['sendbox_country'] = "Nigeria";
        }
        // $data['app_id'] = $app_id;
       
        $data['static_url'] = $static_url;
        
        //load opencart zones 
        $this->load->model('localisation/geo_zone');
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();


        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
       
        //load everything into view.
        $this->response->setOutput($this->load->view('extension/shipping/sendbox', $data));
    }


    

    //validate form

    private function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/shipping/sendbox')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    //save into db
    
    private function save_data($columns, $values){ 

         $query = "INSERT INTO " . DB_PREFIX . "sendbox (" . implode(" ,", $columns). ") VALUES ('" . implode("','", $values) . "');";
         //var_dump($query);
        $exec = $this->db->query($query);
       
        return $exec; 
        
    }

    //update the db
    private function update_data($id, $values){
        //$id = $this->get_data($key);
        
    //    $query = "UPDATE `".DB_PREFIX."sendbox` SET `sendbox_values` =`".$values."` WHERE `".DB_PREFIX."sendbox`.`id` = ".$id.";";
        $query = "UPDATE " . DB_PREFIX . "sendbox SET " . $values . " WHERE id = ". $id . ";";
        $exec = $this->db->query($query);
       return $exec;
    }

//get from db
    private function get_data($key, $value){
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX ."sendbox ORDER BY ID DESC LIMIT 1;");
        return $query;
    }

    //display data
    private function display_data(){
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX ."sendbox ORDER BY ID DESC LIMIT 1;");
        return $query;

    }
    
    private function delete_data($id){
        $query = $this->db->query("DELETE FROM " . DB_PREFIX ."sendbox WHERE id = ". $id . ";");
        return $query;

	}

}


