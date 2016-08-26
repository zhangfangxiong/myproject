<?php

class Model_Cart extends Model_Base
{

    /**
     * 原理：
     * 1，未登陆状态全部操作cookie
     * 2，登陆状态全部操作表，然后同步到cookie
     * 3，读取的时候直接读取cookie
     * 4，未登录到登陆状态切换时，把cookie数据同步到表中（微信一直是登陆状态，不需要这一步）
     *
     */
    const TABLE_NAME = 't_cart';

    //更新购物车cookie
    public static function updateCartCookie($iUserID)
    {
        $aCartParam['iUserID'] = $iUserID;
        $aCartParam['iStatus'] = 1;
        $aCart = self::getAll($aCartParam);
        if (!empty($aCart)) {
            foreach ($aCart as $key => $value) {//重组以产品ID为key
                $aTmp[$value['iProductID']] = $value;
            }
            $aCart = $aTmp;
            Util_Cookie::set(Yaf_G::getConf('authchart', 'cookie'), $aCart);
        } else {
            Util_Cookie::delete(Yaf_G::getConf('authchart', 'cookie'));
        }
        return $aCart;
    }

    //同步cookie到表（未登录切换到登陆时调用）
    public static function sysCookie($iUserID)
    {
        $aCart = Util_Cookie::get(Yaf_G::getConf('authchart', 'cookie'));
        if (!empty($aCart)) {
            foreach ($aCart as $key => $value) {
                $aCartDBParam['iStatus'] = 1;
                $aCartDBParam['iProductID'] = $value['iProductID'];
                $aCartDBParam['iUserID'] = $iUserID;
                $aCartDB = self::getRow($aCartDBParam);
                $aCartDBParam['iNum'] = $value['iNum'];
                if (!empty($aCartDB)) {
                    $aCartDBParam['iAutoID'] = $aCartDB['iAutoID'];
                    return self::updData($aCartDBParam);
                } else {
                    return self::addData($aCartDBParam);
                }
            }
        }
    }

    //获取购物车数据
    public static function getCart($iUserID = 0)
    {
        $aCart = Util_Cookie::get(Yaf_G::getConf('authchart', 'cookie'));
        if (!empty($iUserID) && empty($aCart)) {
            //$aCart = self::updateCartCookie($iUserID);
        }
        return $aCart;
    }

    //清空购物车数据
    public static function flushCart($iUserID = 0)
    {
        if (!empty($iUserID)) {
            $sSql = 'UPDATE ' . self::TABLE_NAME . ' SET iStatus=0 WHERE iUserID=' . $iUserID;
            self::query($sSql);
        }
        Util_Cookie::delete(Yaf_G::getConf('authchart', 'cookie'));
    }

    //删除购物车数据
    public static function deleteCart($iProductID, $iUserID = 0)
    {
        if (!empty($iUserID)) {
            $aCartParam['iUserID'] = $iUserID;
            $aCartParam['iStatus'] = 1;
            $aCartParam['iProductID'] = $iProductID;
            $aCart = self::getRow($aCartParam);
            if (!empty($aCart)) {
                $aCartUpdateParam['iStatus'] = 0;
                $aCartUpdateParam['iAutoID'] = $aCart['iAutoID'];
                if (self::updData($aCartUpdateParam)) {
                    //更新cookie
                    self::updateCartCookie($iUserID);
                    return 1;
                }
            }
            return 0;
        } else {
            $aCart = Util_Cookie::get(Yaf_G::getConf('authchart', 'cookie'));
            if (!empty($aCart)) {
                if (!empty($aCart[$iProductID])) {
                    unset($aCart[$iProductID]);
                    Util_Cookie::set(Yaf_G::getConf('authchart', 'cookie'), $aCart);
                    return 1;
                }
            }
            return 0;
        }
    }

    //加入购物车
    public static function addCart($iProductID, $iUserID = 0)
    {
        if (!empty($iUserID)) {
            $aCartParam['iUserID'] = $iUserID;
            $aCartParam['iStatus'] = 1;
            $aCartParam['iProductID'] = $iProductID;
            $aCart = self::getRow($aCartParam);
            if (!empty($aCart)) {
                $aCartUpdateParam['iStatus'] = 1;
                $aCartUpdateParam['iAutoID'] = $aCart['iAutoID'];
                $aCartUpdateParam['iNum'] = $aCart['iNum'] + 1;
                if (self::updData($aCartUpdateParam)) {
                    //更新cookie
                    $aCart = self::updateCartCookie($iUserID);
                    return count($aCart);
                }
                return 0;
            } else {
                $aCartParam['iNum'] = 1;
                if (self::addData($aCartParam)) {
                    //更新cookie
                    $aCart = self::updateCartCookie($iUserID);
                    return count($aCart);
                }
                return 0;
            }
        } else {
            $aCart = Util_Cookie::get(Yaf_G::getConf('authchart', 'cookie'));
            if (empty($aCart)) {
                $aCookieData[$iProductID]['iNum'] = 1;
                $aCookieData[$iProductID]['iProductID'] = $iProductID;
                Util_Cookie::set(Yaf_G::getConf('authchart', 'cookie'), $aCookieData);
                return count($aCookieData);
            } else {
                if (!empty($aCart[$iProductID])) {
                    $aCart[$iProductID]['iNum'] += 1;
                } else {
                    $aCart[$iProductID]['iNum'] = 1;
                    $aCart[$iProductID]['iProductID'] = $iProductID;
                }
                Util_Cookie::set(Yaf_G::getConf('authchart', 'cookie'), $aCart);
                return count($aCart);
            }
        }
    }
}