<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

defined('BASEPATH') or exit('No direct script access allowed');

class Mtnpay_gateway extends App_gateway
{
    protected $sandboxHost = 'https://ericssondeveloperapi.azure-api.net/';
    protected $productionHost = 'https://ericssondeveloperapi.azure-api.net/';
    protected $target_environment;


    public function __construct()
    {
        /**
         * Call App_gateway __construct function
         */
        parent::__construct();
        /**
         * REQUIRED
         * Gateway unique id
         * The ID must be alpha/alphanumeric
         */
        $this->setId('mtnpay');

        /**
         * REQUIRED
         * Gateway name
         */
        $this->setName('Mtnpay');

        /**
         * Add gateway settings
         */
        $this->setSettings(
            [
                [
                    'name'      => 'mtnpay_subscription_key',
                    'encrypted' => true,
                    'label'     => 'Subscription ket: OCP-APIM-subscritption-key',
                ],
                [
                    'name'      => 'mtnpay_reference_id',
                    'encrypted' => true,
                    'label'     => 'Reference ID',
                ],
                [
                    'name'      => 'mtnpay_api_user',
                    'label'     => 'mtnpay_api_user',
                ],
                [
                    'name'      => 'mtnpay_api_key',
                    'encrypted' => true,
                    'label'     => 'mtnpay_api_key',
                ],
                [
                    'name'          => 'description',
                    'label'         => 'settings_paymentmethod_description',
                    'type'          => 'textarea',
                    'default_value' => 'Payment for Invoice {invoice_number}',
                ],
                [
                    'name'             => 'currencies',
                    'label'            => 'settings_paymentmethod_currencies',
                    'default_value'    => 'USD, EUR, GBP',
                ],
                [
                    'name'          => 'test_mode_enabled',
                    'type'          => 'yes_no',
                    'default_value' => 1,
                    'label'         => 'settings_paymentmethod_testing_mode',
                ],
            ]
        );

        $this->target_environment =  $this->getSetting('test_mode_enabled') == '1' ? 'sandbox' : 'production';
        /**
         * Add notice to generate Api keys
         */
        hooks()->add_action('before_render_payment_gateway_settings', function ()
        {
            echo strtolower(mtnpay_generate_keys());
        });
    }

    /**
     * REQUIRED FUNCTION
     * @param  array $data
     * @return mixed
     */
    public function process_payment($data)
    {
        $this->ci->session->set_userdata([
            'mtnpay_total' => $data['amount']
        ]);
        redirect(site_url('mtnpay/payment/' . $data['invoice']->id . '/' . $data['invoice']->hash));
    }

    public function description($id)
    {
        return str_replace('{invoice_number}', format_invoice_number($id),  $this->getSetting('description'));
    }

    public function subscription_key()
    {
        return $this->decryptSetting('mtnpay_subscription_key');
    }


    public function reference_id()
    {
        return $this->decryptSetting('mtnpay_reference_id');
    }

    public function api_user()
    {
        return $this->getSetting('mtnpay_api_user');
    }

    public function api_key()
    {
        return $this->decryptSetting('mtnpay_api_key');
    }

    public function callback($id, $hash)
    {
        return site_url('mtnpay/gateways/mtnpay_ipn/notify/' . $id . '/' . $hash);
    }

    public function generate_reference_id()
    {
        return mtnpay_generate_keys();
    }

    public function host()
    {
        return $this->getSetting('test_mode_enabled') == '1' ? $this->sandboxHost : $this->productionHost;
    }

    public function widget_link()
    {
        return $this->getSetting('test_mode_enabled') == '1' ?
            'https://widget.northeurope.cloudapp.azure.com:9443/v0.1.0/mobile-money-widget-mtn.js' :
            'https://widget.northeurope.cloudapp.azure.com:9443/v0.1.0/mobile-money-widget-mtn.js';
    }

    public function connect($method, $path, $options)
    {
        $client = new Client();
        try {
            $response = $client->request($method, $this->host() . $path, $options);
            return json_decode($response->getBody());
        } catch (RequestException $e) {
            // TO DO: FIND A WORK AROUNF FOR STAFF ID
            log_activity('PAYMENT ERROR' . $e->getResponse()->getBody());
            return false;
        }
    }

    public function authorization()
    {
        $options = [
            'headers' => [
                'Authorization' => '',
                'Ocp-Apim-Subscription' => $this->subscription_key(),
            ]
        ];
        $path = 'collection/token';
        $token = $this->connect('POST', $path, $options);
        var_dump($token);
        die;
        return 'Basic ' . $token;
    }
    
    /**
     * @return Boolean|float
     */
    public function get_payment($referenceID)
    {
        $options = [
            'headers' => [
                // 'Authorization' => $this->authorization(),
                'X-Target-Environment' => $this->target_environment,
                'Ocp-Apim-Subscription' => $this->subscription_key(),
            ]
        ];

        $path = 'collection/v1_0/requesttopay/' . $referenceID;
        $payment = $this->connect('GET', $path, $options);
        var_dump($payment);
    }

    public function record_payment($amount, $id, $transId)
    {
        $success = $this->addPayment(
            [
                'amount'        => $amount,
                'invoiceid'     => $id,
                'transactionid' => $transId,
                'paymentmethod' => 'MTN',
            ]
        );
        if ($success) {
            log_activity('online_payment_recorded_success');
            set_alert('success', _l('online_payment_recorded_success'));
        } else {
            log_activity('online_payment_recorded_success_fail_database' . var_export($this->input->get(), true));
            set_alert('success', _l('online_payment_recorded_success_fail_database'));
        }
    }
}
