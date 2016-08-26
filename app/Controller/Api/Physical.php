<?php
/**
 * Author: chasel
 * CreateTime: 2016/7/18
 * Description: AF接口
 */

class Controller_Api_Physical extends Controller_Api_Base
{
    private $_aResult = [
        'code' => 1000,
        'msg' => '执行成功'
    ];

    private $codeMsg = array(
        1000 => '执行成功',
        1005 => '公司编号重复',
        1010 => '公司名称重复',
        1015 => '公司联系人邮箱重复',
        1020 => '公司信息错误',
        1030 => '雇员信息错误',
        1040 => '产品信息错误',
        1050 => '必填信息不满足',
        1060 => '密钥错误',
        1070 => '体检计划不存在',
        1071 => '体检计划已存在',
        2000 => '操作失败',
        2001 => '该邮箱已被注册',
        2002 => '该手机已被注册',
        2003 => '没有权限',
        2004 => '公司编号错误',
        2005 => '体检计划对应产品已存在',
        2006 => '体检计划对应产品不存在',
        2007 => '该员工已经参与过体检',
        2008 => '找不到该体检单号对应数据'
    );

    //1：查询某公司的单个体检计划下的所有员工体检信息
    public function getTiJianInfoByPlanIDAction ()
    {
        $sComNo = addslashes($this->getParam('ComNo'));
        $iPlanID = intval($this->getParam('PlanID'));

        if( empty($sComNo) || empty($iPlanID) ) {
            $this->_aResult['code'] = 1050;
            $this->_aResult['msg'] = $this->codeMsg[1050];

            return $this->showMsg($this->_aResult, true);
        }

        $company = Model_User::getRow(['where' => ['sUserName' => $sComNo]]);
        if(empty($company)) {
            $this->_aResult['code'] = 2004;
            $this->_aResult['msg'] = $this->codeMsg[2004];

            return $this->showMsg($this->_aResult, true);
        }

        $plan = Model_Physical_Plan::getDetail($iPlanID);
        if(empty($plan) || $company['iUserID'] != $plan['iHRID']) {
            $this->_aResult['code'] = 1070;
            $this->_aResult['msg'] = $this->codeMsg[1070];

            return $this->showMsg($this->_aResult, true);
        }

        $cards = Model_OrderCard::getCardByPlan($iPlanID, 1);
        if(empty($cards)) {
            $this->_aResult['data'] = array();
            return $this->showMsg($this->_aResult, true);
        }

        $this->_aResult['data'] = $this->getPhysicalInfo($cards, $company['iUserID']);

        return $this->showMsg($this->_aResult, true);
    }

    //2：查询某公司某段时间内的所有员工体检信息
    public function getTiJianInfoByCompanyNameAction ()
    {
        $sComNo = addslashes($this->getParam('ComNo'));
        $sBeginDate = addslashes($this->getParam('BeginDate'));
        $sEndDate = addslashes($this->getParam('EndDate'));

        if( empty($sComNo) || empty($sBeginDate) || empty($sEndDate)) {
            $this->_aResult['code'] = 1050;
            $this->_aResult['msg'] = $this->codeMsg[1050];

            return $this->showMsg($this->_aResult, true);
        }

        $company = Model_User::getRow(['where' => ['sUserName' => $sComNo]]);
        if(empty($company)) {
            $this->_aResult['code'] = 2004;
            $this->_aResult['msg'] = $this->codeMsg[2004];

            return $this->showMsg($this->_aResult, true);
        }

        $where = array(
            'sStartDate >=' => $sBeginDate,
            'sEndDate <=' => $sEndDate,
            'iCreateUserID' => $company['iUserID']
        );
        $cards = Model_OrderCard::getAll(['where' =>$where]);

        $this->_aResult['data'] = $this->getPhysicalInfo($cards, $company['iUserID']);

        return $this->showMsg($this->_aResult, true);
    }

    //1.2  根据公司编号和体检订单流水号查询结果
    public function getTiJianInfoByTiJianEmployeeIDAction ()
    {
        $sComNo = addslashes($this->getParam('ComNo'));
        $sTiJianEmployeeID = addslashes($this->getParam('TiJianEmployeeID'));

        if( empty($sComNo) || empty($sTiJianEmployeeID) ) {
            $this->_aResult['code'] = 1050;
            $this->_aResult['msg'] = $this->codeMsg[1050];

            return $this->showMsg($this->_aResult, true);
        }

        $company = Model_User::getRow(['where' => ['sUserName' => $sComNo]]);
        if(empty($company)) {
            $this->_aResult['code'] = 2004;
            $this->_aResult['msg'] = $this->codeMsg[2004];

            return $this->showMsg($this->_aResult, true);
        }

        $where = array(
            'sPlanSerialNumber' => $sTiJianEmployeeID,
            'iCreateUserID' => $company['iUserID']
        );
        $cards = Model_OrderCard::getAll(['where' =>$where]);

        $this->_aResult['data'] = $this->getPhysicalInfo($cards, $company['iUserID']);

        return $this->showMsg($this->_aResult, true);
    }

    //3：新增公司信息
    public function addCompanyAction ()
    {
        $company = $this->_checkCompany(1);

        $error = Model_User::addData($company);
        if ($error > 0) {
            $this->_aResult['code'] = 1000;
            $this->_aResult['msg'] = $this->codeMsg[1000];
        } else {
            $this->_aResult['code'] = 2000;
            $this->_aResult['msg'] = $this->codeMsg[2000];
        }

        return $this->showMsg($this->_aResult, true);
    }

