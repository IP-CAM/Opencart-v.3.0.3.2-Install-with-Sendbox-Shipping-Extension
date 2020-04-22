<?php
class ControllerExtensionShippingSbship extends Controller{ 
    public function index(){
                // VARS
        $template="extension/shipping/sbship.twig";
       // $this->load->model('kvc/first');	// model class file
        $this->load->language('extension/shipping/sbship'); //language class file

        if (isset($this->session->data['order_id'])) {
            $this->load->model('checkout/order');
            var_dump($data['orderDetails'] = $this->model_checkout_order->getOrder($this->session->data['order_id']));
        }
        
        $this->template = ''.$template.'';
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/shipping/sbship', $data));

    }
}
?>