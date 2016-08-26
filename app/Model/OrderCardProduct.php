<?php

class Model_OrderCardProduct extends Model_Base
{
    const TABLE_NAME = 't_order_card_product_new';
    const STAUSCAN = 1;//可用
    const STATUSNOCAN = 2;//不可用
    const STAUSHASUSE = 3;//已用

    const RESERVEPLAT_PC = 1;//pc预约
    const RESERVEPLAT_WX = 2;//微信预约
    /**
     * 获取多个卡里包含的所有产品并去重
     * @param $iCardIDs 数组或逗号隔开的字符串
     */
    public static function getProductByCardIDs($iCardIDs)
    {
        $iCardIDs = is_array($iCardIDs) ? implode(',', $iCardIDs) : $iCardIDs;
        $sSql = 'SELECT iProductID FROM ' . self::TABLE_NAME . ' WHERE iStatus = 1 AND iCardID IN (' . $iCardIDs . ') GROUP BY iProductID';
        return self::query($sSql, 'col');
    }

    /**
     * 获取某卡里的某个产品
     * @param $iCardID
     * @param $iProductID
     * @return int
     */
    public static function getCardProduct($iCardID, $iProductID)
    {
        $OrderCardProduct['iCardID'] = $iCardID;
        $OrderCardProduct['iProductID'] = $iProductID;
        $OrderCardProduct['iStatus >'] = 0;
        return self::getRow($OrderCardProduct);
    }

    /**
     * 把某卡下所有置为不可用
     */
    private static function _updateCardProductStatus1($iCardID)
    {
        $sSql = 'UPDATE '.self::TABLE_NAME.' SET iStatus='.self::STATUSNOCAN.' WHERE iCardID='.$iCardID;
        return self::query($sSql);
    }

    /**
     * 把某卡下指定产品置为已用
     */
    private static function _updateCardProductStatus2($iCardID,$iProductID)
    {
        $sSql = 'UPDATE '.self::TABLE_NAME.' SET iStatus=3 WHERE iCardID='.$iCardID.' AND iProductID='.$iProductID;
        return self::query($sSql);
    }

    /**
     * 把某卡下所有置为可用
     */
    private static function _updateCardProductStatus3($iCardID)
    {
        $sSql = 'UPDATE '.self::TABLE_NAME.' SET iStatus = 1 AND iUseStatus=0 WHERE iCardID='.$iCardID;
        return self::query($sSql);
    }

    /**
     * 预约成功或取消预约后更改卡的状态（or的情况下使用）
     * @param $iCardID
     * @param $iProductID
     * @param int $iType 0:预约成功，1：取消预约
     * @return array|int|mixed
     */
    public static function updateCardProductStatus($iCardID,$iProductID,$iType = 0)
    {
        if (empty($iType)) {
            if(self::_updateCardProductStatus1($iCardID)) {
                return self::_updateCardProductStatus2($iCardID,$iProductID);
            }
        } else {
            return self::_updateCardProductStatus3($iCardID);
        }
        return 0;
    }

    /**
     * 生成卡包含的产品处理
     * @param $iCardID
     * @param $iProductID
     * @param $sProductName
     * @param $iOPID
     * @param $iOrderID
     * @param array $aParam
     * @return int
     */
    public static function initCardProduct($iCardID, $iProductID, $sProductName, $iOPID, $iOrderID, $aParam = array())
    {
        $OrderCardProduct['iCardID'] = $iCardID;
        $OrderCardProduct['iProductID'] = $iProductID;
        $OrderCardProduct['sProductName'] = $sProductName;
        $OrderCardProduct['iOPID'] = $iOPID;
        $OrderCardProduct['iOrderID'] = $iOrderID;
        //以上为必填的
        if (!empty($aParam['iPayStatus'])) {//确定支付后，该卡为已支付状态
            $OrderCardProduct['iPayStatus'] = $aParam['iPayStatus'];
        }
        if (!empty($aParam['iStatus'])) {
            $OrderCardProduct['iStatus'] = $aParam['iStatus'];
        }
        return self::addData($OrderCardProduct);
    }

