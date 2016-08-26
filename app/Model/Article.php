<?php

class Model_Article extends Model_Base
{

    const TABLE_NAME = 't_article';
    
    /**
     * 取得Class信息
     * @param unknown $sClass
     * @param string $sField
     * @throws Yaf_Exception
     * @return unknown
     */
    public static function getClass($sClass, $sField = null)
    {
        if (empty($sClass)) {
            $aRet = self::query("SELECT * FROM t_article_setting WHERE iStatus=1 LIMIT 1", 'row');
            $sClass = $aRet['sClass'];
        } else {
            $aRet = self::query("SELECT * FROM t_article_setting WHERE sClass='$sClass' AND iStatus=1 LIMIT 1", 'row');
            if (empty($aRet)) {
                throw new Yaf_Exception(__CLASS__ . '::' . __FUNCTION__ . " $sClass not exists!");
            }
        }
        
        $aRet['aShortTitle'] = explode('~', $aRet['sShortTitle']);
        $aRet['aKeyword'] = explode('~', $aRet['sKeyword']);
        $aRet['aAbstract'] = explode('~', $aRet['sAbstract']);
        $aRet['aSource'] = explode('~', $aRet['sSource']);
        $aRet['aMedia'] = explode('~', $aRet['sMedia']);
        
        if (empty($sField)) {
            return $aRet;
        } else {
            return $aRet[$sField];
        }
    }
    
    /**
     * 取得文章的标签
     * @param unknown $sClass
     * @return Ambigous <Ambigous, number, multitype:multitype:, multitype:unknown >
     */
    public static function getTags($sClass)
    {
        return Model_Type::getOption(self::getClass($sClass, 'sTag'));
    }
    
    /**
     * 取得文章的作者
     * @param unknown $sClass
     * @return Ambigous <Ambigous, number, multitype:multitype:, multitype:unknown >
     */
    public static function getAuthors($sClass)
    {
        return Model_Type::getOption(self::getClass($sClass, 'sAuthor'));
    }
    
    /**
     * 取得文章的分类
     * @param unknown $sClass
     * @return Ambigous <Ambigous, number, multitype:multitype:, multitype:unknown >
     */
    public static function getCategorys($sClass)
    {
        return Model_Type::getOption(self::getClass($sClass, 'sCategory'));
    }

    /**
     * 跟据标题取得资讯信息
     * 
     * @param unknown $sTagName            
     * @return Ambigous <number, multitype:, mixed>
     */
    public static function getArticleByName ($sTitle)
    {
        return self::getRow(array(
            'where' => array(
                'sTitle' => $sTitle
            )
        ));
    }

    /**
     * 跟据文章ID取得资讯信息
     * 
     * @param unknown $sTagName            
     * @return Ambigous <number, multitype:, mixed>
     */
    public static function getArticleByID ($iArticleID)
    {
        return self::getDetail($iArticleID);
    }

    /**
     * 自动完成
     * @param unknown $sKey
     * @param unknown $iCityID
     * @return Ambigous <multitype:multitype: Ambigous <> , multitype:, array/string, int/false, number, unknown, unknown>
     */
    public static function autoComplete ($sKey, $iCityID, $iLimit = 10)
    {
        $aWhere = array();
        $aWhere[] = 'iPublishStatus=1';
        $aWhere[] = 'iPublishTime<=' . time();
        $aWhere[] = 'iStatus=1';
        if (! empty($sKey)) {
            $aWhere[] = "(sShortTitle LIKE '" . addslashes($sKey) . "%' OR sTitle LIKE '%" . addslashes($sKey) . "%')";
        }
        if ($iCityID > 0) {
            $aWhere = "FIND_IN_SET($iCityID, sCityID)"; 
        }
        
        return self::query("SELECT * FROM " . self::TABLE_NAME . ' WHERE ' . join(' AND ', $aWhere) . ' LIMIT ' . $iLimit);
    }

    /**
     * 获取已发布文章详情
     * @param $iArticleID        
     */
    public static function getPublishArticle ($iArticleID)
    {
        $aWhere = [
            'iArticleID' => $iArticleID,
            'iStatus' => 1,
            'iPublishStatus' => 1,
            'iPublishTime <=' => time()
        ];
        return self::getRow([
            'where' => $aWhere
        ]);
    }

