<?php

class Controller_Admin_Faq extends Controller_Admin_Base
{

	public $category = null;

	public function actionBefore ()
	{
		parent::actionBefore();

		$this->category = Yaf_G::getConf('category', 'faq');

		$this->assign('aCategory', $this->category);
	}
	
	/**
	 * 问答列表
	 */
	public function listAction ()
	{
		$iPage = $this->getParam('page') ? intval($this->getParam('page')) : 1;
        
        $aParam = $this->getParams();
        $aWhere = [	'iStatus' => Model_Faq::STATUS_VALID ];
        if (!empty($aParam['sTitle'])) {
            $aWhere['sTitle LIKE'] = '%' . trim($aParam['sTitle']) . '%';
        }

        if (!empty($aParam['iType'])) {
            $aWhere['iType'] = $aParam['iType'];
        }

        if (!empty($aParam['iIsShow'])) {
            $aWhere['iIsShow'] = $aParam['iIsShow'];
        }

        if (!empty($aParam['iIsApply'])) {
            $aWhere['iIsApply'] = $aParam['iIsApply'];
        }

        if (!empty($aParam['iIsShare'])) {
            $aWhere['iIsShare'] = $aParam['iIsShare'];
        }

        if (!empty($aParam['iIsTop'])) {
            $aWhere['iIsTop'] = $aParam['iIsTop'];
        }
        
        $aFaq = Model_Faq::getList($aWhere, $iPage);

        if (!empty($aFaq['aList'])) {
            foreach ($aFaq['aList'] as $key => $value) {
            	$aFaq['aList'][$key]['sType'] = $this->category[$value['iType']];
            	$aFaq['aList'][$key]['sIsShow'] = $value['iIsShow'] == 1 ? '是' : '否';
            	$aFaq['aList'][$key]['sIsApply'] = $value['iIsApply'] == 1 ? '是' : '否';
            	$aFaq['aList'][$key]['sIsShare'] = $value['iIsShare'] == 1 ? '是' : '否';
            	$aFaq['aList'][$key]['sIsTop'] = $value['iIsTop'] == 1 ? '是' : '否';
            	$aFaq['aList'][$key]['sStartTime'] = date('Y-m-d', $value['iStartTime']);
            	$aFaq['aList'][$key]['sEndTime'] = date('Y-m-d', $value['iEndTime']);
            }
        }

        $this->assign('aData', $aFaq);
        $this->assign('aParam', $aParam);
	}	

	/**
	 * 新增问答
	 */
	public function addAction ()
	{
		if ($this->_request->isPost()) {
			$aData = $this->_checkData();
			if (empty($aData)) {
				return null;
			}
			
			if (Model_Faq::addData($aData) > 0) {
                return $this->showMsg('增加成功！', true);
            } else {
                return $this->showMsg('增加失败！', false);
            }
		}
	}

	/**
	 * 编辑问答
	 */
	public function editAction ()
	{
		if ($this->_request->isPost()) {
            $aFaq = $this->_checkData(2);
            if (empty($aFaq)) {
                return null;
            }
            if (Model_Faq::updData($aFaq) > 0) {
                return $this->showMsg('修改成功！', true);
            } else {
                return $this->showMsg('修改失败！', false);
            }
        } else {
            $id = $this->getParam('id');
            
            $aFaq = Model_Faq::getDetail($id);
            if (empty($aFaq)) {
                return $this->showMsg('该问答不存在！', false);
            }

            $aFaq['sStartTime'] = date('Y-m-d', $aFaq['iStartTime']);
            $aFaq['sEndTime'] = date('Y-m-d', $aFaq['iEndTime']);            	
            
            $this->assign('aFaq', $aFaq);
        }
	}

	/**
     * 请求数据检测
     * @param int $iType 1:添加，2:修改
     * @return array|bool
     */
    public function _checkData($iType = 1)
    {
        $aParam = $this->getParams();

        if (empty($aParam['sTitle'])) {
            return $this->showMsg('请输入标题关键字！', false);
        }
        
        if (empty($aParam['iType'])) {
            return $this->showMsg('请选择新闻类别', false);
        }
        
        if (empty($aParam['sContent'])) {
            return $this->showMsg('请输入新闻内容', false);
        }
        
        if (empty($aParam['iIsShow'])) {
            return $this->showMsg('请选择是否显示', false);
        }
        
        if (empty($aParam['iIsApply'])) {
            return $this->showMsg('请选择是否可以申请', false);
        }
        
        if (empty($aParam['iIsShare'])) {
            return $this->showMsg('请选择是否可以分享', false);
        }
        
        if (empty($aParam['iIsTop'])) {
            return $this->showMsg('请选择是否置顶', false);
        }
        
        if (empty($aParam['sStartTime'])) {
            return $this->showMsg('请输入有效开始时间', false);
        } else {
        	$aParam['iStartTime'] = strtotime($aParam['sStartTime']);
        }
        
        if (empty($aParam['sEndTime'])) {
            return $this->showMsg('请输入有效结束时间', false);
        } else {
        	$aParam['iEndTime'] = strtotime($aParam['sEndTime']);
        }
        
        if ($iType == 2) {
            if (empty($aParam['iAutoID'])) {
                return $this->showMsg('非法操作！', false);
            }
            if (!Model_Faq::getDetail($aParam['iAutoID'])) {
                return $this->showMsg('问答不存在！', false);
            }
        }

        return $aParam;
    }
}