    /**
     * 体检数据核对
     * @param unknown $aParam
     * @param unknown $iPage
     * @param string $sOrder
     * @param number $iPageSize
     * @param string $sUrl
     * @param unknown $aArg
     * @return Ambigous <NULL, boolean, string, multitype:, array/string, int/false, number, unknown, unknown>
     */
    public static function getCheckList($aParam, $iPage, $sOrder = 'iUpdateTime DESC', $iPageSize = 20, $sUrl = '', $aArg = array())
    {
        $sWhere = self::_buildWhere($aParam);
        $iPage = max($iPage, 1);
        $sOrder = 'ORDER BY ' . $sOrder;
        $sLimit = 'LIMIT ' . ($iPage - 1) * $iPageSize . ',' . $iPageSize;

        $sSQL = 'SELECT p.*, c.sRealName as sRealName, c.sMobile, card.iUserID, card.iCreateUserID, card.iCreateUserType, card.sStartDate, card.sEndDate FROM ' . self::TABLE_NAME . ' AS p
                LEFT JOIN '. Model_OrderCard::TABLE_NAME. ' as card on p.iCardID = card.iAutoID
    			 LEFT JOIN '.Model_Customer::TABLE_NAME. ' AS c ON card.iUserID=c.iUserID
    			 ' . $sWhere . ' ' . $sOrder . ' ' . $sLimit;

        $aRet['aList'] = self::getOrm()->query($sSQL);

        if(!empty($aRet['aList'])) {
            foreach($aRet['aList'] as &$card) {
                $card['sCompanyName'] = '';
                $card['sCompanyCode'] = '';
                $card['sUserName'] = '';
                $card['sEmail'] = "";

                if(!empty($card['iUserID']) && !empty($card['iCreateUserID']) && 2 == intval($card['iCreateUserType'])) {
                    $company = Model_CustomerCompany::getRow(['where' => ['iUserID' => $card['iUserID'], 'iCreateUserID' => $card['iCreateUserID']]]);
                    if(!empty($company)) {
                        $card['sCompanyName'] = $company['sCompanyName'];
                        $card['sCompanyCode'] = $company['sCompanyCode'];
                        $card['sUserName'] = $company['sUserName'];
                        $card['sEmail'] = $company['sEmail'];
                    }
                }
            }
        }

        if ($iPage == 1 && count($aRet['aList']) < $iPageSize) {
            $aRet['iTotal'] = count($aRet['aList']);
            $aRet['aPager'] = null;
        } else {
            unset($aParam['limit'], $aParam['order']);
            $sCntSQL = 'SELECT count(*) as total FROM ' . self::TABLE_NAME . ' AS p
                        LEFT JOIN '. Model_OrderCard::TABLE_NAME. ' as card on p.iCardID = card.iAutoID
    			        LEFT JOIN '.Model_Customer::TABLE_NAME. ' AS c ON card.iUserID=c.iUserID
    			        ' . $sWhere;
            $ret = self::getOrm()->query($sCntSQL);
            $aRet['iTotal'] = $ret[0]['total'];
            $aRet['aPager'] = Util_Page::getPage($aRet['iTotal'], $iPage, $iPageSize, '', $aParam); // update by cjj 2015-02-13 分页增加query 参数
        }

        return $aRet;
    }


