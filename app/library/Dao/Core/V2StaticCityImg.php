<?php

class Dao_Core_V2StaticCityImg extends Dao_Core_AbstractStatic
{
    protected $_tableName = 'v2_static_city_img';

    protected function __CACHE__getImgByCity($city)
    {
        $where = [
            'city'   => $city,
            'status' => 1,
        ];

        return $this->field('img_url')->where($where)->fetchOne();
    }

    protected function __CACHE__getImgByPro($province)
    {
        $where = [
            'province'       => $province,
            'is_pro_capital' => 1,
            'status'         => 1,
        ];

        return $this->field('img_url')->where($where)->fetchOne();
    }
}