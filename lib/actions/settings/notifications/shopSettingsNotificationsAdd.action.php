<?php
class shopSettingsNotificationsAddAction extends shopSettingsNotificationsAction
{
    public function execute()
    {
        $this->view->assign('events', $this->getEvents());
        $this->view->assign('transports', self::getTransports());
        $this->view->assign('templates', $this->getTemplates());
    }


    public function getTemplates()
    {
        $result = array();

        /* new order email notification template */
        $result['order.create']['subject'] = sprintf( _w('New order %s'), '{$order.id}');
        $result['order.create']['body'] = '<style>
table.table { margin-top: 25px; margin-left: -10px; width: 100%; border-spacing:0; border-collapse:collapse; }
table.table td { padding: 15px 7px 20px; border-top: 1px solid #ddd; border-bottom: 1px solid #ddd; }
table.table td.min-width { width: 1%; }
table.table td p { margin: 0; }
table.table td input.numerical { width: 50px; margin-right: 5px; text-align: right; }
table.table tr.no-border td { border: none; }
table.table tr.thin td { padding-top: 13px; padding-bottom: 0; }
.align-right { text-align: right; }
</style>
        
<h1>'.sprintf( _w('New order %s'), '{$order.id}').'</h1>

<table class="table">
    <tr>
        <th></th>
        <th>'._w('Quantity').'</th>
        <th>'._w('Total').'</th>
    </tr>
    {$subtotal = 0}
    {foreach $order.items as $item}
    <tr>
        <td>
            {$item.name|escape}
            {if !empty($item.download_link)}<a href="{$item.download_link}"><strong>'._w('Download').'</strong></a>{/if}
        </td>
        <td class="align-center">x {$item.quantity}</td>
        <td class="align-center">{wa_currency($item.price * $item.quantity, $order.currency)}</td>
    </tr>
    {$subtotal = $subtotal + $item.price * $item.quantity}
    {/foreach}
    <tr class="no-border thin">
        <td colspan="2" class="align-right">'._w('Subtotal').'</td>
        <td class="align-center">{wa_currency($subtotal, $order.currency)}</td>
    </tr>
    <tr class="no-border thin">
        <td colspan="2" class="align-right">'._w('Discount').'</td>
        <td class="align-center">{wa_currency($order.discount, $order.currency)}</td>
    </tr>
    <tr class="no-border thin">
        <td colspan="2" class="align-right">'._w('Shipping').'</td>
        <td class="align-center">{wa_currency($order.shipping, $order.currency)}</td>
    </tr>
    <tr class="no-border thin">
        <td colspan="2" class="align-right">'._w('Tax').'</td>
        <td class="align-center">{wa_currency($order.tax, $order.currency)}</td>
    </tr>
    <tr class="no-border thin large">
        <td colspan="2" class="align-right"><b>'._w('Total').'</b></td>
        <td class="align-center bold">{wa_currency($order.total, $order.currency)}</td>
    </tr>
</table>

<h3>'._w('Ship to').'</h3>
<p>{$customer->get("name", "html")|escape}<br>
{$shipping_address}</p>

<h3>'._w('Bill to').'</h3>
<p>{$customer->get("name", "html")|escape}<br>
{$billing_address}</p>

<p>'._w('View and manage your order').': <a href="{$order_url}" target="_blank"><strong>{$order_url}</strong></a>
{if !empty($order.params.auth_pin)}<br>'._w('PIN').': <strong>{$order.params.auth_pin}</strong>{/if}
</p>

<p>'.sprintf( _w('Thank you for shopping at %s!'), '{$wa->shop->settings("name")|escape}').'</p>

<p>--<br>
{$wa->shop->settings("name")|escape}<br>
<a href="mailto:{$wa->shop->settings("email")}">{$wa->shop->settings("email")}</a><br>
{$wa->shop->settings("phone")}<br></p>';


        /* order shipment (sending out) email notification template */
        $result['order.ship']['subject'] = sprintf( _w('Order %s has been sent out!'), '{$order.id}');
        $result['order.ship']['body'] = '<p>'.sprintf( _w('Hi %s'), '{$customer->get("name", "html")}').'</p>

<p>'.sprintf( _w('Your order %s has been shipped!'), '{$order.id}').'
{if !empty($action_data.params.tracking_number)}
   '.sprintf( _w('The shipment tracking number is <strong>%s</strong>'), '{$action_data.params.tracking_number|escape}').'
{/if}
</p>

{if !empty($action_data.params.tracking_number) && !empty($shipping_plugin)}
    {$tracking = $shipping_plugin->tracking($action_data.params.tracking_number)}
    {if $tracking}
    <p>{$tracking}</p>
    {/if}
{/if}

<p>'.sprintf( _w('Thank you for shopping at %s!'), '{$wa->shop->settings("name")|escape}').'</p>

<p>--<br>
{$wa->shop->settings("name")|escape}<br>
<a href="mailto:{$wa->shop->settings("email")}">{$wa->shop->settings("email")}</a><br>
{$wa->shop->settings("phone")}<br></p>';


        /* order cancellation email notification template */
        $result['order.delete']['subject'] = sprintf( _w('Order %s has been cancelled'), '{$order.id}');
        $result['order.delete']['body'] = '<p>'.sprintf( _w('Hi %s'), '{$customer->get("name", "html")}').'</p>

<p>'.sprintf( _w('Your order %s has been cancelled. If you want your order to be re-opened, please contact us.'), '{$order.id}').'</p>

<p>'.sprintf( _w('Thank you for shopping at %s!'), '{$wa->shop->settings("name")|escape}').'</p>

<p>--<br>
{$wa->shop->settings("name")|escape}<br>
<a href="mailto:{$wa->shop->settings("email")}">{$wa->shop->settings("email")}</a><br>
{$wa->shop->settings("phone")}<br></p>';


        /* misc order status change email notification template */
        $result['order']['subject'] = sprintf( _w('Order %s has been updated'), '{$order.id}');
        $result['order']['body'] = '<p>'.sprintf( _w('Hi %s'), '{$customer->get("name", "html")}').'</p>

<p>'.sprintf( _w('Your order %s status has been updated to <strong>%s</strong>'), '{$order.id}', '{$status}').'</p>

<p>'.sprintf( _w('Thank you for shopping at %s!'), '{$wa->shop->settings("name")|escape}').'</p>

<p>--<br>
{$wa->shop->settings("name")|escape}<br>
<a href="mailto:{$wa->shop->settings("email")}">{$wa->shop->settings("email")}</a><br>
{$wa->shop->settings("phone")}<br></p>';

        return $result;

    }
}