    public static function getErrorList($aParam, $iPage, $sOrder = 'iUpdateTime DESC', $iPageSize = 20, $sUrl = '', $aArg = array())
    {
        $sWhere = self::_buildWhere($aParam);
        $iPage = max($iPage, 1);
        $sOrder = 'ORDER BY ' . $sOrder;
        $sLimit = 'LIMIT ' . ($iPage - 1) * $iPageSize . ',' . $iPageSize;

        $sSQL = 'SELECT p.*,s.sName as sStroeName,s.sAddress, c.sRealName as sUserName,card.iUserID, card.iCreateUserID, card.iCreateUserType FROM ' . self::TABLE_NAME . ' AS p
                 LEFT JOIN '. Model_OrderCard::TABLE_NAME. ' card on p.iCardID = card.iAutoID
    			 LEFT JOIN t_store AS s ON s.iStoreID=p.iStoreID
    			 LEFT JOIN '.Model_Customer::TABLE_NAME. ' AS c ON card.iUserID=c.iUserID
    			' . $sWhere . ' ' . $sOrder . ' ' . $sLimit;

        $aRet['aList'] = self::query($sSQL);

        if(!empty($aRet['aList'])) {
            foreach($aRet['aList'] as &$row) {
                $row['sCoName'] = '';

                if(!empty($row['iCreateUserType']) && 2 == intval($row['iCreateUserType'])) {
                    $company = Model_CustomerCompany::getRow(['where' => ['iUserID' => $row['iUserID'], 'iCreateUserID' => $row['iCreateUserID']]]);
                    if(!empty($company)) {
                        $row['sCoName'] = $company['sCompanyName'];
                    }
                }
            }
        }

        if ($iPage == 1 && count($aRet['aList']) < $iPageSize) {
            $aRet['iTotal'] = count($aRet['aList']);
            $aRet['aPager'] = null;
        } else {
            unset($aParam['limit'], $aParam['order']);
            $sCntSQL = 'SELECT count(*) total FROM ' . self::TABLE_NAME . ' AS p
                 LEFT JOIN '. Model_OrderCard::TABLE_NAME. ' card on p.iCardID = card.iAutoID
    			 LEFT JOIN t_store AS s ON s.iStoreID=p.iStoreID
    			 LEFT JOIN '.Model_Customer::TABLE_NAME. ' AS c ON card.iUserID=c.iUserID
    			' . $sWhere;

            $ret = self::query($sCntSQL);
            $aRet['iTotal'] = $ret[0]['total'];
            $aRet['aPager'] = Util_Page::getPage($aRet['iTotal'], $iPage, $iPageSize, '', $aParam); // update by cjj 2015-02-13 分页增加query 参数
        }

        return $aRet;
    }

    /**
     * 按分页取预给信息数据
     * @param unknown $aParam
     * @param unknown $iPage
     * @param string $sOrder
     * @param number $iPageSize
     * @param string $sUrl
     * @param unknown $aArg
     * @return Ambigous <NULL, boolean, string, multitype:, array/string, int/false, number, unknown, unknown>
     */
    public static function getStat1($aParam, $iPage, $sOrder = 'iUpdateTime DESC', $iPageSize = 20, $sUrl = '', $aArg = array())
    {
        $sWhere = self::_buildWhere($aParam);
        $iPage = max($iPage, 1);
        $sOrder = 'ORDER BY ' . $sOrder;
        $sLimit = 'LIMIT ' . ($iPage - 1) * $iPageSize . ',' . $iPageSize;

        $sSQL = 'SELECT p.*,s.sName as sStroeName,s.sAddress, c.sRealName as sUserName,card.iUserID, card.iCreateUserID, card.iCreateUserType FROM ' . self::TABLE_NAME . ' AS p
                 LEFT JOIN '. Model_OrderCard::TABLE_NAME. ' card on p.iCardID = card.iAutoID
    			 LEFT JOIN t_store AS s ON s.iStoreID=p.iStoreID
    			 LEFT JOIN '.Model_Customer::TABLE_NAME. ' AS c ON card.iUserID=c.iUserID
    			' . $sWhere . ' ' . $sOrder . ' ' . $sLimit;

        $aRet['aList'] = self::query($sSQL);

        if(!empty($aRet['aList'])) {
            foreach($aRet['aList'] as &$row) {
                $row['sCoName'] = '';

                if(!empty($row['iCreateUserType']) && 2 == intval($row['iCreateUserType'])) {
                    $company = Model_CustomerCompany::getRow(['where' => ['iUserID' => $row['iUserID'], 'iCreateUserID' => $row['iCreateUserID']]]);
                    if(!empty($company)) {
                        $row['sCoName'] = $company['sCompanyName'];
                    }
                }
            }
        }

        if ($iPage == 1 && count($aRet['aList']) < $iPageSize) {
            $aRet['iTotal'] = count($aRet['aList']);
            $aRet['aPager'] = null;
        } else {
            unset($aParam['limit'], $aParam['order']);
            $sCntSQL = 'SELECT count(*) total FROM ' . self::TABLE_NAME . ' AS p
                 LEFT JOIN '. Model_OrderCard::TABLE_NAME. ' card on p.iCardID = card.iAutoID
    			 LEFT JOIN t_store AS s ON s.iStoreID=p.iStoreID
    			 LEFT JOIN '.Model_Customer::TABLE_NAME. ' AS c ON card.iUserID=c.iUserID
    			' . $sWhere;

            $ret = self::query($sCntSQL);
            $aRet['iTotal'] = $ret[0]['total'];
            $aRet['aPager'] = Util_Page::getPage($aRet['iTotal'], $iPage, $iPageSize, '', $aParam); // update by cjj 2015-02-13 分页增加query 参数
        }

        return $aRet;
    }

