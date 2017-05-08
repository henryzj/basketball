<?php

class Dao_Core_V2AppVersion extends Dao_Core_AbstractStatic
{
    protected $_tableName = 'v2_app_version';

    protected function __CACHE__getLastestVerInfo($appId)
    {
        return $this->where(['app_id' => $appId, 'status' => 1])->order('`version_no_full` DESC')->fetchRow();
    }

    // 找到大于当前版本的所有版本
    protected function __CACHE__getGreatVersionList($appId, $versionNo)
    {
        $versionNos = [];

        foreach (explode('.', $versionNo) as $value) {
            $versionNos[] = str_pad($value, 4, '0', STR_PAD_LEFT);
        }

        $where = [
            'app_id'          => $appId,
            'status'          => 1,
            'version_no_full' => ['>', implode('.', $versionNos)],
        ];

        return $this->where($where)->order('`version_no_full` DESC')->fetchAll();
    }
}