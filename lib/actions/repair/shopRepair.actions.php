<?php

class shopRepairActions extends waActions
{

    public function __construct()
    {
        if (!$this->getUser()->isAdmin('shop')) {
            throw new waRightsException(_ws('Access denied'));
        }
    }

    protected function preExecute()
    {
        $response = $this->getResponse();
        $response->addHeader('Content-Type', 'text/plain; charset=utf-8');
        $response->sendHeaders();
        wa()->getStorage()->close();
        parent::preExecute();
    }

    public function defaultAction()
    {
        $methods = array_diff(get_class_methods(get_class($this)), get_class_methods(get_parent_class($this)));
        $callback = create_function('$n', 'return preg_match("@^(\w+)Action$@",$n,$m)?($m[1]!="default"?$m[1]:false):false;');
        $actions = array_filter($methods, $callback);
        $actions = array_map($callback, $actions);
        print "Available repair actions:\n\t";
        print implode("\n\t", $actions);
    }

    public function productcountsAction()
    {
        $model = new shopProductModel();
        $model->correctCount();
        echo "OK";
    }

    public function categoriesAction()
    {
        $model = new shopCategoryModel();
        $model->repair();
        echo "OK";
    }

    public function featuresSelectableAction()
    {
        $model = new waModel();


        $product_features_selectable_model = new shopProductFeaturesSelectableModel();

        // delete unbinded old values in shop_product_features_selectable
        $sql = "SELECT DISTINCT ps.product_id, pf.feature_id FROM `shop_product_skus` ps
                JOIN `shop_product_features` pf ON ps.product_id = pf.product_id AND ps.id = pf.sku_id
                LEFT JOIN `shop_product_features_selectable` fs ON fs.product_id = pf.product_id AND fs.feature_id = pf.feature_id AND fs.value_id = pf.feature_value_id
                WHERE ps.virtual = 1 AND fs.value_id IS NULL";

        foreach ($model->query($sql)->fetchAll() as $key) {
            $product_features_selectable_model->deleteByField($key);
        }

        // insert new actual values in shop_product_features_selectable
        $sql = "SELECT DISTINCT ps.product_id, pf.feature_id, pf.feature_value_id AS value_id FROM `shop_product_skus` ps
                JOIN `shop_product_features` pf ON ps.product_id = pf.product_id AND ps.id = pf.sku_id
                LEFT JOIN `shop_product_features_selectable` fs ON fs.product_id = pf.product_id AND fs.feature_id = pf.feature_id AND fs.value_id = pf.feature_value_id
                WHERE ps.virtual = 1 AND fs.value_id IS NULL";

        foreach ($model->query($sql)->fetchAll() as $item) {
            $product_features_selectable_model->insert($item);
        }

        echo "OK";
    }

