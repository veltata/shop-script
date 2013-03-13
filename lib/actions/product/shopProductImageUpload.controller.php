<?php

class shopProductImageUploadController extends shopUploadController
{
    /**
     * @var shopProductImagesModel
     */
    private $model;

    protected function save(waRequestFile $file)
    {
        // check image
        if (!($image = $file->waImage())) {
            throw new waException('Incorrect image');
        }

        if (!$this->model) {
            $this->model = new shopProductImagesModel();
        }

        $data = array(
            'product_id'      => waRequest::post('product_id', null, waRequest::TYPE_INT),
            'upload_datetime' => date('Y-m-d H:i:s'),
            'width'           => $image->width,
            'height'          => $image->height,
            'size'            => $file->size,
            'ext'             => $file->extension,
        );
        $image_id = $data['id'] = $this->model->add($data);
        if (!$image_id) {
            throw new waException("Database error");
        }

        $image_path = shopImage::getPath($data);
        if ((file_exists($image_path) && !is_writable($image_path)) || (!file_exists($image_path) && !waFiles::create($image_path))) {
            $this->model->deleteById($image_id);
            throw new waException(sprintf("The insufficient file write permissions for the %s folder.", substr($image_path, strlen($this->getConfig()->getRootPath()))));
        }

        $file->moveTo($image_path);

        /**
         * @var shopConfig $config
         */
        $config = $this->getConfig();
        shopImage::generateThumbs($data, $config->getImageSizes());

        return array(
            'id'             => $image_id,
            'name'           => $file->name,
            'type'           => $file->type,
            'size'           => $file->size,
            'url_thumb'      => shopImage::getUrl($data, $config->getImageSize('thumb')),
            'url_crop'       => shopImage::getUrl($data, $config->getImageSize('crop')),
            'url_crop_small' => shopImage::getUrl($data, $config->getImageSize('crop_small')),
            'description'    => ''
        );
    }
}
