<?php

/**
 * LBS相关测试脚本
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Controller_Lbs extends Core_Controller_Test
{
    // temp debug
    public function indexAction()
    {
        $r[] = Com_BaiduLBS_GeoStorage::detailPoi('v2_static_hotel_info', 3039);

        vd($r);
    }

    // 初始化百度lbs表结构--酒店数据
    public function initStaticHotelAction()
    {
        // 新增字段
        // $r[] = Com_BaiduLBS_GeoStorage::createCol('v2_static_hotel_info', [
        //     'name'            => '酒店ID',
        //     'key'             => 'hotel_id',
        //     'type'            => 1,
        //     'is_unique_field' => 1,
        // ]);

        // // 新增字段
        // $r[] = Com_BaiduLBS_GeoStorage::createCol('v2_static_hotel_info', [
        //     'name'                => '状态',
        //     'key'                 => 'status',
        //     'type'                => 1,
        //     'is_sortfilter_field' => 1,
        // ]);

        // // 新增字段
        // $r[] = Com_BaiduLBS_GeoStorage::createCol('v2_static_hotel_info', [
        //     'name'            => '关键字',
        //     'key'             => 'keywords',
        //     'type'            => 3,
        //     'is_search_field' => 1,
        //     'max_length'      => 512,  // 检索字段，最大512字节
        // ]);

        // // 新增字段
        // $r[] = Com_BaiduLBS_GeoStorage::createCol('v2_static_hotel_info', [
        //     'name'            => '酒店名称 (英文)',
        //     'key'             => 'name_en',
        //     'type'            => 3,
        //     'is_search_field' => 1,
        //     'max_length'      => 512,  // 检索字段，最大512字节
        // ]);

        // 新增字段
        $r[] = Com_BaiduLBS_GeoStorage::createCol('v2_static_hotel_info', [
            'name'                => '店铺类型',
            'key'                 => 'hotel_type',
            'type'                => 1,
            'is_sortfilter_field' => 1,
            'default_value'       => 1,    // 默认为酒店
            'max_length'          => 512,  // 检索字段，最大512字节
        ]);

        vd($r);
    }

    // 初始化百度lbs表结构--用户位置
    public function initUserLocationAction()
    {
        // 新增字段
        $r[] = Com_BaiduLBS_GeoStorage::createCol('v2_user_location', [
            'name'            => 'UID',
            'key'             => 'user_id',
            'type'            => 1,
            'is_unique_field' => 1,
        ]);

        // 新增字段
        $r[] = Com_BaiduLBS_GeoStorage::createCol('v2_user_location', [
            'name'                => '性别',
            'key'                 => 'sex',
            'type'                => 1,
            'is_sortfilter_field' => 1,
        ]);

        // 新增字段
        $r[] = Com_BaiduLBS_GeoStorage::createCol('v2_user_location', [
            'name'                => '更新时间',
            'key'                 => 'updated_at',
            'type'                => 1,
            'is_sortfilter_field' => 1,
        ]);

        vd($r);
    }
}