<?php

/**
 * 导入、导出 Excel 格式
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Excel.php 9273 2014-02-26 13:53:46Z jiangjian $
 */

class Com_Excel
{
    /**
     * 构建 CSV 字符串
     *
     * @param array $title 标题名
     * @param array $datas 内容
     * @return string
     */
    public static function buildCsv(array $title, array $datas, $addTab = true)
    {
        $tab = $addTab ? "\t" : '';
        $title = '"' . implode($tab . '","', $title) . '"' . "\n";

        $content = '';

        foreach ($datas as $val) {
            $content .= '"' . implode($tab . '","', $val) . '"' . "\n";
        }

        return $title . $content;
    }

    /**
     * 导出数据为 CSV
     *
     * @param string $fileName 文件名
     * @param array $title 标题名
     * @param array $datas 内容
     * @return void
     */
    public static function exportCsv($fileName, array $title, array $datas, $addTab = true)
    {
        // 构建 CSV 字符串
        $content = self::buildCsv($title, $datas, $addTab);

        $fileName = iconv('UTF-8', 'GBK', $fileName);

        header('Content-Disposition: attachment; filename=' . $fileName . '.csv');
        header('Content-Type:application/octet-stream');

        echo $content;
        // echo iconv('UTF-8', 'GBK', $content);

        exit();
    }

    /**
     * 上传并导入 csv 格式
     *
     * @param string $fileInputName 例如 $_FILES['upload_file'] 中的 upload_file
     *
     * @return array
     */
    public static function importCsv($fileInputName)
    {
        $data = $_FILES[$fileInputName];
        $fileInfo = pathinfo($data['name']);

        if ($fileInfo['extension'] != 'csv') {
            throw new Core_Exception_Fatal('上传文件格式不正确，必须为CSV格式');
        }

        // 文件上传失败
        if (! $data['tmp_name']) {
            throw new Core_Exception_Fatal('文件上传失败，tmp_name 读取失败');
        }

        $fileName = $data['tmp_name'];
        @chmod($fileName, 0777);

        $returnList = [];

        if (! $handle = fopen($fileName, 'r')) {
            throw new Core_Exception_Fatal('临时文件打开失败');
        }

        while (! feof($handle)) {
            $data = mb_convert_encoding(trim(strip_tags(fgets($handle))), 'utf-8', 'gbk');
            if ($data) {
                $returnList[] = explode(',', $data);
            }
        }

        unset($returnList[0]);

        fclose($handle);
        @unlink($fileName);

        return $returnList;
    }

    /**
     * 上传并导入 csv 格式
     * @param  string $fileInputName 例如 $_FILES['upload_file'] 中的 upload_file
     * @param  integer $startRow  起始行，默认从第1行开始
     * @param  integer $startCol  起始列，默认从第0列开始
     * @return array
     */
    public static function importXls($fileInputName, $startRow = 1, $startCol = 0, $endCol = null)
    {
        Yaf_Loader::import(SYS_PATH . 'Third/PHPExcel.php');

        $data = $_FILES[$fileInputName];
        $fileInfo = pathinfo($data['name']);

        if (! in_array($fileInfo['extension'], ['xls', 'xlsx'])) {
            throw new Core_Exception_Fatal('上传文件格式不正确，必须为Excel格式');
        }

        // 文件上传失败
        if (! $data['tmp_name']) {
            throw new Core_Exception_Fatal('文件上传失败，tmp_name 读取失败');
        }

        $fileName = $data['tmp_name'];
        @chmod($fileName, 0777);

        // 设置以Excel2007格式(Excel2007-2010工作簿)
        if ($fileInfo['extension'] =='xlsx' ) {
            $reader = PHPExcel_IOFactory::createReader('Excel2007');
        }
        // 设置以Excel5格式(Excel97-2003工作簿)
        else {
            $reader = PHPExcel_IOFactory::createReader('Excel5');
        }

        // 载入excel文件
        $excel = $reader->load($fileName);

        // 读取第一個工作表
        $sheet = $excel->getActiveSheet();

        // 取得总行数
        $highestRow = $sheet->getHighestRow();

        // 取得总列数
        $highestColumm = $sheet->getHighestColumn();

        // 字母列转换为数字列 如:AA变为27
        $highestColumm = PHPExcel_Cell::columnIndexFromString($highestColumm);

        // 结束的列
        if ($endCol != null) {
            $highestColumm = min($highestColumm, $endCol + 1);
        }

        $returnList = [];

        // 循环读取每个单元格的数据
        // 行数是以第1行开始
        for ($row = $startRow; $row <= $highestRow; $row++){
            // 列数是以第0列开始
            for ($column = $startCol; $column < $highestColumm; $column++) {
                $returnList[$row][] = $sheet->getCellByColumnAndRow($column, $row)->getCalculatedValue();
            }
        }

        return $returnList;
    }
}