    public function productRemoveFeaturesSelectableAction()
    {
        $model = new waModel();
        $model->exec('DELETE pf FROM shop_product_features pf
JOIN shop_product_features_selectable pfs
ON pf.product_id = pfs.product_id AND pf.feature_id = pfs.feature_id
WHERE pf.sku_id IS NULL');

        echo 'OK';
    }


    public function sortAction()
    {
        $this->getResponse()->addHeader('Content-type', 'text/plain');
        $this->getResponse()->sendHeaders();

        $sql_set = "SET @sort := 0, @context := ''";

        $sql_context = 'UPDATE `%1$s` SET
`sort`=(@sort := IF(@context != `%3$s`, 0, @sort +1)),
`%3$s` = (@context := `%3$s`)
ORDER BY `%3$s`,`sort`,`%2$s`';

        $sql_single = 'UPDATE `%1$s` SET
`sort`=(@sort := @sort +1)
ORDER BY `sort`,`%2$s`';

        $tables = array(
            'shop_plugin'                   => 'shopPluginModel',
            'shop_product_skus'             => 'shopProductSkusModel',
            'shop_type'                     => 'shopTypeModel',
            'shop_type_features'            => 'shopTypeFeaturesModel',
            'shop_feature_values_dimension' => 'shopFeatureValuesDimensionModel',
            'shop_feature_values_double'    => 'shopFeatureValuesDoubleModel',
            'shop_feature_values_text'      => 'shopFeatureValuesTextModel',
            'shop_feature_values_varchar'   => 'shopFeatureValuesVarcharModel',
            'shop_feature_values_color'     => 'shopFeatureValuesColorModel',
            'shop_importexport'             => 'shopImportexportModel',
        );

        $counter = 0;

        $trace = waRequest::request('trace');

        foreach ($tables as $table => $table_model) {
            if (class_exists($table_model)) {
                $model = new $table_model();
                /**
                 * @var $model shopSortableModel
                 */
                print sprintf("#%d\tRepair sort field at `%s` table:\n", ++$counter, $table);
                try {
                    $id = $model->getTableId();
                    if (is_array($id)) {
                        $id = implode('`, `', $id);
                    }
                    if ($context = $model->getTableContext()) {
                        $sql = sprintf($sql_context, $model->getTableName(), $id, $context);
                    } else {
                        $sql = sprintf($sql_single, $model->getTableName(), $id);
                    }
                    if ($trace) {
                        print "{$sql_set};\n{$sql};\n";
                    }
                    $model->exec($sql_set);
                    $model->exec($sql);
                    print "OK";
                } catch (waDbException $e) {
                    print "ERROR:".$e->getMessage();
                }
                print "\n\n";
            }
        }
        if (empty($model)) {
            $model = new waModel();
        }

        $tables = array(
            'shop_product_images'   => 'product_id',
            'shop_product_pages'    => 'product_id',
            'shop_service_variants' => 'service_id',
            //'shop_set_products'     => 'set_id',
            //'shop_tax_zip_codes'    => 'tax_id',
        );
        foreach ($tables as $table => $context) {
            $sqls = array();
            $sqls[] = "SET @sort := 0, @context := ''";
            $sqls[] = "UPDATE `{$table}` SET
`sort`=(@sort := IF(@context != `{$context}`, 0, @sort +1)),
`{$context}` = (@context := `{$context}`)
ORDER BY `{$context}`,`sort`,`id`";

            print sprintf("#%d\tRepair sort field at `%s` table:\n", ++$counter, $table);
            while ($sql = array_shift($sqls)) {
                try {
                    if ($trace) {
                        print "{$sql};\n";
                    }
                    $model->exec($sql);
                } catch (waDbException $e) {
                    print "ERROR:".$e->getMessage()."\n\n";
                    break;
                }
            }
            if (!$sqls) {
                print "OK\n\n";
            }
        }

        $tables = array(
            'shop_currency' => 'code',
            'shop_service'  => 'id',
            'shop_set'      => 'id',
            'shop_stock'    => 'id',

        );
        foreach ($tables as $table => $id) {
            $sqls = array();
            $sqls[] = "SET @sort := 0";
            $sqls[] = "UPDATE `{$table}` SET
`sort`=(@sort := @sort +1)
ORDER BY `sort`,`{$id}`";

            print sprintf("#%d\tRepair sort field at `%s` table:\n", ++$counter, $table);
            while ($sql = array_shift($sqls)) {
                try {
                    if ($trace) {
                        print "{$sql};\n";
                    }
                    $model->exec($sql);
                } catch (waDbException $e) {
                    print "ERROR:".$e->getMessage()."\n\n";
                    break;
                }
            }
            if (!$sqls) {
                print "OK\n\n";
            }
        }
    }

    public function skuAction()
    {
        $repaired = false;
        $model = new waModel();
        $sql = <<<SQL
UPDATE `shop_product` `p`
LEFT JOIN `shop_product_skus` `s` ON
  (`s`.`product_id`=`p`.`id`)
  AND
  (`s`.`id`=`p`.`sku_id`)
SET `p`.`sku_id`=NULL
WHERE `s`.`id` IS NULL
SQL;

        $result = $model->query($sql);
        /**
         * @var waDbResultUpdate $result
         */
        if ($count = $result->affectedRows()) {
            $repaired = true;
            print sprintf("%d product(s) with invalid default SKU ID restored\n", $count);
        }

        $sql = <<<SQL
UPDATE `shop_product` `p`
JOIN `shop_product_skus` `s`
ON (`s`.`product_id`=`p`.`id`)
SET `p`.`sku_id`=`s`.`id`
WHERE `p`.`sku_id` IS NULL
SQL;
        $result = $model->query($sql);
        /**
         * @var waDbResultUpdate $result
         */
        if ($count = $result->affectedRows()) {
            $repaired = true;
            print sprintf("%d product(s) with missed default SKU ID restored\n", $count);
        }
        if (!$repaired) {
            print "nothing to repair";
        }
    }

    public function emptyPathAction()
    {
        $paths = array();
        $wa = wa();

        if (waRequest::request('all')) {
            $apps = array_keys(wa()->getApps(true));
        } else {
            $apps = array('shop');
        }

        foreach ($apps as $app_id) {
            $paths[] = $wa->getDataPath(null, true, $app_id, false);
            $paths[] = $wa->getDataPath(null, false, $app_id, false);
        }

        foreach ($paths as $path) {
            $count = 0;
            $path = preg_replace('@[\\/]+@', DIRECTORY_SEPARATOR, $path);
            print sprintf("Checking %s directory...\n", $path);
            if (file_exists($path)) {
                $this->removeEmptyPaths($path, $count);
                if ($count) {
                    print sprintf("OK\tDeleted %d directories\n\n", $count);
                } else {
                    print "OK\tThere no empty directories\n\n";
                }
            } else {
                print "OK\tDirectory not exists\n\n";
            }
            flush();
        }
    }

    private function removeEmptyPaths($path, &$counter, $base_path = null)
    {
        static $time = 0;
        if ($base_path === null) {
            $base_path = $path;
        }
        $empty = true;
        $files = waFiles::listdir($path);
        foreach ($files as $file) {
            $file_path = $path.DIRECTORY_SEPARATOR.$file;
            if (is_dir($file_path)) {
                if (!$this->removeEmptyPaths($file_path, $counter, $base_path)) {
                    $empty = false;
                }
            } else {
                $empty = false;
            }
        }
        if ($empty) {
            ++$counter;
            if (waRequest::request('check')) {
                $result = '';
            } else {
                $result = @rmdir($path) ? 'OK' : 'NO';
            }

            print sprintf("\t%5d\t%s\t%s\n", $counter, $result, substr($path.DIRECTORY_SEPARATOR, strlen($base_path)));
            if ($counter % 100 == 0) {
                $time = time();
                flush();
            }
        }
        if ((time() - $time) > 5) {
            $time = time();
            flush();
        }
        return $empty;
    }
}
