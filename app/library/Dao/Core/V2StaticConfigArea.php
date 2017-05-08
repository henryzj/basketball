<?php

class Dao_Core_V2StaticConfigArea extends Dao_Core_AbstractStatic
{
    protected $_tableName = 'v2_static_config_area';

    // 以二级形式展现
    // 直辖市精确到区，普通省份精确到市
    protected function __CACHE__getTwoLevelList()
    {
        $list = $this->where(['level' => ['IN', [0, 1]]])->fetchAll();

        $subItems = [];

        foreach ($list as $value) {
            // 直辖市
            // 北京、天津、上海、重庆、香港、澳门
            if (in_array($value['pid'], [11, 12, 31, 50, 81, 91])) {
                // 下辖区县
                $districts = $this->where(['pid' => $value['id']])->fetchAll();
                foreach ($districts as $district) {
                    $subItems[$value['pid']][] = [
                        'id' => $district['id'],
                        'name' => $district['name'],
                    ];
                }
            }
            // 其他省
            else {
                $subItems[$value['pid']][] = [
                    'id' => $value['id'],
                    'name' => $value['name'],
                ];
            }
        }

        // 省份
        $returnList = $subItems[0];

        // 归类市
        foreach ($returnList as &$province) {
            $province['sub_items'] = $subItems[$province['id']];
        }

        return $returnList;
    }
}