<?php

/**
 * 供应商处理类
 */
class Model_Supplier extends Model_Base
{
    const TABLE_NAME = '';

    const BAIDUHOLIDAYAPIURL = 'http://apis.baidu.com/xiaogg/holiday/holiday';//节假日api

    //获取所有供应商
    public static function getSupplierList()
    {
        $where = array(
            'sClass' => 'supplier',
            'iStatus' => 1
        );
        $aSupplier = Model_Type::getAll($where);
        return $aSupplier;
    }

    //根据标示符获取供应商
    public static function getSupplierByCode($sSupplierCode)
    {
        $where = array(
            'sClass' => 'supplier',
            'iStatus' => 1,
            'sCode' => $sSupplierCode
        );
        $aSupplier = Model_Type::getRow($where);
        return $aSupplier;
    }

    /**
     * 取消预约
     * @param $sSupplierCode
     * @param $aStoreCode
     * @param $aCardProduct
     * @param $iPhysicalType
     * @return array|int
     */
    public static function cancalReserve($sSupplierCode, $aStoreCode, $aCardProduct, $iPhysicalType = 1)
    {
        $aHasApiConf = Yaf_G::getConf('aHasApi', 'suppliers');
        $sClassName = $aHasApiConf[$sSupplierCode]['classname'];
        $sUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/api/' . $sClassName . '/cancelorder/';
        $aParam['orderid'] = $aCardProduct['sApiReserveOrderID'];
        $aParam['cardnumber'] = $aCardProduct['sApiCardID'];
        $aParam['hospid'] = $aStoreCode;
        $aParam['iPhysicalType'] = $iPhysicalType;

        $sUrl .= "?data=" . json_encode($aParam);
        $aData = self::curl($sUrl);
        $aData = json_decode($aData, true);
        //$aData['code'] = 1;
        if (!empty($aData['code'])) {
            $aCardProductParam['iAutoID'] = $aCardProduct['iAutoID'];
            $aCardProductParam['sApiReserveOrderID'] = '';
            if ($sSupplierCode != 'cimin') {//慈珉的也要单独处理,也日了狗了
                $aCardProductParam['sApiCardID'] = '';//卡号不能清，可以共用
            }
            Model_OrderCardProduct::updData($aCardProductParam);
            return 1;//取消预约成功
        } else {
            return 0;//取消预约失败
        }
    }

