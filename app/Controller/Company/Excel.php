<?php

/**
 * 企业后台_员工信息导出导入
 * User: xuchuyuan
 * Date: 16/4/12 13:00
 */
class Controller_Company_Excel extends Controller_Company_Employee
{

	const DESC_PREV = '员工';

	const DESC_FAMILY_PREV = '家属';
	
	const DESC_TAIL = '不能为空\\n';

	public $sex = null;

	public $comlevel = null;

	public $log = '';

	public $familylog = '';

	public $import = null;

	public $cardtype = null;

	public $relationship = null;

	public $desc = [
		'sUserName' 	  => '员工编号',
		'sRealName'       => '姓名',
		'iSex'            => '性别',
		'sEmail'          => '邮箱',
		'sMobile'         => '电话',
		'sIdentityCard'   => '身份证',
		'sBirthDate'      => '生日'
	];

	public $familyDesc = [
		'sRealName'       => '姓名',
		'iSex'            => '性别',
		'sDocumentNumber' => '身份证',
		'sBirthDate'      => '生日'
	];

	public function actionBefore ()
	{
		parent::actionBefore();

		$this->sex = Yaf_G::getConf('aSex');

		$this->import = Yaf_G::getConf('aImport');

		foreach ($this->level as $key => $value) {
			$this->comlevel[$value] = $key;
		}

		$this->cardtype = Yaf_G::getConf('aCardType');

		$this->relationship = Yaf_G::getConf('aRelationship');		
	}

	public function downDemoAction ()
	{
		$iType = $this->getParam('type');
		if ($iType == 1) {
			$filepath = '/data/wwwroot/xcy/51joying/doc/employeedemo.xls';
			// $filepath = 'http://' . Yaf_G::getConf('static', 'domain') . '/doc/employeedemo.xls';
			$filename = '员工信息导入模板.xls';
		}

		if ($iType == 2) {
			$filepath = 'http://' . '/data/wwwroot/xcy/51joying/doc/family.xls';
			// $filepath = Yaf_G::getConf('static', 'domain') . '/doc/family.xls';
			$filename = '员工家属信息导入模板.xls';
		}

		Util_File::download($filepath, $filename);
	}

