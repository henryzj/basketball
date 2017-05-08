<?php

/**
 * 云扣人脸识别 - Recognition 特征识别
 *
 * @link http://www.recog.cn/
 *
 * @author JiangJian <silverd29@hotmail.com>
 */

class Com_Recog_Recognition
{
    /**
     * 比较2张图片的相似度
     *
     * @link http://www.recog.cn/api/doc.html#106
     *
     * @return array
     */
    public static function compareByPic($base64Pic1, $base64Pic2)
    {
        $params = [
            'base64Pic1' => $base64Pic1,
            'base64Pic2' => $base64Pic2,
            'bioType'    => 1, // 生物识别算法类型：目前只能为1--可见光，人脸
        ];

        return Com_Recog_Util::request('http://api.recog.cn/Recognition/compareByPic', $params);
    }

    /**
     * 比较2个人的相似度
     *
     * @link http://www.recog.cn/api/doc.html#107
     *
     * @return array
     */
    public static function compareByPerson($personId1, $personId2)
    {
        $params = [
            'personId1' => $personId1,
            'personId1' => $personId1,
            'bioType'    => 1, // 生物识别算法类型：目前只能为1--可见光，人脸
        ];

        return Com_Recog_Util::request('http://api.recog.cn/Recognition/compareByPerson', $params);
    }

    /**
     * 判定1张图片是否属于某一个人
     *
     * @link http://www.recog.cn/api/doc.html#108
     *
     * @return array
     */
    public static function verifyByPic($base64Pic, $personId2)
    {
        $params = [
            'base64Pic' => $base64Pic,
            'personId1' => $personId1,
            'bioType'   => 1, // 生物识别算法类型：目前只能为1--可见光，人脸
        ];

        return Com_Recog_Util::request('http://api.recog.cn/Recognition/verifyByPic', $params);
    }
}