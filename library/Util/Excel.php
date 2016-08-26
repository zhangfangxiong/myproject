<?php

/**
 * Excel处理相关
 */
class Util_Excel
{
    private $sFile;
    
    private $oPHPExcel;
    private $oReader;
    private $oSheet;
    private $iRowNum;
    private $iColNum;
    private $iCurrRow;
    
    public function load ($sFile)
    {
        $this->sFile = $sFile;
        $this->oReader = PHPExcel_IOFactory::createReader('Excel5');
        $this->oReader->setReadDataOnly(true);
        $this->oPHPExcel = $this->oReader->load($sFile);
        $this->oSheet = $this->oPHPExcel->getActiveSheet();
        $this->iRowNum = $this->oSheet->getHighestRow();
        $sHighestColumn = $this->oSheet->getHighestColumn();
        $this->iColNum = PHPExcel_Cell::columnIndexFromString($sHighestColumn);
        $this->iCurrRow = 1;
    }
    
    /**
     * 查找Excel的下一行
     * @return array
     */
    public function getNextRow()
    {
        $aRow = array();
        for (; $this->iCurrRow <= $this->iRowNum; $this->iCurrRow ++) {
            $aRow = array();
            $sFind = false;
            for ($col = 0; $col < $this->iColNum; $col ++) {
                $sVal = trim($this->oSheet->getCellByColumnAndRow($col, $this->iCurrRow)->getValue());
                $aRow[] = $sVal;
                if (! empty($sVal)) {
                    $sFind = true;
                }
            }
            
            if ($sFind) {
                break;
            }
        }
        
        return $aRow;
    }
}