    /*
     * param $isAdd 默认新增，false为修改
     */
    private function _checkCompany($isAdd = true) {
        $params = $this->getParam('CompanyInfo');
        if(empty($params)){
            $this->_aResult['code'] = 1050;
            $this->_aResult['msg'] = $this->codeMsg[1050];

            return $this->showMsg($this->_aResult, true);
        }

        $params = json_decode($params, true);
        $iRoleID = 0;

        if($isAdd) {
            if( empty($params['ComNo']) || empty($params['NameCN']) || empty($params['LinkMan']) || empty($params['Email']) || empty($params['Mobile']) ) {
                $this->_aResult['code'] = 1050;
                $this->_aResult['msg'] = $this->codeMsg[1050];

                return $this->showMsg($this->_aResult, true);
            }

            $company = Model_User::getRow(['where' => ['sUserName' => $params['ComNo']]]);
            if(!empty($company)) {
                $this->_aResult['code'] = 1005;
                $this->_aResult['msg'] = $this->codeMsg[1005];

                return $this->showMsg($this->_aResult, true);
            }

            $aRole = Model_Role::getRoleByName(Model_Role::HRROLENAME, 2);
            if (empty($aRole)) {
                $this->_aResult['code'] = 2003;
                $this->_aResult['msg'] = $this->codeMsg[2003];

                return $this->showMsg($this->_aResult, true);
            }
            $iRoleID = $aRole['iRoleID'];

        }else {
            if( empty($params['ComNo']) ) {
                $this->_aResult['code'] = 1050;
                $this->_aResult['msg'] = $this->codeMsg[1050];

                return $this->showMsg($this->_aResult, true);
            }

            $company = Model_User::getRow(['where' => ['sUserName' => $params['ComNo']]]);
            if(empty($company)) {
                $this->_aResult['code'] = 2004;
                $this->_aResult['msg'] = $this->codeMsg[2004];

                return $this->showMsg($this->_aResult, true);
            }
        }

        //验证邮箱
        if (!empty($params['Email'])) {
            $aUser = Model_User::getUserByEmail($params['Email'], Model_User::TYPE_HR);
            if (!empty($aUser)) {
                $this->_aResult['code'] = 2001;
                $this->_aResult['msg'] = $this->codeMsg[2001];

                return $this->showMsg($this->_aResult, true);
            }
        }

        //验证手机是否被注册
        if (!empty($params['Mobile'])) {
            $aUser = Model_User::getUserByMobile($params['Mobile'], Model_User::TYPE_HR);
            if (!empty($aUser)) {
                $this->_aResult['code'] = 2002;
                $this->_aResult['msg'] = $this->codeMsg[2002];

                return $this->showMsg($this->_aResult, true);
            }
        }

        if($isAdd) {
            $company = array(
                'sUserName' => $params['ComNo'],
                'sRealName' => $params['NameCN'],
                'sUserEngName' =>  $params['NameEN'],
                'sUserShortName' => $params['NameShort'],
                'sHrName' => $params['LinkMan'],
                'sEmail' => $params['Email'],
                'sCoAddress' => $params['Address'],
                'sCoZipCode' => $params['PostNum'],
                'sMobile' => $params['Mobile'],
                'sCoMobile' => $params['Phone'],
                'sCoFex' => $params['Fax'],
                'sCoWebsite' => $params['Web'],
                'sCoDesc' => $params['Memo'],
                'sAFManagerName' => $params['ManagerName'],
                'sAFMangaerEmail' => $params['ManagerEmail'],
                'sAFMangerMobile' => $params['ManagerMobile'],
                'iSendMassage' => intval($params['IsSendToHr']),
                'iRoleID' => $iRoleID,
                'iType' => Model_User::TYPE_HR,
                'iIsCheck' => Model_User::ISCHECK,
                'sPassword' => md5(Yaf_G::getConf('cryptkey', 'cookie') . $params['ComNo']),
                'iCreateUserID' => -99,
                'iStatus' => 2,//默认锁定
                'iChannel' => 2 //渠道默认为上海AF
            );
        }else {
            $curCompany = Model_User::getRow(['where' => ['sUserName' => $params['ComNo']]]);
            $company = array(
                'sUserName' => $params['ComNo'],
                'sRealName' => ( !empty($params['NameCN']) ) ? $params['NameCN'] : $curCompany['sRealName'],
                'sUserEngName' => ( !empty($params['NameEN']) ) ? $params['NameEN'] : $curCompany['sUserEngName'],
                'sUserShortName' => ( !empty($params['NameShort']) ) ? $params['NameShort'] : $curCompany['sUserShortName'],
                'sHrName' => ( !empty($params['LinkMan']) ) ? $params['LinkMan'] : $curCompany['sHrName'],
                'sEmail' => ( !empty($params['Email']) ) ? $params['Email'] : $curCompany['sEmail'],
                'sCoAddress' => ( !empty($params['Address']) ) ? $params['Address'] : $curCompany['sCoAddress'],
                'sCoZipCode' => ( !empty($params['PostNum']) ) ? $params['PostNum'] : $curCompany['sCoZipCode'],
                'sMobile' => ( !empty($params['Mobile']) ) ? $params['Mobile'] : $curCompany['sMobile'],
                'sCoMobile' => ( !empty($params['Phone']) ) ? $params['Phone'] : $curCompany['sCoMobile'],
                'sCoFex' => ( !empty($params['Fax']) ) ? $params['Fax'] : $curCompany['sCoFex'],
                'sCoWebsite' => ( !empty($params['Web']) ) ? $params['Web'] : $curCompany['sCoWebsite'],
                'sCoDesc' => ( !empty($params['Memo']) ) ? $params['Memo'] : $curCompany['sCoDesc'],
                'sAFManagerName' => ( !empty($params['MannagerName']) ) ? $params['MannagerName'] : $curCompany['sAFManagerName'],
                'sAFMangaerEmail' => ( !empty($params['ManagerEmail']) ) ? $params['ManagerEmail'] : $curCompany['sAFMangaerEmail'],
                'sAFMangerMobile' => ( !empty($params['ManagerMobile']) ) ? $params['ManagerMobile'] : $curCompany['sAFMangerMobile'],
                'iSendMassage' => ( !empty($params['IsSendToHr']) ) ?  $params['IsSendToHr'] : $curCompany['iSendMassage']
            );
        }

        return $company;
    }