    /**
     * 构建搜索条件
     * @param unknown $aParam
     */
    public static function _buildWhere($aParam)
    {
        $aWhere = array();
        $aWhere[] = 'p.iStatus not in(0, 2)';

        if (isset($aParam['iAutoID']) && !empty($aParam['iAutoID'])) {
            $aWhere[] = 'card.iAutoID=' . $aParam['iAutoID'];
        }
        if (isset($aParam['iAutoID NOT IN']) && !empty($aParam['iAutoID NOT IN'])) {
            $iAutoID = implode(',', $aParam['iAutoID NOT IN']);
            $aWhere[] = 'card.iAutoID NOT IN (' . $iAutoID . ')';
        }
        if (!empty($aParam['iUserID'])) {
            $aWhere[] = 'card.iUserID=' . intval($aParam['iUserID']) . '';
        }

        if (!empty($aParam['sRealName'])) {
            $aWhere[] = 'c.sRealName="' . addslashes($aParam['sRealName']) . '"';
        }

        if (! empty($aParam['sUserName'])) {
            $customerCompany = Model_CustomerCompany::getRow(['where' => ['sUserName' => addslashes($aParam['sUserName'])]]);
            if(!empty($customerCompany)) {
                $aWhere[] = 'c.iUserID=' . $customerCompany['iUserID'];
            }
        }
        //公司编号
        if (! empty($aParam['sCoCode'])) {
            $where = array('sCompanyCode' => addslashes($aParam['sCoCode']));
            $userIDs = Model_CustomerCompany::getCol(['where' => $where, 'group' => 'iUserID'], 'iUserID');

            if($userIDs) {
                $aWhere[] = 'card.iCreateUserID in (' . implode(",", $userIDs) . ') and card.iCreateUserType = 2';
            }else {//查不到情况
                $aWhere[] = 'card.iCreateUserID = -99';
            }
        }
        //公司名称
        if (! empty($aParam['sCoName'])) {
            $where = array('sCompanyName' => addslashes($aParam['sCoCode']));
            $userIDs = Model_CustomerCompany::getCol(['where' => $where, 'group' => 'iUserID'], 'iUserID');
            if($userIDs) {
                $aWhere[] = 'card.iCreateUserID in (' . implode(",", $userIDs) . ') and card.iCreateUserType = 2';
            }else {//查不到情况
                $aWhere[] = 'card.iCreateUserID = -99';
            }
        }


        if (isset($aParam['iBookStatus']) && -1 != intval($aParam['iBookStatus'])) {
            $aWhere[] = 'p.iBookStatus=' . intval($aParam['iBookStatus']) . '';
        }
        if (! empty($aParam['iPreStatus'])) {
            $aWhere[] = 'p.iPreStatus=' . intval($aParam['iPreStatus']) . '';
        }
        if (isset($aParam['iStatus']) && -1 != intval($aParam['iStatus'])) {
            $aWhere[] = 'card.iStatus=' . intval($aParam['iStatus']) . '';
        }
        if (!empty($aParam['iPhysicalType'])) {
            $aWhere[] = 'card.iPhysicalType=' . intval($aParam['iPhysicalType']) . '';
        }
        if (! empty($aParam['iPhysicalTime'])) {
            $iPhysicalTime = strtotime($aParam['iPhysicalTime']);
            $sPhysicalTime = date("Y-m-d", $iPhysicalTime);

            $aWhere[] = 'p.iOrderTime >=' . strtotime($sPhysicalTime. "00:00:00") . '';
            $aWhere[] = 'p.iOrderTime <=' . strtotime($sPhysicalTime. "23:59:59") . '';
        }
        if (! empty($aParam['iCityID'])) {
            $aWhere[] = 's.iCityID=' . intval($aParam['iCityID']) . '';
        }
        if (! empty($aParam['iSupplierID'])) {
            $aWhere[] = 's.iSupplierID=' . intval($aParam['iSupplierID']) . '';
        }
        if (! empty($aParam['iPlat'])) {
            $aWhere[] = 'p.iPlat=' . intval($aParam['iPlat']) . '';
        }

        if (-1 != intval($aParam['iSendMsg'])) {
            $aWhere[] = 'p.iSendMsg=' . intval($aParam['iSendMsg']) . '';
        }
        if (-1 != intval($aParam['iSendEMail'])) {
            $aWhere[] = 'p.iSendEMail=' . intval($aParam['iSendEMail']) . '';
        }
        if (! empty($aParam['sIdentityCard'])) {
            $aWhere[] = 'c.sIdentityCard= "' . addslashes($aParam['sIdentityCard']) . '"';
        }
        if (! empty($aParam['sCardCode'])) {
            $aWhere[] = 'card.sCardCode= "' . addslashes($aParam['sCardCode']) . '"';
        }

        //操作时间
        if (! empty($aParam['sOptStartDate'])) {
            $aWhere[] = 'p.iCreateTime >=' . strtotime($aParam['sOptStartDate']) . '';
        }
        if (! empty($aParam['sOptEndDate'])) {
            $aWhere[] = 'p.iCreateTime <=' . strtotime($aParam['sOptEndDate']) . '';
        }

        //是否显示体检卡
        if(!empty($aParam['iIsShow'])) {
            $aWhere[] = "card.iOrderType in (3, 4)";
        }

        if (empty($aWhere)) {
            $sWhere = '';
        } else {
            $sWhere = ' WHERE ' . join(' AND ', $aWhere);
        }

        return $sWhere;
    }

