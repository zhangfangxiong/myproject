<?php

class Controller_Admin_Index extends Controller_Admin_Base
{

    protected $aLoginType = [
        1=>[
            'url' => '/admin/login',
            'name' => 'admin后台'
        ],
        2=>[
            'url' => '/admin/hrlogin',
            'name' => 'hr后台'
        ],
        4=>[
            'url' => '/admin/supplierlogin',
            'name' => '供应商后台'
        ],
    ];
    protected $bCheckLogin = false;

    public function indexAction ()
    {
        // 当前用户
        $this->aCurrUser = Util_Cookie::get(Yaf_G::getConf('authkey', 'cookie'));
        if (!$this->aCurrUser) {
            $sUrl = '/admin/login';
            return $this->redirect($sUrl);
        } else {
            //体检预约数和到检数(只取今年的)
            $aReserveParam['iBookStatus IN'] = [1,2];
            $aReserveParam['iReserveTime >'] = strtotime(date('Y',time()).'0101');
            $aReseveData = Model_OrderCardProduct::getAll($aReserveParam);
            $aReserveMonthData = [];
            $aReserveArriveMonthData = [];
            if (!empty($aReseveData)) {
                $aReserveMonthData = [];
                foreach ($aReseveData as $key => $value) {//按月份分组
                    $aReserveMonthData[date('Ym',$value['iReserveTime'])][] = $value;
                    if ($value['iBookStatus'] == 2) {
                        $aReserveArriveMonthData[date('Ym',$value['iReserveTime'])][] = $value;
                    }
                }
            }
            //print_r($aReserveArriveMonthData);die;
            $this->assign('aReserveMonthData', $aReserveMonthData);//预约数
            $this->assign('aReserveArriveMonthData', $aReserveArriveMonthData);//到检数
            $this->assign('aCurrUser', $this->aCurrUser);
        }
    }

    //admin后台登陆
    public function loginAction ()
    {
        $iType = 1;
        $this->assign('iType',$iType);
        $this->assign('aLoginType',$this->aLoginType);
    }

    //hr后台登陆
    public function hrloginAction ()
    {
        $this->_frame = 'hrlogin.phtml';
        $iType = 2;
        $this->assign('iType',$iType);
        $this->assign('aLoginType',$this->aLoginType);
    }

    //供应商后台登陆
    public function supplierloginAction ()
    {
        $iType = 4;
        $this->assign('iType',$iType);
        $this->assign('aLoginType',$this->aLoginType);
    }

    public function logoutAction ()
    {
        $type = $this->getParam('admintype');
        if ($type == 2) {
            $logout = '/admin/hrlogin';
        } else if ($type == 4) {
            $logout = '/admin/supplierlogin';
        } else {
            $logout = '/admin/login';
        }

        Util_Cookie::delete(Yaf_G::getConf('authkey', 'cookie'));
        $this->redirect($logout);
    }

    public function signinAction ()
    {
        $sAdminName = $this->getParam('username');
        $sPassword = $this->getParam('password');
        $bRemember = $this->getParam('remember');
        $iType = $this->getParam('type') ? intval($this->getParam('type')) : 1;//可以各个后台登陆
        $aUser = Model_User::getUserByUserName($sAdminName,$iType);
        if (empty($aUser)) {
            return $this->showMsg('帐号不存在！', false);
        }
        if ($aUser['iIsCheck'] == Model_User::NOCHECK) {
            return $this->showMsg('帐号待审核！', false);
        }
        if ($aUser['iIsCheck'] == Model_User::REFUSE) {
            return $this->showMsg('帐号未审核通过！', false);
        }
        if ($aUser['iStatus'] == Model_User::STATUS_TYPE_LOCK) {
            return $this->showMsg('帐号被禁用！', false);
        }
        if ($aUser['iStatus'] == Model_User::STATUS_TYPE_LEAVE) {
            return $this->showMsg('该员工已离职！', false);
        }
        if ($aUser['iStatus'] == Model_User::STATUS_TYPE_DELETE) {
            return $this->showMsg('该账号已删除！', false);
        }
        if ($aUser['sPassword'] != md5(Yaf_G::getConf('cryptkey', 'cookie') . $sPassword)) {
            return $this->showMsg('密码不正确！', false);
        }
        $aCookie = array(
            'iUserID' => $aUser['iUserID'],
            'sUserName' => $aUser['sUserName'],
            'sRealName' => $aUser['sRealName'],
            'iType' => $aUser['iType'],//用户类型
        );
        if ($bRemember) {
            $expire = 86400 * 7;
        } else {
            $expire = 0;
        }
        Util_Cookie::set(Yaf_G::getConf('authkey', 'cookie'), $aCookie, $expire);

        $aPermissions = Model_Permission::getUserPermissions($aCookie['iUserID']);

        if ($iType == 1) {
            $sUrl = '/admin/';
        }
        if ($iType == 2) {
            $sUrl = '/company/employer/index';
        }
        if ($iType == 4) {
            $sUrl = '/supplier/order/index';
        }
        
        return $this->showMsg([
            'msg' => '登录成功！',
            'sUrl' => $sUrl
        ], true);
    }
    
