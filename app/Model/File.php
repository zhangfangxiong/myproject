<?php
/**
 * Created by PhpStorm.
 * User: felixtang
 * Date: 14-12-23
 * Time: 下午3:48
 */

class Model_File extends Model_Base
{
    const TABLE_NAME = 't_file';

    // iAutoID, sKey, sExt, iHostID, iCreateTime, iUpdateTime

    public static function getFileByKey($p_sFileKey) {
        return self::getRow(array('where'=>array('sKey'=>$p_sFileKey)));
    }
}