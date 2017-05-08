<?php

class Dao_Core_V2StaticMembsLv extends Dao_Core_AbstractStatic
{
    protected $_tableName = 'v2_static_membs_lv';

    protected function __CACHE__getListByBloc($blocId, $fields = null)
    {
        if (null === $fields) {
            $fields = ['id', 'name', 'rank', 'profit_lightspot', 'logo_url'];
        }

        return $this->field($fields)->where(['bloc_id' => $blocId, 'is_shown' => 1, 'status' => 1])->order('`display_order` DESC')->fetchAll();
    }

    protected function __CACHE__getLvInfo($blocId, $realName)
    {
        return $this->where(['bloc_id' => $blocId, 'real_name' => $realName, 'status' => 1])->order('`rank` DESC')->fetchRow();
    }
}