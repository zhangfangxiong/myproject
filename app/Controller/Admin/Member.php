<?php

/**
 * 前段用户管理
 */
class Controller_Admin_Member extends Controller_Admin_Base
{

    /**
     * 用户信息
     */
    public function infoAction ()
    {
        $iUserID = $this->aCurrUser['iUserID'];
        $aUser = Model_User::getDetail($iUserID);
        $this->assign('aUser', $aUser);
        $this->assign('aBusiness', Model_Domain::getPairDomain(Model_Domain::TYPE_CO_INDUSTRY)); // 行业
    }

    /**
     * 密码修改
     */
    public function chgpwdAction ()
    {
        if ($this->isPost()) {
            $sOldPass = $this->getParam('oldpass');
            $sNewPass = $this->getParam('newpass');
            $aUser = Model_User::getDetail($this->aCurrUser['iUserID']);
            $sCryptkey = Yaf_G::getConf('cryptkey', 'cookie');
            if ($aUser['sPassword'] != md5($sCryptkey . $sOldPass)) {
                return $this->showMsg('旧密码不正确！', false);
            }
            Model_User::updData(array(
                'iUserID' => $this->aCurrUser['iUserID'],
                'sPassword' => md5($sCryptkey . $sNewPass)
            ));
            return $this->showMsg('密码修改成功！', true);
        }
        $this->_request->getMethod();
    }

    /**
     * 用户删除
     */
    public function delAction ()
    {
        $iUserID = intval($this->getParam('id'));
        $iRet = Model_User::delData($iUserID);
        if ($iRet == 1) {
            return $this->showMsg('用户删除成功！', true);
        } else {
            return $this->showMsg('用户删除失败！', false);
        }
    }

    /**
     * 用户列表
     */
    public function listAction ()
    {
        $iPage = intval($this->getParam('page'));
        if (isset($_GET['page'])) {
            $iPage = $_GET['page'];
        }
        $aWhere = array();
        $aParam = $this->getParams();
        if (! empty($aParam['iType'])) {
            $aWhere['iType'] = $aParam['iType'];
        }
        if (! empty($aParam['iCoIndustry'])) {
            $aWhere['iCoIndustry'] = $aParam['iCoIndustry'];
        }
        if (! empty($aParam['sEmail'])) {
            $aWhere['sEmail LIKE'] = '%' . $aParam['sEmail'] . '%';
        }
        if (! empty($aParam['sMobile'])) {
            $aWhere['sMobile LIKE'] = '%' . $aParam['sMobile'] . '%';
        }
        if (! empty($aParam['iStatus'])) {
            $aWhere['iStatus'] = $aParam['iStatus'];
        } else {
            $aWhere['iStatus IN'] = '1,2';
        }
        
        $aList = Model_User::getList($aWhere, $iPage, $this->getParam('sOrder', ''));
        $this->assign('aList', $aList);
        $this->assign('aParam', $aParam);
        $this->assign('aBusiness', Model_Domain::getPairDomain(Model_Domain::TYPE_CO_INDUSTRY)); // 行业
    }

    /**
     * 用户修改
     */
    public function editAction ()
    {
        if ($this->_request->isPost()) {
            $aUser = $this->_checkData('update');
            if (empty($aUser)) {
                return null;
            }
            $aUser['iUserID'] = intval($this->getParam('iUserID'));
            $aOldUser = Model_User::getDetail($aUser['iUserID']);
            if (empty($aOldUser)) {
                return $this->showMsg('用户不存在！', false);
            }
            if ($aOldUser['sEmail'] != $aUser['sEmail'] && $aOldUser['iStatus'] > 0) {
                if (Model_User::getUserByEmail($aUser['sEmail'])) {
                    return $this->showMsg('用户已经存在！', false);
                }
            }
            if (1 == Model_User::updData($aUser)) {
                
                // 判断利率是否变化
                if ($aOldUser['iRate'] != $aUser['iRate']) {
                    Model_Media::query("UPDATE  t_media  SET  iRate= {$aUser['iRate']} WHERE iUserID={$aUser['iUserID']}");
                }
                return $this->showMsg('用户信息更新成功！', true);
            } else {
                return $this->showMsg('用户信息更新失败！', false);
            }
        } else {
            $iUserID = intval($this->getParam('id'));
            $aUser = Model_User::getDetail($iUserID);
            $this->assign('aUser', $aUser);
            $this->assign('aBusiness', Model_Domain::getPairDomain(Model_Domain::TYPE_CO_INDUSTRY)); // 行业
        }
    }