    //4：修改公司信息
    public function  updateCompanyAction ()
    {
        $company = $this->_checkCompany(false);

        $error = Model_User::updList("sUserName = '". $company['sUserName']. "'", $company);

        if ($error > 0) {
            $this->_aResult['code'] = 1000;
            $this->_aResult['msg'] = $this->codeMsg[1000];
        } else {
            $this->_aResult['code'] = 2000;
            $this->_aResult['msg'] = $this->codeMsg[2000];
        }

        return $this->showMsg($this->_aResult, true);
    }

    //5：创建体检计划
    public function addTiJianPlanAction ()
    {
        $sPlanName = addslashes($this->getParam('PlanName'));
        $sBeginDate = addslashes($this->getParam('BeginDate'));
        $sEndDate = addslashes($this->getParam('EndDate'));
        $sComNo = addslashes($this->getParam('ComNo'));

        if( empty($sPlanName) || empty($sBeginDate) || empty($sEndDate) || empty($sComNo) ) {
            $this->_aResult['code'] = 1050;
            $this->_aResult['msg'] = $this->codeMsg[1050];

            return $this->showMsg($this->_aResult, true);
        }

        $company = Model_User::getRow(['where' => array('sUserName' => $sComNo)]);

        if(empty($company)) {
            $this->_aResult['code'] = 2004;
            $this->_aResult['msg'] = $this->codeMsg[2004];

            return $this->showMsg($this->_aResult, true);
        }else {
            $iUserID = $company['iUserID'];
        }

        $exist = Model_Physical_Plan::getRow([
            'where' => [
                'iHRID' => $iUserID,
                'sPlanName' => $sPlanName,
            ]
        ]);
        if($exist) {
            $this->_aResult['code'] = 1071;
            $this->_aResult['msg'] = $this->codeMsg[1071];

            return $this->showMsg($this->_aResult, true);
        }

        $plan = array(
            'iHRID' => $iUserID,
            'sPlanName' => $sPlanName,
            'sStartDate' => $sBeginDate,
            'sEndDate' => $sEndDate,
            'iStatus' => 1,
            'iCreateID' => -99
        );
        $iPlanID = Model_Physical_Plan::addData($plan);

        if ($iPlanID > 0) {
            $this->_aResult['code'] = 1000;
            $this->_aResult['msg'] = $this->codeMsg[1000];
            $this->_aResult['planID'] = $iPlanID;
        } else {
            $this->_aResult['code'] = 2000;
            $this->_aResult['msg'] = $this->codeMsg[2000];
        }

        return $this->showMsg($this->_aResult, true);
    }

    //6：设置体检计划的产品
    public function addProductWithPlanAction ()
    {
        $iPlanID = intval($this->getParam('PlanID'));
        $sComNo = addslashes($this->getParam('ComNo'));
        $sProductNo = addslashes($this->getParam('ProductNo'));

        if( empty($iPlanID) || empty($sComNo) || empty($sProductNo) ) {
            $this->_aResult['code'] = 1050;
            $this->_aResult['msg'] = $this->codeMsg[1050];

            return $this->showMsg($this->_aResult, true);
        }

        //验证体检计划
        $plan = Model_Physical_Plan::getDetail($iPlanID);
        if(empty($plan) || -99 != $plan['iCreateID'] ) {
            $this->_aResult['code'] = 1070;
            $this->_aResult['msg'] = $this->codeMsg[1070];

            return $this->showMsg($this->_aResult, true);
        }

        //验证产品
        $product = Model_Product::getRow([
            'where' => [
                'sProductCode' => $sProductNo,
            ]
        ]);
        if(empty($product) || 2 != $product['iType']) {
            $this->_aResult['code'] = 1040;
            $this->_aResult['msg'] = $this->codeMsg[1040];

            return $this->showMsg($this->_aResult, true);
        }

        //验证计划对应产品
        $planProduct = Model_Physical_PlanProduct::getRow(['where' => array(//接口创建的体检计划只能绑定一个产品
            'iPlanID' => $iPlanID,
        )]);
        if(!empty($planProduct)) {
            $this->_aResult['code'] = 2005;
            $this->_aResult['msg'] = $this->codeMsg[2005];

            return $this->showMsg($this->_aResult, true);
        }

        //验证产品是否能被该公司看到
        $company = Model_User::getRow(['where' => array('sUserName' => $sComNo)]);
        //取得该公司能看到的所有产品
        $procom = Model_Product::getAllUserProduct($company['iUserID'], Model_ProductChannel::TYPE_COMPANY, $company['iChannel']);

        if(!empty($procom)) {
            $comCodes = array();
            foreach ($procom as $pc) {
                $comCodes[] = $pc['sProductCode'];
            }

            if(!in_array($sProductNo, $comCodes)) {
                $this->_aResult['code'] = 1040;
                $this->_aResult['msg'] = $this->codeMsg[1040];

                return $this->showMsg($this->_aResult, true);
            }
        }else {
            $this->_aResult['code'] = 1040;
            $this->_aResult['msg'] = $this->codeMsg[1040];

            return $this->showMsg($this->_aResult, true);
        }

        $data = array(
            'iPlanID' => $iPlanID,
            'iProductID' => $product['iProductID'],
            'iStatus' => 1
        );
        $error = Model_Physical_PlanProduct::addData($data);

        if ($error > 0) {
            $this->_aResult['code'] = 1000;
            $this->_aResult['msg'] = $this->codeMsg[1000];
        } else {
            $this->_aResult['code'] = 2000;
            $this->_aResult['msg'] = $this->codeMsg[2000];
        }

        return $this->showMsg($this->_aResult, true);
    }

