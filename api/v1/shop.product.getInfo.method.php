<?php

class shopProductGetInfoMethod extends waAPIMethod
{
    public function execute()
    {
        $id = waRequest::get('id');

        $product_model = new shopProductModel();
        $data = $product_model->getById($id);

        $this->response = $data;

        $p = new shopProduct($id);
        if ($p['image_id']) {
            $this->response['image_url'] = shopImage::getUrl(array(
                'product_id' => $id,
                'id' => $p['image_id'],
                'ext' => $p['ext']
            ), wa('shop')->getConfig()->getImageSize('default'), true);
        }
        $this->response['skus'] = array_values($p->skus);
        $this->response['categories'] = array_values($p->categories);
        $this->response['images'] = array_values($p->getImages('thumb', true));
        $this->response['features'] = array();
        foreach ($p->features as $f => $v) {
            if (is_array($v)) {
                $this->response['features'][$f] = array_values($v);
            } else {
                $this->response['features'][$f] = (string)$v;
            }
        }
    }
}