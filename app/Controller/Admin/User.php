<?php

/**
 * Created by PhpStorm.
 * User: zhangfangxiong
 * Date: 2016/4/14
 * Time: 9:50
 */
class Controller_Admin_User extends Controller_Admin_Base
{

    const PAGESIZE = 20;

    public function actionAfter()
    {
        parent::actionAfter();
        $this->assign('aType', Model_User::$aType);
    }

    /**
     * 员工管理
     */
    public function listAction()
    {
        $iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        $aParam = $this->getParams();
        $aWhere = [];
        if (!empty($aParam['sUserName'])) {
            $aWhere['sUserName LIKE'] = '%' . trim($aParam['sUserName']) . '%';
        }
        if (!empty($aParam['iJobTitleID'])) {
            $aWhere['iJobTitleID'] = intval($aParam['iJobTitleID']);
        }
        if (!empty($aParam['iDeptID'])) {
            $aWhere['iDeptID'] = intval($aParam['iDeptID']);
        }
        if (!empty($aParam['sRealName'])) {
            $aWhere['sRealName LIKE'] = '%' . trim($aParam['sRealName']) . '%';
        }
        if (!empty($aParam['iStatus'])) {
            $aWhere['iStatus'] = intval($aParam['iStatus']);
        }
        $aWhere['iType'] = Model_User::TYPE_ADMIN;
        $aData = Model_User::getList($aWhere, $iPage);
        $this->assign('aData', $aData);
        $this->assign('aParam', $aParam);
        $this->assign('aStatus', Model_User::$aStatus);
        $this->assign('aDept', Model_Type::getOption('dept'));
        $this->assign('aJobGradeID', Model_Type::getOption('jobgrade'));
    }
    
    /**
     * 首页
     */
    public function indexAction()
    {

    }

    /**
     * 管理员账号
     */
    public function adminListAction()
    {
        $iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        $aParam = $this->getParams();
        $aWhere = [];
        if (!empty($aParam['sUserName'])) {
            $aWhere['sUserName LIKE'] = '%' . trim($aParam['sUserName']) . '%';
        }
        if (!empty($aParam['sRealName'])) {
            $aWhere['sRealName LIKE'] = '%' . trim($aParam['sRealName']) . '%';
        }
        $aWhere['iType'] = Model_User::TYPE_ADMIN;
        $aData = Model_User::getList($aWhere, $iPage);
        $this->assign('aData', $aData);
        $this->assign('aParam', $aParam);
        $this->assign('aStatus', Model_User::$aStatus);
        $this->assign('aRole', Model_Role::getPairRoles(Model_User::TYPE_ADMIN, null));
    }

    /**
     * 企业用户账号
     */
    public function hrListAction()
    {
        $iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        $aParam = $this->getParams();
        $aWhere = [];
        if (!empty($aParam['sUserName'])) {
            $aWhere['sUserName LIKE'] = '%' . trim($aParam['sUserName']) . '%';
        }
        if (!empty($aParam['sRealName'])) {
            $aWhere['sRealName LIKE'] = '%' . trim($aParam['sRealName']) . '%';
        }
        $aWhere['iType'] = Model_User::TYPE_HR;
        $aData = Model_User::getList($aWhere, $iPage);
        $this->assign('aData', $aData);
        $this->assign('aParam', $aParam);
        $this->assign('aStatus', Model_User::$aStatus);
    }

    /**
     * 个人用户账号
     */
    public function userListAction()
    {
        $iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        $aParam = $this->getParams();
        $aWhere = [];
        if (!empty($aParam['sUserName'])) {
            $aWhere['sUserName LIKE'] = '%' . trim($aParam['sUserName']) . '%';
        }
        if (!empty($aParam['sRealName'])) {
            $aWhere['sRealName LIKE'] = '%' . trim($aParam['sRealName']) . '%';
        }
        if (!empty($aParam['sCompanyName'])) {
            $aCompany = Model_User::getUserByRealName(trim($aParam['sCompanyName']), Model_User::TYPE_HR);
            if (!empty($aCompany)) {
                $aWhere['iCreateUserID'] = $aCompany['iUserID'];
            } else {
                $aWhere['iCreateUserID'] = -999;//搞个永远搜不到的数
            }
        }
        $aData = Model_Customer::getList($aWhere, $iPage);
        if (!empty($aData['aList'])) {
            $aCompanyStatus = Yaf_G::getConf('aCompanyState');
            foreach ($aData['aList'] as $key => $value) {
                $aData['aList'][$key]['sCompanyName'] = '';
                $aCompanyParam['iUserID'] = $value['iUserID'];
                $aCompanyParam['iStatus >'] = 0;
                $aCompanys = Model_Company_Company::getPair($aCompanyParam,'iCompanyID','iStatus');
                if (!empty($aCompanys)) {
                    foreach ($aCompanys as $k => $v) {
                        $aCompany = Model_User::getDetail($k);
                        $aData['aList'][$key]['sCompanyName'] .= $aCompany['sRealName']."(<span>".$aCompanyStatus[$v]."</span>)<br>";
                    }
                }
            }
        }
        $this->assign('aData', $aData);
        $this->assign('aParam', $aParam);
        $this->assign('aStatus', Model_User::$aStatus);
    }

    /**
     * 员工锁定
     * @return bool
     */
    public function lockUserAction()
    {
        $iUserID = $this->getParam('id');
        $aUser = Model_User::getDetail($iUserID);
        if (empty($aUser)) {
            return $this->showMsg('该用户不存在！', false);
        }
        $aUser['iStatus'] = ($aUser['iStatus'] == Model_User::STATUS_TYPE_LOCK) ? Model_User::STATUS_TYPE_NORMAL : Model_User::STATUS_TYPE_LOCK;
        $sType = ($aUser['iStatus'] == Model_User::STATUS_TYPE_LOCK) ? '锁定' : '解锁';
        if (Model_User::updData($aUser) > 0) {
            return $this->showMsg('用户' . $sType . '成功！', true);
        } else {
            return $this->showMsg('用户' . $sType . '失败！', false);
        }
    }