    /**
     * 预约改期
     * @param $sSupplierCode
     * @param $aStoreCode
     * @param $aCardProduct
     * @return array|int
     */
    public static function reReserveDate($sSupplierCode, $aStoreCode, $sProductCode, $sDate, $aCard, $aCardProduct)
    {
        $aHasApiConf = Yaf_G::getConf('aHasApi', 'suppliers');
        $sClassName = $aHasApiConf[$sSupplierCode]['classname'];
        if ($sSupplierCode == 'ruici') {//瑞慈要单独处理,日了狗了
            $sUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/api/' . $sClassName . '/modifymentDate/';
            $aCustomer = Model_Customer::getDetail($aCard['iUserID']);
            $aUserInfoParam['Mobile'] = $aCustomer['sMobile'];
            $aUserInfoParam['CertNo'] = $aCustomer['sIdentityCard'];
            $aUserInfoParam['Name'] = $aCustomer['sRealName'];
            $aUserInfoParam['Birthday'] = $aCustomer['sBirthDate'];
            $aUserInfoParam['Address'] = '';
            $aUserInfoParam['Sex'] = $aCustomer['iSex'];
            $aUserInfoParam['isMarry'] = $aCustomer['iMarriage'];
            $aUserInfoParam['CertType'] = $aCustomer['iCardType'];
            $aUserInfoParam['Remark'] = '';
            $aUserInfoParam['Department'] = '';
            $aParam['packageIDs'] = $sProductCode;
            $aParam['ReserID'] = $aCardProduct['sApiReserveOrderID'];
            $aParam['institutionID'] = $aStoreCode;
            $aParam['markDate'] = $sDate;
            $aParam['userInfo'] = json_encode($aUserInfoParam);
            $aParam['attachInsideIDs'] = '';
            $aParam['attachOutsideIDs'] = '';
            $aParam['oAttachIDs'] = '';
            $aParam['PackageName'] = $aCardProduct['sProductName'];
            $aParam['virtualCardID'] = $aCardProduct['sApiCardID'];
            $sUrl .= "?" . http_build_query($aParam);
            $aData = self::curl($sUrl);
            $aData = json_decode($aData, true);            
            if (!empty($aData['code'])) {
                $aCardProductParam['iAutoID'] = $aCardProduct['iAutoID'];
                $aCardProductParam['sApiReserveOrderID'] = $aData['orderid'];
                $aCardProductParam['sApiCardID'] = $aData['cardnumber'];
                Model_OrderCardProduct::updData($aCardProductParam);
                return 1;//预约改期成功
            } else {
                return 0;//预约改期失败
            }
        }
        $sUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/api/' . $sClassName . '/modifymentDate/';
        $aParam['orderid'] = $aCardProduct['sApiReserveOrderID'];
        $aParam['cardnumber'] = $aCardProduct['sApiCardID'];
        $aParam['hospid'] = $aStoreCode;
        $aParam['regdate'] = $sDate;
        $aParam['iPhysicalType'] = $aCard['iPhysicalType'];

        $sUrl .= "?data=" . json_encode($aParam);  
        // print_r($sUrl);die;      
        $aData = self::curl($sUrl);
        $aData = json_decode($aData, true);
        if (!empty($aData['code'])) {
            $aCardProductParam['iAutoID'] = $aCardProduct['iAutoID'];
            $aCardProductParam['sApiReserveOrderID'] = $aData['orderid'];
            $aCardProductParam['sApiCardID'] = $aData['cardnumber'];
            Model_OrderCardProduct::updData($aCardProductParam);
            return 1;//预约改期成功
        } else {
            return 0;//预约改期失败
        }
    }

