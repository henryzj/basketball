<?php

class Controller_Wx extends Core_Controller_Test
{
    public function syncMenuAction()
    {
        $menus = [
            'button' => [
                [
                   'type' => 'view',
                   'name' => '订酒店',
                   'url'   => sUrl('/hotel/index'),
                ],
                [
                    'name' => '个人中心',
                    'sub_button' => [
                        [
                           'type' => 'view',
                           'name' => '常见问题',
                           'url'   => sUrl('/hotel/help'),
                        ],
                        [
                            'type' => 'view',
                            'name' => '个人账户',
                            'url'  => sUrl('/wallet'),
                        ],
                        [
                            'type' => 'view',
                            'name' => '个人信息',
                            'url'  => sUrl('/hotel/contactList'),
                        ],
                        [
                            'type' => 'view',
                            'name' => '我的订单',
                            'url'  => sUrl('/hotel/orderIndex'),
                        ],
                    ],
                ],
            ]
        ];

        $result = Model_Weixin_Common::syncMenu($menus);
        vd($result);
    }
}