    /**
     * 增加用户
     */
    public function addAction ()
    {
        if ($this->_request->isPost()) {
            $aUser = $this->_checkData('add');
            if (empty($aUser)) {
                return null;
            }
            if (Model_User::getRow(array(
                'where' => array(
                    'iType' => $aUser['iType'],
                    'sEmail' => $aUser['sEmail'],
                    'iStatus IN ' => '1,2'
                )
            ))) {
                return $this->showMsg('用户已经存在！', false);
            }
            if (Model_User::addData($aUser) > 0) {
                return $this->showMsg('用户增加成功！', true);
            } else {
                return $this->showMsg('用户增加失败！', false);
            }
        } else {
            $this->assign('aBusiness', Model_Domain::getPairDomain(Model_Domain::TYPE_CO_INDUSTRY)); // 行业
        }
    }

    /**
     * 导出
     */
    public function explodeAction ()
    {
        $aWhere = array();
        $aParam = $this->getParams();
        if (! empty($aParam['iType'])) {
            $aWhere['iType'] = $aParam['iType'];
        }
        if (! empty($aParam['iCoIndustry'])) {
            $aWhere['iCoIndustry'] = $aParam['iCoIndustry'];
        }
        if (! empty($aParam['sEmail'])) {
            $aWhere['sEmail LIKE'] = '%' . $aParam['sEmail'] . '%';
        }
        if (! empty($aParam['sMobile'])) {
            $aWhere['sMobile LIKE'] = '%' . $aParam['sMobile'] . '%';
        }
        if (! empty($aParam['iStatus'])) {
            $aWhere['iStatus'] = $aParam['iStatus'];
        } else {
            $aWhere['iStatus IN'] = '1,2';
        }
        
        $aList = Model_User::getAll(array(
            'where' => $aWhere
        ));
        
        header("Content-type:application/vnd.ms-excel");
        header("Content-Disposition:filename=用户_" . date('Y-m-d', time()) . ".xls");
        $str_explode = "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'><html xmlns='http://www.w3.org/1999/xhtml'><head><meta http-equiv='Content-Type' content='text/html; charset=UTF-8' /><title>导出</title><style>td{text-align:center;font-size:12px;font-family:Arial, Helvetica, sans-serif;border:#1C7A80 1px solid;color:#152122;width:100px;}table,tr{border-style:none;}.title{background:#7DDCF0;color:#FFFFFF;font-weight:bold;}</style></head><body>";
        
        $str_explode .= '<table cellspacing="0" cellpadding="3" rules="rows" border="1" id="" style="border-style:None;width:100%;border-collapse:collapse;">
							<tr>
								<th scope="col">帐号类型</th>
								<th scope="col">登录帐号</th>
								<th scope="col">手机号</th>
								<th scope="col">联系人名称</th>
								<th scope="col">公司名称</th>
								<th scope="col">所属行业</th>
								<th scope="col">公司地址</th>
								<th scope="col">公司网站</th>
								<th scope="col">公司介绍</th>
								<th scope="col">微信</th>
								<th scope="col">QQ</th>
								<th scope="col">余额</th>
								<th scope="col">时间</th>
							</tr>';
        foreach ($aList as $key => $val) {
            $iType = ((isset($val['iType']) && $val['iType'] == '1') ? '自媒体' : '广告主');
            $sEmail = $val['sEmail'];
            $sMobile = $val['sMobile'];
            $sRealName = $val['sRealName'];
            $sCoName = $val['sCoName'];
            
            // 行业
            $sCoIndustry = '';
            if ($val['sCoIndustry'] != '') {
                $rowlevel = Model_Domain::getAll(array(
                    'where' => array(
                        'iAutoID IN' => $val['sCoIndustry']
                    )
                )); // 合作等级
                if (isset($rowlevel) && count($rowlevel) > 0) {
                    foreach ($rowlevel as $lrow) {
                        $sCoIndustry .= ' ' . $lrow['sName'];
                    }
                }
            }
            
            $sCoAddress = $val['sCoAddress'];
            $sCoWebsite = $val['sCoWebsite'];
            $sCoDesc = $val['sCoDesc'];
            $sWeixin = $val['sWeixin'];
            $sQQ = $val['sQQ'];
            $iMoney = $val['iMoney'];
            $iCreateTime = date('Y-m-d H:i:s', $val['iCreateTime']);
            
            $str_explode .= '<tr>
								<td align="left">' . $iType . '</td>
								<td align="left">' . $sEmail . '</td>
								<td align="left">' . $sMobile . '</td>
								<td align="left">' . $sRealName . '</td>
								<td align="left">' . $sCoName . '</td>
								<td align="left">' . $sCoIndustry . '</td>
								<td align="left">' . $sCoAddress . '</td>
								<td align="left">' . $sCoWebsite . '</td>
								<td align="left">' . $sCoDesc . '</td>
								<td align="left">' . $sWeixin . '</td>
								<td align="left">' . $sQQ . '</td>
								<td align="left">' . $iMoney . '</td>
								<td align="left">' . $iCreateTime . '</td>
							</tr>';
        }
        $str_explode .= '</table>';
        
        $str_explode .= "</body></html>";
        
        echo $str_explode;
    }