    /**
     * 按分页取文章数据
     * @param unknown $aParam
     * @param unknown $iPage
     * @param string $sOrder
     * @param number $iPageSize
     * @param string $sUrl
     * @param unknown $aArg
     * @return Ambigous <NULL, boolean, string, multitype:, array/string, int/false, number, unknown, unknown>
     */
    public static function getPageList ($aParam, $iPage, $sOrder = 'iUpdateTime DESC', $iPageSize = 20, $sUrl = '', $aArg = array())
    {
        $aParam['iStatus'] = self::STATUS_VALID;
        $sWhere = self::_buildWhere($aParam);
        $iPage = max($iPage, 1);
        $sOrder = 'ORDER BY ' . $sOrder;
        $sLimit = 'LIMIT ' . ($iPage - 1) * $iPageSize . ',' . $iPageSize;

        $sSQL = 'SELECT * FROM ' . self::TABLE_NAME . $sWhere . ' ' . $sOrder . ' ' . $sLimit;
//         echo $sSQL;exit;
        $aRet['aList'] = self::getOrm()->query($sSQL);
        if ($iPage == 1 && count($aRet['aList']) < $iPageSize) {
            $aRet['iTotal'] = count($aRet['aList']);
            $aRet['aPager'] = null;
        } else {
            unset($aParam['limit'], $aParam['order']);
            $sCntSQL = 'SELECT COUNT(*) as total FROM ' . self::TABLE_NAME . $sWhere;
            $ret = self::getOrm()->query($sCntSQL);
            $aRet['iTotal'] = $ret[0]['total'];
            $aRet['aPager'] = Util_Page::getPage($aRet['iTotal'], $iPage, $iPageSize, '', $aParam); // update by cjj 2015-02-13 分页增加query 参数
        }
        
        return $aRet;
    }

    /**
     * 更新文章的统计
     * 
     * @param $iType 1:访问量,2:分享量 ,3:点赞量           
     */
    public static function updateDayStat ($iArticleID, $iType = 1)
    {
        $aArticle = self::getArticleByID($iArticleID);
        if (empty($aArticle)) {
            return 0;
        }
        
        $sTodayField = '';
        $aData = array('iArticleID' => $iArticleID);
        switch ($iType) {
            case 2:
                $aData['iTotalShare'] += 1;
                $sTodayField = 'iTodayShare';
                break;
            case 3:
                $aData['iTotalGood'] += 1;
                $sTodayField = 'iTodayGood';
                break;
            case 1:
            default:
                $aData['iTotalVisit'] += 1;
                $sTodayField = 'iTodayVisit';
                break;
        }
        
        if (substr($aArticle[$sTodayField], 0, 8) == date('Ymd')) {
            $aData[$sTodayField] += 1;
        } else {
            $aData[$sTodayField] = date('Ymd000001');
        }
        
        return Model_Article::updData($aData);
    }

    /**
     * 获取今天热点文单
     * @param unknown $aParam
     * @param unknown $iType
     * @param number $iLimit
     * @return Ambigous <multitype:multitype: Ambigous <> , multitype:, array/string, int/false, number, unknown, unknown>
     */
    public static function getTodayHotList ($aParam, $iType, $iLimit = 10)
    {
        $sWhere = self::_buildWhere($aParam);
        switch ($iType) {
            case 2:
                $sField = 'iTodayShare';
                break;
            case 3:
                $sField = 'iTodayGood';
                break;
            case 1:
            default:
                $sField = 'iTodayVisit';
        }
        
        return self::query("SELECT * FROM " . self::TABLE_NAME . " $sWhere ORDER BY $sField DESC LIMIT $iLimit");
    }
    
    /**
     * 获得热点文章
     * @param unknown $aParam
     * @param unknown $iType
     * @param number $iLimit
     * @return Ambigous <multitype:multitype: Ambigous <> , multitype:, array/string, int/false, number, unknown, unknown>
     */
    public static function getTotalHotList ($aParam, $iType, $iLimit = 10)
    {
        $sWhere = self::_buildWhere($aParam);
        switch ($iType) {
            case 2:
                $sField = 'iTotalShare';
                break;
            case 3:
                $sField = 'iTotalGood';
                break;
            case 1:
            default:
                $sField = 'iTotalVisit';
        }
    
        return self::query("SELECT * FROM " . self::TABLE_NAME . " $sWhere ORDER BY $sField DESC LIMIT $iLimit");
    }
    
