<?php

class Controller_Admin_Article extends Controller_Admin_Base
{

    /**
     * (non-PHPdoc)
     *
     * @see Yaf_Controller::__call()
     */
    public function __call ($sMethod, $aArg)
    {
        $sClass = str_replace('Action', '', $sMethod);
        $aClass = Model_Article::getClass($sClass);
        if (empty($aClass)) {
            parent::__call($sMethod, $aArg);
            return false;
        }
        $this->listAction($sClass);
        $this->setViewScript('/Admin/Article/list.phtml');
    }
    
    /**
     * 资讯列表
     */
    public function listAction($sClass = '')
    {
        if (empty($sClass)) {
            $sClass = $this->getParam('class', '');
        }
        $aClass = Model_Article::getClass($sClass);
        $aParam['sClass'] = $aClass['sClass'];
        
        $aParam = array();
        $aParam['iArticleID'] = $this->getParam('iArticleID');
        $aParam['iCategoryID'] = $this->getParam('iCategoryID');
        $aParam['iPublishStatus'] = $this->getParam('iPublishStatus');
        $aParam['sTitle'] = $this->getParam('sTitle');
        $aParam['sOrder'] = $this->getParam('sOrder');
        $aParam['iStartTime'] = $this->getParam('iStartTime');
        $aParam['iEndTime'] = $this->getParam('iEndTime');
        $aParam['sTagName'] = $this->getParam('sTagName');
        $aParam['iTagID'] = $this->getParam('iTagID') && !empty($aParam['sTagName']) ? $this->getParam('iTagID') : 0;
        $aParam['sAuthor'] = $this->getParam('sAuthor');
        $aParam['iAuthorID'] = $this->getParam('iAuthorID') && !empty($aParam['sAuthor']) ? $this->getParam('iAuthorID') : 0;
        if ($aClass['iCityID'] > 0) {
            $aParam['iCityID'] = $this->iCurrCityID;
        }

        $iPage = intval($this->getParam('page'));
        $sOrder = 'iUpdateTime DESC';
        if (!empty($aParam['sOrder'])) {
            $sOrder = $aParam['sOrder'];
        }
        $aParam['sOrder'] = $sOrder;
        $aList = Model_Article::getPageList($aParam, $iPage, $sOrder);
        $this->assign('aParam', $aParam);
        $this->assign('aList', $aList);
        $this->assign('aClass', $aClass);
        $this->assign('aCategory', Model_Article::getCategorys($sClass));
        $this->_assignUrl($aClass['sClass']);
    }

    /**
     * 下架资讯
     */
    public function offAction()
    {
        $iArticleID = $this->getParam('id');
        if (!$iArticleID) {
            $this->showMsg('非法操作', false);
        }
        
        if (is_string($iArticleID) && strpos($iArticleID, ",") === false) {
            $iArticleID = array($iArticleID);
        } elseif (is_string($iArticleID) && strpos($iArticleID, ",")) {
            $iArticleID = explode(",", $iArticleID);
        } else {
            return $this->showMsg('非法操作！', false);
        }
        
        $fail_article = array();
        $secc_article = array();
        foreach ($iArticleID as $key => $value) {
            $aArticle[$key] = Model_Article::getDetail($value);
            $aRow = array(
                'iArticleID' => $value,
                'iPublishStatus' => 0
            );
            $iRet = Model_Article::updData($aRow);
            if ($iRet != 1) {
                $fail_article[] = $value;
            } else {
                $secc_article[] = $value;
            }
        }
        
        $return = array("fail" => $fail_article, "secc" => $secc_article);
        $this->getResponse()->clearBody();
        
        return $this->showMsg($return, true);
    }