	/**
	 * 导入员工Excel
	 * @return
	 */
	public function importEmployeeAction ()
	{
		if ($this->isPost()) {
			$params = $this->getParams();
			if (!$params['sFile']) {
	            return $this->showMsg('文件不能为空', false);
	        }

			$aFile = explode('.', $params['sFile']);
	        $oFile = new File_Storage();
	        $ret = $oFile->getFile($aFile[0], $aFile[1]);
			if ($ret) {
				$PHPExcel  = new PHPExcel();
	            $PHPReader = new PHPExcel_Reader_Excel2007();
	            
	            $file_path = $oFile->getDestFile($aFile[0]);
	            if(!$PHPReader->canRead($file_path)){
	                $PHPReader = new PHPExcel_Reader_Excel5();
	                if(!$PHPReader->canRead($file_path)){
	                    return $this->showMsg('Excel文件处理错误', false);                
	                }
	            }
	            $PHPExcel = $PHPReader->load($file_path);
	            $currentSheet = $PHPExcel->getSheet(0);
	            $allRow = $currentSheet->getHighestRow();

	            $employee['iDeptID'] = intval($this->getParam('iDeptID'));
				if (!$employee['iDeptID'] || !isset($this->dept[$employee['iDeptID']])) {
					return $this->showMsg('请选择需要导入的部门', false);
				}			

				$count = 0;
				for ($i = 2; $i <= $allRow; $i++) { //第1行是表头,从第2行开始读取
					$customer = [];
					$sUserName = $PHPExcel->getActiveSheet()->getCell("A".$i)->getValue();
					$customer['sRealName'] = $PHPExcel->getActiveSheet()->getCell("B".$i)->getValue();
					
					$sex = $PHPExcel->getActiveSheet()->getCell("C".$i)->getValue();
					$customer['iSex'] = isset($this->import['sex'][$sex]) ? $this->import['sex'][$sex] : 0;

					$marriage = $PHPExcel->getActiveSheet()->getCell("D".$i)->getValue();
					$customer['iMarriage'] = isset($this->import['marriage'][$marriage]) ? $this->import['marriage'][$marriage] : 0;

					$customer['sMobile']       = $PHPExcel->getActiveSheet()->getCell("H".$i)->getValue();
					$customer['sIdentityCard'] = $PHPExcel->getActiveSheet()->getCell("I".$i)->getValue();
					$customer['sBirthDate']    = $PHPExcel->getActiveSheet()->getCell("J".$i)->getValue();
					if (!$customer['sBirthDate']) {
						$customer['sBirthDate'] = Model_Company_Employee::getBirthdateByID($customer['sIdentityCard']);
					}
				
					$bool = $this->checkData2($customer);
					if (!$bool) continue;
					$customer['iCreateUserID'] = $this->enterpriseId;

					$row = Model_CustomerNew::checkIsExist($customer['sRealName'], $customer['sIdentityCard']);
					if ($row) {
						$customer['iUserID'] = $row['iUserID'];
						$aCompany = Model_Company_Company::checkIsExist($customer['iUserID'], $this->enterpriseId);
						if ($aCompany) {
							continue;
						}

						Model_CustomerNew::updData($customer);
					} else {
						$customer['sUserName'] = Model_CustomerNew::initUserName();
						$customer['sPassword'] = md5(Yaf_G::getConf('cryptkey', 'cookie') .$customer['sUserName']);
						$customer['iUserID'] = Model_CustomerNew::addData($customer);
					}

					$employee = [];
					$employee['iDeptID'] = intval($this->getParam('iDeptID'));
					$employee['iCreateUserID']   = $this->enterpriseId;
					$employee['iCompanyID']   = $this->enterpriseId;

					$level = $PHPExcel->getActiveSheet()->getCell("F".$i)->getValue();
					$employee['iJobGradeID'] = isset($this->comlevel[$level]) ? $this->comlevel[$level] : 0;

					$employee['sUserName'] = $sUserName;
					if (!$employee['sUserName']) {
						$employee['sUserName'] = Model_Company_Company::initUserName();
					}

					$employee['iUserID']	   = $customer['iUserID'];
					$employee['sJobTitleName'] = $PHPExcel->getActiveSheet()->getCell("E".$i)->getValue();
					$employee['sEmail']        = $PHPExcel->getActiveSheet()->getCell("G".$i)->getValue();
					$employee['sJobDate']      = $PHPExcel->getActiveSheet()->getCell("K".$i)->getValue();
					$employee['sRemark']       = $PHPExcel->getActiveSheet()->getCell("L".$i)->getValue();
					$employee['sCiicNumber']   = $PHPExcel->getActiveSheet()->getCell("M".$i)->getValue();
					$employee['sExpNumber']    = $PHPExcel->getActiveSheet()->getCell("N".$i)->getValue();	
					
					$aCompanyUser = Model_User::getDetail($this->enterpriseId);
					$employee['sCompanyName']  = $aCompanyUser['sRealName'];
					$employee['sCompanyCode']  = $aCompanyUser['sUserName'];

					Model_Company_Company::addData($employee);

					$count++;
				}

				return $this->showMsg(['导入完成，导入成功'.$count.'条', $this->log], true);
			}
		} else {
			$this->_excelMenu(1);
		}
	}

