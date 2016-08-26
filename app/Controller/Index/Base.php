<?php

/**
 * 网站前端base文件
 * Created by PhpStorm.
 * User: zhangfangxiong
 * Date: 16/04/08
 * Time: 下午2:32
 */
class Controller_Index_Base extends Yaf_Controller
{
    protected $aUser = []; //当前用户
    protected $iCurrUserID = 0;
    protected $sCompanyName = 'registers';//用户所属公司的用户名，定死的

    /**
     * 执行动作之前
     */
    public function actionBefore ()
    {
        //当前用户
        $this->aUser = Util_Cookie::get(Yaf_G::getConf('indexuserkey', 'cookie'));
        $this->iCurrUserID = $this->aUser['iUserID'];

        if (!$this->aUser) {
            $aCreateUser = Model_User::getUserByUserName($this->sCompanyName, 2);
            if (empty($aCreateUser)) {
                return $this->show404('请先在admin后台添加一个用户名为“registers”的hr用户', false);
            }            
        } else {
            //加入创建人和渠道信息
            $aCreateUser = Model_User::getDetail($this->aUser['iCreateUserID']);
            $aUser = Model_Customer::getDetail($this->aUser['iUserID']);
        }
        $this->aUser['iCreateUserID'] = $aCreateUser['iUserID'];
        $this->aUser['iChannel'] = $aCreateUser['iChannel'];
        $this->aUser['iChannelID'] = $aCreateUser['iChannel'];
        $this->aUser['iSex'] = $aUser['iSex'] != 1 ? ($aUser['iSex'] == 2 && $aUser['iMarriage'] == 1) ? 2 : 3 : 1;
       
        $this->assign('sStaticRoot', 'http://' . Yaf_G::getConf('static', 'domain'));
        $aMenu = Model_Lang::getMenu();
        $sPhoneNum = Util_Common::getConf('sZixunPhone', 'web');
        $aCommonLang = Model_Lang::getCommonLang();
        $this->assign('aCommonLang',$aCommonLang);
        $this->assign('sPhoneNum',$sPhoneNum);
        $this->assign('aMenu',$aMenu);
        $this->assign('aUser', $this->aUser);
        $this->setBasicData();
    }

    /**
     * 设置基础数据 城市 证件号类型之类
     */
    public function setBasicData ()
    {
        $aCitys = Model_City::getAll([
            'where' => [
                'iStatus' => Model_City::STATUS_VALID
            ],
            'order' => 'sPinyin ASC' 
        ]);
        foreach ($aCitys as $k => $v) {
            $aCity[$v['iCityID']] = $v['sCityName'];
        }
        $this->assign('aCity', $aCity);
        
        $aCardType = Yaf_G::getConf('aCardType');
        $this->assign('aCardType', $aCardType);   

        $aSupplier = Model_Type::getOption('supplier');
        $this->assign('aSupplier', $aSupplier);               
    }

    /**
     * 获取用户所能看到的产品
     * @param int $iPage
     */
    protected function getCanViewProduct($iPage = 1)
    {
        $aUser = Model_User::getDetail($this->aUser['iCreateUserID']);
        $aProductList = Model_Product::getUserViewProductList($this->aUser['iCreateUserID'], 2, $aUser['iChannel'], $iPage);
        return $aProductList;
    }

    /**
     * 执行动作之后
     */
    public function actionAfter ()
    {
        
    }

    /**
     * 获取产品门店和预约人数
     * @return [type] [description]
     */
    public function getStoreAndPersonNumber ($iProductID) 
    {
        $aProductStoreParam['iProductID'] = $iProductID;
        $aProductStoreParam['iStatus'] = 1;
        $aProductStoreParam['iType'] = Model_ProductStore::EXPANDPRODUCT;
        $aProductStore = Model_ProductStore::getPair($aProductStoreParam, 'iAutoID', 'iStoreID');

        $sStoreNum = count($aProductStore);
        $sCardNum = Model_OrderCardProduct::getCnt(['where' => [
                'iType' => Model_ProductStore::EXPANDPRODUCT,
                'iProductID' => $iProductID,
                'iBookStatus IN' => ['0', '1', '2', '5']
        ]]);

        return [$sStoreNum, $sCardNum];
    }

}