    //7：新增体检的员工(非计划内的)
    public function addTiJianEmployeeAction ()
    {
        return $this->showMsg($this->_aResult, true);
    }

    //8：新增体检的员工(计划内的)
    public function addTiJianEmployeeWithPlanAction ()
    {
        $iPlanID = intval($this->getParam('PlanID'));
        $sComNo = addslashes($this->getParam('ComNo'));
        $sProductNo = addslashes($this->getParam('ProductNo'));
        $sTiJianEmployeeInfo = $this->getParam('TiJianEmployeeInfo');

        if( empty($iPlanID) || empty($sComNo) || empty($sProductNo) || empty($sTiJianEmployeeInfo) ) {
            $this->_aResult['code'] = 1050;
            $this->_aResult['msg'] = $this->codeMsg[1050];

            return $this->showMsg($this->_aResult, true);
        }

        $product = Model_Product::getProductByCode($sProductNo);
        $company = Model_User::getUserByUserName($sComNo, Model_User::TYPE_HR);

        if(empty($product)) {
            $this->_aResult['code'] = 1040;
            $this->_aResult['msg'] = $this->codeMsg[1040];

            return $this->showMsg($this->_aResult, true);
        }

        if(empty($company)) {
            $this->_aResult['code'] = 2004;
            $this->_aResult['msg'] = $this->codeMsg[2004];

            return $this->showMsg($this->_aResult, true);
        }

        $planProduct = Model_Physical_PlanProduct::getRow(['where' => array('iPlanID' => $iPlanID, 'iProductID' => $product['iProductID'])]);
        if(empty($planProduct)) {
            $this->_aResult['code'] = 2006;
            $this->_aResult['msg'] = $this->codeMsg[2006];

            return $this->showMsg($this->_aResult, true);
        }

        $data = array(
            'iPlanID' => $iPlanID,
            'stype' => 2, //体检计划
            'iSend' => 1,
            'aUserID' => array(),
            'aProductID' => array(),
            'aUseType' => array(),
            'aAttribute' => array(),
            'aPhysicalType' => array(),
            'aPaperReport' => array(),
            'aPayType' => array(),
            'aStartDate' => array(),
            'aEndDate' => array()
        );

        //暂定名称为employees
        $employee = json_decode($sTiJianEmployeeInfo, true);
        if(!empty($employee)) {
            $iOrderType = 4;
            $iCreateUserType = 2;
            $seriaNumber = Util_Tools::uniStringGen();

            $customer = Model_CustomerCompany::getRow(['where' => ['sUserName' => $employee['EmployeeNo'], 'iStatus' => 1]]);
            if( empty($customer) ) {
                $customer = $this->addEmployee($employee);
            }

            if( empty($employee['PayType']) || empty($employee['TiJianType']) ) {
                $this->_aResult['code'] = 1050;
                $this->_aResult['msg'] = $this->codeMsg[1050];

                return $this->showMsg($this->_aResult, true);
            }

            $card = Model_OrderCard::getAll([
                'where' => [
                    'iUserID' => $customer['iUserID'],
                    'iCreateUserID' => $company['iUserID'],
                    'iOrderType' => Model_Physical_Product::TYPE_PRODUCT_PLAN,
                    'iPhysicalType' => 1,
                    'iTiJianYear' => intval($employee['TiJianYear']),
                    'iStatus IN' => ['-99', 1]
                ]
            ]);

            if(!empty($card)) {//同一家公司，同一个员工一年只能参与一次年度体检
                $this->_aResult['code'] = 2007;
                $this->_aResult['msg'] = $this->codeMsg[2007];

                return $this->showMsg($this->_aResult, true);
            }

            Model_OrderCard::initCard($iOrderType, 0, $company['iUserID'], $iCreateUserType, 0, 0, ['iUserID' => $customer['iUserID'], 'iStatus' => '-99', 'iPlanID' => $iPlanID, 'sPlanSerialNumber' => $seriaNumber, 'iResoure' => 1, 'sThirdOrderNum' => $employee['ThirdOrderNum'], 'iTiJianYear' => $employee['TiJianYear']]);

            $attribute = 1;
            if($employee['Sex'] == "女" && $employee['Marital'] == "未婚") {
                $attribute = 2;
            }
            if($employee['Sex'] == "女" && $employee['Marital'] == "已婚") {
                $attribute = 3;
            }
            $data['aUserID'][] = $customer['iUserID'];
            $data['aProductID'][$customer['iUserID']] = $product['iProductID'];
            $data['aUseType'][$customer['iUserID']] = 1;
            $data['aAttribute'][$customer['iUserID']] = $attribute;
            $data['aPhysicalType'][$customer['iUserID']] = intval($employee['TiJianType']);
            $data['aPaperReport'][$customer['iUserID']] = 0;//默认不提供纸质报告
            $data['aPayType'][$customer['iUserID']] = intval($employee['PayType']);
            $data['aStartDate'][$customer['iUserID']] = substr($employee['BeginDate'], 0, 10);
            $data['aEndDate'][$customer['iUserID']] = substr($employee['EndDate'], 0, 10);

            $data['iSend'] = 1;
        }


        Model_OrderCard::createCard($data, $company['iUserID']);

        $this->_aResult['TiJianEmployeeID'] = $seriaNumber;

        return $this->showMsg($this->_aResult, true);
    }

