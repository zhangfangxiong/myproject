<?php

/**
 * Created by PhpStorm.
 * User: zhangfangxiong
 * Date: 2016/4/14
 * Time: 9:50
 */
class Controller_Admin_ClientEmployee extends Controller_Admin_Base
{
    /**
     * 修改
     */
    public function editAction()
    {
        if ($this->_request->isPost()) {
            $aParam = $this->_checkData(2);
            if (empty($aParam)) {
                return null;
            }
            //整合用户表字段
            $aUser['iUserID'] = $aParam['iUserID'];
            $aUser['sRealName'] = $aParam['sRealName'];
            $aUser['sMobile'] = $aParam['sMobile'];
            Model_Customer::begin();
            if (Model_Customer::updData($aUser)) {
                $aUserData = Model_Customer::getDetail($aParam['iUserID']);
                $aUserCompanyDataParam['iCompanyID'] = $aUserData['iCreateUserID'];
                $aUserCompanyDataParam['iUserID'] = $aParam['iUserID'];
                $aUserCompanyData = Model_CustomerCompany::getRow($aUserCompanyDataParam);
                if (empty($aUserCompanyData)) {
                    Model_Customer::rollback();
                    return $this->showMsg('修改失败！', false);
                }
                //整合用户公司表字段
                $aUserCompany['iAutoID'] = $aUserCompanyData['iAutoID'];
                $aUserCompany['iUserID'] = $aParam['iUserID'];
                $aUserCompany['iCompanyID'] = $aParam['aCreateUser']['iUserID'];
                $aUserCompany['sCompanyName'] = $aParam['aCreateUser']['sRealName'];
                $aUserCompany['sCompanyCode'] = $aParam['aCreateUser']['sUserName'];
                $aUserCompany['iDeptID'] = $aParam['iDeptID'];
                $aUserCompany['iJobGradeID'] = $aParam['iJobGradeID'];
                $aUserCompany['sJobTitleName'] = $aParam['sJobTitleName'];
                $aUserCompany['sEmail'] = $aParam['sEmail'];
                $aUserCompany['sRemark'] = $aParam['sRemark'];
                if (Model_CustomerCompany::updData($aUserCompany)) {
                    Model_Customer::commit();
                    return $this->showMsg('修改成功！', true);
                } else {
                    Model_Customer::rollback();
                    return $this->showMsg('修改失败！', false);
                }

            } else {
                Model_Customer::rollback();
                return $this->showMsg('修改失败！', false);
            }
        } else {
            $iUserID = $this->getParam('id');
            if (empty($iUserID)) {
                return $this->showMsg('参数有误！', false);
            }
            $aUser = Model_Customer::getDetail($iUserID);
            if (empty($aUser)) {
                return $this->showMsg('该员工不存在！', false);
            }
            $aSex = Yaf_G::getConf('aSex');
            $this->assign('aSex', $aSex);
            $this->assign('aDepartment', Model_Company_Department::getPairDepartment($aUser['iCreateUserID']));
            $this->assign('aLevel', Model_Company_Level::getPairLevel($aUser['iCreateUserID']));
            $this->assign('aStatus', Model_User::$aStatus);
            $this->assign('aUser', $aUser);
            $this->assign('iUserID', $aUser['iCreateUserID']);
        }
    }