    /**
     * 设置权限
     */
    public function setUserRoleAction()
    {
        if ($this->_request->isPost()) {
            $aUser['iRoleID'] = intval($this->getParam('iRoleID'));
            $aUser['iUserID'] = intval($this->getParam('iUserID'));
            if (Model_User::updData($aUser) > 0) {
                return $this->showMsg('权限设置成功！', true);
            } else {
                return $this->showMsg('权限设置失败！', false);
            }
        } else {
            $iUserID = $this->getParam('id');
            $aUser = Model_User::getDetail($iUserID);
            if (empty($aUser)) {
                return $this->showMsg('该用户不存在！', false);
            }
            $this->assign('aUser', $aUser);
            $this->assign('aRole', Model_Role::getPairRoles(Model_User::TYPE_ADMIN, null));
        }
    }

    /**
     * 员工密码重置
     * @return bool
     */
    public function resetUserPwdAction()
    {
        $iUserID = $this->getParam('id');
        $aUser = Model_User::getDetail($iUserID);
        if (empty($aUser)) {
            return $this->showMsg('该用户不存在！', false);
        }
        $aUser['sPassword'] = md5(Yaf_G::getConf('cryptkey', 'cookie') . $aUser['sUserName']);
        if (Model_User::updData($aUser) > 0) {
            return $this->showMsg('用户密码重置成功！', true);
        } else {
            return $this->showMsg('用户密码已重置', false);
        }
    }

    /**
     * 员工修改
     */
    public function editAction()
    {
        if ($this->_request->isPost()) {
            $aUser = $this->_checkData(2);
            if (empty($aUser)) {
                return null;
            }
            $aUser['iLastUpdateUserID'] = $this->aCurrUser['iUserID'];
            if (Model_User::updData($aUser) > 0) {
                return $this->showMsg('用户编辑成功！', true);
            } else {
                return $this->showMsg('用户编辑失败！', false);
            }
        } else {
            $iUserID = $this->getParam('id');
            $aUser = Model_User::getDetail($iUserID);
            if (empty($aUser)) {
                return $this->showMsg('该用户不存在！', false);
            }
            $this->assign('aUser', $aUser);
            $this->assign('aSex', Model_User::$aSex);
            $this->assign('aStatus', Model_User::$aStatus);
            $this->assign('aDept', Model_Type::getOption('dept'));
            $this->assign('aJobTitleID', Model_Type::getOption('jobgrade'));
        }
    }

    /**
     * 增加员工
     */
    public function addAction()
    {
        if ($this->_request->isPost()) {
            $aUser = $this->_checkData(1);
            if (empty($aUser)) {
                return null;
            }
            $aUser['sUserName'] = Model_User::initUserName(1);
            $aUser['iType'] = Model_User::TYPE_ADMIN;
            $aUser['iStatus'] = Model_User::STATUS_TYPE_NORMAL;
            $aUser['iIsCheck'] = Model_User::ISCHECK;
            $aUser['sPassword'] = md5(Yaf_G::getConf('cryptkey', 'cookie') . $aUser['sUserName']);
            $aUser['iCreateUserID'] = $aUser['iLastUpdateUserID'] = $this->aCurrUser['iUserID'];
            if (Model_User::addData($aUser) > 0) {
                return $this->showMsg('用户增加成功！', true);
            } else {
                return $this->showMsg('用户增加失败！', false);
            }
        } else {
            $this->assign('aSex', Model_User::$aSex);
            $this->assign('aStatus', Model_User::$aStatus);
            $this->assign('aDept', Model_Type::getOption('dept'));
            $this->assign('aJobTitleID', Model_Type::getOption('jobgrade'));
        }
    }

    /**
     * 请求数据检测
     * @param int $iType 1:添加，2:修改
     * @return array|bool
     */
    public function _checkData($iType)
    {
        $aParam = $this->getParams();
        if (!empty($aParam['sMobile']) && !Util_Validate::isMobile($aParam['sMobile'])) {
            return $this->showMsg('手机不符合规范！', false);
        }
        if (!empty($aParam['sEmail']) && !Util_Validate::isEmail($aParam['sEmail'])) {
            return $this->showMsg('邮箱不符合规范！', false);
        }
        if ($iType == 2) {
            if (empty($aParam['iUserID'])) {
                return $this->showMsg('非法操作！', false);
            }
            if (!Model_User::getDetail($aParam['iUserID'])) {
                return $this->showMsg('用户不存在！', false);
            }
        }
        //验证邮箱
        if (!empty($aParam['sEmail'])) {
            $aUser = Model_User::getUserByEmail($aParam['sEmail'], Model_User::TYPE_ADMIN);
            if (!empty($aUser['iUserID']) && $aUser['iUserID'] != $aParam['iUserID']) {
                return $this->showMsg('该邮箱已被注册！', false);
            }
        }
        //验证手机是否被注册
        if (!empty($aParam['sMobile'])) {
            $aUser = Model_User::getUserByMobile($aParam['sMobile'], Model_User::TYPE_ADMIN);
            if (!empty($aUser['iUserID']) && $aUser['iUserID'] != $aParam['iUserID']) {
                return $this->showMsg('该手机已被注册！', false);
            }
        }
        return $aParam;
    }