    //8.1：体检通知发送
    public function sendTiJianMessageAction ()
    {
        $iPlanID = intval($this->getParam('PlanID'));
        $sContent = $this->getParam('Content');
        $iSentType = intval($this->getParam('SentType'));

        if( empty($iPlanID) || empty($sContent) || empty($iSentType) ) {
            $this->_aResult['code'] = 1050;
            $this->_aResult['msg'] = $this->codeMsg[1050];

            return $this->showMsg($this->_aResult, true);
        }

        $plan = Model_Physical_Plan::getDetail($iPlanID);
        if(empty($plan)) {
            $this->_aResult['code'] = 1070;
            $this->_aResult['msg'] = $this->codeMsg[1070];

            return $this->showMsg($this->_aResult, true);
        }

        $aEnterprise = Model_User::getDetail($plan['iHRID']);
        if ($aEnterprise) {
            $enterprise = $aEnterprise['sUserName'];
        }

        $userIds = [];
        $aPhysical = Model_OrderCard::getCardByPlan($iPlanID, $iSentType);

        if (empty($aPhysical)) {
            $this->_aResult['code'] = 1070;
            $this->_aResult['msg'] = $this->codeMsg[1070];

            return $this->showMsg($this->_aResult, true);
        }

        foreach ($aPhysical as $key => $value) {
            $userIds[] = $value['iUserID'];
        }

        $aUsers = [];
        $aEmpolyee = [];
        if ($userIds) {
            $aEmpolyee = Model_CustomerNew::getListByPKIDs($userIds);
            if ($aEmpolyee) {
                foreach ($aEmpolyee as $key => $value) {
                    $aUsers[$value['iUserID']]['sRealName'] = $value['sRealName'];
                    $aUsers[$value['iUserID']]['sMobile'] = $value['sMobile'];

                    $aCompany = Model_Company_Company::checkIsExist($value['iUserID'], $plan['iHRID']);
                    $aUsers[$value['iUserID']]['sEmail'] = !empty($aCompany) ? $aCompany['sEmail'] : "";
                }
            }
        }

        foreach ($aPhysical as $key => $value) {
            $mailRes = Util_Mail::send($aUsers[$value['iUserID']]['sEmail'], '体检通知', $sContent);
            if ($mailRes) {
                $data = [];
                $data['iAutoID'] = $value['iAutoID'];
                $data['iSendEMail'] = 1;
                Model_OrderCard::updData($data);
            }
        }

        return $this->showMsg($this->_aResult, true);
    }

    //9：获取所有通用体检产品信息
    public function getTiJianProductListAction ()
    {
        $products = Model_Product::getAll(['where' => ['iStatus' => 1]]);
        $this->_aResult['data'] = array();

        if(!empty($products)) {
            foreach($products as $p) {
                $this->_aResult['data'][] = array(
                    'ProductNo' => $p['sProductCode'],
                    'ProductName' => $p['sProductName'],
                    'SaleValue1' => floatval($p['iManPrice']),
                    'SaleValue2' => floatval($p['iWomanPrice1']),
                    'SaleValue3' => floatval($p['iWomanPrice2']),
                    'ProductType' => $p['iProductType'],//待定
                );
            }
        }

        return $this->showMsg($this->_aResult, true);
    }

    //10：获取某公司定制的体检产品信息
    public function getTiJianProductListByCompanyAction ()
    {
        return $this->showMsg($this->_aResult, true);
    }

    //11：获取某个体检产品下面的体检单项信息
    public function getTiJianItemListByProductAction ()
    {
        $sProductNo = addslashes($this->getParam('ProductNo'));

        if( empty($sProductNo) ) {
            $this->_aResult['code'] = 1050;
            $this->_aResult['msg'] = $this->codeMsg[1050];

            return $this->showMsg($this->_aResult, true);
        }

        $product = Model_Product::getRow(['where' => ['sProductCode' => $sProductNo, 'iType' => 2]]);
        if(empty($product)) {
            $this->_aResult['code'] = 1040;
            $this->_aResult['msg'] = $this->codeMsg[1040];

            return $this->showMsg($this->_aResult, true);
        }

        $iProductID = $product['iProductID'];
        $items = Model_Item::getProductItemsByID($iProductID);
        $this->_aResult['data'] = array();

        if(!empty($items)) {
            foreach($items as $item) {
                $catName = '';
                $iCat = $item['iCat'];
                $category = Model_Product_Category::getDetail($iCat);
                if(!empty($category)) {
                    $catName = $category['sCateName'];
                }

                $this->_aResult['data'][] = array(
                    'ItemID' => $item['sCode'],
                    'ItemName' => $item['sName'],
                    'TypeName' => $catName
                );
            }
        }

        return $this->showMsg($this->_aResult, true);
    }

