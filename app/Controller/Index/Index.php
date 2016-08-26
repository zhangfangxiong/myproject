<?php

/**
 * 提供网站首页
 * Created by PhpStorm.
 * User: xiejinci
 * Date: 14/12/24
 * Time: 下午2:32
 */
class Controller_Index_Index extends Controller_Index_Base
{
    private $aBannerParam = [];
    /**
     * 执行动作之前
     */
    public function actionBefore ()
    {
        parent::actionBefore();
        $this->aBannerParam['iModuleID'] = 1;
        $this->aBannerParam['iStatus'] = 1;
    }
    
    public function indexAction()
    {
        $this->_frame = 'pcbasic.phtml';

        $aFaq = Model_Faq::getList([], 1, 'iCreateTime Desc');
        $this->assign('aFaq', $aFaq);
    }

    public function newsAction () 
    {
        $this->_frame = 'pcbasic.phtml';

        $id = $this->getParam('id');
        $aNews = Model_Faq::getDetail($id);
        if (!$id || !$aNews) {
            return $this->redirect('index2');
        }

        $aFaq = Model_Faq::getList([], 1, 'iCreateTime Desc');
        
        $this->assign('aFaq', $aFaq);
        $this->assign('aNews', $aNews);
    }
    public function cooperAction()
    {
        $this->_frame = 'pcbasic.phtml';
    }
    public function aboutAction()
    {
        $this->_frame = 'pcbasic.phtml';
    }
}