    /**
     * 添加
     */
    public function addAction()
    {
        if ($this->_request->isPost()) {
            $aParam = $this->_checkData(1);
            if (empty($aParam)) {
                return null;
            }
            //整合用户表字段
            $aUser['sRealName'] = $aParam['sRealName'];
            $aUser['sMobile'] = $aParam['sMobile'];
            $aUser['iCreateUserID'] = $aParam['iCreateUserID'];
            $aUser['sUserName'] = Model_Customer::initUserName();
            $aUser['iLastUpdateUserID'] = $aParam['iCreateUserID'];
            $aUser['sPassword'] = md5(Yaf_G::getConf('cryptkey', 'cookie') . $aUser['sUserName']);
            Model_Customer::begin();
            if (!empty($aParam['iUserID'])) {//老用户
                $aUser['iUserID'] = $aParam['iUserID'];
                if (Model_Customer::updData($aUser)) {
                    $iUserID = $aUser['iUserID'];
                } else {
                    Model_Customer::rollback();
                    return $this->showMsg('添加失败！', false);
                }
            } else {
                $iUserID = Model_Customer::addData($aUser);
            }

            if ($iUserID > 0) {
                //整合用户公司表字段
                $aUserCompany['iUserID'] = $iUserID;
                $aUserCompany['sUserName'] = Model_CustomerCompany::initUserName($aParam['iCreateUserID']);
                $aUserCompany['iCompanyID'] = $aParam['iCreateUserID'];
                $aUserCompany['sCompanyName'] = $aParam['aCreateUser']['sRealName'];
                $aUserCompany['sCompanyCode'] = $aParam['aCreateUser']['sUserName'];
                $aUserCompany['iCreateUserID'] = $this->aCurrUser['iUserID'];
                $aUserCompany['iDeptID'] = $aParam['iDeptID'];
                $aUserCompany['iJobGradeID'] = $aParam['iJobGradeID'];
                $aUserCompany['sJobTitleName'] = $aParam['sJobTitleName'];
                $aUserCompany['sEmail'] = $aParam['sEmail'];
                $aUserCompany['sRemark'] = $aParam['sRemark'];
                if (Model_CustomerCompany::addData($aUserCompany)) {
                    Model_Customer::commit();
                    return $this->showMsg('添加成功！', true);
                } else {
                    Model_Customer::rollback();
                    return $this->showMsg('添加失败！', false);
                }
            } else {
                Model_Customer::rollback();
                return $this->showMsg('添加失败！', false);
            }
        } else {
            $iUserID = $this->getParam('id');
            if (empty($iUserID)) {
                return $this->showMsg('参数有误！', false);
            }
            $aUser = Model_User::getDetail($iUserID);
            if (empty($aUser)) {
                return $this->showMsg('该员工不存在！', false);
            }
            $aSex = Yaf_G::getConf('aSex');
            $this->assign('aSex', $aSex);
            $this->assign('aDepartment', Model_Company_Department::getPairDepartment($iUserID));
            $this->assign('aLevel', Model_Company_Level::getPairLevel($iUserID));
            $this->assign('aStatus', Model_User::$aStatus);
            $this->assign('iUserID', $iUserID);
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
        if (!Util_Validate::isCLength($aParam['sRealName'], 1, 10)) {
            return $this->showMsg('姓名为1到10个字！', false);
        }
        if (!Util_Validate::isIdcard($aParam['sIdentityCard'])) {
            return $this->showMsg('身份证格式不正确！', false);
        }
        if (!Util_Validate::isMobile($aParam['sMobile'])) {
            return $this->showMsg('手机不符合规范！', false);
        }
        if (!empty($aParam['sEmail']) && !Util_Validate::isEmail($aParam['sEmail'])) {
            return $this->showMsg('邮箱不符合规范！', false);
        }
        if ($iType == 1) {
            if (empty($aParam['id'])) {
                return $this->showMsg('非法操作！', false);
            }
            $aCreateUser = Model_User::getDetail($aParam['id']);
            if (empty($aCreateUser)) {
                return $this->showMsg('用户不存在！', false);
            }
            $aParam['iCreateUserID'] = $aCreateUser['iUserID'];//这里的添加是当前用户添加的，但是是添加给某个企业用户，所以用他当iCreateUserID
            $aParam['aCreateUser'] = $aCreateUser;

            //验证身份证是否被注册
            if (!empty($aParam['sIdentityCard'])) {
                $aUser = Model_Customer::getUserByIdentityCard($aParam['sIdentityCard']);
                if (!empty($aUser)) {
                    $aParam['iUserID'] = $aUser['iUserID'];//用来判断是否老用户
                }
                if (!empty($aUser['iUserID']) && $aUser['sRealName'] != $aParam['sRealName']) {
                    //如果身份证存在，校对真实名
                    return $this->showMsg('该身份证已被注册,且真实名和你输入不一致，请核对信息或者联系管理员！', false);
                }
                if (!empty($aUser['iUserID']) && $aUser['iCreateUserID'] == $aParam['id']) {
                    //同一个公司不能添加同一个身份证用户，多个公司覆盖信息
                    return $this->showMsg('您已添加过该身份证的员工！', false);
                }
            }
        } elseif($iType==2) {
            //验证身份证是否被注册
            if (!empty($aParam['sIdentityCard'])) {
                $aUser = Model_Customer::getUserByIdentityCard($aParam['sIdentityCard']);
                if (!empty($aUser) && $aUser['iUserID'] != $aParam['iUserID']) {
                    return $this->showMsg('该身份证已被添加，请核对信息或者联系管理员！', false);
                }
            }
        }
        return $aParam;
    }

}