    /**
     * 客户列表
     */
    public function clientlistAction()
    {
        $iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        $aParam = $this->getParams();
        $aWhere = [];
        if (!empty($aParam['sUserName'])) {
            $aWhere['sUserName LIKE'] = '%' . trim($aParam['sUserName']) . '%';
        }
        if (!empty($aParam['sRealName'])) {
            $aWhere['sRealName LIKE'] = '%' . trim($aParam['sRealName']) . '%';
        }
        if (!empty($aParam['iProperty'])) {
            $aWhere['iProperty'] = intval($aParam['iProperty']);
        }
        if (!empty($aParam['iRelationLevel'])) {
            $aWhere['iRelationLevel'] = intval($aParam['iRelationLevel']);
        }
        if (!empty($aParam['iCreditLevel'])) {
            $aWhere['iCreditLevel'] = intval($aParam['iCreditLevel']);
        }
        if (!empty($aParam['iChannel'])) {
            $aWhere['iChannel'] = intval($aParam['iChannel']);
        }
        $aWhere['iType'] = Model_User::TYPE_HR;

        $aData = Model_User::getList($aWhere, $iPage);
        $aProperty = Yaf_G::getConf('aProperty');
        $aRelationLevel = Yaf_G::getConf('aRelationLevel');
        $aCreditLevel = Yaf_G::getConf('aCreditLevel');
        $aChannel = Yaf_G::getConf('aChannel');
        $aCustomerManager = Model_User::getPairUser(Model_User::TYPE_ADMIN, Model_User::STATUS_TYPE_NORMAL);
        $this->assign('aData', $aData);
        $this->assign('aParam', $aParam);
        $this->assign('aIsCheck', Model_User::$aIsCheck);
        $this->assign('aProperty', $aProperty);
        $this->assign('aCreditLevel', $aCreditLevel);
        $this->assign('aRelationLevel', $aRelationLevel);
        $this->assign('aChannel', $aChannel);
        $this->assign('aCustomerManager', $aCustomerManager);
    }

    /**
     * 客户审核列表
     */
    public function clientcheckAction()
    {
        $iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        $aParam = $this->getParams();
        $aWhere = [];
        if (!empty($aParam['sUserName'])) {
            $aWhere['sUserName LIKE'] = '%' . trim($aParam['sUserName']) . '%';
        }
        if (!empty($aParam['sRealName'])) {
            $aWhere['sRealName LIKE'] = '%' . trim($aParam['sRealName']) . '%';
        }
        if (!empty($aParam['iProperty'])) {
            $aWhere['iProperty'] = intval($aParam['sRealName']);
        }
        if (!empty($aParam['iRelationLevel'])) {
            $aWhere['iRelationLevel'] = intval($aParam['iRelationLevel']);
        }
        if (!empty($aParam['iCreditLevel'])) {
            $aWhere['iCreditLevel'] = intval($aParam['iCreditLevel']);
        }
        if (!empty($aParam['iChannel'])) {
            $aWhere['iChannel'] = intval($aParam['iChannel']);
        }
        $aWhere['iType'] = Model_User::TYPE_HR;
        $aWhere['iIsCheck IN'] = [Model_User::NOCHECK, Model_User::REFUSE];

        $aData = Model_User::getList($aWhere, $iPage);
        $aProperty = Yaf_G::getConf('aProperty');
        $aRelationLevel = Yaf_G::getConf('aRelationLevel');
        $aCreditLevel = Yaf_G::getConf('aCreditLevel');
        $aChannel = Yaf_G::getConf('aChannel');
        $aCustomerManager = Model_User::getPairUser(Model_User::TYPE_ADMIN, Model_User::STATUS_TYPE_NORMAL);
        $this->assign('aData', $aData);
        $this->assign('aParam', $aParam);
        $this->assign('aIsCheck', Model_User::$aIsCheck);
        $this->assign('aProperty', $aProperty);
        $this->assign('aCreditLevel', $aCreditLevel);
        $this->assign('aRelationLevel', $aRelationLevel);
        $this->assign('aChannel', $aChannel);
        $this->assign('aCustomerManager', $aCustomerManager);
    }

    /**
     * 我的客户
     */
    public function myClientAction()
    {
        $iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        $aParam = $this->getParams();
        $aWhere = [];
        if (!empty($aParam['sUserName'])) {
            $aWhere['sUserName LIKE'] = '%' . trim($aParam['sUserName']) . '%';
        }
        if (!empty($aParam['sRealName'])) {
            $aWhere['sRealName LIKE'] = '%' . trim($aParam['sRealName']) . '%';
        }
        if (!empty($aParam['iProperty'])) {
            $aWhere['iProperty'] = intval($aParam['sRealName']);
        }
        if (!empty($aParam['iRelationLevel'])) {
            $aWhere['iRelationLevel'] = intval($aParam['iRelationLevel']);
        }
        if (!empty($aParam['iCreditLevel'])) {
            $aWhere['iCreditLevel'] = intval($aParam['iCreditLevel']);
        }
        if (!empty($aParam['iChannel'])) {
            $aWhere['iChannel'] = intval($aParam['iChannel']);
        }
        $aWhere['iType'] = Model_User::TYPE_HR;
        $aWhere['iCustomerManager'] = $this->aCurrUser['iUserID'];
        $aData = Model_User::getList($aWhere, $iPage);
        $aProperty = Yaf_G::getConf('aProperty');
        $aRelationLevel = Yaf_G::getConf('aRelationLevel');
        $aCreditLevel = Yaf_G::getConf('aCreditLevel');
        $aChannel = Yaf_G::getConf('aChannel');
        $aCustomerManager = Model_User::getPairUser(Model_User::TYPE_ADMIN, Model_User::STATUS_TYPE_NORMAL);
        $this->assign('aData', $aData);
        $this->assign('aParam', $aParam);
        $this->assign('aIsCheck', Model_User::$aIsCheck);
        $this->assign('aProperty', $aProperty);
        $this->assign('aCreditLevel', $aCreditLevel);
        $this->assign('aRelationLevel', $aRelationLevel);
        $this->assign('aChannel', $aChannel);
        $this->assign('aCustomerManager', $aCustomerManager);
    }

