<?php

namespace Merconis\Core;

class ls_shop_singularStorage
{
    protected $arr_allowedTypes = array(
        'int',
        'float',
        'str',
        'bln',
        'arr'
    );

    protected $arr_values = array();


    /*
     * Current object instance (Singleton)
     */
    protected static $objInstance;

    /*
     * Prevent cloning of the object (Singleton)
     */
    private function __clone()
    {
    }


    /*
     * Return the current object instance (Singleton)
     */
    public static function getInstance()
    {
        if (!is_object(self::$objInstance)) {
            self::$objInstance = new self();
        }
        return self::$objInstance;
    }

    /*
     * Prevent direct instantiation (Singleton)
     */
    protected function __construct()
    {
    }

    public function __get($str_key)
    {
        if (!array_key_exists($str_key, $this->arr_values)) {
            $this->getValueFromDb($str_key);
        }

        return $this->arr_values[$str_key];
    }

    public function __set($str_key, $var_value)
    {
        $this->writeValueToDb($str_key, $var_value);
    }

    protected function writeValueToDb($str_key, $var_value)
    {
        $str_type = $this->getTypeFromKey($str_key);

        if ($str_type === 'arr' && is_array($var_value)) {
            $var_value = serialize($var_value);
        } else if ($str_type === 'bln') {
            $var_value = $var_value ? '1' : '';
        }

        $obj_dbres = \Database::getInstance()
            ->prepare("
                SELECT		`" . $str_type . "_value`
                FROM		`tl_ls_shop_singular_storage`
                WHERE		`key` = ?
            ")
            ->execute(
                $str_key
            );

        if (!$obj_dbres->numRows) {
            if ($var_value !== null) {
                \Database::getInstance()
                    ->prepare("
                        INSERT INTO	`tl_ls_shop_singular_storage`
                        SET			`key` = ?,
                                    `" . $str_type . "_value` = ?
                    ")
                    ->execute(
                        $str_key,
                        $var_value
                    );
            }
        } else {
            if ($var_value !== null) {
                \Database::getInstance()
                    ->prepare("
                        UPDATE		`tl_ls_shop_singular_storage`
                        SET			`" . $str_type . "_value` = ?
                        WHERE		`key` = ?
                    ")
                    ->limit(1)
                    ->execute(
                        $var_value,
                        $str_key
                    );
            } else {
                \Database::getInstance()
                    ->prepare("
                        DELETE FROM	`tl_ls_shop_singular_storage`
                        WHERE		`key` = ?
                    ")
                    ->limit(1)
                    ->execute(
                        $str_key
                    );
            }
        }

        /*
         * Read the value from the db right after we inserted it. This is to
         * make sure that the value that would be read from $this->arr_values
         * is exactly what's in the database because under certain circumstances
         * inserting something into the database can convert values (i.e. inserting
         * a decimal value into an integer field etc.).
         */
        $this->getValueFromDb($str_key);
    }

    protected function getValueFromDb($str_key)
    {
        $str_type = $this->getTypeFromKey($str_key);

        $obj_dbres = \Database::getInstance()
            ->prepare("
                SELECT		`" . $str_type . "_value`
                FROM		`tl_ls_shop_singular_storage`
                WHERE		`key` = ?
            ")
            ->execute(
                $str_key
            );

        $var_result = $obj_dbres->numRows ? $obj_dbres->first()->{$str_type . '_value'} : null;

        if ($var_result !== null) {
            if ($str_type === 'arr' && !is_array($var_result)) {
                $var_result = \Contao\StringUtil::deserialize($var_result);
            } else if ($str_type === 'bln') {
                $var_result = $var_result ? true : false;
            }
        }

        $this->arr_values[$str_key] = $var_result;
    }

    protected function getTypeFromKey($str_key)
    {
        $arr_keyParts = explode('_', $str_key, 2);
        $str_type = $arr_keyParts[0];
        if (!in_array($str_type, $this->arr_allowedTypes)) {
            throw new \Exception(__METHOD__ . ' => Type ' . $str_type . ' is not supported');
        }
        return $str_type;
    }

    public function clearCache($str_key)
    {
        if (array_key_exists($str_key, $this->arr_values)) {
            unset($this->arr_values[$str_key]);
        }
    }
}