    //批量作废卡
    public static function batchDiscard ($sCardIDs) {
        $sqlproduct = "update ". self::TABLE_NAME . " set iBookStatus = 4 where iAutoID in (". $sCardIDs. ") and iBookStatus not in(2, 4, 5)";

        self::query($sqlproduct);
    }

    //获取退款信息
    public static function getRefund($aParam, $iPage, $sOrder = 'iUpdateTime DESC', $iPageSize = 20)
    {
        $iPage = max(1, $iPage);
        $sOrder = ' ORDER BY ' . $sOrder;
        $sLimit = ' LIMIT ' . ($iPage - 1) * $iPageSize . ',' . $iPageSize;

        $aWhere = array();
        //已处理未处理
//		if (isset($aParam['iType']) && ! empty($aParam['iType'])) {
//			$aWhere[] = 'p.iType=' . intval($aParam['iType']);
//		}
        if (!empty($aParam['sUserName'])) {
            $aWhere[] = 'c.sRealName=' . addslashes($aParam['sRealName']) . '';
        }
        if (!empty($aParam['sIdentityCard'])) {
            $aWhere[] = 'c.sIdentityCard="' . addslashes($aParam['sIdentityCard']) . '"';
        }
        if (!empty($aParam['sOrderCode'])) {
            $aWhere[] = 'oi.sOrderCode="' . addslashes($aParam['sOrderCode']) . '"';
        }

        if (empty($aWhere)) {
            $sWhere = ' ';
        } else {
            $sWhere = ' ' . join(' AND ', $aWhere);
        }

        $sSQL = "select ocp.*, c.sRealName, c.sIdentityCard, oi.sOrderCode, oi.sPayOrderID from " . SELF::TABLE_NAME . " AS ocp".
                " left join ". Model_OrderCard::TABLE_NAME. " as card on ocp.iCardID = card.iAutoID".
                " left join ". Model_Customer::TABLE_NAME. " as c on c.iUserID = card.iUserID" .
                " left join ". Model_OrderInfo::TABLE_NAME. " as oi on card.iOrderID = oi.iOrderID".
                " where ocp.iBookStatus = 3";

        if (!empty($aParam['type'])) {
            $sSQL .= ' and ocp.iCheckRefund = 1';
        }else {
            $sSQL .= " and ocp.iLastProductID != '' and ocp.iCheckRefund = 0";
        }

        if(!empty($where)) {
            $sSQL .= " and ". $sWhere . $sOrder . $sLimit;
        }else {
            $sSQL .= $sWhere . $sOrder . $sLimit;
        }

        $aRet['aList'] = self::query($sSQL);

        if(!empty($aRet['aList'])) {
            foreach($aRet['aList'] as &$card) {
                $curProduct = Model_Product::getDetail($card['iProductID']);
                $preProduct = Model_Product::getDetail($card['iLastProductID']);
                $card['refund'] = 0;

                if(!empty($curProduct) && !empty($preProduct)) {
                    if(1 == intval($card['iSex'])){//男
                        $card['refund'] = $curProduct['iManPrice'] - $preProduct['iManPrice'];
                    }else if(2 == intval($card['iSex'])){//女未婚
                        $card['refund'] = $curProduct['iWomanPrice1'] - $preProduct['iWomanPrice1'];
                    }else if(3 == intval($card['iSex'])){//女已婚
                        $card['refund'] = $curProduct['iWomanPrice2'] - $preProduct['iWomanPrice2'];
                    }
                }
            }
        }

        if ($iPage == 1 && count($aRet['aList']) < $iPageSize) {
            $aRet['iTotal'] = count($aRet['aList']);
            $aRet['aPager'] = null;
        } else {
            unset($aParam['limit'], $aParam['order']);
            $sCntSQL = "select count(*) as total from " . SELF::TABLE_NAME . " AS ocp".
                " left join ". Model_OrderCard::TABLE_NAME. " as card on ocp.iCardID = card.iAutoID".
                " left join ". Model_Customer::TABLE_NAME. " as c on c.iUserID = card.iUserID" .
                " left join ". Model_OrderInfo::TABLE_NAME. " as oi on card.iOrderID = oi.iOrderID".
                " where ocp.iBookStatus = 3 and ocp.iLastProductID != '' ";
            if(!empty($sWhere)) {
                $sCntSQL .= " and ". $sWhere;
            }

            $ret = self::getOrm()->query($sCntSQL);
            $aRet['iTotal'] = $ret[0]['total'];
            $aRet['aPager'] = Util_Page::getPage($aRet['iTotal'], $iPage, $iPageSize, '', $aParam);
        }

        return $aRet;
    }