    /**
     * 更换城市
     */
    public function changeAction ()
    {
        // 当前用户
        $aCookie = Util_Cookie::get(Yaf_G::getConf('authkey', 'cookie'));
        if (empty($aCookie)) {
            return $this->redirect('/admin/login');
        }
        $this->aCurrUser = $aCookie;
        
        $iCityID = $this->getParam('id');
        $aCity = Model_City::getDetail($iCityID);
        if (empty($aCity) || $aCity['iBackendShow'] == 0 || $aCity['iStatus'] == 0) {
            return $this->showMsg('城市不存在或未开放！', false);
        }
        $aUser = Model_User::getDetail($this->aCurrUser['iUserID']);
        $aCityID = explode(',', $aUser['sCityID']);
        if ($aUser['sCityID'] != '-1' && ! in_array($iCityID, $aCityID)) {
            return $this->showMsg('您没有访问该城市的权限，请联系管理员！', false);
        }
        Util_Cookie::set('city', $iCityID);
        return $this->showMsg('城市切换成功!', true);
    }

    public function permissionAction ()
    {
        $aMenuList = Model_Menu::getMenus();
        
        $aCtrClass = array();
        $aMenuAction = array();
        foreach ($aMenuList as $aMenu) {
            if ($aMenu['bIsLeaf']) {
                $aRoute = Yaf_G::getRoute($aMenu['sUrl']);
                $aMenuAction[$aRoute['module'] . '_' . $aRoute['controller'] . '_' . $aRoute['action']] = $aMenu['sMenuName'];
                $aCtrClass[$aRoute['module'] . '_' . $aRoute['controller']] = array(
                    'iMenuID' => $aMenu['iMenuID'],
                    'sMenuName' => $aMenu['sMenuName'],
                    'sUrl' => $aMenu['sUrl']
                );
            }
        }
        
        $aPermission = array();
        foreach ($aCtrClass as $sCtrClass => $aMenu) {
            try {
                $sCtrClass = 'Controller_' . $sCtrClass;
                if (class_exists($sCtrClass)) {
                    $oCtr = new ReflectionClass($sCtrClass);
                    $aMethod = $oCtr->getMethods();
                    foreach ($aMethod as $oMethod) {
                        $sAction = $oMethod->getName();
                        if (substr($sAction, - 6) === 'Action') {
                            $sAction = substr($sAction, 0, - 6);
                            $aRow = array(
                                $aMenu['iMenuID']
                            );
                            $aRow[] = Yaf_G::routeToUrl($sCtrClass . '_' . $sAction);
                            $sDoc = $oMethod->getDocComment();
                            $matches = null;
                            if (preg_match('/\s+\*\s+(.+)/i', $sDoc, $matches)) {
                                $aRow[] = $matches[1];
                            } elseif (isset($aMenuAction[$sCtrClass . '_' . $sAction])) {
                                $aRow[] = $aMenuAction[$sCtrClass . '_' . $sAction];
                            } else {
                                $aRow[] = $aMenu['sMenuName'] . '::' . $sAction;
                            }
                            $aPermission[] = $aRow;
                        }
                    }
                }
            } catch (Exception $e) {
                $aPermission[] = array(
                    $aMenu['iMenuID'],
                    Yaf_G::getUrl($aMenu['sUrl']),
                    $aMenu['sMenuName']
                );
            }
        }
        $this->showMsg($aPermission, true);
    }

    public function testAction()
    {

//        $ch = curl_init();
//        $post_data = array(
//            "account" => "sdk_zhongyingbx",
//            "password" => "20150921",
//            "destmobile" => "15026490504",
//            "msgText" => "6666你好【中盈公司】",
//            "sendDateTime" => ""
//        );
//
//        curl_setopt($ch, CURLOPT_HEADER, false);
//        curl_setopt($ch, CURLOPT_POST, true);
//        curl_setopt($ch,CURLOPT_BINARYTRANSFER,true);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        $post_data = http_build_query($post_data);
////echo $post_data;
//        curl_setopt($ch, CURLOPT_POSTFIELDS,$post_data);
//        //curl_setopt($ch, CURLOPT_HTTPHEADER, 'Content-Type: application/x-www-form-urlencoded; charset=utf-8');
//        curl_setopt($ch, CURLOPT_URL, 'http://www.jianzhou.sh.cn/JianzhouSMSWSServer/http/sendBatchMessage');
////$info=
//        echo curl_exec($ch);die;
////curl_close($ch);

        $aa = Sms_Joying::sendBatch(13127732668,44444,20160808150000);
        print_r($aa);die;
    }
}