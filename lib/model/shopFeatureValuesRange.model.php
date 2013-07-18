<?php
class shopFeatureValuesRangeModel extends shopFeatureValuesModel
{
    protected $table = 'shop_feature_values_range';

    protected function getValue($row)
    {
        return new shopRangeValue($row);
    }

    public function getProductValues($product_id, $feature_id, $field = 'value')
    {
        $values = parent::getProductValues($product_id, $feature_id, array('begin_base_unit', 'end_base_unit'));
        return $values;
    }

    protected function parseValue($value, $type)
    {
        $dimensions = shopDimension::getInstance();
        $data = array();
        /**
         * @todo string based pattern
         * d.d(\s+|\.\.\.?|\s*[\-—]\s*)\d\.\d
         */
        if (strpos($type, '.')) { //


            if (!is_array($value) || ((count($value) == 1) && !isset($value['value']))) {
                $matches = null;
                $value = trim(is_array($value) ? reset($value) : $value);
                if (preg_match('/^(.*)\s+([\D]+)$/', $value, $matches)) {
                    $value = array(
                        'value' => trim($matches[1]),
                        'unit' => trim($matches[2]),
                    );
                } else {
                    $value = array(
                        'value' => trim($value),
                        'unit' => null,
                    );
                }
                $values = array_map('trim', preg_split('/\s*([\s—]|\.\.)\s/', $value['value'], 2));
                if (count($values) > 1) {
                    $value['value'] = array(
                        'begin' => ($values[0] === '') ? null : $this->castValue('double', $values[0]),
                        'end' => ($values[1] === '') ? null : $this->castValue('double', $values[1]),
                    );
                } else {
                    $value['value'] = array(
                        'begin' => ($values[0] === '') ? null : $this->castValue('double', $values[0]),
                        'end' => null,
                    );
                }
            }

            $type = preg_replace('/^.+\./', '', $type);
            $value['type'] = $type;
        }

        if (!empty($value['code'])) {
            if (strpos($value['code'], '.')) {
                list($data['type'], $data['unit']) = explode('.', $value['code'], 2);
            } elseif ($dimension = $dimensions->getDimension(empty($value['type']) ? $value['code'] : $value['type'])) {
                $data['type'] = !empty($value['type']) ? $value['type'] : $value['code'];
                $data['unit'] = !empty($value['unit']) ? $value['unit'] : $dimension['base_unit'];
            } else {
                $data['unit'] = !empty($value['unit']) ? $value['unit'] : '';
                $data['type'] = null;
            }
        } else {
            $data['type'] = !empty($value['type']) ? $value['type'] : '';
            if ($dimension = $dimensions->getDimension($data['type'])) {
                $data['unit'] = !empty($value['unit']) ? $value['unit'] : $dimension['base_unit'];
            } else {
                $data['unit'] = !empty($value['unit']) ? $value['unit'] : '';
            }
        }


        $data['unit'] = $dimensions->fixUnit($data['type'], $data['unit']);
        $data['begin'] = (isset($value['value']['begin']) && ($value['value']['begin'] !== '')) ? $this->castValue('double', $value['value']['begin']) : null;
        $data['begin_base_unit'] = $dimensions->convert($data['begin'], $data['type'], null, $data['unit']);

        $data['end'] = (isset($value['value']['end']) && ($value['value']['end'] !== '')) ? $this->castValue('double', $value['value']['end']) : null;
        $data['end_base_unit'] = $dimensions->convert($data['end'], $data['type'], null, $data['unit']);
        return $data;
    }

    protected function getSearchCondition()
    {
        return '(`begin`= :begin) AND (`end`=:end) AND (`unit` = s:unit)';
    }
}


class shopRangeValue implements ArrayAccess
{
    static private $format = '%s %s';
    /**
     * @var shopDimensionValue
     */
    private $begin;
    /**
     * @var shopDimensionValue
     */
    private $end;
    private $unit;

    /**
     * @param array $row
     */
    public function __construct($row)
    {
        $begin = $row;
        $begin['value'] = $begin['begin'];
        $this->begin = new shopDimensionValue($begin);
        $end = $row;
        $end['value'] = $end['end'];
        $this->end = new shopDimensionValue($end);
        foreach ($row as $field => $value) {
            if (!isset($this->$field)) {
                $this->$field = $value;
            }
        }
    }

    /**
     * @param string $format
     */
    public static function setFormat($format)
    {
        self::$format = $format;
    }

    public function __toString()
    {
        $str = array();
        if ($this->begin !== null) {
            $str[] = $this->begin->format(false);
        } else {
            $str[] = '∅';
        }

        if ($this->end !== null) {
            $str[] = $this->end->format(false);
        } else {
            $str[] = '∞';
        }
        return implode(' — ', $str);
    }

    /**
     * @param mixed $offset
     * @return boolean true on success or false on failure.
     */
    public function offsetExists($offset)
    {

    }


    public function offsetGet($offset)
    {

    }


    public function offsetSet($offset, $value)
    {

    }


    public function offsetUnset($offset)
    {

    }


    public function __set($field, $value)
    {
        return $this->$field = $value;
    }

    public function __get($field)
    {
        if ($field == 'units') {
            return shopDimension::getUnits($this->unit);
        }
        if ($field == 'value') {
            $str = array();
            if ($this->begin !== null) {
                $str[] = sprintf(self::$format, _w('from'), $this->begin);
                if ($this->end !== null) {
                    $str[] = '—';
                }
            }

            if ($this->end !== null) {
                $str[] = sprintf(self::$format, _w('to'), $this->end);
            }
            return implode(' ', $str);
        }
        if (!isset($this->$field)) {
            var_dump($field);
        }
        return isset($this->$field) ? $this->$field : '!!!' . $field . var_dump($field);
    }
}