    /**
     * 发布资讯
     */
    public function publishAction()
    {
        $iArticleID = $this->getParam('id');
        if (!$iArticleID) {
            $this->showMsg('非法操作', false);
        }
        
        if (is_string($iArticleID) && strpos($iArticleID, ",") === false) {
            $iArticleID = array($iArticleID);
        } elseif (is_string($iArticleID) && strpos($iArticleID, ",")) {
            $iArticleID = explode(",", $iArticleID);
        } else {
            return $this->showMsg('非法操作！', false);
        }
        
        $fail_article = array();
        $secc_article = array();
        foreach ($iArticleID as $key => $value) {
            $aArticle[$key] = Model_Article::getDetail($value);
            $aRow = $this->_checkData('edit', $aArticle[$key]);
            if (empty($aRow)) {
                $fail_article[] = $value;
                continue;
            }
            $aRow = array(
                'iArticleID' => $value,
                'iPublishStatus' => 1
            );
            $iRet = Model_Article::updData($aRow);
            if ($iRet != 1) {
                $fail_article[] = $value;
            } else {
                $secc_article[] = $value;
            }
        }
        
        $return = array("fail" => $fail_article, "secc" => $secc_article);
        $this->getResponse()->clearBody();
        
        return $this->showMsg($return, true);
    }

    /**
     * 删除资讯
     *
     * @return boolean
     */
    public function delAction()
    {
        $iArticleID = $this->getParam('id');
        if (!$iArticleID) {
            return $this->showMsg('非法操作！', false);
        }
        
        if (is_string($iArticleID) && strpos($iArticleID, ",") === false) {
            $iArticleID = array($iArticleID);
        } elseif (is_string($iArticleID) && strpos($iArticleID, ",")) {
            $iArticleID = explode(",", $iArticleID);
        } else {
            return $this->showMsg('非法操作！', false);
        }
        
        $fail_article = array();
        foreach ($iArticleID as $key => $value) {
            $iRet = Model_Article::delData($value);
            if ($iRet != 1) {
                $fail_article[] = $value;
            }
        }
        
        if (empty($fail_article)) {
            return $this->showMsg('资讯删除成功！', true);
        }
        $ids = join(',', $fail_article);
        
        return $this->showMsg('资讯' . $ids . '删除失败！', false);
    }

    /**
     * 编辑资讯
     *
     * @return boolean
     */
    public function editAction()
    {
        if ($this->isPost()) {
            $aArticle = $this->_checkData('edit');
            if (empty($aArticle)) {
                return null;
            }
            
            $sAction = '保存';
            if ($this->getParam('iOptype') > 0) {
                $aArticle['iPublishStatus'] = 1;//发布需要将该字段改为1
                $sAction = '发布';
            }
            $aArticle['iArticleID'] = intval($this->getParam('iArticleID'));
            
            $aClass = Model_Article::getClass($aArticle['sClass']);
            //修改需要加上当前修改人ID
            $aCurrUserInfo = $this->aCurrUser;
            $aArticle['iUpdateUserID'] = $aCurrUserInfo['iUserID'];
            if (1 == Model_Article::updData($aArticle)) {
                return $this->showMsg(['sMsg' => $aClass['sTitle'].'信息' . $sAction . '成功！', 'iArticleID' => $aArticle['iArticleID']], true);
            } else {
                return $this->showMsg($aClass['sTitle'].'信息' . $sAction . '失败！', false);
            }
        } else {
            $this->_response->setHeader('Access-Control-Allow-Origin', '*');

            $iArticleID = intval($this->getParam('id'));
            $aArticle = Model_Article::getDetail($iArticleID);
            if ($aArticle['sTag']) {
                $aArticle['aTag'] = explode(',', $aArticle['sTag']);
            }

            $this->assign('aArticle', $aArticle);
            $this->assign('aCategory', Model_Article::getCategorys($aArticle['sClass']));
            $this->assign('aTag', Model_Article::getTags($aArticle['sClass']));
            $this->assign('aAuthor', Model_Article::getAuthors($aArticle['sClass']));
            $this->assign('sUploadUrl', Yaf_G::getConf('upload', 'url'));
            $this->assign('sFileBaseUrl', 'http://' . Yaf_G::getConf('file', 'domain'));
            $this->_assignUrl($aArticle['sClass']);
            $this->assign('aClass', Model_Article::getClass($aArticle['sClass']));
        }
    }

