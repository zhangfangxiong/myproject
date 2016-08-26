<?php

class Controller_Admin_Banner extends Controller_Admin_Base
{

    /**
     * banner滚动删除
     */
    public function delAction ()
    {
        $iBadwordID = intval($this->getParam('id'));
        $iRet = Model_Banner::delData($iBadwordID);
        if ($iRet == 1) {
            return $this->showMsg('banner滚动删除成功！', true);
        } else {
            return $this->showMsg('banner滚动删除失败！', false);
        }
    }

    /**
     * banner滚动列表
     */
    public function listAction ()
    {
        $iPage = intval($this->getParam('page'));
        if (isset($_GET['page'])) {
            $iPage = $_GET['page'];
        }
        $aWhere = array(
            'iStatus' => 1
        );
        
        $aParam = $this->getParams();
        if (! empty($aParam['title'])) {
            $aWhere['title LIKE'] = '%' . $aParam['title'] . '%';
        }
        
        $aList = Model_Banner::getList($aWhere, $iPage);
        $this->assign('aList', $aList);
        $this->assign('aParam', $aParam);
    }

    /**
     * banner滚动修改
     */
    public function editAction ()
    {
        if ($this->_request->isPost()) {
            $aBanner = $this->_checkData('update');
            if (empty($aBanner)) {
                return null;
            }
            $aBanner['iAutoID'] = intval($this->getParam('iAutoID'));
            $aOldBanner = Model_Banner::getDetail($aBanner['iAutoID']);
            if (empty($aOldBanner)) {
                return $this->showMsg('banner滚动不存在！', false);
            }
            if (1 == Model_Banner::updData($aBanner)) {
                return $this->showMsg('banner滚动信息更新成功！', true);
            } else {
                return $this->showMsg('banner滚动信息更新失败！', false);
            }
        } else {
            $iBannerID = intval($this->getParam('id'));
            $aBanner = Model_Banner::getDetail($iBannerID);
            $this->assign('aBanner', $aBanner);
        }
    }

    /**
     * 增加banner滚动
     */
    public function addAction ()
    {
        if ($this->_request->isPost()) {
            $aBanner = $this->_checkData('add');
            if (empty($aBanner)) {
                return null;
            }
            $aBanner['iStatus'] = 1;
            $aBanner['iCreateTime'] = time();
            if (Model_Banner::addData($aBanner) > 0) {
                return $this->showMsg('banner滚动增加成功！', true);
            } else {
                return $this->showMsg('banner滚动增加失败！', false);
            }
        }
    }

    /**
     * 请求数据检测
     *
     * @return mixed
     */
    public function _checkData ($sType = 'add')
    {
        $title = $this->getParam('title');
        $imgurl = $this->getParam('imgurl');
        $link = $this->getParam('link');
        $rank = $this->getParam('rank');
        $iUpdateTime = time();
        
        $aRow = array(
            'title' => $title,
            'imgurl' => $imgurl,
            'link' => $link,
            'rank' => $rank,
            'iUpdateTime' => $iUpdateTime
        );
        
        return $aRow;
    }
}