    /**
     * 客户信息
     */
    public function clientInfoAction()
    {
        $this->_clientMenu(1);

        $iUserID = $this->getParam('id');
        $aUser = Model_User::getDetail($iUserID);
        if (empty($aUser)) {
            return $this->showMsg('该用户不存在！', false);
        }
        $aIndustry = Yaf_G::getConf('aIndustry');
        $aProperty = Yaf_G::getConf('aProperty');
        $aCreditLevel = Yaf_G::getConf('aCreditLevel');
        $aRelationLevel = Yaf_G::getConf('aRelationLevel');
        $aChannel = Yaf_G::getConf('aChannel');
        $aUserNum = Yaf_G::getConf('aUserNum');
        $aAuthenticateType = Yaf_G::getConf('aAuthenticateType');
        $aSendMassage = Yaf_G::getConf('aSendMassage');
        $aLockUser = Yaf_G::getConf('aLockUser');
        $aCanLoadReport = Yaf_G::getConf('aCanLoadReport');
        $aIsCheck = Model_User::$aIsCheck;
        $aParam['where']['iStatus'] = Model_User::STATUS_TYPE_NORMAL;
        $aParam['where']['iType'] = Model_User::TYPE_HR;
        $aCustomerManager = Model_User::getPairUser(Model_User::TYPE_ADMIN, Model_User::STATUS_TYPE_NORMAL);
        $aUser['iLockUser'] = ($aUser['iStatus'] == Model_User::STATUS_TYPE_LOCK) ? 1 : 0;
        $this->assign('aIndustry', $aIndustry);
        $this->assign('aProperty', $aProperty);
        $this->assign('aCreditLevel', $aCreditLevel);
        $this->assign('aRelationLevel', $aRelationLevel);
        $this->assign('aChannel', $aChannel);
        $this->assign('aUserNum', $aUserNum);
        $this->assign('aCustomerManager', $aCustomerManager);
        $this->assign('aAuthenticateType', $aAuthenticateType);
        $this->assign('aSendMassage', $aSendMassage);
        $this->assign('aLockUser', $aLockUser);
        $this->assign('aCanLoadReport', $aCanLoadReport);
        $this->assign('aIsCheck', $aIsCheck);
        $this->assign('aUser', $aUser);
    }

    /**
     * 联系人信息
     */
    public function contecterInfoAction()
    {
        $this->_clientMenu(2);
        $iUserID = intval($this->getParam('id'));
        $iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        if (empty($iUserID)) {
            return $this->showMsg('参数有误！', false);
        }
        $aUser = Model_User::getDetail($iUserID);
        if (empty($aUser)) {
            return $this->showMsg('该用户不存在！', false);
        }
        $aParam['iStatus'] = 1;
        $aParam['iUserID'] = $iUserID;
        $aData = Model_Contecter::getList($aParam, $iPage);
        $this->assign('aData', $aData);
        $this->assign('iUserID', $iUserID);
        $this->assign('aUser', $aUser);
    }

    /**
     * 沟通纪录
     */
    public function communicateAction()
    {
        $this->_clientMenu(3);
        $iUserID = intval($this->getParam('id'));
        $iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        if (empty($iUserID)) {
            return $this->showMsg('参数有误！', false);
        }
        $aUser = Model_User::getDetail($iUserID);
        if (empty($aUser)) {
            return $this->showMsg('该用户不存在！', false);
        }
        $iStime = strtotime($this->getParam('iStime'));
        $iEtime = strtotime($this->getParam('iEtime'));
        $sContent = trim($this->getParam('sContent'));
        if (!empty($iStime)) {
            $aParam['iTime >= '] = $iStime;
        }
        if (!empty($iEtime)) {
            $aParam['iEtime < '] = $iEtime;
        }
        if (!empty($sContent)) {
            $aParam['sContent LIKE'] = '%' . $sContent . '%';
        }
        $aParam['iStatus'] = 1;
        $aParam['iUserID'] = $iUserID;
        $aData = Model_Communicate::getList($aParam, $iPage);
        $aCommunicateLevel = Yaf_G::getConf('aCommunicateLevel');
        $aCommunicateResult = Yaf_G::getConf('aCommunicateResult');
        $aParam['iStime'] = $iStime;
        $aParam['iEtime'] = $iEtime;
        $aParam['sContent'] = $sContent;
        $this->assign('aParam', $aParam);
        $this->assign('aCommunicateLevel', $aCommunicateLevel);
        $this->assign('aCommunicateResult', $aCommunicateResult);
        $this->assign('aData', $aData);
        $this->assign('aUser', $aUser);
    }