    /*
     * 暂时不实现
    */
    //12：获取某个体检产品下面可供选择的加项信息
    public function getChooseItemListByProductAction ()
    {
        $sProductNo = addslashes($this->getParam('ProductNo'));

        if( empty($sProductNo) ) {
            $this->_aResult['code'] = 1050;
            $this->_aResult['msg'] = $this->codeMsg[1050];

            return $this->showMsg($this->_aResult, true);
        }

        $product = Model_Product::getRow(['where' => ['sProductCode' => $sProductNo, 'iType' => 2]]);
        if(empty($product)) {
            $this->_aResult['code'] = 1040;
            $this->_aResult['msg'] = $this->codeMsg[1040];

            return $this->showMsg($this->_aResult, true);
        }

        $iProductID = $product['iProductID'];
        $items = Model_Addtion::getProductAddtions($iProductID);
        $this->_aResult['data'] = array();

        if(!empty($items)) {
            foreach($items as $item) {
                $typeName = array(
                    1 => '单项加项',
                    2 => '组合单项',
                    3 => '服务加项'
                );

                $this->_aResult['data'][] = array(
                    'ItemID' => $item['sCode'],
                    'ItemName' => $item['sName'],
                    'TypeName' => isset($typeName[$item['iType']]) ? $typeName[$item['iType']] : ''
                );
            }
        }

        return $this->showMsg($this->_aResult, true);
    }

    //13：获取某个体检产品下面可供选择的体检门店信息
    public function getTiJianShopListByProductAction ()
    {
        $sProductNo = addslashes($this->getParam('ProductNo'));

        if( empty($sProductNo) ) {
            $this->_aResult['code'] = 1050;
            $this->_aResult['msg'] = $this->codeMsg[1050];

            return $this->showMsg($this->_aResult, true);
        }

        $product = Model_Product::getRow(['where' => ['sProductCode' => $sProductNo, 'iType' => 2]]);
        if(empty($product)) {
            $this->_aResult['code'] = 1040;
            $this->_aResult['msg'] = $this->codeMsg[1040];

            return $this->showMsg($this->_aResult, true);
        }

        $iProductID = $product['iProductID'];
        $prostore = Model_ProductStore::getProductStores($iProductID, Model_ProductStore::EXPANDPRODUCT);

        $storeIDs = array();
        if(!empty($prostore)) {
            foreach($prostore as $ps) {
                $storeIDs[] = $ps['iStoreID'];
            }
        }

        $this->_aResult['data'] = array();
        $stores = array();
        if(!empty($storeIDs)) {
            $stores = Model_Store::getAll(['where' => ['iStoreID in' => $storeIDs]]);
        }

        if(!empty($stores)) {
            foreach($stores as $store){
                $iSupplierID = $store['iSupplierID'];
                $aSupplier = Model_Type::getDetail($iSupplierID);
                $supplierName = !empty($aSupplier) ? $aSupplier['sTypeName'] : '';

                $acity = Model_City::getDetail(intval($store['iCityID']));
                $city = !empty($acity) ? $acity['sCityName'] : '';

                $aRegioin = Model_Region::getDetail(intval($store['iRegionID']));
                $region = !empty($aRegioin) ? $aRegioin['sRegionName'] : '';

                $this->_aResult['data'][] = array(
                    'SupplierNo' => $store['sCode'],
                    'SupplierName' => $store['sName'],
                    'SupplierType' => $supplierName,
                    'City' => $city,
                    'Area' => $region,
                    'Address' => $store['sAddress']
                );
            }
        }

        return $this->showMsg($this->_aResult, true);
    }

    //14：获取所有的体检门店信息(14,15合并为一个)
    public function getAllTiJianShopListAction ()
    {
        //美年：100，爱康：105，慈铭：110，仁爱医院：115,  108医院：120
        $aSupplierName = array(
            100 => 'meinian',//美年
            105 => 'aikang',//爱康
            110 => 'cimin',//慈铭
            115 => 'renai',//仁爱医院
            120 => 'ruici'//瑞慈
        );
        $sSupplierType = intval($this->getParam('SupplierType'));
        $where = array('iStatus' => 1);

        if(!empty($sSupplierType) && isset($aSupplierName[$sSupplierType])) {
            $supplier = Model_Type::getRow(['where' => ['sClass' => 'supplier', 'sCode' => $aSupplierName[$sSupplierType]]]);
            if(!empty($supplier)) {
                $where['iSupplierID'] = $supplier['iTypeID'];
            }
        }

        $this->_aResult['data'] = array();
        $stores = Model_Store::getAll($where);
        if(!empty($stores)) {
            foreach($stores as $store){
                $iSupplierID = $store['iSupplierID'];
                $aSupplier = Model_Type::getDetail($iSupplierID);
                $supplierName = !empty($aSupplier) ? $aSupplier['sTypeName'] : '';

                $acity = Model_City::getDetail(intval($store['iCityID']));
                $city = !empty($acity) ? $acity['sCityName'] : '';

                $aRegioin = Model_Region::getDetail(intval($store['iRegionID']));
                $region = !empty($aRegioin) ? $aRegioin['sRegionName'] : '';

                $this->_aResult['data'][] = array(
                    'SupplierNo' => $store['sCode'],
                    'SupplierName' => $store['sName'],
                    'SupplierType' => $supplierName,
                    'City' => $city,
                    'Area' => $region,
                    'Address' => $store['sAddress']
                );
            }
        }

        return $this->showMsg($this->_aResult, true);
    }

