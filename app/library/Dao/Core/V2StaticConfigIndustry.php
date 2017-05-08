<?php

class Dao_Core_V2StaticConfigIndustry extends Dao_Core_AbstractStatic
{
    protected $_tableName = 'v2_static_config_industry';

    protected function __CACHE__getArrayList()
    {
        $list = $this->fetchAll();

        $subItems = [];

        foreach ($list as $value) {
            $subItems[$value['pid']][] = [
                'id' => $value['id'],
                'name' => $value['name'],
            ];
        }

        // 省份
        $returnList = $subItems[0];

        // 归类市
        foreach ($returnList as &$value) {
            $value['sub_items'] = $subItems[$value['id']];
        }

        return $returnList;
    }
}