    /**
     * 定制产品
     */
    public function clientProductAction()
    {
        $this->_clientMenu(4);
        $iPageSize = self::PAGESIZE;
        $iUserID = intval($this->getParam('id'));
        $iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        $aParam = $this->getParams();
        if (empty($iUserID)) {
            return $this->showMsg('参数有误！', false);
        }
        $aUser = Model_User::getDetail($iUserID);
        if (empty($aUser)) {
            return $this->showMsg('员工不存在！', false);
        }
        if (!empty($aParam['sKeyword'])) {
            $aParam['sWhere'] = '(sProductCode="' . $aParam['sKeyword'] . '" OR sProductName LIKE "%' . $aParam['sKeyword'] . '%")';
        }
        $aParam['iStatus'] = 1;

        $aProductChannel = Model_ProductChannel::getProductByChannel($aUser['iChannel']);
        if (!empty($aProductChannel)) {
            $aTmp = [];
            foreach ($aProductChannel as $key => $value) {
                $aTmp[] = $value['iProductID'];
            }
            $aParam['iProductID IN'] = $aTmp;
        }
        if (!empty($aParam['iProductID IN'])) {
            $aData = Model_Product::getList($aParam, $iPage, 'iUpdateTime DESC', $iPageSize);
        } else {
            $aData['aList'] = [];
        }


        if (!empty($aData['aList'])) {
            //整合需要的数据
            foreach ($aData['aList'] as $key => $value) {
                if ($value['iCanCompany'] != 1) {//去掉没有开通公司渠道的产品
                    unset($aData['aList'][$key]);
                }
                //基础产品单项数目
                $aProductItem = Model_ProductItem::getProductItems($value['iParentID'], Model_ProductItem::BASEPRODUCT,1,true);
                $aHasStore = Model_ProductStore::getProductStores($value['iProductID'], Model_ProductStore::EXPANDPRODUCT);
                $aData['aList'][$key]['iStoreNum'] = count($aHasStore);
                $aData['aList'][$key]['iCityNum'] = 0;
                $aData['aList'][$key]['iSupplierNum'] = 0;
                $aData['aList'][$key]['iComChannelNum'] = 0;
                $aData['aList'][$key]['iComUserNum'] = 0;
                $aData['aList'][$key]['iIndivChannelNum'] = 0;
                $aData['aList'][$key]['iIndivUserNum'] = 0;
                $aData['aList'][$key]['iItemChange'] = '';
                //统计单项变化
                $aSonItem = Model_ProductItem::getProductItems($value['iProductID'], Model_ProductItem::EXPANDPRODUCT,1,true);

                //单项交集
                $aItemIntersect = array_intersect($aProductItem,$aSonItem);
                //单项差集
                $aItemDiff = array_diff($aProductItem,$aSonItem);
                $aItemDiff1 = array_diff($aSonItem,$aProductItem);
                $sItemChange = '';
                if (!empty($aItemDiff)) {
                    $sItemChange .= '减少'.count($aItemDiff).'个';
                }
                if (!empty($aItemDiff1)) {
                    if (!empty($sItemChange)) {
                        $sItemChange .= ',';
                    }
                    $sItemChange .= '增加'.count($aItemDiff1).'个';
                }
                if (empty($aItemDiff) && empty($aItemDiff1)) {
                    $sItemChange = '无变化';
                }
                $aData['aList'][$key]['iItemChange'] = $sItemChange;
                //统计城市和供应商数目
                if (!empty($aHasStore)) {
                    foreach ($aHasStore as $k => $val) {
                        $aProductData = Model_Store::getDetail($val['iStoreID']);
                        $aHasStore[$key]['aStore'] = $aProductData;
                        //按城市分组
                        $aCityTmp[$aProductData['iCityID']][] = 1;
                        //按供应商分组
                        $aSupplierTmp[$aProductData['iSupplierID']][] = 1;
                    }
                    $aData['aList'][$key]['iCityNum'] = count($aCityTmp);
                    $aData['aList'][$key]['iSupplierNum'] = count($aSupplierTmp);
                }
                //统计渠道和客户数量
                if (!empty($value['iCanCompany'])) {
                    $aChannel = Model_ProductChannel::getChannelInfoByProductID($value['iProductID'], Model_ProductChannel::TYPE_COMPANY);
                    $aData['aList'][$key]['iComChannelNum'] = count($aChannel);
                    if (!empty($aChannel)) {
                        $iNumTmp = 0;
                        $aViewList = [];
                        foreach ($aChannel as $k => $val) {
                            //统计渠道所有支持数目
                            if ($val['iViewRange'] == 0 || $val['iViewRange'] == 2) {//全部和不可见要统计渠道所有支持数目
                                $aUserParam['where']['iStatus >'] = 0;
                                $aUserParam['where']['iChannel'] = $val['iChannelID'];
                                $aChannelUser = Model_User::getCnt($aUserParam);
                                $iNumTmp += $aChannelUser;
                                if ($val['iViewRange'] == 2) {
                                    $aViewList = Model_Product::getUserViewlist($value['iProductID'], Model_ProductChannel::TYPE_COMPANY, $val['iChannelID']);
                                    $iNumTmp = $iNumTmp - count($aViewList);
                                }
                            } else {
                                $aViewList = Model_Product::getUserViewlist($value['iProductID'], Model_ProductChannel::TYPE_COMPANY, $val['iChannelID']);
                                if (!empty($aViewList)) {
                                    $iNumTmp += count($aViewList);
                                }
                            }
                        }
                        $aData['aList'][$key]['iComUserNum'] = $iNumTmp;
                    }
                }
                if (!empty($value['iCanIndividual'])) {
                    $aChannel = Model_ProductChannel::getChannelInfoByProductID($value['iProductID'], Model_ProductChannel::TYPE_INDIVIDUAL);
                    $aData['aList'][$key]['iIndivChannelNum'] = count($aChannel);
                    if (!empty($aChannel)) {
                        $iNumTmp = 0;
                        $aViewList = [];
                        foreach ($aChannel as $k => $val) {
                            //统计渠道所有支持数目
                            if ($val['iViewRange'] == 0 || $val['iViewRange'] == 2) {//全部和不可见要统计渠道所有支持数目
                                $aUserParam['where']['iStatus >'] = 0;
                                $aUserParam['where']['iChannel'] = $val['iChannelID'];
                                $aChannelUser = Model_User::getCnt($aUserParam);
                                $iNumTmp += $aChannelUser;
                                if ($val['iViewRange'] == 2) {
                                    $aViewList = Model_Product::getUserViewlist($value['iProductID'], Model_ProductChannel::TYPE_INDIVIDUAL, $val['iChannelID']);
                                    $iNumTmp = $iNumTmp - count($aViewList);
                                }
                            } else {
                                $aViewList = Model_Product::getUserViewlist($value['iProductID'], Model_ProductChannel::TYPE_INDIVIDUAL, $val['iChannelID']);
                                if (!empty($aViewList)) {
                                    $iNumTmp += count($aViewList);
                                }
                            }
                        }
                        $aData['aList'][$key]['iIndivUserNum'] = $iNumTmp;
                    }
                }
            }
        }
        $aStatus = Yaf_G::getConf('aStatus', 'product');
        $this->assign('aStatus', $aStatus);
        $this->assign('aParam', $aParam);
        $this->assign('aData', $aData);
        $this->assign('aUser', $aUser);
        //print_r($aUser);die;
    }

