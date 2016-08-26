<?php
/**
 * Created by PhpStorm.
 * User: felixtang
 * Date: 14-12-23
 * Time: 下午3:49
 */
class Model_FileMeta extends Model_Base
{
    const TABLE_NAME = 't_file_meta';

    // 主要用于显示逻辑
    const BID_DEFAULT = 1;  // 默认文件类型
    const BID_BANNER  = 2;  // Banner文件类型

    // iAutoID, sKey, sFileName, iSize, iWidth, iHeight, iIP, iCreateTime, iUpdateTime
    public static function getFileMetaByKey($p_sKey) {
        return self::getRow(array('where'=>array('sKey'=>$p_sKey)));
    }
}