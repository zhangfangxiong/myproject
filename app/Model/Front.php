<?php

/**
 *
 * @author xiejinci
 *
 */
class Model_Front extends Model_Base
{

    const TABLE_NAME = 't_front';

    /**
     * 取得Class信息
     * 
     * @param unknown $sClass            
     * @param string $sField            
     * @throws Yaf_Exception
     * @return unknown
     */
    public static function getClass ($sClass, $sField = null)
    {
        if (empty($sClass)) {
            $aRet = self::query("SELECT * FROM t_front_setting WHERE iStatus=1 LIMIT 1", 'row');
            $sClass = $aRet['sClass'];
        } else {
            $aRet = self::query("SELECT * FROM t_front_setting WHERE sClass='$sClass' AND iStatus=1 LIMIT 1", 'row');
            if (empty($aRet)) {
                throw new Yaf_Exception(__CLASS__ . '::' . __FUNCTION__ . " $sClass not exists!");
            }
        }
        
        if ($aRet['sSource']) {
            $aTarget = array();
            if (strlen($aRet['sTitle']) > 2) {
                $aTemp = explode('|', $aRet['sTitle']);
                $aTarget[] = '#sSourceID=' . $aTemp[0];
                $aRet['sValue'] = $aTemp[1];
            }
            if (strlen($aRet['sImage']) > 2) {
                $aTemp = explode('|', $aRet['sImage']);
                $aTarget[] = '#sImage=' . $aTemp[0];
                $aTarget[] = '#sImageShow=' . $aTemp[1];
            }
            if (strlen($aRet['sUrl']) > 2) {
                $aTarget[] = '#sUrl=' . $aRet['sUrl'];
            }
            if (strlen($aRet['sDiy1']) > 2) {
                $aTemp = explode('|', $aRet['sDiy1']);
                if (count($aTemp) == 2) {
                    $aRet['sDiy1'] = $aTemp[0];
                    $aTarget[] = '#sDiy1=' . $aTemp[1];
                }
            }
            if (strlen($aRet['sDiy2']) > 2) {
                $aTemp = explode('|', $aRet['sDiy2']);
                if (count($aTemp) == 2) {
                    $aRet['sDiy2'] = $aTemp[0];
                    $aTarget[] = '#sDiy2=' . $aTemp[1];
                }
            }
            if (strlen($aRet['sDiy3']) > 2) {
                $aTemp = explode('|', $aRet['sDiy3']);
                if (count($aTemp) == 2) {
                    $aRet['sDiy3'] = $aTemp[0];
                    $aTarget[] = '#sDiy3=' . $aTemp[1];
                }
            }
            if (strlen($aRet['sRemark']) > 2) {
                $aTarget[] = '#sRemark=' . $aRet['sRemark'];
            }
            if (strlen($aRet['sColor']) > 2) {
                $aTarget[] = '#sColor=' . $aRet['sColor'];
            }
        }
        $aRet['sTarget'] = join('|', $aTarget);
        
        if (empty($sField)) {
            return $aRet;
        } else {
            return $aRet[$sField];
        }
    }

    /**
     * 取得某个类别的所有类型
     *
     * @param string $sClass            
     * @return multitype:
     */
    public static function getFronts ($sClass, $iCityID = 0)
    {
        return self::getAll(array(
            'where' => array(
                'sClass' => $sClass,
                'iCityID' => $iCityID,
                'iStatus' => 1
            ),
            'order' => 'iOrder ASC'
        ));
    }

    /**
     * 取得下一个Order
     *
     * @param unknown $iParentID            
     * @return number
     */
    public static function getNextOrder ($sClass, $iCityID = 0)
    {
        $iOrder = self::getOne(array(
            'where' => array(
                'sClass' => $sClass,
                'iCityID' => $iCityID,
                'iStatus' => 1
            ),
            'order' => 'iOrder DESC'
        ), 'iOrder');
        if ($iOrder) {
            $iOrder += 1;
        } else {
            $iOrder = 0;
        }
        return $iOrder;
    }

    /**
     * 改变顺序
     *
     * @param unknown $aFType            
     * @param unknown $iDirect            
     */
    public static function changeOrder ($aFType, $iDirect)
    {
        $aParam = array(
            'where' => array(
                'iStatus' => 1,
                'sClass' => $aFType['sClass'],
                'iCityID' => $aFType['iCityID'],
                'iAutoID !=' => $aFType['iAutoID']
            )
        );
        if ($iDirect == 0) {
            $iAdd = - 1;
            $aParam['where']['iOrder <='] = $aFType['iOrder'];
            $aParam['order'] = 'iOrder DESC';
        } else {
            $iAdd = 1;
            $aParam['where']['iOrder >='] = $aFType['iOrder'];
            $aParam['order'] = 'iOrder ASC';
        }
        $aList = self::getAll($aParam);
        if (empty($aList)) {
            return 0;
        }
        
        $aTType = $aList[0];
        if ($aTType['iOrder'] == $aFType['iOrder']) {
            $iOrder = $aFType['iOrder'] + $iAdd;
            self::updData(array(
                'iAutoID' => $aFType['iAutoID'],
                'iOrder' => $iOrder
            ));
            foreach ($aList as $k => $v) {
                if ($k == 0) {
                    continue;
                }
                $iOrder += $iAdd;
                self::updData(array(
                    'iAutoID' => $v['iAutoID'],
                    'iOrder' => $iOrder
                ));
            }
        } else {
            self::updData([
                'iAutoID' => $aFType['iAutoID'],
                'iOrder' => $aTType['iOrder']
            ]);
            self::updData([
                'iAutoID' => $aTType['iAutoID'],
                'iOrder' => $aFType['iOrder']
            ]);
        }
        return count($aList);
    }
}