    /**
     * 根据条件取得文章
     * @param unknown $aParam
     * @param string $sOrder
     * @param number $iLimit
     * @return Ambigous <multitype:multitype: Ambigous <> , multitype:, array/string, int/false, number, unknown, unknown>
     */
    public static function getArticlesByWhere ($aParam, $sOrder = 'iPublishTime DESC', $iLimit = 20)
    {
        $sWhere = self::_buildWhere($aParam);
        return self::query('SELECT * FROM ' . self::TABLE_NAME . ' ' . $sWhere . ' ORDER BY ' . $sOrder . ' LIMIT ' . $iLimit);
    }
    
    /**
     * 统计时间段新增文章数
     * 
     * @param strting $sClass 类型
     * @param int $iCityID  0：所有，城市
     * @param int $iCategoryID  0：所有，分类
     * @param string $sTag
     * @param int $iStartTime
     * @param int $iEndTime
     * @param int $iPublishStatus null：所有，0：未发布的，1：已发布的
     * @param int $iStatus null：所有，0：删除的，1：未删除的
     */
    public static function getCntByWhere ($aParam)
    {
        $sSQL = 'SELECT COUNT(*) FROM ' . self::TABLE_NAME;
        $sWhere = self::_buildWhere($aParam);
        return self::query($sSQL . $sWhere, 'one');
    }
    
    /**
     * 构建搜索条件
     * @param unknown $aParam
     */
    public static function _buildWhere($aParam)
    {
        $aWhere = array();
        if (isset($aParam['iArticleID']) && ! empty($aParam['iArticleID'])) {
            $aWhere[] = 'iArticleID=' . $aParam['iArticleID'];
        }
        if (isset($aParam['iArticleID NOT IN']) && ! empty($aParam['iArticleID NOT IN'])) {
            $iArticleID = implode(',', $aParam['iArticleID NOT IN']);
            $aWhere[] = 'iArticleID NOT IN (' . $iArticleID . ')';
        }
        if (! empty($aParam['sClass'])) {
            $aWhere[] = 'sClass="' . addslashes($aParam['sClass']) . '"';
        }
        if (! empty($aParam['iCityID'])) {
            $aWhere[] = 'FIND_IN_SET(' . $aParam['iCityID'] . ',sCityID)';
        }
        if (! empty($aParam['iCategoryID'])) {
            $aWhere[] = 'FIND_IN_SET(' . $aParam['iCategoryID'] . ',sCategoryID)';
        }
        if (isset($aParam['iPublishStatus']) && $aParam['iPublishStatus'] != - 1 && $aParam['iPublishStatus'] !== '') {
            $aWhere[] = 'iPublishStatus=' . $aParam['iPublishStatus'];
        }
        if (isset($aParam['iStatus']) && $aParam['iStatus'] != - 1 && $aParam['iStatus'] !== '') {
            $aWhere[] = 'iStatus=' . $aParam['iStatus'];
        }
        if (! empty($aParam['sTitle'])) {
            $aWhere[] = 'sTitle LIKE \'%' . addslashes($aParam['sTitle']) . '%\'';
        }
        // 如果有作者ID就不用作者名
        if (! empty($aParam['iAuthorID']) && $aParam['iAuthorID'] > 0) {
            $aWhere[] = 'iAuthorID=' . $aParam['iAuthorID'];
        } elseif (! empty($aParam['sAuthor'])) {
            $aWhere[] = 'sAuthor LIKE \'%' . addslashes($aParam['sAuthor']) . '%\'';
        }
        if (! empty($aParam['iTagID']) && $aParam['iTagID'] > 0) {
            $aWhere[] = 'FIND_IN_SET(' . $aParam['iTagID'] . ', sTag)';
        }
        // 时间换算
        if (! empty($aParam['iStartTime'])) {
            $aWhere[] = 'iPublishTime >=' . strtotime($aParam['iStartTime']);
        }
        if (! empty($aParam['iEndTime'])) {
            $aWhere[] = 'iPublishTime <=' . strtotime($aParam['iEndTime']);
        }
        // 时间换算
        if (! empty($aParam['iStartCreateTime'])) {
            $aWhere[] = 'iCreateTime >=' . strtotime($aParam['iStartCreateTime']);
        }
        if (! empty($aParam['iEndCreateTime'])) {
            $aWhere[] = 'iCreateTime <=' . strtotime($aParam['iEndCreateTime']);
        }
        if (empty($aWhere)) {
            $sWhere = '';
        } else {
            $sWhere = ' WHERE ' . join(' AND ', $aWhere);
        }
        
        return $sWhere;
    }
}