    /**
     * 增加资讯
     *
     * @return boolean
     */
    public function addAction()
    {
        if ($this->isPost()) {
            $aArticle = $this->_checkData();
            if (empty($aArticle)) {
                return null;
            }
            $sAction = '保存';
            if ($this->getParam('iOptype') > 0) {
                $aArticle['iPublishStatus'] = 1;//发布需要将该字段改为1
                $sAction = '发布';
            }
            
            $aClass = Model_Article::getClass($aArticle['sClass']);
            //增加需要加上当前添加人ID
            $aCurrUserInfo = $this->aCurrUser;
            $aArticle['iUpdateUserID'] = $aCurrUserInfo['iUserID'];
            $aArticle['iCreateUserID'] = $aCurrUserInfo['iUserID'];
            $iArticleID = Model_Article::addData($aArticle);
            if ($iArticleID > 0) {
                return $this->showMsg(['sMsg' => $aClass['sTitle'] . '信息' . $sAction . '成功！', 'iArticleID' => $iArticleID], true);
            } else {
                return $this->showMsg($aClass['sTitle'] . '信息' . $sAction . '失败！', false);
            }
        } else {
            $this->_response->setHeader('Access-Control-Allow-Origin', '*.*');
            $sClass = $this->getParam('class');
            $this->assign('iCityID', $this->iCurrCityID);
            $this->assign('aCategory', Model_Article::getCategorys($sClass));
            $this->assign('aTag', Model_Article::getTags($sClass));
            $this->assign('aAuthor', Model_Article::getAuthors($sClass));
            $this->assign('sUploadUrl', Yaf_G::getConf('upload', 'url'));
            $this->assign('sFileBaseUrl', 'http://' . Yaf_G::getConf('file', 'domain'));
            $this->assign('aClass', Model_Article::getClass($sClass));
            $this->_assignUrl($sClass);
        }
    }

    /**
     * 接收param方法
     * param $newid 0:接收数据,大于0:表中获取数据,通过传进来的articleid获取
     * return array
     */
    private function _getParams()
    {
        return $aRow = array(
            'sTitle' => trim($this->getParam('sTitle')),
            'iCityID' => $this->iCurrCityID,
            'iCategoryID' => intval($this->getParam('iCategoryID')),
            'sAuthorName' => $this->getParam('sAuthorName'),
            'iAuthorID' => $this->getParam('iAuthorID'),
            'sAbstract' => $this->getParam('sAbstract'),
            'sContent' => $this->getParam('sContent'),
            'sLoupanID' => trim($this->getParam('sLoupanID')),
            'sSource' => $this->getParam('sSource'),
            'sTag' => trim($this->getParam('sTag')),
            'sKeyword' => $this->getParam('sKeyword'),
            'sImage' => $this->getParam('sImage'),
            'sShortTitle' => $this->getParam('sShortTitle'),
            'iPublishTime' => strtotime($this->getParam('iPublishTime')),
            'iOptype' => $this->getParam('iOptype') ? $this->getParam('iOptype') : 0,//操作类型0：保存,1：发布
            'sMedia' => trim($this->getParam('sMedia')),
            'sClass' => $this->getParam('sClass')
        );
    }


