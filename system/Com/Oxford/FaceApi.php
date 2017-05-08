<?php

/**
 * 微软牛津计划 - 人脸识别
 *
 * 这款免费的人脸识别产品包含所有(Face APIs)的方法。
 * 这些方法有人脸检测，人脸验证，相似人脸搜索，人脸分组和人脸辨识。
 * 这些APIs每分钟最多可以接受20条请求，每月最多接受5000条请求。
 *
 * @link https://www.projectoxford.ai/doc/
 *
 * @author JiangJian <silverd29@hotmail.com>
 */

class Com_Oxford_FaceApi
{
    /**
     * 人脸检测（年龄、性别等）
     *
     * @link https://dev.projectoxford.ai/docs/services/563879b61984550e40cbbe8d/operations/563879b61984550f30395236
     *
     * @return array
     */
    public static function detect($imgUrl, $isImgStream = false)
    {
        $url = 'https://api.projectoxford.ai/face/v1.0/detect?' . http_build_query([
            'returnFaceId'         => true,
            'returnFaceLandmarks'  => false,
            'returnFaceAttributes' => null,
        ]);

        // 图片二进制流
        if ($isImgStream) {
            $contentType = 'application/octet-stream';
            $params = $imgUrl;
        }
        // 图片URL网址
        else {
            $contentType = 'application/json';
            $params = json_encode(['url' => $imgUrl]);
        }


        $result = Com_Oxford_Util::request($url, $params, $contentType);

        return $result;
    }

    /**
     * 人脸比较相似度
     *
     * @link https://dev.projectoxford.ai/docs/services/563879b61984550e40cbbe8d/operations/563879b61984550f30395237
     *
     * @return array
     */
    public static function findSimilars($faceId)
    {
        $params = [
            'faceId' => $faceId,
            'faceListId' => null,
            'faceIds' => [],
            'maxNumOfCandidatesReturned' => 10,
        ];

        $url = 'https://api.projectoxford.ai/face/v1.0/findsimilars';

        $result = Com_Oxford_Util::request($url, json_encode($params));

        return $result;
    }
}