    /*
     * 作废
     */
    public function CancelTiJianEmployeeAction ()
    {
        $sTiJianEmployeeID = addslashes($this->getParam('TiJianEmployeeID'));

        if( empty($sTiJianEmployeeID) ) {
            $this->_aResult['code'] = 1050;
            $this->_aResult['msg'] = $this->codeMsg[1050];

            return $this->showMsg($this->_aResult, true);
        }

        $card = Model_OrderCard::getRow(['where' => ['sPlanSerialNumber' => $sTiJianEmployeeID]]);
        if(!empty($card)) {
            $iCardID = $card['iAutoID'];

            Model_OrderCardProduct::updList("iCardID =". $iCardID, ['iBookStatus' => 4]);
        }

        return $this->showMsg($this->_aResult, true);
    }

    /*
     * 暂时不实现
     */
    //16：个性化体检产品设置（仅限于调整原体检产品对应的门店数据）
    /*
    public function CreateNewTiJianProductAction ()
    {
        $sOldProductNo = $this->getParam('OldProductNo');
//        $sNewProductName = $this->getParam('NewProductName');
        $sShopList = $this->getParam('ShopList');
        $sComNo = $this->getParam('ComNo');

        if( empty($sOldProductNo) || empty($sShopList) || empty($sComNo) ) {
            $this->_aResult['code'] = 1050;
            $this->_aResult['msg'] = $this->codeMsg[1050];

            return $this->showMsg($this->_aResult, true);
        }

        $product = Model_Product::getRow(['where' => ['sProductCode' => $sOldProductNo, 'iType' => 2]]);
        if(empty($product)) {
            $this->_aResult['code'] = 1040;
            $this->_aResult['msg'] = $this->codeMsg[1040];

            return $this->showMsg($this->_aResult, true);
        }

        $company = Model_User::getRow(['where' => ['sUserName' => $sComNo]]);
        if(empty($company)) {
            $this->_aResult['code'] = 2004;
            $this->_aResult['msg'] = $this->codeMsg[2004];

            return $this->showMsg($this->_aResult, true);
        }

        $iProductID = $product['iProductID'];
        $iCompanyID = $company['iUserID'];

        Model_UserProductBase::updList("iUserID = $iCompanyID AND iProductID = $iProductID and iType = 1 and iChannelID = 2", array('sAlias' => $sNewProductName));
        Model_UserProductStore::updList("iUserID = $iCompanyID AND iProductID = $iProductID and iType = 1 and iChannelID = 2", array('iStatus' => 0));

        $aShopList = explode(',', $sShopList);
        foreach($aShopList as $shopCode) {
            $shop = Model_Store::getRow(['where' => ['sCode' => $shopCode]]);
            if(!empty($shop)) {
                $iStoreID = $shop['iStoreID'];

                $data = array(
                    'iUserID' => $iCompanyID,
                    'iChannelID' => $company['iChannel'],
                    'iProductID' => $iProductID,
                    'iStoreID' => $iStoreID,
                    'iCreateUserID' => -99,
                    'iLastUpdateUserID' => -99
                );
                Model_UserProductStore::addData($data);
            }else {
                continue;
            }
        }

        $this->_aResult['ProductNo'] = $sOldProductNo;

        return $this->showMsg($this->_aResult, true);
    }
    */

    //17：修改体检人员的手机号码和邮箱
    public function updateTiJianEmployeeAction ()
    {
        $sTiJianEmployeeID = $this->getParam('TiJianEmployeeID');
        $sEmail = $this->getParam('Email');
        $sMobile = $this->getParam('Mobile');

        if( empty($sTiJianEmployeeID) ) {
            $this->_aResult['code'] = 1050;
            $this->_aResult['msg'] = $this->codeMsg[1050];

            return $this->showMsg($this->_aResult, true);
        }

        $card = Model_OrderCard::getRow(['where' => ['sPlanSerialNumber' => $sTiJianEmployeeID]]);

        if(empty($card)) {
            $this->_aResult['code'] = 2008;
            $this->_aResult['msg'] = $this->codeMsg[2008];

            return $this->showMsg($this->_aResult, true);
        }

        $customer = Model_CustomerCompany::getRow(['where' => ['iUserID' => $card['iUserID'], 'iCreateUserID' => $card['iCreateUserID'], 'iStatus' => 1]]);
        $data = array();
        if(!empty($sEmail)) {
            $data['sEmail'] = $sEmail;
        }
        if(!empty($sMobile)) {
            $data['sMobile'] = $sMobile;
        }

        if(empty($data)) {
            $this->_aResult['code'] = 1050;
            $this->_aResult['msg'] = $this->codeMsg[1050];

            return $this->showMsg($this->_aResult, true);
        }

        Model_Customer::updData(array('iUserID' => $customer['iUserID'], 'sMobile' => $data['sMobile']));

        unset($data['sMobile']);
        Model_CustomerCompany::updList("sUserName = '". $customer['sUserName']. "'", $data);

        return $this->showMsg($this->_aResult, true);
    }


