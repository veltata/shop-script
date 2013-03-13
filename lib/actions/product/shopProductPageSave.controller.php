<?php

class shopProductPageSaveController extends waJsonController
{
    /**
     * @var shopProductPagesModel
     */
    private $pages_model;
    /**
     * @var shopProductModel
     */
    private $product_model;

    private $param_names = array('description', 'keywords');

    /**
     * @var array
     */
    private $product;

    public function __construct() {
        $this->pages_model = new shopProductPagesModel();
        $this->product_model = new shopProductModel();
    }

    public function execute()
    {
        $id = waRequest::get('id', null, waRequest::TYPE_INT);

        $data = $this->getData($id);
        if ($id) {
            if (!$this->pages_model->update($id, $data)) {
                $this->errors[] = _w('Error saving product page');
                return;
            }
        } else {
            $id = $this->pages_model->add($data);
            if (!$id) {
                $this->errors[] = _w('Error saving product page');
                return;
            }
        }

        $page = $this->pages_model->getById($id);
        $product = $this->getProduct($page['product_id']);
        $page['name'] = htmlspecialchars($data['name']);
        $page['frontend_url'] = rtrim(
            wa()->getRouteUrl('/frontend/productPage', array(
                'product_url' => $product['url'],
                'page_url' => ''
            ), true
        ), '/');
        $page['preview_hash'] = $this->pages_model->getPreviewHash();

        $this->response = $page;
    }

    public function getData($id)
    {
        $data = waRequest::post('info');
        if (!$id) {
            $product_id = waRequest::get('product_id', null, waRequest::TYPE_INT);
            $product = $this->getProduct($product_id);
            $data['product_id'] = $product['id'];
        }
        $data['url'] = trim($data['url'], '/');
        if (!$id && !$data['url']) {
            $data['url'] = shopHelper::transliterate($data['name']);
        }
        if (empty($data['name'])) {
            $data['name'] = '('._ws('no-title').')';
        }
        $data['status'] = isset($data['status']) ? 1 : 0;
        $data['params'] = $this->getParams();
        return $data;
    }

    public function getProduct($product_id)
    {
        if ($this->product === null) {
            $this->product = $this->product_model->getById($product_id);
            if (!$this->product) {
                throw new waException(_w("Unknown product"), 404);
            }
        }
        return $this->product;
    }

    public function getParams()
    {
        $params = array();
        foreach ((array)waRequest::post('params', array()) as $name => $value) {
            if ($value && in_array($name, $this->param_names)) {
                $params[$name] = $value;
            }
        }
        return $params;
    }
}