    /**
     * 请求数据检测
     *
     * @return mixed
     */
    public function _checkData ($sType = 'add')
    {
        $iType = $this->getParam('iType');
        $sEmail = $this->getParam('sEmail');
        $sMobile = $this->getParam('sMobile');
        $sRealName = $this->getParam('sRealName');
        $sPassword = $this->getParam('sPassword');
        $sCoName = $this->getParam('sCoName');
        $iCoIndustry = $this->getParam('iCoIndustry');
        $sCoAddress = $this->getParam('sCoAddress');
        $sCoWebSite = $this->getParam('sCoWebSite');
        $sCoDesc = $this->getParam('sCoDesc');
        $sWeixin = $this->getParam('sWeixin');
        $iDisplay = $this->getParam('iDisplay');
        $sQQ = $this->getParam('sQQ');
        $iIncome = $this->getParam('iIncome');
        $iMoney = $this->getParam('iMoney');
        $iStatus = $this->getParam('iStatus');
        $iRate = $this->getParam('iRate');
        $iUpdateTime = time();
        
        if (! Util_Validate::isEmail($sEmail)) {
            return $this->showMsg('输入的邮箱地址不合法！', false);
        }
        if (($sType == 'add' || ! empty($sPassword)) && ! Util_Validate::isLength($sPassword, 6, 20)) {
            return $this->showMsg('登录密码长度范围为6到20字符！', false);
        }
        if (! Util_Validate::isMobile($sMobile)) {
            return $this->showMsg('输入的手机号码不合法！', false);
        }
        // if (! Util_Validate::isLength($sRealName, 2, 20)) {
        // return $this->showMsg('真实姓名长度范围为2到20字符！', false);
        // }
        $aDomain = Model_Domain::getPairDomain(Model_Domain::TYPE_CO_INDUSTRY);
        // if (! isset($aDomain[$iCoIndustry])) {
        // return $this->showMsg('选择的行业不存在！', false);
        // }
        
        $aRow = array(
            'iType' => $iType,
            'sEmail' => $sEmail,
            'sMobile' => $sMobile,
            'sRealName' => $sRealName,
            'sCoName' => $sCoName,
            'iCoIndustry' => $iCoIndustry,
            'sCoAddress' => $sCoAddress,
            'iDisplay' => $iDisplay,
            'sCoWebSite' => $sCoWebSite,
            'sCoDesc' => $sCoDesc,
            'sWeixin' => $sWeixin,
            'sQQ' => $sQQ,
            'iIncome' => $iIncome,
            'iMoney' => $iMoney,
            'iStatus' => $iStatus,
            'iUpdateTime' => $iUpdateTime,
            'iRate' => $iRate
        );
        if (! empty($sPassword)) {
            $aRow['sPassword'] = md5(Yaf_G::getConf('cryptkey', 'cookie') . $sPassword);
        }
        return $aRow;
    }
}