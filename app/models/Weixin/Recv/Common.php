<?php

/**
 * 微信公众号接收消息
 * 接收普通消息
 *
 * @link http://mp.weixin.qq.com/wiki/10/79502792eef98d6e0c6e1739da387346.html
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Weixin_Recv_Common extends Model_Weixin_Recv_Abstract
{
    public function process()
    {
        if ($this->_data['MsgType'] == 'text') {

            // wendy 专用密语
            if ($this->_data['Content'] == 'wendy') {

                $string = '<a href="' . sUrl('/gm/hotel/index')  . '">- 服务费申请退款审核</a>' . PHP_EOL . PHP_EOL
                         . '<a href="' . sUrl('/gm/wallet/index')  . '">- 余额提现审核</a>' . PHP_EOL . PHP_EOL
                         . '<a href="' . sUrl('/gm/hotel/orderlist') . '">- 酒店订单列表</a>';

                $this->replyText($string);
            }
        }
    }
}