    /**
     * 预约(有接口)
     * @param $sSupplierCode 供应商代码
     * @param $sDate
     * @param $aCard
     * @param $aCardProduct
     * @param $aProduct
     * @param $aStore
     * @return array
     */
    public static function reserve($sSupplierCode, $aStoreCode, $sProductCode, $sDate, $aCard, $aCardProduct)
    {
        $aHasApiConf = Yaf_G::getConf('aHasApi', 'suppliers');
        $sClassName = $aHasApiConf[$sSupplierCode]['classname'];
        $sUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/api/' . $sClassName . '/order/';
        switch ($sSupplierCode) {
            case 'ruici'://已OK（预约改期报错）
                //先调用买卡接口

                $sBuyCardUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/api/' . $sClassName . '/createVirtualCard/';
                $aBuyParam['packageID'] = $sProductCode;
                $sBuyCardUrl .= "?" . http_build_query($aBuyParam);
                $aBuyCard = self::curl($sBuyCardUrl);
                $aBuyCard = json_decode($aBuyCard, true);
                if (!empty($aBuyCard['code'])) {
                    $aParam['virtualCardID'] = $aBuyCard['cardnumber'];
                } else {
                    return 0;
                }
                $aCustomer = Model_Customer::getDetail($aCard['iUserID']);
                $aUserInfoParam['Mobile'] = $aCustomer['sMobile'];
                $aUserInfoParam['CertNo'] = $aCustomer['sIdentityCard'];
                $aUserInfoParam['Name'] = $aCustomer['sRealName'];
                $aUserInfoParam['Birthday'] = $aCustomer['sBirthDate'];
                $aUserInfoParam['Address'] = '';
                $aUserInfoParam['Sex'] = $aCustomer['iSex'];
                $aUserInfoParam['isMarry'] = $aCustomer['iMarriage'];
                $aUserInfoParam['CertType'] = $aCustomer['iCardType'];
                $aUserInfoParam['Remark'] = '';
                $aUserInfoParam['Department'] = '';
                $aParam['InstitutionID'] = $aStoreCode;
                $aParam['markDate'] = $sDate;
                $aParam['packageID'] = $sProductCode;
                $aParam['userInfo'] = json_encode($aUserInfoParam);
                $aParam['attachInsideIDs'] = '';
                $aParam['attachOutsideIDs'] = '';
                $aParam['oAttachIDs'] = '';
                $aParam['PackageName'] = $aCardProduct['sProductName'];
                $sUrl .= "?" . http_build_query($aParam);
                //print_r($sUrl);die;
                break;
            case 'renai'://已OK
                $aCustomer = Model_Customer::getDetail($aCard['iUserID']);
                $aParam['cardnumber'] = $aCardProduct['iAutoID'];
                $aParam['regdate'] = $sDate;
                $aParam['packagecode'] = $sProductCode;
                $aParam['adittion'] = '';
                $aParam['additioncode'] = '';
                $aParam['hospid'] = $aStoreCode;
                $aParam['name'] = $aCustomer['sRealName'];
                $aParam['sex'] = $aCustomer['iSex'];
                $aParam['married'] = $aCustomer['iMarriage'];
                $aParam['contacttel'] = $aCustomer['sMobile'];
                $aParam['idnumber'] = $aCustomer['sIdentityCard'];
                $aParam['reportaddress'] = '';
                $aParam['thirdnum'] = $aCardProduct['iAutoID'];
                $sUrl .= "?" . http_build_query($aParam);
                break;
            case 'meinian'://已OK
                $aCustomer = Model_Customer::getDetail($aCard['iUserID']);
                $aParam['customerName'] = $aCustomer['sRealName'];
                $aParam['customerIdentityNo'] = $aCustomer['sIdentityCard'];
                $aParam['customerGender'] = $aCustomer['iSex'];
                $aParam['customerMobilePhone'] = $aCustomer['sMobile'];
                $aParam['customerBirthday'] = $aCustomer['sBirthDate'];
                $aParam['customerMedicalStatus'] = $aCustomer['iMarriage'];
                $aParam['appointmentHospitalCode'] = $aStoreCode;
                $aParam['appointmentPackageCode'] = $sProductCode;
                $aParam['appointmentDate'] = $sDate;
                $aParam['outOrderCode'] = $aCardProduct['iOrderID'];
                $aParam['hasAuthorized'] = 'N';//是否授权回传体检报告(Y：是, N：否)
                $aParam['Dept1'] = '';
                $aParam['AddItems'] = '';
                $aParam['iPhysicalType'] = $aCard['iPhysicalType'];
                $sUrl .= "?" . http_build_query($aParam);
                break;
            case 'aikang'://已OK
                //NONEM672198
                $aCustomer = Model_Customer::getDetail($aCard['iUserID']);
                $aParam['cardnumber'] = $aCardProduct['iAutoID'];
                $aParam['regdate'] = $sDate;
                $aParam['packagecode'] = $sProductCode;
                $aParam['hospid'] = $aStoreCode;
                $aParam['name'] = $aCustomer['sRealName'];
                $aParam['sex'] = $aCustomer['iSex'];
                $aParam['married'] = $aCustomer['iMarriage'];
                $aParam['contacttel'] = $aCustomer['sMobile'];
                $aParam['idnumber'] = $aCustomer['sIdentityCard'];
                $aParam['reportaddress'] = '';
                $aParam['thirdnum'] = $aCardProduct['iAutoID'];
                $sUrl .= "?" . http_build_query($aParam);
                break;
            case 'cimin'://已OK（预约改期报错）
                if (!empty($aCardProduct['sApiCardID'])) {
                    $aParam['hospitalOrderId'] = $aCardProduct['sApiCardID'];
                } else {
                    //先调用买卡接口
                    $sBuyCardUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/api/' . $sClassName . '/createCode/';
                    $aCustomer = Model_Customer::getDetail($aCard['iUserID']);
                    $aBuyParam['orderId'] = $aCardProduct['iAutoID'];
                    //$aBuyParam['orderId'] = 999;
                    $aBuyParam['customerName'] = $aCustomer['sRealName'];
                    $aBuyParam['customerIdentityType'] = $aCustomer['iCardType'];
                    $aBuyParam['customerIdentityNo'] = $aCustomer['sIdentityCard'];
                    $aBuyParam['customerGender'] = $aCustomer['iSex'];
                    $aBuyParam['phoneNo'] = $aCustomer['sMobile'];
                    $aBuyParam['customerBirthday'] = $aCustomer['sBirthDate'];
                    $aBuyParam['medicalStatus'] = $aCustomer['iMarriage'];
                    $aBuyParam['hasAuthorized'] = 'N';//是否授权查看体检报告： 是否授权查看体检报告： Y否： N-
                    $aBuyParam['VIP'] = 2;//体检时是否为 体检时是否为 VIP 1 是 2否
                    $aBuyParam['checkcity'] = $aCustomer['iCityID'];
                    $sBuyCardUrl .= "?" . http_build_query($aBuyParam);
                    $aBuyCard = self::curl($sBuyCardUrl);
                    $aBuyCard = json_decode($aBuyCard, true);
                    if (!empty($aBuyCard['code'])) {
                        $aParam['hospitalOrderId'] = $aBuyCard['cardnumber'];
                    } else {
                        return 0;
                    }
                }

                //cs_m
                $aParam['orderId'] = $aCardProduct['iAutoID'];
                //$aParam['orderId'] = 999;
                $aParam['hospitalSubId'] = $aStoreCode;
                $aParam['medicalPackage'] = $sProductCode;
                $aParam['appointmentTime'] = $sDate;
                $aParam['VIP'] = 2;
                $sUrl .= "?" . http_build_query($aParam);
        }
        $aData = self::curl($sUrl);
        $aData = json_decode($aData, true);
        if (!empty($aData['code'])) {
            $aCardProductParam['iAutoID'] = $aCardProduct['iAutoID'];
            $aCardProductParam['sApiReserveOrderID'] = $aData['orderid'];
            $aCardProductParam['sApiCardID'] = $aData['cardnumber'];
            Model_OrderCardProduct::updData($aCardProductParam);
            return 1;//预约成功
        } else {
            return 0;//预约失败
        }
    }

