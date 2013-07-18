<?php

class shopWorkflowRestoreAction extends shopWorkflowAction
{
    public function execute($oder_id = null)
    {
        // Restore previous state
        $log_model = new shopOrderLogModel();
        $this->state_id = $log_model->getPreviousState($oder_id, $params);

        // Restore order.paid_*, customer.total_spent and customer.affiliation_bonus
        $paid_date = ifset($params['paid_date']);
        if ($paid_date) {
            $t = strtotime($paid_date);
            $result['update'] = array(
                    'paid_year' => date('Y', $t),
                    'paid_quarter' => floor((date('n', $t) - 1) / 3) + 1,
                    'paid_month' => date('n', $t),
                    'paid_date' => date('Y-m-d', $t),
            );
            return $result;
        }

        return true;
    }

    public function postExecute($order_id = null, $result = null)
    {
        $data = parent::postExecute($order_id, $result);

        if ($order_id != null) {
            $order_model = new shopOrderModel();
            $app_settings_model = new waAppSettingsModel();

            if ($this->state_id != 'refunded') {
                $update_on_create = $app_settings_model->get('shop', 'update_stock_count_on_create_order');
                if ($update_on_create) {
                    $order_model->reduceProductsFromStocks($order_id);
                } else if (!$update_on_create && $this->state_id != 'new') {
                    $order_model->reduceProductsFromStocks($order_id);
                }
            }

            $order = $order_model->getById($order_id);
            if ($order && $order['paid_date']) {
                shopAffiliate::applyBonus($order_id);
                shopCustomers::recalculateTotalSpent($order['contact_id']);
            }
        }
        return $data;
    }
}
