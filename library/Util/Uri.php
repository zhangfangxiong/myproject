<?php

class Util_Uri
{

    /**
     * 获取URL的Domain
     *
     * @param
     *            $p_sUrl
     */
    public static function getDomain ($p_sUrl)
    {
        if (empty($p_sUrl)) {
            $sDomain = '';
        } else {
            $sDomain = parse_url($p_sUrl, PHP_URL_HOST);
        }
        
        return $sDomain;
    }

    /**
     * 获取前当URL
     *
     * @return string
     */
    public static function getCurrUrl ()
    {
        return Yaf_G::getUrl();
    }

    /**
     * 生成新的搜索URL
     *
     * @param unknown $aParam            
     * @param unknown $sReplaceKey            
     * @param unknown $sNewValue            
     */
    public static function makeSearchUrl ($aParam, $sReplaceKey, $sNewValue, $iSelect = 0, $sUri = null)
    {
        unset($aParam['page']);
        if (! is_array($sReplaceKey)) {
            $sReplaceKey = array($sReplaceKey);
            $sNewValue = array($sNewValue);
        }
        
        foreach ($sReplaceKey as $k => $v) {
            $aParam = self::makeSearchParam($aParam, $sReplaceKey[$k], $sNewValue[$k], $iSelect);
        }
        
        return Yaf_G::getUrl($sUri, $aParam) . '.html';
    }

    public static function makeSearchParam($aParam, $sReplaceKey, $sNewValue, $iSelect)
    {
        if ($sNewValue === null) {
            unset($aParam[$sReplaceKey]);
        } else {
            if (! empty($aParam[$sReplaceKey])) {
                if (is_array($aParam[$sReplaceKey]) && in_array($sNewValue, $aParam[$sReplaceKey])) {
        
                } elseif ($aParam[$sReplaceKey] == $sNewValue) {
                    unset($aParam[$sReplaceKey]);
                }
            }
            if ($iSelect == 0) { // 单选
                if ($aParam[$sReplaceKey] == $sNewValue) {
                    unset($aParam[$sReplaceKey]);
                } else {
                    $aParam[$sReplaceKey] = $sNewValue;
                }
            } else {
                if (empty($aParam[$sReplaceKey])) {
                    $aParam[$sReplaceKey] = array();
                }
        
                $bFind = false;
                foreach ($aParam[$sReplaceKey] as $k => $v) {
                    if ($v == $sNewValue) {
                        $bFind = true;
                        unset($aParam[$sReplaceKey][$k]);
                    }
                }
        
                if ($bFind == false) {
                    $aParam[$sReplaceKey][] = $sNewValue;
                }
            }
        }
        
        return $aParam;
    }
    
    /**
     * 获取文件服务路径
     *
     * @param string $p_sFileKey            
     * @param string $p_sExtension            
     * @param int $p_iWidth            
     * @param int $p_iHeight            
     * @param string $p_sOption            
     * @return string
     */
    public static function getDFSViewURL ($p_sFileKey, $p_iWidth = 0, $p_iHeight = 0, $p_sOption = '', $sBiz = '')
    {
        $sViewUrl = Yaf_G::getConf('dfsview', 'url');
        
        if (! $p_sFileKey) {
            return '';
        }
        if ('banner' == $sBiz) {
            $sViewUrl .= '/fjbanner';
        }
        
        list ($p_sKey, $p_sExt) = explode('.', $p_sFileKey);
        if (0 == $p_iWidth && 0 == $p_iHeight) {
            return $sViewUrl . '/' . $p_sKey . '.' . $p_sExt;
        } else {
            if ('' == $p_sOption) {
                return $sViewUrl . '/' . $p_sKey . '/' . $p_iWidth . 'x' . $p_iHeight . '.' . $p_sExt;
            } else {
                return $sViewUrl . '/' . $p_sKey . '/' . $p_iWidth . 'x' . $p_iHeight . '_' . $p_sOption . '.' . $p_sExt;
            }
        }
    }
}