    /**
     * 客户员工信息
     */
    public function clientEmployeeAction()
    {
        $this->_clientMenu(5);
        $iUserID = intval($this->getParam('id'));
        if (empty($iUserID)) {
            return $this->showMsg('参数有误！', false);
        }
        $aUser = Model_User::getDetail($iUserID);
        if (empty($aUser)) {
            return $this->showMsg('该用户不存在！', false);
        }
        $iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        $aParam = $this->getParams();
        $aWhere = [];
        if (!empty($aParam['sUserName'])) {
            $aWhere['sUserName LIKE'] = '%' . trim($aParam['sUserName']) . '%';
        }
        if (!empty($aParam['sIdentityCard'])) {
            $aWhere['sIdentityCard'] = intval($aParam['sIdentityCard']);
        }
        if (!empty($aParam['iDeptID'])) {
            $aWhere['iDeptID'] = intval($aParam['iDeptID']);
        }
        if (!empty($aParam['sRealName'])) {
            $aWhere['sRealName LIKE'] = '%' . trim($aParam['sRealName']) . '%';
        }
        if (!empty($aParam['iStatus'])) {
            $aWhere['iStatus'] = intval($aParam['iStatus']);
        }
        $aWhere['iCreateUserID'] = $iUserID;
        $aData = Model_Customer::getList($aWhere, $iPage);
        if (!empty($aData['aList'])) {
            foreach ($aData['aList'] as $key => &$value) {
                $aUserCompanyDataParam['iCompanyID'] = $value['iCreateUserID'];
                $aUserCompanyDataParam['iUserID'] = $value['iUserID'];
                $aUserCompanyData = Model_CustomerCompany::getRow($aUserCompanyDataParam);
                if (!empty($aUserCompanyData)) {
                    $value['iDeptID'] = $aUserCompanyData['iDeptID'];
                    $value['iJobGradeID'] = $aUserCompanyData['iJobGradeID'];
                    $value['iJobTitleID'] = $aUserCompanyData['iJobTitleID'];
                    $value['sJobTitleName'] = $aUserCompanyData['sJobTitleName'];
                }
            }
        }
        $this->assign('aData', $aData);
        $this->assign('aParam', $aParam);
        $this->assign('aStatus', Model_User::$aStatus);
        $this->assign('aUser', $aUser);
        $this->assign('aDepartment', Model_Company_Department::getPairDepartment($iUserID));
    }

    /**
     * 客户订单信息
     */
    public function clientOrderAction()
    {
        if ($this->_request->isPost()) {

        } else {

        }
    }

    /**
     * 客户体检计划
     */
    public function clientplanAction()
    {
        $page = $this->getParam('page');
        $userId = $this->getParam('id');
        $aUser = Model_User::getDetail($userId);

        Util_Cookie::set('iHrID', $userId, '86400 * 7');
        
        $this->_clientMenu(7);
        $this->assign('aUser', $aUser);

        $params = $this->getParams();
        $where['iHRID'] = $userId;
        !empty($params['sPlanName']) ? $where['sPlanName'] = $params['sPlanName'] : '';
        isset($params['iStatus']) && ($params['iStatus'] != '-1') ? $where['iStatus'] = intval($params['iStatus']) : '';
        $this->assign('aParam', $params);

        // $aProduct = Model_Physical_Product::getAll([
        //     'iUserID' => $userId,
        //     'iType' => Model_Physical_Product::TYPE_PRODUCT_PLAN,
        //     'iStatus >' => Model_Physical_Product::STATUS_UNCONFIRM
        // ]);

        // $aProduct = Model_OrderCard::getAll([
        //     'iCompanyID' => $userId,
        //     'iType' => Model_Physical_Product::TYPE_PRODUCT_PLAN,
        //     'iStatus >' => Model_Physical_Product::STATUS_UNCONFIRM
        // ]);

        
        // if ($aProduct) {
        //     $sPlanIDs = '';
        //     foreach ($aProduct as $key => $value) {
        //         if ($value['iPlanID']) {
        //             $aPlanID[] = $value['iPlanID'];
        //         }
        //     }
        //     if ($aPlanID) {
        //         $sPlanIDs = implode(',', $aPlanID);
        //         if ($sPlanIDs) {
        //             $where['iAutoID IN'] = $sPlanIDs;
        //         } else {
        //             return null;
        //         }
        //     } else {
        //         return null;
        //     }
        // } else {
        //     return null;
        // }

        $aPlan = Model_Physical_Plan::getList($where, $page, 'iUpdateTime Desc');
        if ($aPlan['aList']) {
            foreach ($aPlan['aList'] as $key => $value) {
                $aPlan['aList'][$key]['sPublishTime'] = date('Y-m-d H:i:s', $value['iCreateTime']);

                switch ($value['iStatus']) {
                    case 1:
                        $sStatus = '进行中';
                        break;
                    case 2:
                        $sStatus = '已结束';
                        break;
                    default:
                        $sStatus = '未启动';
                        break;
                }
                $aPlan['aList'][$key]['sStatus'] = $sStatus;
            }
        }

        $this->assign('aPlan', $aPlan);
    }