    /**
     * 请求数据检测
     * @param $sType 操作类型 add:添加,edit:修改:
     * @param $iOptype 操作类型 0:保存,1:发布
     * @return mixed
     *
     */
    public function _checkData($sType = 'add', $aParam = array())
    {
        $aRow = empty($aParam) ? $this->_getParams() : $aParam;
        $aClass = Model_Article::getClass($aRow['sClass']); 
        
        //保存和发布都需要做的判断
        if (! Util_Validate::isLength($aRow['sTitle'], 5, 80)) {
            return $this->showMsg('标题长度范围为5到80个字！', false);
        }
        
        if (! empty($aParam) || (isset($aRow['iOptype']) && $aRow['iOptype'] > 0)) {
            if ($aClass['iShortTitle']) {
                if (! Util_Validate::isLength($aRow['sShortTitle'], $aClass['aShortTitle'][0], $aClass['aShortTitle'][1])) {
                    return $this->showMsg('短标题长度范围为'.$aClass['aShortTitle'][0].'到'.$aClass['aShortTitle'][1].'个字！', false);
                }
            }
            if ($aClass['sAuthor']) {
                if (!Util_Validate::isLength($aRow['sAuthorName'], 2, 20)) {
                    return $this->showMsg('作者长度范围为2到20个字！', false);
                }
                
                $sAuthorType = $aClass['sAuthor'];
                if (!Model_Type::getTypeByName($sAuthorType, $aRow['sAuthorName'])) {
                    return $this->showMsg('作者不存在', false);
                }
            }
            
            if ($aClass['sMedia']) {
                if (!Util_Validate::isLength($aRow['sMedia'], $aClass['aMedia'][0], $aClass['aMedia'][1])) {
                    return $this->showMsg('媒体来源长度范围为'.$aClass['aMedia'][0].'到'.$aClass['aMedia'][1].'个字！', false);
                }
            }
            if ($aClass['sKeyword']){
                if (!Util_Validate::isLength($aRow['sKeyword'], $aClass['aKeyword'][0], $aClass['aKeyword'][1])) {
                    return $this->showMsg('关键字长度范围为'.$aClass['aKeyword'][0].'到'.$aClass['aKeyword'][1].'个字！', false);
                }
            }
            if ($aClass['sAbstract']) {
                if (!Util_Validate::isLength($aRow['sAbstract'], $aClass['aAbstract'][0], $aClass['aAbstract'][1])) {
                    return $this->showMsg('摘要长度范围为'.$aClass['aAbstract'][0].'到'.$aClass['aAbstract'][1].'个字！', false);
                }
            }
            if (!Util_Validate::isLength($aRow['sContent'], 100, 16777215)) {
                return $this->showMsg('内容长度范围为100到65535个字！', false);
            }
            if ($aClass['sCategoryID'] && $aRow['sCategoryID'] < 0) {
                return $this->showMsg('请选择一个分类！', false);
            }
            if ($aClass['iImage'] && empty($aRow['sImage'])) {
                return $this->showMsg('请选择一张默认图片！', false);
            }
            if ($aRow['iPublishTime'] == 0) {
                $iPublishTime = time();
            }

            if ($aClass['sTag'] && $aRow['sTag']) {
                $sTag = explode(',', $aRow['sTag']);
                foreach ($sTag as $key => $value) {
                    $aTag = Model_Type::getDetail($value);
                    if (empty($aTag) || $aTag['iStatus'] != 1) {
                        return $this->showMsg('标签不存在,无效标签名称为（' . $value . ')', false);
                    }
                }
            } else {
                $aClass['sTag'] = '';
            }
        }
        
        //去掉非字段的元素
        unset($aRow['iOptype']);
        return $aRow;
    }

    /**
     * 设置各种URL
     * @param unknown $sClass
     */
    protected function _assignUrl($sClass)
    {
        $this->assign('sListUrl', "/admin/article/list/class/$sClass.html");
        $this->assign('sAddUrl', "/admin/article/add/class/$sClass.html");
        $this->assign('sEditUrl', '/admin/article/edit.html');
        $this->assign('sDelUrl', '/admin/article/del.html');
        $this->assign('sPublishUrl', '/admin/article/publish.html');
        $this->assign('sOffUrl', '/admin/article/off.html');
    }
}