    //通过order_card_product表主键获得相关卡人人员信息
    public static function getCardinfoByIDs($iocpIDs){
        $sql = "select ocp.*, c.sRealName, c.iUserID, c.sMobile, card.iCreateUserID, card.sCardCode, card.sStartDate, card.sEndDate from ". self::TABLE_NAME. " as ocp".
                " left join ". Model_OrderCard::TABLE_NAME. " as card on ocp.iCardID = card.iAutoID".
                " left jion ". Model_Customer::TABLE_NAME. " as c on card.iUserID = c.iUserID".
                " where ocp.iAutoID in($iocpIDs)";

        return self::query($sql);
    }

    /*
     * 根据cardid和产品id，获取体检综合信息
     */
    public static function getCardProductInfo($iCardID){
        $sql = "select ocp.*, store.sName, store.sAddress, p.iProductID, p.sProductCode, p.sProductName, c.iSex, c.iMarriage from ". self::TABLE_NAME.
            " as ocp left join ".Model_Store::TABLE_NAME. " as store on ocp.iStoreID = store.iStoreID".
            " left join ". Model_Product::TABLE_NAME. " as p on ocp.iProductID = p.iProductID".
            " left join ". Model_OrderCard::TABLE_NAME. " as card on ocp.iCardID = card.iAutoID".
            " left join ". Model_Customer::TABLE_NAME. " as c on card.iUserID = c.iUserID".
            " where ocp.iCardID = $iCardID and ocp.iStatus in(1, 3)";

        return self::query($sql);
    }