	/**
	 * 检测参数，记录log
	 * @return [array]
	 */
	public function checkData2 ($customer)
	{
		$this->log .= "\n" . $customer['sRealName'];
		if (!$customer['sRealName']) {
			$this->log .= self::DESC_PREV.$this->desc['sRealName'].self::DESC_TAIL;
			return false;
		}
		if (!$customer['iSex']) {
			$this->log .= self::DESC_PREV.$this->desc['iSex'].self::DESC_TAIL;
			return false;
		}
		if (!$customer['sMobile']) {
			$this->log .= self::DESC_PREV.$this->desc['sMobile'].self::DESC_TAIL;
			return false;
		}
		if (!$customer['sIdentityCard']) {
			$this->log .= self::DESC_PREV.$this->desc['sIdentityCard'].self::DESC_TAIL;
			return false;
		}
		if ($customer['sUserName']) {
			$aCompany = Model_Company_Company::getUserByUserName($customer['sUserName'], $this->enterpriseId);
			if ($aCompany) {
				$this->log .= self::DESC_PREV.$this->desc['sRealName'].'相同员工编号已存在';
				return false;
			}
		}

		$aCustomer = Model_CustomerNew::getUserByIdentityCard($customer['sIdentityCard']);
		if ($aCustomer && $aCustomer['sRealName'] != $customer['sRealName']) {
			$this->log .= self::DESC_PREV.$this->desc['sRealName'].'相同身份证号员工已存在';
			return false;
		}
		if ($aCustomer) {
			$aCompany = Model_Company_Company::checkIsExist($aCustomer['iUserID'], $this->enterpriseId);
			if ($aCompany) {
				$this->log .= '员工已存在';
				return false;
			}
		}

		return true;
	}


	/**
	 * 导入家属Excel
	 * @return
	 */
	public function importFamilyAction ()
	{
		if ($this->isPost()) {
			$params = $this->getParams();
			if (!$params['sFile']) {
	            return $this->showMsg('文件不能为空', false);
	        }
			$aFile = explode('.', $params['sFile']);
	        $oFile = new File_Storage();
	        $ret = $oFile->getFile($aFile[0], $aFile[1]);
	        if ($ret) {
				$PHPExcel  = new PHPExcel();
	            $PHPReader = new PHPExcel_Reader_Excel2007();
	            
	            $file_path = $oFile->getDestFile($aFile[0]);
	            if(!$PHPReader->canRead($file_path)){
	                $PHPReader = new PHPExcel_Reader_Excel5();
	                if(!$PHPReader->canRead($file_path)){
	                    return $this->showMsg('Excel文件处理错误', false);                
	                }
	            }
	            $PHPExcel = $PHPReader->load($file_path);
	            $currentSheet = $PHPExcel->getSheet(0);
	            $allRow = $currentSheet->getHighestRow();				
				for ($i = 3; $i <= $allRow; $i++) { //第1、2行是表头,从第3行开始读取
					$sRealName = $PHPExcel->getActiveSheet()->getCell("A".$i)->getValue();
					$sIdentityCard = $PHPExcel->getActiveSheet()->getCell("B".$i)->getValue();	
					$customer = Model_CustomerNew::checkIsExist($sRealName, $sIdentityCard);
					if (!$customer) {
						$this->familylog .= $sRealName . '无此员工';
						continue;
					}

					$where = [
						'iUserID' => $customer['iUserID'],
						'iCreateUserID' => $this->enterpriseId,
						'iStatus >' => Model_Company_Employee::STATUS_INVALID
					];	
					$aCompany = Model_Company_Company::getRow(['where' => $where]);
					if (!$aCompany) {
						$this->familylog .= $sRealName . '无此员工';
						continue;
					}
					
					$family['iCCID'] = $aCompany['iAutoID'];

					$sex = $PHPExcel->getActiveSheet()->getCell("D".$i)->getValue();
					$family['iSex'] = isset($this->import['sex'][$sex]) ? $this->import['sex'][$sex] : 0;

					$relation = $PHPExcel->getActiveSheet()->getCell("E".$i)->getValue();
					$family['iRelationID'] = isset($this->relationship[$relation]) ? $this->relationship[$relation] : 0;	
					$family['sRealName']       = $PHPExcel->getActiveSheet()->getCell("C".$i)->getValue();	
					$family['sEmail']          = $PHPExcel->getActiveSheet()->getCell("F".$i)->getValue();
					$family['sMobile']         = $PHPExcel->getActiveSheet()->getCell("G".$i)->getValue();
					$family['iDocumentTypeID'] = 1;
					$family['sDocumentNumber'] = $PHPExcel->getActiveSheet()->getCell("H".$i)->getValue();
					$family['sBirthDate']       = $PHPExcel->getActiveSheet()->getCell("I".$i)->getValue();
					$family['sRemark']         = $PHPExcel->getActiveSheet()->getCell("J".$i)->getValue();			
					$bool = $this->checkFamilyData($family);
					if (!$bool) continue;

					$family['iEnterpriseID'] = $this->enterpriseId;
					$family['iEmployeeID'] = $customer['iUserID'];
					list($row, $desc) = Model_Company_Family::checkExist($family);
					if ($row) {
						$this->familylog .= $family['sRealName'].$desc.'  \\n';
						continue;
					}
					
					Model_Company_Family::addData($family);
				}
			}

			return $this->showMsg(['导入完成', $this->familylog], true);
		} else {
			$this->_excelMenu(2);
		}		
	}


