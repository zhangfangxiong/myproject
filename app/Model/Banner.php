<?php
/**
 * Created by PhpStorm.
 * User: zhangfangxiong
 * Date: 16/04/06
 * Time: 下午2:32
 */
class Model_Banner extends Model_Base
{
    const TABLE_NAME = 't_banner';

    /**
     * 获取module及内部的type结构(其实就是个备注作用)
     * module数组里包含的key是typeid,
     * typeid的value值,0:无效,1:有效
     */
    public static function getModuleInfo()
    {
        $aModule = [
            1 => [//前端大图banner
                1=>1,//首页
                2=>1,//安心体检
                3=>1,//安心保障
            ],
            2 => [//继续添加

            ],
        ];
        return $aModule;
    }

    //获取banner
    public static function getBanner($p_aParam, $p_iLimit = 100, $p_sOrder = 'iOrder DESC,iUpdateTime DESC')
    {
        $aWhere = ['iStatus' => 1];

        if (isset($p_aParam['iModuleID'])) {
            $aWhere['iModuleID'] = intval($p_aParam['iModuleID']);
        }

        if (isset($p_aParam['iTypeID'])) {
            $aWhere['iTypeID'] = intval($p_aParam['iTypeID']);
        }

        if (isset($p_aParam['iStatus'])) {
            $aWhere['iStatus'] = intval($p_aParam['iStatus']);
        }
        $aList = self::getAll(
            [
                'where' => $aWhere,
                'order' => $p_sOrder,
                'limit' => $p_iLimit
            ]
        );

        return $aList;
    }

}