    private function getPhysicalInfo($cards, $iCompanyID){
        $data = array();

        if(!empty($cards)) {
            foreach($cards as $card) {
                $iCardID = $card['iAutoID'];
                $iCustomerID = $card['iUserID'];

                $cardprosInfo = Model_OrderCardProduct::getCardProductInfo($iCardID);
                $customerInfo = Model_Customer::getCustomerCompanyInfo($iCustomerID, $iCompanyID);

                $physicalInfo = array();
                if(!empty($cardprosInfo)) {
                    foreach($cardprosInfo as $cardInfo) {
                        $salePrice = 0;
                        if(!empty($cardInfo['iStoreID'])) { //获得供应价格
                            $iSexMar = 1;
                            if(2 == intval($cardInfo['iSex']) && 1 == intval($cardInfo['iMarriage'])) {
                                $iSexMar = 2;
                            }
                            if(2 == intval($cardInfo['iSex']) && 2 == intval($cardInfo['iMarriage'])) {
                                $iSexMar = 3;
                            }

                            $where = array(
                                'iProductID' => $cardInfo['iProductID'],
                                'iStoreID' => $cardInfo['iStoreID'],
                                'iSex' => $iSexMar
                            );
                            $productStore = Model_StoreCode::getRow(['where' => $where]);

                            if(!empty($productStore)) {
                                $salePrice = $productStore['sSupplierPrice'];
                            }
                        }

                        $physicalInfo[] = array(
                            'TiJianDate' => !empty($cardInfo) && !empty($cardInfo['iReserveTime']) ? date('Y-m-d H:i:s',$cardInfo['iReserveTime']) : "",
                            'DaoJianDate' => !empty($cardInfo) && !empty($cardInfo['iOrderTime']) ? date('Y-m-d H:i:s',$cardInfo['iOrderTime']) : "",
                            'ReportDate' => !empty($cardInfo) && !empty($cardInfo['iReportTime']) ? date('Y-m-d H:i:s',$cardInfo['iReportTime']) : "",
                            'SendTime' => !empty($cardInfo) && !empty($cardInfo['iSendEmailTime']) ? date('Y-m-d H:i:s',$cardInfo['iSendEmailTime']) : "",
                            'ProductNo' => !empty($cardInfo) ? $cardInfo['sProductCode'] : "",
                            'ProductName' => !empty($cardInfo) ? $cardInfo['sProductName'] : "",
                            'SaleValue' => $salePrice,
                            'ShopName' => !empty($cardInfo) ? $cardInfo['sName'] : "",
                            'ShopAddress' => !empty($cardInfo) ? $cardInfo['sAddress'] : "",
                            'IsArrived' => !empty($cardInfo) ? $cardInfo['iBookStatus'] : 0,
                        );
                    }
                }

                $data[] = array(
                    'ComNo' => !empty($customerInfo) ? $customerInfo['sCompanyCode'] : "",
                    'NameCN' => !empty($customerInfo) ? $customerInfo['sCompanyName'] : "",
                    'EmployeeNo' => !empty($customerInfo) ? $customerInfo['sUserName'] : "",
                    'EmployeeName' => !empty($customerInfo) ? $customerInfo['sRealName'] : "",
                    'Sex' => !empty($customerInfo) && 1 == intval($customerInfo['iSex'])? "男" : "女",
                    'IdNum' => !empty($customerInfo) ? $customerInfo['sIdentityCard'] : "",
                    'Mobile' => !empty($customerInfo) ? $customerInfo['sMobile'] : "",
                    'Email' => !empty($customerInfo) ? $customerInfo['sEmail'] : "",
                    'Marital' => !empty($customerInfo) && 1 == intval($customerInfo['iMarriage'])? "未婚" : "已婚",
                    "PhysicalInfo" => $physicalInfo
                );
            }
        }

        return $data;
    }

    public function addEmployee($employee){
        $company = Model_User::getUserByUserName($employee['ComNo'], Model_User::TYPE_HR);
        $existCustomer = Model_Customer::getRow(['where' => ['sIdentityCard' => $employee['IdNum']]]);

        $customer = array();
        $iUserID = 0;
        if(!empty($existCustomer)) {
            $customer = $existCustomer;
            $iUserID = $customer['iUserID'];
        }else {
            $sUserName = Model_Customer::initUserName();
            $customer = array(
                'iType' => 1,
                'sMobile' => $employee['Mobile'],
                'sUserName' => $sUserName,
                'sRealName' => $employee['EmployeeName'],
                'sPassword' => md5(Yaf_G::getConf('cryptkey', 'cookie') . $sUserName),
                'iSex' => "男" == $employee['Sex'] ? 1 : 2,
                'sEmail' => $employee['Email'],
                'iMarriage' => "已婚" == $employee['Marital'] ? 2 : 1,
                'sIdentityCard' => $employee['IdNum'],
                'iCreateUserID' => $company['iUserID'],
                'sBirthDate' => $employee['Birthday']
            );

            $iUserID = Model_Customer::addData($customer);
        }

        $customerCompany = array(
            'iUserID' => $iUserID,
            'sUserName' => $employee['EmployeeNo'],
            'iCompanyID' => $company['iUserID'],
            'sCompanyName' => $employee['NameCN'],
            'sCompanyCode' => $employee['ComNo'],
            'iCreateUserID' => $company['iUserID'],
            'sEmail' => $employee['Email']
        );
        $iccID = Model_CustomerCompany::addData($customerCompany);

        return $customerCompany;
    }


//    public function actionAfter ()
//    {
//        //加密算法
//        //基础数据
//        $response = json_encode($this->_aResult, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
//        $data = array(
//            'sControllerName' => $this->_name,
//            'sUrl' => $_SERVER['REQUEST_URI'],
//            'sRequest' => $params,
//            'sResponse' => '',
//        );
//        Model_Interfacelog::addData($data);
//
//    }
}