	/**
	 * 检测参数，记录log
	 * @return [array]
	 */
	public function checkFamilyData ($family)
	{
		if ($family['sRealName']) {
			$this->familylog .= '\\n' . $family['sRealName'];
		}		
		if (!$family['sRealName']) {
			$this->familylog .= self::DESC_FAMILY_PREV.$this->familyDesc['sRealName'].self::DESC_TAIL;
			return false;
		}
		if (!$family['iSex']) {
			$this->familylog .= self::DESC_FAMILY_PREV.$this->familyDesc['iSex'].self::DESC_TAIL;
			return false;
		}
		if (!$family['sDocumentNumber']) {
			$this->familylog .= self::DESC_FAMILY_PREV.$this->familyDesc['sDocumentNumber'].self::DESC_TAIL;
			return false;
		}
		if (!$family['sBirthDate']) {
			$this->familylog .= self::DESC_FAMILY_PREV.$this->familyDesc['sBirthDate'].self::DESC_TAIL;
			return false;
		}

		return true;
	}


	/**
	 * 导出Excel
	 * @return [file]
	 */
	public function exportAction ()
	{
		$param = $this->getParams();
		$where = [
			'iCreateUserID' => $this->enterpriseId
		];
		
		isset($param['iStatus']) && intval($param['iStatus'])
		? $where['iStatus'] = intval($param['iStatus']) 
		: $where['iStatus >'] = Model_Company_Employee::STATUS_INVALID;
		
		$aEmployee = Model_Company_Company::getAll(['where' => $where]);
		if (!$aEmployee) {
			return $this->showMsg('暂无数据', false);
		}

		$aEmployee = $this->setEmployeeData($aEmployee);

		$PHPExcel = new PHPExcel();
        //填入表头
        $PHPExcel->getActiveSheet()->setCellValue('A1', '员工编号');
        $PHPExcel->getActiveSheet()->setCellValue('B1', '员工姓名');
        $PHPExcel->getActiveSheet()->setCellValue('C1', '员工身份证号');
        $PHPExcel->getActiveSheet()->setCellValue('D1', '性别');
        $PHPExcel->getActiveSheet()->setCellValue('E1', '所属机构');
        $PHPExcel->getActiveSheet()->setCellValue('F1', '电子邮件');
        $PHPExcel->getActiveSheet()->setCellValue('G1', '手机');

        //设置单元格宽度
        $PHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $PHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $PHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $PHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(10);
        $PHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $PHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(30);
        $PHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);