    /**
     * 根据标志符获取可预约时间
     * @param $sSupplierCode 供应商代码
     * @param int $sDate 分页需要
     * @param string $sStoreCode 有接口的供应商需要
     * @param int $iPhysicalType 美年区分体检类型 (1=年度体检,2=入职体检)
     * @return array
     */
    public static function getReserveTimeByCode($sSupplierCode, $sDate = 0, $sStoreCode = '', $iPhysicalType = 1)
    {
        $aHoliDayWeekList = [];
        $aSupplierConf = Yaf_G::getConf('aReservedate', 'suppliers');
        $aHasApiConf = Yaf_G::getConf('aHasApi', 'suppliers');
        if (empty($aSupplierConf[$sSupplierCode]) && empty($aHasApiConf[$sSupplierCode])) {
            return $aHoliDayWeekList;
        }
        if (!empty($sDate)) {
            $aDateList = self::getDateList($sDate);
        } else {
            $aDateList = self::getThisMonTo35();
        }
        switch ($sSupplierCode) {
            case !empty($aSupplierConf[$sSupplierCode])://没有接口的供应商
                $aHoliDayList = self::getHolidayList($aDateList);
                //按星期分成5行
                $aHoliDayWeekList = array_chunk($aHoliDayList, 7, true);
                $sTodayDate = date('Ymd', time());
                $j = empty($sDate) ? 0 : 999;//顺延日期,下一页的不需要顺延
                foreach ($aHoliDayWeekList as $key => $value) {
                    $i = 0;
                    foreach ($value as $k => $val) {
                        $i++;
                        if ($k <= $sTodayDate) {//当天和小于当天的
                            $aHoliDayWeekList[$key][$k] = 0;
                        } elseif ($j < $aSupplierConf[$sSupplierCode][9]) {//需要顺延的日期
                            $aHoliDayWeekList[$key][$k] = 0;
                            if ($aSupplierConf[$sSupplierCode][$i] == 1) {
                                $j++;
                            }
                        } elseif ($val == 2) {//节假日
                            continue;
                        } elseif ($aSupplierConf[$sSupplierCode][$i] == 0 && $val == 0 && $i > 5) {//因假日调整，周末变成工作日，变成可预约日期
                            $aHoliDayWeekList[$key][$k] = 1;
                        } else {
                            $aHoliDayWeekList[$key][$k] = $aSupplierConf[$sSupplierCode][$i];//根据配置文件调整
                        }
                    }
                }
                break;
            case !empty($aHasApiConf[$sSupplierCode]) :
                $aDateList1 = array_flip($aDateList);
                $aHoliDayWeekList = array_chunk($aDateList1, 7, true);
                $sClassName = $aHasApiConf[$sSupplierCode]['classname'];
                $aParam['hospid'] = $sStoreCode;
                $aParam['timeFrom'] = date('Y-m-d', strtotime($aDateList[0]));
                $aParam['timeTo'] = date('Y-m-d', strtotime($aDateList[34]));
                $aParam['iPhysicalType'] = $iPhysicalType;
                $aParamData = json_encode($aParam);
                $sUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/api/' . $sClassName . '/getschedule/data/' . $aParamData;
                $aData = self::curl($sUrl);
                $aData = json_decode($aData, true);
                foreach ($aHoliDayWeekList as $key => $value) {
                    foreach ($value as $k => $val) {
                        $sTmp = date('Y-m-d', strtotime($k));
                        if (in_array($sTmp, $aData['data'])) {
                            $aHoliDayWeekList[$key][$k] = 1;
                        } else {
                            $aHoliDayWeekList[$key][$k] = 0;
                        }
                    }
                }
                break;
        }
        return $aHoliDayWeekList;
    }

