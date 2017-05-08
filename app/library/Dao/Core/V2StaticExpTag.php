<?php

class Dao_Core_V2StaticExpTag extends Dao_Core_Abstract
{
    protected $_tableName = 'v2_static_exp_tag';

    public function getList(array $tagIds = [])
    {
        $where = [
            'id'     => ['IN', $tagIds],
            'status' => 1,
        ];

        return $this->field('`name`, `color`')->where($where)->fetchAll();
    }

    public function getAllList()
    {
        return $this->field('`id`, `name`, `color`')->where(['status' => 1])->fetchAll();
    }

    public function touchByName($hotelType, $tagName)
    {
        if (! $tagId = $this->where(['type' => $hotelType, 'name' => $tagName])->fetchPk()) {
            $tagId = $this->insert([
                'type'       => $hotelType,
                'name'       => $tagName,
                'color'      => '#4cc2d6',
                'use_times'  => 1,
                'created_at' => $GLOBALS['_DATE'],
            ]);
        } else {
            $this->incrByPk($tagId, 'use_times');
        }

        return $tagId;
    }

    // 获取某一类店铺的热门标签
    public function getTopList($hotelType = 1, $limit = 16)
    {
        $cacheKey = 'ExpTag:Type:' . $hotelType;

        if (! $tagList = F('Memcache')->get($cacheKey)) {
            $tagList = $this->field(['id', 'name', 'color', 'use_times'])->where(['type' => $hotelType, 'status' => 1])->order('`use_times` DESC, `created_at` DESC, `id` DESC')->limit($limit)->fetchAll();

            F('Memcache')->set($cacheKey, $tagList, 86400);
        }

        return $tagList;
    }
}