    /**
     * 查看审核详情
     */
    public function clientCheckDetailAction()
    {
        if ($this->_request->isPost()) {
            $aParam = $this->getParams();
            $iUserID = $this->getParam('iUserID');
            $aUser = Model_User::getDetail($iUserID);
            if (empty($aUser)) {
                return $this->showMsg('该用户不存在！', false);
            }
            $sCheckStatus = $aParam['iIsCheck'] == 1 ? '通过' : '拒绝';
            if (Model_User::updData($aParam)) {
                if (!empty($aParam['iIfSendAccount'])) {
                    //发送账号todo
                }
                return $this->showMsg($sCheckStatus . '成功！', true);
            } else {
                return $this->showMsg($sCheckStatus . '失败！', false);
            }
        } else {
            $iUserID = $this->getParam('id');
            $iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
            $aUser = Model_User::getDetail($iUserID);
            if (empty($aUser)) {
                return $this->showMsg('该用户不存在！', false);
            }
            //联系人相关
            $aParam['iStatus'] = 1;
            $aParam['iUserID'] = $iUserID;
            $aData = Model_Contecter::getList($aParam, $iPage);
            //详情信息相关
            $aIndustry = Yaf_G::getConf('aIndustry');
            $aProperty = Yaf_G::getConf('aProperty');
            $aCreditLevel = Yaf_G::getConf('aCreditLevel');
            $aRelationLevel = Yaf_G::getConf('aRelationLevel');
            $aChannel = Yaf_G::getConf('aChannel');
            $aUserNum = Yaf_G::getConf('aUserNum');
            $aAuthenticateType = Yaf_G::getConf('aAuthenticateType');
            $aSendMassage = Yaf_G::getConf('aSendMassage');
            $aLockUser = Yaf_G::getConf('aLockUser');
            $aCanLoadReport = Yaf_G::getConf('aCanLoadReport');
            $aIsCheck = Model_User::$aIsCheck;
            $aParam['where']['iStatus'] = Model_User::STATUS_TYPE_NORMAL;
            $aParam['where']['iType'] = Model_User::TYPE_ADMIN;
            $aCustomerManager = Model_User::getPairUser(Model_User::TYPE_ADMIN, Model_User::STATUS_TYPE_NORMAL);
            $aUser['iLockUser'] = ($aUser['iStatus'] == Model_User::STATUS_TYPE_LOCK) ? 1 : 0;
            $this->assign('aIndustry', $aIndustry);
            $this->assign('aProperty', $aProperty);
            $this->assign('aCreditLevel', $aCreditLevel);
            $this->assign('aRelationLevel', $aRelationLevel);
            $this->assign('aChannel', $aChannel);
            $this->assign('aUserNum', $aUserNum);
            $this->assign('aCustomerManager', $aCustomerManager);
            $this->assign('aAuthenticateType', $aAuthenticateType);
            $this->assign('aSendMassage', $aSendMassage);
            $this->assign('aLockUser', $aLockUser);
            $this->assign('aCanLoadReport', $aCanLoadReport);
            $this->assign('aIsCheck', $aIsCheck);
            $this->assign('aUser', $aUser);
            $this->assign('aData', $aData);
        }
    }

    /**
     * 新增客户
     */
    public function addClientAction()
    {
        if ($this->_request->isPost()) {
            $aUser = $this->_checkClientData(1);
            if (empty($aUser)) {
                return null;
            }
            $aUser['iType'] = Model_User::TYPE_HR;
            $aUser['iIsCheck'] = Model_User::NOCHECK;
            $aUser['sPassword'] = md5(Yaf_G::getConf('cryptkey', 'cookie') . $aUser['sUserName']);
            $aUser['iCreateUserID'] = $aUser['iLastUpdateUserID'] = $this->aCurrUser['iUserID'];
            $aRole = Model_Role::getRoleByName(Model_Role::HRROLENAME, 2);
            if (empty($aRole)) {
                return $this->showMsg('请先在权限中设置一个hr系统管理员账号', false);
            }
            $aUser['iRoleID'] = $aRole['iRoleID'];
            if ($aUser['iLockUser'] == 1) {
                $aUser['iStatus'] = Model_User::STATUS_TYPE_LOCK;
            } else {
                $aUser['iStatus'] = Model_User::STATUS_TYPE_NORMAL;
            }
            if (Model_User::addData($aUser) > 0) {
                return $this->showMsg('用户增加成功！', true);
            } else {
                return $this->showMsg('用户增加失败！', false);
            }
        } else {
            $aIndustry = Yaf_G::getConf('aIndustry');
            $aProperty = Yaf_G::getConf('aProperty');
            $aCreditLevel = Yaf_G::getConf('aCreditLevel');
            $aRelationLevel = Yaf_G::getConf('aRelationLevel');
            $aChannel = Yaf_G::getConf('aChannel');
            $aUserNum = Yaf_G::getConf('aUserNum');
            $aAuthenticateType = Yaf_G::getConf('aAuthenticateType');
            $aSendMassage = Yaf_G::getConf('aSendMassage');
            $aLockUser = Yaf_G::getConf('aLockUser');
            $aCanLoadReport = Yaf_G::getConf('aCanLoadReport');
            $aParam['where']['iStatus'] = Model_User::STATUS_TYPE_NORMAL;
            $aParam['where']['iType'] = Model_User::TYPE_ADMIN;
            $aCustomerManager = Model_User::getAll($aParam);
            $this->assign('aIndustry', $aIndustry);
            $this->assign('aProperty', $aProperty);
            $this->assign('aCreditLevel', $aCreditLevel);
            $this->assign('aRelationLevel', $aRelationLevel);
            $this->assign('aChannel', $aChannel);
            $this->assign('aUserNum', $aUserNum);
            $this->assign('aCustomerManager', $aCustomerManager);
            $this->assign('aAuthenticateType', $aAuthenticateType);
            $this->assign('aSendMassage', $aSendMassage);
            $this->assign('aLockUser', $aLockUser);
            $this->assign('aCanLoadReport', $aCanLoadReport);
        }
    }

    /**
     * 客户修改
     */
    public function editClientAction()
    {
        if ($this->_request->isPost()) {
            $aUser = $this->_checkClientData(2);
            if (empty($aUser)) {
                return null;
            }
            if ($aUser['iLockUser'] == 1) {
                $aUser['iStatus'] = Model_User::STATUS_TYPE_LOCK;
            } else {
                $aUser['iStatus'] = Model_User::STATUS_TYPE_NORMAL;
            }
            $aUser['iLastUpdateUserID'] = $this->aCurrUser['iUserID'];
            if (Model_User::updData($aUser) > 0) {
                return $this->showMsg('用户编辑成功！', true);
            } else {
                return $this->showMsg('用户编辑失败！', false);
            }
        } else {
            $iUserID = $this->getParam('id');
            $aUser = Model_User::getDetail($iUserID);
            if (empty($aUser)) {
                return $this->showMsg('该用户不存在！', false);
            }
            $aUser['iLockUser'] = ($aUser['iStatus'] == Model_User::STATUS_TYPE_LOCK) ? 1 : 0;
            $aIndustry = Yaf_G::getConf('aIndustry');
            $aProperty = Yaf_G::getConf('aProperty');
            $aCreditLevel = Yaf_G::getConf('aCreditLevel');
            $aRelationLevel = Yaf_G::getConf('aRelationLevel');
            $aChannel = Yaf_G::getConf('aChannel');
            $aUserNum = Yaf_G::getConf('aUserNum');
            $aAuthenticateType = Yaf_G::getConf('aAuthenticateType');
            $aSendMassage = Yaf_G::getConf('aSendMassage');
            $aLockUser = Yaf_G::getConf('aLockUser');
            $aCanLoadReport = Yaf_G::getConf('aCanLoadReport');
            $aParam['where']['iStatus'] = Model_User::STATUS_TYPE_NORMAL;
            $aParam['where']['iType'] = Model_User::TYPE_ADMIN;
            $aCustomerManager = Model_User::getAll($aParam);
            $this->assign('aIndustry', $aIndustry);
            $this->assign('aProperty', $aProperty);
            $this->assign('aCreditLevel', $aCreditLevel);
            $this->assign('aRelationLevel', $aRelationLevel);
            $this->assign('aChannel', $aChannel);
            $this->assign('aUserNum', $aUserNum);
            $this->assign('aCustomerManager', $aCustomerManager);
            $this->assign('aAuthenticateType', $aAuthenticateType);
            $this->assign('aSendMassage', $aSendMassage);
            $this->assign('aLockUser', $aLockUser);
            $this->assign('aCanLoadReport', $aCanLoadReport);
            $this->assign('aUser', $aUser);
        }
    }