    /**
     * 获取未来多少天的日期列表
     * @param $sStartDate 格式 20160502
     * @param $iPageSize 根据样式，默认35天
     */
    public static function getDateList($sStartDate, $iPageSize = 35)
    {
        $aDate = [];
        for ($i = strtotime($sStartDate); $i < strtotime($sStartDate) + 86400 * $iPageSize; $i += 86400) {
            $aDate[] = date("Ymd", $i);
        }
        return $aDate;
    }

    /**
     * 获取本周一开始的未来35天日期列表
     */
    public static function getThisMonTo35()
    {
        $iTime = time();
        //获取本周一的日期
        $iCurrWeek = date('w', $iTime);
        $iCurrWeek = $iCurrWeek == 0 ? 7 : $iCurrWeek;
        $iMonDay = $iTime - ($iCurrWeek - 1) * 86400;
        //获取从本周一开始的未来35天日期列表
        return self::getDateList(date('Ymd', $iMonDay));
    }

    /**
     * 调用百度接口获取节假日列表
     */
    public static function getHolidayList($aDate)
    {
        $aSupplierConf = Yaf_G::getConf('apikey', 'baiduapi');
        $aParam['d'] = implode(',', $aDate);
        $aHeader = ['apikey:' . $aSupplierConf];
        $sUrl = self::BAIDUHOLIDAYAPIURL . '?d=' . $aParam['d'];
        $aData = self::curl($sUrl, false, $aParam, '', $aHeader);
        return json_decode($aData, true);
    }

    /**
     * 远程访问地址
     * @param $url
     * 访问url
     */
    public static function curl($sUrl, $bPost = false, $aData = array(), $host = '', $header = [], $isRedirect = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, true);
        curl_setopt($ch, CURLOPT_URL, $sUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (!empty($host)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $host);//设置host
        }
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        if ($bPost) {
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        if ($bPost && !empty($aData)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $aData);
        }
        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }

        if ($isRedirect) {
            curl_setopt($ch, CURLOPT_AUTOREFERER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $content = curl_exec($ch);
        $response = curl_getinfo($ch);
        curl_close($ch);
        return $content;
    }


}