<?php

/**
 * 中国公民身份证验证类
 *
 * @author zhengjiang
 */

class Com_IdCard
{
    /**
     * 检测身份证是否合法
     * 规则：15/18位
     *
     * @param string $idNo
     * @return bool
     */
    public static function validate($idNo)
    {
        $vProvince = [
            '11','12','13','14','15','21','22',
            '23','31','32','33','34','35','36',
            '37','41','42','43','44','45','46',
            '50','51','52','53','54','61','62',
            '63','64','65','71','81','82','91'
        ];

        // 身份证位数
        if (! preg_match('/^([\d]{17}[xX\d]|[\d]{15})$/', $idNo)) {
            return false;
        }

        // 省份验证
        if (! in_array(substr($idNo, 0, 2), $vProvince)) {
            return false;
        }

        $vLength = strlen($idNo);

        // 将15位转换成18位
        if ($vLength == 15) {
            $idNo = self::trans15To18($idNo);
        }

        // 验证生日
        $vBirthday = substr($idNo, 6, 4) . '-' . substr($idNo, 10, 2) . '-' . substr($idNo, 12, 2);

        if (date('Y-m-d', strtotime($vBirthday)) != $vBirthday) {
            return false;
        }

        // 18位身份证校验码有效性检查
        $idBase = substr($idNo, 0, 17);

        if (self::getVerifyNo($idBase) != strtoupper(substr($idNo, 17, 1))) {
            return false;
        }

        return true;
    }

    // 计算身份证校验码，根据国家标准GB 11643-1999
    public static function getVerifyNo($idBase)
    {
        if (strlen($idBase) != 17)
        {
            return false;
        }

        // 检验18位身份证的校验码是否正确。
        // 校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
        //加权因子
        $factor = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];

        //校验码对应值
        $verifyNoList = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
        $sum = 0;

        for ($i = 0; $i < strlen($idBase); $i++) {
            $sum += substr($idBase, $i, 1) * $factor[$i];
        }

        $mod = $sum % 11;
        $verifyNo = $verifyNoList[$mod];

        return $verifyNo;
    }

    // 将15位身份证升级到18位
    public static function trans15To18($idNo)
    {
        if (strlen($idNo) != 15) {
            return false;
        } else {
            // 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
            if (array_search(substr($idNo, 12, 3), array('996', '997', '998', '999')) !== false) {
                $idNo = substr($idNo, 0, 6) . '18'. substr($idNo, 6, 9);
            } else {
                $idNo = substr($idNo, 0, 6) . '19'. substr($idNo, 6, 9);
            }
        }

        $newIdNo = $idNo . self::getVerifyNo($idNo);
        return $newIdNo;
    }

    // 根据身份证号码获取生日和性别
    public static function parseBirthdaySex($idNo)
    {
        if (! self::validate($idNo)) {
            return false;
        }

        if (strlen($idNo) == 15) {
            $idNo = self::trans15To18($idNo);
        }

        $birthday = substr($idNo, 6, 4) . '-' . substr($idNo, 10, 2) . '-' . substr($idNo, 12, 2);

        // 身份证到数第二位为性别，奇数为男，偶数为女
        $sex = substr($idNo, 16, 1) % 2 == 1 ? 1 : 2;

        return [
            'birthday' => $birthday,
            'sex'      => $sex,
        ];
    }
}