        //设置字体样式
        $PHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $PHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setName('黑体')->setSize(14);
        $PHPExcel->getActiveSheet()->getStyle('B1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $PHPExcel->getActiveSheet()->getStyle('B1')->getFont()->setName('黑体')->setSize(14);
        $PHPExcel->getActiveSheet()->getStyle('C1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $PHPExcel->getActiveSheet()->getStyle('C1')->getFont()->setName('黑体')->setSize(14);
        $PHPExcel->getActiveSheet()->getStyle('D1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $PHPExcel->getActiveSheet()->getStyle('D1')->getFont()->setName('黑体')->setSize(14);
        $PHPExcel->getActiveSheet()->getStyle('E1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $PHPExcel->getActiveSheet()->getStyle('E1')->getFont()->setName('黑体')->setSize(14);
        $PHPExcel->getActiveSheet()->getStyle('F1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $PHPExcel->getActiveSheet()->getStyle('F1')->getFont()->setName('黑体')->setSize(14);
        $PHPExcel->getActiveSheet()->getStyle('G1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $PHPExcel->getActiveSheet()->getStyle('G1')->getFont()->setName('黑体')->setSize(14);        

        //填入列表
        foreach ($aEmployee as $key => $value){            
            $PHPExcel->getActiveSheet()->setCellValue('A'.($key+2), $value['sUserName']);
            $PHPExcel->getActiveSheet()->getStyle('A'.($key+2))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $PHPExcel->getActiveSheet()->setCellValue('B'.($key+2), $value['sRealName']);
            $PHPExcel->getActiveSheet()->getStyle('B'.($key+2))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $PHPExcel->getActiveSheet()->setCellValueExplicit('C'.($key+2), $value['sIdentityCard'], PHPExcel_Cell_DataType::TYPE_STRING);
            $PHPExcel->getActiveSheet()->getStyle('C'.($key+2))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $PHPExcel->getActiveSheet()->setCellValue('D'.($key+2), $this->sex[$value['iSex']]);
            $PHPExcel->getActiveSheet()->getStyle('D'.($key+2))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $PHPExcel->getActiveSheet()->setCellValue('E'.($key+2), $this->dept[$value['iDeptID']]);
            $PHPExcel->getActiveSheet()->getStyle('E'.($key+2))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $PHPExcel->getActiveSheet()->setCellValue('F'.($key+2), $value['sEmail']);
            $PHPExcel->getActiveSheet()->getStyle('F'.($key+2))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $PHPExcel->getActiveSheet()->setCellValue('G'.($key+2), $value['sMobile']);
            $PHPExcel->getActiveSheet()->getStyle('G'.($key+2))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        }
     
        $filename = "员工信息.xls";  
		$encoded_filename = urlencode($filename);  
		$encoded_filename = str_replace("+", "%20", $encoded_filename);  
		
		//到浏览器
		header('Content-Type: application/octet-stream');  
		if (preg_match("/MSIE/", $ua)) {  
		   header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');  
		} else if (preg_match("/Firefox/", $ua)) {  
		   header('Content-Disposition: attachment; filename*="utf8\'\'' . $filename . '"');  
		} else {  
		   header('Content-Disposition: attachment; filename="' . $filename . '"');  
		} 

        $objWriter = new PHPExcel_Writer_Excel5($PHPExcel);  
        header("Content-Type: application/force-download");  
        header("Content-Type: application/octet-stream");  
        header("Content-Type: application/download");  
        header("Content-Transfer-Encoding: binary");  
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");  
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");  
        header("Pragma: no-cache");  

        $objWriter->save('php://output');
	}

	/*
	 * excelmenu
	 */
    private function _excelMenu($iMenu)
    {
        $aMenu = [
            1 => [
                'url' => '/company/excel/importemployee',
                'name' => '导入员工信息',
            ],
            2 => [
                'url' => '/company/excel/importfamily',
                'name' => '导入员工家属信息',
            ]            
        ];
        $this->assign('aMenu', $aMenu);
        $this->assign('iMenu', $iMenu);
    }
}