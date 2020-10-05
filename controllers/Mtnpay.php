<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

defined('BASEPATH') or exit('No direct script access allowed');

class Mtnpay extends App_Controller
{
    public function payment($invoice_id, $invoice_hash)
    {
        check_invoice_restrictions($invoice_id, $invoice_hash);

        $this->load->model('invoices_model');
        $invoice = $this->invoices_model->get($invoice_id);
        load_client_language($invoice->clientid);

        $data  = $invoice;
        $data->total = $this->session->userdata('mtnpay_total');
        $data->description = $this->mtnpay_gateway->description($invoice_id);
        $data->api_user = $this->mtnpay_gateway->api_user();
        $data->widget_link = $this->mtnpay_gateway->widget_link();
        echo $this->get_html($data);
    }

    /**
     * Get payment gateway html view
     *
     * @param  array|object  $data
     *
     * @return string
     */
    protected function get_html($data)
    {
        ob_start(); ?>
        <?php echo payment_gateway_head() ?>
        <script type="text/javascript" src="<?= $data->widget_link ?>"></script>
        <style>
            .mright25 {
                margin-right: 25px !important;
            }
        </style>

        <body class="gateway-paddle-checkout">
            <div class="container">
                <div class="col-md-6 col-md-offset-3 mtop30">
                    <div class="mbot30 text-center">
                        <?php echo payment_gateway_logo(); ?>
                    </div>
                    <div class="row">
                        <div class="panel_s">
                            <div class="panel-body">
                                <h4 class="no-margin">
                                    <?php echo _l('payment_for_invoice'); ?> <a href="<?php echo site_url('invoice/' . $data->id . '/' . $data->hash); ?>"><?php echo format_invoice_number($data->id); ?></a>
                                </h4>
                                <hr />
                                <h4 class="mbot20">
                                    <?php echo _l('payment_total', app_format_money($data->total, $data->currency_name)); ?>
                                </h4>
                                <div class="mobile-money-qr-payment" data-api-user-id="<?= $data->api_user ?>" data-amount="<?= $data->total ?>" data-currency="<?= $data->currency_name ?>" data-external-id="<?= $data->id . '-' . $data->hash ?>"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php echo payment_gateway_scripts(); ?>
            <script type="text/javascript">
                window.addEventListener('mobile-money-qr-payment-created', function(event) {
                    console.log('Invoice:', event.detail);
                });
                window.addEventListener('mobile-money-qr-payment-canceled', function(event) {
                    console.log('Invoice:', event.detail);
                });
                window.addEventListener('mobile-money-qr-payment-failed', function(event) {
                    console.log('Invoice:', event.detail);
                });
                window.addEventListener('mobile-money-qr-payment-successful', function(event) {
                    console.log('Invoice:', event.detail);
                    var txref = event.detail.referenceId;
                    location.replace("<?php echo html_escape(site_url('mtnpay/complete?invoiceid=' . $data['invoice']->id . '&hash=' . $data['invoice']->hash)); ?>" + "&txref=" + txref)
                });
            </script>
            <?php echo payment_gateway_footer(); ?>
    <?php
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }

    public function complete($invoice_id, $invoice_hash, $token)
    {
        check_invoice_restrictions($invoice_id, $invoice_hash);
        log_activity('new momopay payment attempt; reference-id;' . $token);
        $this->load->model('invoices_model');
        $invoice = $this->invoices_model->get($invoice_id);
        load_client_language($invoice->clientid);
        $payment = $this->mtnpay_gateway->get_payment($token);
        if (is_object($payment) && $payment->status === "SUCCESSFUL") {
            $this->mtnpay_gateway->record_payment($payment->amount, $invoice_id, $token);
        } elseif (is_object($payment) && $payment->status === "PENDING") {
            set_alert('primary', 'Your transaction is being processed, you will get an email notfication shortly if successful');
            log_activity('Momopay Transaction With Status PENDING: ' . var_export($payment, true));
        } else {
            set_alert('danger', 'Your transaction was unsuccessful');
        }
        $this->session->unset_userdata('paddle_total');
        redirect(site_url('invoice/' . $invoice_id . '/' . $invoice_hash));
    }
}
