<?php

class shopCheckoutConfirmation extends shopCheckout
{
    protected $step_id = 'confirmation';

    public function display()
    {

        $settings = wa('shop')->getConfig()->getCheckoutSettings();

        if (!empty($settings['confirmation']['terms']) && waRequest::get('terms')) {
            echo $settings['confirmation']['terms'];
            exit;
        }

        $cart = new shopCart();
        $items = $cart->items(false);

        $subtotal = $cart->total(false);
        $order = array('contact' => $this->getContact(), 'total' => $subtotal, 'items' => $items);
        $order['discount'] = shopDiscounts::calculate($order);

        $contact = $this->getContact();

        $view = wa()->getView();
        if (!$contact) {
            $view->assign('error', _w('Not enough data in the contact information to place the order.'));
            return;
        }

        $shipping_address = $contact->getFirst('address.shipping');
        if (!$shipping_address) {
            $shipping_address = array('data' => array(), 'value' => '');
        }
        $billing_address = $contact->getFirst('address.billing');
        if (!$billing_address) {
            $billing_address = array('data' => array(), 'value' => '');
        }

        $discount_rate = $subtotal ? ($order['discount'] / $subtotal) : 0;
        $taxes = shopTaxes::apply($items, array('shipping' => $shipping_address['data'],
            'billing' => $billing_address['data'], 'discount_rate' => $discount_rate));

        $tax = 0;
        $tax_included = 0;
        foreach ($taxes as $t) {
            if (isset($t['sum'])) {
                $tax += $t['sum'];
            }
            if (isset($t['sum_included'])) {
                $tax_included += $t['sum_included'];
            }
        }

        if (!isset($order['shipping'])) {
            $shipping_step = new shopCheckoutShipping();
            $rate = $shipping_step->getRate();
            if ($rate) {
                $order['shipping'] = $rate['rate'];
            } else {
                $order['shipping'] = 0;
            }
        }


        $params = array();
        if ($shipping = $this->getSessionData('shipping')) {
            $params['shipping_id'] = $shipping['id'];
            $params['shipping_rate_id'] = $shipping['rate_id'];
            $params['shipping_name'] = $shipping['name'];
        }

        if ($payment_id = $this->getSessionData('payment')) {
            $params['payment_id'] = $payment_id;
            $plugin_model = new shopPluginModel();
            $plugin_info = $plugin_model->getById($payment_id);
            $params['payment_name'] = $plugin_info['name'];
            $params['payment_plugin'] = $plugin_info['plugin'];
        }

        $view->assign(array(
            'params' => $params,
            'contact' => $contact,
            'items' => $items,
            'shipping' => $order['shipping'],
            'discount' => $order['discount'],
            'total' => $subtotal - $order['discount'] + $order['shipping'] + $tax,
            'tax' => $tax_included + $tax,
            'subtotal' => $subtotal,
            'shipping_address' => $shipping_address,
            'billing_address' => !empty($settings['contactinfo']['fields']['address.billing']) ? $billing_address : false,
            'terms' => !empty($settings['confirmation']['terms']) ? $settings['confirmation']['terms'] : false
        ));
    }


    public function execute()
    {
        if ($comment = waRequest::post('comment')) {
            $this->setSessionData('comment', $comment);
        }
        $settings = wa('shop')->getConfig()->getCheckoutSettings();
        if (!empty($settings['confirmation']['terms']) && !waRequest::post('terms')) {
            return false;
        }
        return true;
    }

    public function getOptions($config)
    {
        $terms = include(wa('shop')->getConfig()->getAppPath('lib/config/data/terms.php'));
        $locale = wa()->getLocale();
        if (!isset($terms[$locale])) {
            $locale = 'en_US';
        }
        return '<div class="field">
                <div class="name">'._w('Terms of service').'<br><span class="hint">HTML</span></div>
                <div class="value">
                    <textarea id="confirmation-terms" name="config[terms]">'.(!empty($config['terms']) ? $config['terms'] : '').'</textarea>
                </div>
                <div class="value">
                    <p class="hint">'._w('If you want your customers to be prompted to read and agree to your company’s terms of service, refund and privacy policies or any other legal information during the checkout, enter the text to the field above. A checkbox to agree and a link to read this legal information will be shown on the Confirmation checkout step.').'
                    <a id="confirmation-generate-terms" href="#" class="inline-link"><b><i>'._w('Generate sample policy').'</i></b></a></p>
                    <p id="confirmation-terms-red" class="red small"'.(!empty($config['terms']) ? '' : ' style="display:none"').'>'._w('For Terms of service to work, make sure you updated <strong>checkout.confirmation.html</strong> template file from the original design theme (either Default or CUSTOM). In case there is no checkbox to agree to the Terms of service in the template file along with the terms legal text entered in the backend, order checkout may stop accepting orders due to the lack of a checkbox user could click, and an error message output. The original checkout.confirmation.html template file contains both checkbox and error output text. Please make sure to update it.').'</p>
                </div>
                <div style="display:none" id="confirmation-terms-sample">'.$terms[$locale].'</div>
                <script>
                    $("#confirmation-generate-terms").click(function () {
                        var t = $("#confirmation-terms");
                        if (!t.val().length || confirm("'._w('Your current terms of service content will be erased. Are you sure?').'")) {
                            t.val($("#confirmation-terms-sample").html());
                            $("#confirmation-terms-red").show();
                        }
                        return false;
                    });
                    $("#confirmation-terms").keyup(function () {
                        if ($(this).val().length) {
                            $("#confirmation-terms-red").show();
                        } else {
                            $("#confirmation-terms-red").hide();
                        }
                    });
                </script>
            </div>';
    }

}