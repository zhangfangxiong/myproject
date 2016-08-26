<?php
class Controller_Admin_Message extends Controller_Admin_Base {
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Yaf_Controller::__call()
	 */
	public function __call($sMethod, $aArg) {
		$sClass = str_replace ( 'Action', '', $sMethod );
		$aClass = Model_Message::getClass ( $sClass );
		if (empty ( $aClass )) {
			parent::__call ( $sMethod, $aArg );
			return false;
		}
		$this->listAction ( $sClass );
		$this->setViewScript ( '/Admin/Message/list.phtml' );
	}
	
	/**
	 * 资讯列表
	 */
	public function listAction($sClass = '') {
		if (empty ( $sClass )) {
			$sClass = $this->getParam ( 'class', '' );
		}
		$aClass = Model_Message::getClass ( $sClass );
		
		$aParam = array ();
		$aParam ['sClass'] = $aClass ['sClass'];
		$aParam ['iMessageID'] = $this->getParam ( 'iMessageID' );
		$aParam ['sKeyword'] = $this->getParam ( 'sKeyword' );
		$aParam ['sOrder'] = $this->getParam ( 'sOrder' );
		$aParam ['iStartTime'] = $this->getParam ( 'iStartTime' );
		$aParam ['iEndTime'] = $this->getParam ( 'iEndTime' );
		$aParam ['iStatus'] = $this->getParam ( 'iStatus' );
		$iPage = intval ( $this->getParam ( 'page' ) );
		$sOrder = 'iUpdateTime DESC';
		if (! empty ( $aParam ['sOrder'] )) {
			$sOrder = $aParam ['sOrder'];
		}
		$aParam ['sOrder'] = $sOrder;
		$aList = Model_Message::getPageList ( $aParam, $iPage, $sOrder );
		$this->assign ( 'aParam', $aParam );
		$this->assign ( 'aList', $aList );
		$this->assign ( 'aClass', $aClass );
		$this->_assignUrl ( $aClass ['sClass'] );
	}
	
	/**
	 * 审核留言
	 */
	public function reviewAction() {
		$iStatus = ( int ) $this->getParam ( 'status' );
		$this->delAction ( $iStatus );
	}
	
	/**
	 * 删除资讯
	 *
	 * @return boolean
	 */
	public function delAction($iStatus = 0) {
		$sMessageID = $this->getParam ( 'id' );
		if (empty($sMessageID)) {
			return $this->showMsg ( '非法操作！', false );
		}
		$aMessageID = explode ( ',', $sMessageID );
		$fail_message = array ();
		foreach ( $aMessageID as $iMessageID ) {
			$iRet = Model_Message::updData ( array (
					'iMessageID' => $iMessageID,
					'iStatus' => $iStatus 
			) );
			if ($iRet != 1) {
				$fail_message [] = $iMessageID;
			}
		}
		
		if (empty ( $fail_message )) {
			return $this->showMsg ( '操作成功！', true );
		}
		$ids = join ( ',', $fail_message );
		
		return $this->showMsg ( $ids . '操作失败！', false );
	}
	
	/**
	 * 回复留言
	 *
	 * @return boolean
	 */
	public function replyAction() {
		if ($this->isPost ()) {
			$aMessage = $this->_checkData ();
			if (empty ( $aMessage )) {
				return null;
			}
			
			$aMessage ['iStatus'] = 1; // 后台回复不需要审核
			$aClass = Model_Message::getClass ( $aMessage ['sClass'] );
			if (Model_Message::addData ( $aMessage )) {
				return $this->showMsg ( $aClass ['sClassTitle'] . '信息回复成功！', true );
			} else {
				return $this->showMsg ( $aClass ['sClassTitle'] . '信息回复失败！', false );
			}
		} else {
			$this->_response->setHeader ( 'Access-Control-Allow-Origin', '*' );
			
			$iMessageID = intval ( $this->getParam ( 'id' ) );
			$aMessage = Model_Message::getDetail ( $iMessageID );
			
			$this->assign ( 'aMessage', $aMessage );
			$this->assign ( 'aAuthor', Model_Message::getAuthors ( $aMessage ['sClass'] ) );
			$this->assign ( 'aClass', Model_Message::getClass ( $aMessage ['sClass'] ) );
			$this->_assignUrl ( $aMessage ['sClass'] );
		}
	}
	
	/**
	 * 请求数据检测
	 *
	 * @param $sType 操作类型
	 *        	add:添加,edit:修改:
	 * @return mixed
	 *
	 */
	public function _checkData($sType = 'add') {
		return $aRow = array (
				'iReplyID' => intval ( $this->getParam ( 'iReplyID' ) ),
				'iTargetID' => intval ( $this->getParam ( 'iTargetID' ) ),
				'iUserID' => intval ( $this->getParam ( 'iUserID' ) ),
				'sContent' => trim ( $this->getParam ( 'sContent' ) ),
				'sDiy1' => trim ( $this->getParam ( 'sDiy1' ) ),
				'sDiy2' => trim ( $this->getParam ( 'sDiy2' ) ),
				'sDiy3' => trim ( $this->getParam ( 'sDiy3' ) ),
				'sImage' => trim ( $this->getParam ( 'sImage' ) ),
				'sClass' => $this->getParam ( 'sClass' ) 
		);
	}
	
	/**
	 * 设置各种URL
	 *
	 * @param unknown $sClass        	
	 */
	protected function _assignUrl($sClass) {
		$this->assign ( 'sListUrl', "/admin/message/list/class/$sClass.html" );
		$this->assign ( 'sAddUrl', "/admin/message/add/class/$sClass.html" );
		$this->assign ( 'sEditUrl', '/admin/message/edit.html' );
		$this->assign ( 'sDelUrl', '/admin/message/del.html' );
		$this->assign ( 'sReviewUrl', '/admin/message/review.html' );
		$this->assign ( 'sReplyUrl', '/admin/message/reply.html' );
		$this->assign ( 'sPublishUrl', '/admin/message/publish.html' );
		$this->assign ( 'sOffUrl', '/admin/message/off.html' );
		$this->assign ( 'aStatus', Util_Common::getConf ( 'aReviewStatus' ) );
	}
}