<?php

class Dao_Core_V2StaticHotelBloc extends Dao_Core_AbstractStatic
{
    protected $_tableName = 'v2_static_hotel_bloc';

    protected function __CACHE__getList($hasCard = null)
    {
        $where = ['status' => 1];

        if (null !== $hasCard) {
            $where['has_card'] = intval($hasCard);
        }

        return $this->where($where)->order('`display_order` DESC')->fetchAll();
    }
}