    /**
     * 获取卡下的所有产品
     * @param  [int] $iCardID [description]
     * @return [array]
     */
    public static function getAllCardProduct ($iCardID)
    {
        return self::getAll(['where' => [
            'iCardID' => $iCardID,
            'iStatus' => 1
        ]]);
    }

    /**
     * 预约成功通知
     * @return [type] [description]
     */
    public static function sendMailMsg ($iCPID, $iUserID = 0)
    {   
        $content = Yaf_G::getConf('ordermail', 'physical');
        $msg  = Yaf_G::getConf('ordermsg', 'physical');

        $aCP = Model_OrderCardProduct::getDetail($iCPID);
        $aStore = Model_Store::getDetail($aCP['iStoreID']);

        if (!$iUserID) {
            $aCard = Model_OrderCard::getDetail($aCP['iCardID']);
            $iUserID = $aCard['iUserID'];
        }
        $aCustomer = Model_CustomerNew::getDetail($iUserID);    
        
        $content = preg_replace('/\【员工姓名\】/', $aCustomer['sRealName'], $content);
        $content = preg_replace('/\【体检日期\】/', date('Y-m-d', $aCP['iOrderTime']), $content);
        $content = preg_replace('/\【体检套餐\】/', $aCP['sProductName'], $content);
        $content = preg_replace('/\【体检地点\】/', $aStore['sAddress'], $content);
        $content = preg_replace('/\【体检门店\】/', $aStore['sName'], $content);
        $content = preg_replace('/\【营业时间\】/', $aStore['sWorktime'], $content);
        $content = preg_replace('/\【附近交通\】/', $aStore['sTraffic'], $content);
        $mailRes = Util_Mail::send($aCustomer['sEmail'], '体检预约成功通知', $content);

        $msg = preg_replace('/\【员工姓名\】/', $aCustomer['sRealName'], $msg);
        $msg = preg_replace('/\【体检日期\】/', date('Y-m-d', $aCP['iOrderTime']), $msg);
        $msg = preg_replace('/\【体检套餐\】/', $aCP['sProductName'], $msg);
        $msg = preg_replace('/\【体检门店\】/', $aStore['sName'], $msg);
        $msg = preg_replace('/\【体检地点\】/', $aStore['sAddress'], $msg);
        $smsRes = Sms_Joying::sendBatch($aCustomer['sMobile'], $msg);
    }


    /**
     * 体检报告通知
     * @return [type] [description]
     */
    public static function sendReportMailMsg ($aCard)
    {   
        $content = Yaf_G::getConf('reportmail', 'physical');
        $msg  = Yaf_G::getConf('reportmsg', 'physical');

        $aCustomer = Model_CustomerNew::getDetail($aCard['iUserID']);
    
        $content = preg_replace('/\【员工姓名\】/', $aCustomer['sRealName'], $content);
        $content = preg_replace('/\【体检卡号\】/', $aCard['sCardCode'], $content);
        $mailRes = Util_Mail::send($aCustomer['sEmail'], '体检报告已出通知', $content);

        $msg = preg_replace('/\【员工姓名\】/', $aCustomer['sRealName'], $msg);
        $msg = preg_replace('/\【体检卡号\】/', $aCard['sCardCode'], $msg);
        $smsRes = Sms_Joying::sendBatch($aCustomer['sMobile'], $msg);
    }
    
}