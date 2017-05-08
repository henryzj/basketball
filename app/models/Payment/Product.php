<?php

class Model_Payment_Product extends Core_Model_Abstract
{
    // 产品类型定义
    public static $productTypes = [
        'Wallet'    => 1,
        'BookHotel' => 1,
    ];

    /**
     * 实例工厂
     *
     * @param string $productType
     * @return Model_Payment_Product_*
     */
    public static function factory($productType)
    {
        if (! isset(self::$productTypes[$productType])) {
            throw new Model_Payment_Exception_Common('Invalid ProductType');
        }

        $className = 'Model_Payment_Product_' . ucfirst($productType);

        return new $className($productType);
    }
}