    /**
     * 请求数据检测
     * @param int $iType 1:添加，2:修改
     * @return array|bool
     */
    public function _checkClientData($iType)
    {
        $aParam = $this->getParams();
        if (!Util_Validate::isCLength($aParam['sRealName'], 1, 20)) {
            return $this->showMsg('客户名称为6到20个字！', false);
        }

        if (empty($aParam['sUserName'])) {
            return $this->showMsg('客户编号/账号不能为空', false);
        }

        if (empty($aParam['sUserShortName'])) {
           // return $this->showMsg('客户简称不能为空', false);
        }

        if (!empty($aParam['sMobile']) && !Util_Validate::isMobile($aParam['sMobile'])) {
            return $this->showMsg('手机不符合规范！', false);
        }
        if (!Util_Validate::isEmail($aParam['sEmail'])) {
            return $this->showMsg('邮箱不符合规范！', false);
        }
        if ($iType == 2) {
            if (empty($aParam['iUserID'])) {
                return $this->showMsg('非法操作！', false);
            }
            $aUser = Model_User::getDetail($aParam['iUserID']);
            if (empty($aUser)) {
                return $this->showMsg('用户不存在！', false);
            }
            if ($aParam['sUserName'] != $aUser['sUserName']) {//如果修改用户名，密码重置
                $aParam['sPassword'] = md5(Yaf_G::getConf('cryptkey', 'cookie') . $aParam['sUserName']);
            }
        }
        //验证邮箱
        if (!empty($aParam['sEmail'])) {
            $aUser = Model_User::getUserByEmail($aParam['sEmail'], Model_User::TYPE_HR);
            if (!empty($aUser['iUserID']) && $aUser['iUserID'] != $aParam['iUserID']) {
                return $this->showMsg('该邮箱已被注册！', false);
            }
        }
        //验证手机是否被注册
        if (!empty($aParam['sMobile'])) {
            $aUser = Model_User::getUserByMobile($aParam['sMobile'], Model_User::TYPE_HR);
            if (!empty($aUser['iUserID']) && $aUser['iUserID'] != $aParam['iUserID']) {
                return $this->showMsg('该手机已被注册！', false);
            }
        }

        //验证手机是否被注册
        if (!empty($aParam['sUserName'])) {
            $aUser = Model_User::getUserByUserName($aParam['sUserName'], Model_User::TYPE_HR);
            if (!empty($aUser['iUserID']) && $aUser['iUserID'] != $aParam['iUserID']) {
                return $this->showMsg('该账号已被注册！', false);
            }
        }
        return $aParam;
    }

    public function chgpwdAction ()
    {
        if ($this->_request->isPost()) {
            $sOldPwd = strval($this->getParam('sOldPwd'));
            $sNewPwd = strval($this->getParam('sNewPwd'));
            $sConfirmPwd = strval($this->getParam('sConfirmPwd'));

            if (!$sOldPwd || !$sNewPwd || !$sConfirmPwd) {
                return $this->showMsg('请输入密码', false);
            }
            if ($sConfirmPwd != $sNewPwd) {
                return $this->showMsg('新旧密码不一致,请重新输入', false);
            }
            
            $where = [
                'iUserID' => $this->aCurrUser['iUserID'],
                'iType' => Model_User::TYPE_ADMIN,
                'iStatus' => Model_User::STATUS_TYPE_NORMAL         
            ];          
            $aUser = Model_User::getRow([
                'where' => $where
            ]);
            if ($aUser) {
                if ($aUser['sPassword'] == md5(Yaf_G::getConf('cryptkey', 'cookie') .$sOldPwd)) {
                    $where['sPassword'] = md5(Yaf_G::getConf('cryptkey', 'cookie') . $sNewPwd);
                    $result = Model_User::updData($where);  
                }               
            }

            $msg = !empty($result) ? '修改密码成功' : '修改密码失败';
            $bool = !empty($result) ? true : false;
            return $this->showMsg($msg, $bool);
        }
    }

    //客户详情menu
    private function _clientMenu($iMenu)
    {
        $aMenu = [
            1 => [
                'url' => '/admin/user/clientinfo',
                'name' => '客户信息',
            ],
            2 => [
                'url' => '/admin/user/contecterinfo',
                'name' => '联系人信息',
            ],
            3 => [
                'url' => '/admin/user/communicate',
                'name' => '沟通纪录',
            ],
            4 => [
                'url' => '/admin/user/clientproduct',
                'name' => '定制产品',
            ],
            5 => [
                'url' => '/admin/user/clientemployee',
                'name' => '员工信息',
            ],
            /**
            6 => [
                'url' => '/admin/user/clientorder',
                'name' => '订单信息',
            ],
             */
            7 => [
                'url' => '/admin/user/clientplan',
                'name' => '体检计划',
            ]
        ];
        $this->assign('aMenu', $aMenu);
        $this->assign('iMenu', $iMenu);
    }

}