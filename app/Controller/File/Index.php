<?php

/**
 * Created by PhpStorm.
 * User: yaobiqing
 * Date: 14/12/24
 * Time: 下午2:32
 */
class Controller_File_Index extends Yaf_Controller
{

    /**
     * 文件上传
     *
     * @return bool
     */
    public function uploadAction ()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Headers: X-Requested-With,X_Requested_With');
        
        $sFromURL = $this->getRequest()->getHttpReferer();
        $oStorage = new File_Storage();
        $mResult = $oStorage->isAllowedDomain($sFromURL);
        
        $needCheckQRCode = false;
        $paramCheck = $this->getParam("nc");
        if ($paramCheck == "qrcode") {
            $needCheckQRCode = true;
        }
        
        $iRetError = 0;
        $canUpload = true;
        if (true === $mResult) {
            $aFiles = $this->getUploadFiles();
            if ($needCheckQRCode) {
                foreach ($aFiles as $aFile) {
                    $canUpload = $this->_isQRCode($aFile['tmp_name']);
                }
            }
            
            if ($canUpload) {
                $sIP = $this->getRequest()->getClientIP();
                $sDomain = Util_Uri::getDomain($sFromURL);
                $aUpdFiles = array();
                
                // 批量上传图片 @todo 待优化
                foreach ($aFiles as $aFile) {
                    list ($iError, $mResult) = $oStorage->saveFile($aFile['name'], $aFile['tmp_name'], $aFile['error'], $aFile['size'], $sIP, $sDomain);
                    
                    if ($iError == 0) {
                        $iRetError = 0;
                        $mResult['iError'] = $iError;
                        $aUpdFiles[$aFile['key']] = $mResult;
                    } else {
                        $aUpdFiles[$aFile['key']] = array(
                            'iError' => $iError,
                            'sMsg' => $mResult
                        );
                    }
                }
                
                $mResult = $aUpdFiles;
            } else {
                $iRetError = 1;
                $mResult = array(
                    'msg' => '请上传二维码！'
                );
            }
        } else {
            $iRetError = 1;
            $mResult = array(
                'sMsg' => 'The upload domain is forbidden.'
            );
        }
        
        $this->getResponse()->setHeader('Content-Type', 'application/json');
        $aRet = array_merge(array(
            'iError' => $iRetError
        ), $mResult);
        $this->getResponse()->setBody(json_encode($aRet));
        
        return false;
    }

    private function _isQRCode ($img_file)
    {
        require_once LIB_PATH . '/QRCode/QrReader.php';
        $result = true;
        try {
            $qrcode = new QrReader($img_file);
            $text = $qrcode->text();
        } catch (Exception $e) {
            $result = false;
        }
        if (empty($text)) {
            $result = false;
        }
        return $result;
    }

    /**
     * banner文件上传
     *
     * @return bool
     */
    public function banneruploadAction ()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Headers: X-Requested-With,X_Requested_With');
        
        $sFromURL = $this->getRequest()->getHttpReferer();
        $oStorage = new File_Storage();
        $mResult = $oStorage->isAllowedDomain($sFromURL);
        
        $iRetError = 0;
        if (true === $mResult) {
            $aFiles = $this->getUploadFiles();
            $sIP = $this->getRequest()->getClientIP();
            $sDomain = Util_Uri::getDomain($sFromURL);
            $aUpdFiles = array();
            
            // 批量上传图片 @todo 待优化
            foreach ($aFiles as $aFile) {
                list ($iError, $mResult) = $oStorage->saveFile($aFile['name'], $aFile['tmp_name'], $aFile['error'], $aFile['size'], $sIP, $sDomain, Model_FileMeta::BID_BANNER);
                
                if ($iError == 0) {
                    $iRetError = 0;
                    $mResult['iError'] = $iError;
                    $aUpdFiles[$aFile['key']] = $mResult;
                } else {
                    $aUpdFiles[$aFile['key']] = array(
                        'iError' => $iError,
                        'sMsg' => $mResult
                    );
                }
            }
            
            $mResult = $aUpdFiles;
        } else {
            $iRetError = 1;
            $mResult = array(
                'sMsg' => 'The upload domain is forbidden.'
            );
        }
        
        $this->getResponse()->setHeader('Content-Type', 'application/json');
        $aRet = array_merge(array(
            'iError' => $iRetError
        ), $mResult);
        $this->getResponse()->setBody(json_encode($aRet));
        
        return false;
    }

    protected function sendUnauthorized ()
    {
        $oResponse = $this->getResponse();
        $oResponse->setHeader("HTTP/1.1", "401 Unauthorized");
        $oResponse->setResponseCode(401);
        echo "401 Unauthorized";
        
        return false;
    }

    /**
     *
     * @return bool 图片显示逻辑
     */
    public function viewAction ()
    {
        $oRequest = $this->getRequest();
        $params = $this->getParams();
        if (isset($params['key'])) {
            $sKey = $params['key'];
            $iWidth = 0;
            $iHeight = 0;
            if (isset($params['w']) && isset($params['h'])) {
                $iWidth = $params['w'];
                $iHeight = $params['h'];
            }
            
            $iWaterMarkPosition = 0;
            $iWaterMarkPath = 0;
            
            if (isset($params['p'])) {
                $iWaterMarkPosition = intval($params['p']);
            }
            if (isset($params['m'])) {
                $iWaterMarkPath = intval($params['m']);
            }
            
            $sExt = $params['ext'];
            $bCrop = (isset($params['crop']) && "c" == $params['crop']) ? true : false;
            $aFileInfo = '';
            $none_match = @$oRequest->getParam('HTTP_IF_NONE_MATCH');
            
            $sDomain = $this->getRequest()->getHttpHost();
            $bllFile = new File_Storage();
            $iBid = Model_FileMeta::BID_DEFAULT;
            if (isset($params['biz']) && 'banner' == $params['biz']) {
                $iBid = Model_FileMeta::BID_BANNER;
            }
            
            $mRet = $bllFile->getFile($sKey, $sExt, $aFileInfo, $sDomain, $iWidth, $iHeight, $bCrop, $iWaterMarkPosition, $iWaterMarkPath, false, $iBid);
            
            if (true === $mRet) {
                if ($aFileInfo['sETag'] == $none_match) {
                    return $this->sendNoModify();
                } else {
                    return $this->sendFile($aFileInfo['sContent'], $aFileInfo['sETag'], $aFileInfo['sMimeType']);
                }
            }
            
            if (in_array($mRet, [
                File_Storage::FILE_NOT_EXISTS,
                File_Storage::FILE_EXT_INVAILD,
                File_Storage::FILE_MIMETYPE_NOT_ALLOWED
            ])) {
                return $this->sendNotFound();
            }
            
            if ($mRet === File_Storage::FILE_IMAGE_DIMENSION_NOT_ALLOWED) {
                return $this->sendUnauthorized();
            }
        } else {
            return $this->sendNotFound();
        }
    }

    /**
     *
     * @return bool void
     */
    public function downloadAction ()
    {
        $params = $this->getParams();
        if (isset($params['key'])) {
            $sKey = $params['key'];
            $iWidth = 0;
            $iHeight = 0;
            
            if (isset($params['w']) && isset($params['h'])) {
                $iWidth = $params['w'];
                $iHeight = $params['h'];
            }
            if (isset($params['p'])) {
                $iWaterMarkPosition = intval($params['p']);
            }
            if (isset($params['m'])) {
                $iWaterMarkPath = intval($params['m']);
            }
            
            $sExt = $params['ext'];
            $bCrop = (isset($params['crop']) && "c" == $params['crop']) ? true : false;
            $aFileInfo = '';
            
            $sDomain = $this->getRequest()->getHttpHost();
            $bllFile = new File_Storage();
            $iBid = Model_FileMeta::BID_DEFAULT;
            if (isset($params['biz']) && 'banner' == $params['biz']) {
                $iBid = Model_FileMeta::BID_BANNER;
            }
            
            $mRet = $bllFile->getFile($sKey, $sExt, $aFileInfo, $sDomain, $iWidth, $iHeight, $bCrop, $iWaterMarkPosition, $iWaterMarkPath, true, $iBid);
            if (true === $mRet) {
                if ($iWidth > 0 && $iHeight > 0) {
                    if (true == $bCrop) {
                        $aFileInfo['sName'] = $sKey . '_' . $iWidth . 'x' . $iHeight . '_' . $params['crop'];
                    } else {
                        $aFileInfo['sName'] = $sKey . '_' . $iWidth . 'x' . $iHeight;
                    }
                }
                
                return $this->sendDownloadFile($sKey, $aFileInfo);
            }
            
            if (in_array($mRet, [
                File_Storage::FILE_NOT_EXISTS,
                File_Storage::FILE_EXT_INVAILD,
                File_Storage::FILE_MIMETYPE_NOT_ALLOWED
            ])) {
                return $this->sendNotFound();
            }
            
            if ($mRet === File_Storage::FILE_IMAGE_DIMENSION_NOT_ALLOWED) {
                return $this->sendUnauthorized();
            }
        } else {
            return $this->sendNotFound();
        }
    }

    /**
     *
     * @return bool 404
     */
    protected function sendNotFound ()
    {
        $oResponse = $this->getResponse();
        $oResponse->setHeader("HTTP/1.1", "404 Not Found");
        $oResponse->setResponseCode(404);
        $oResponse->setHeader('Cache-Control', 'no-cache');
        echo "404 Not Found";
        
        return false;
    }

    /**
     *
     * @return bool 304
     */
    protected function sendNoModify ()
    {
        $oResponse = $this->getResponse();
        $oResponse->setHeader('Cache-Control', 'max-age:' . Yaf_G::getConf('browseCache', 'file'));
        $oResponse->setHeader('HTTP/1.1', '304 Not Modified.');
        $oResponse->setResponseCode(304);
        
        return false;
    }

    /**
     *
     * @param
     *            $p_Content
     * @param
     *            $p_eTag
     * @param string $p_sMime            
     * @return bool 浏览器输出文件
     */
    protected function sendFile ($p_Content, $p_eTag, $p_sMime = 'image/jpg')
    {
        $oResponse = $this->getResponse();
        $oResponse->setHeader('Cache-Control', 'max-age:' . Yaf_G::getConf('browseCache', 'file'));
        $oResponse->setHeader('Content-Type', $p_sMime);
        $oResponse->setHeader('ETag', $p_eTag);
        $oResponse->setHeader('Last-Modified', gmdate("D, d M Y H:i:s", time()) . " GMT");
        $oResponse->setBody($p_Content);
        
        return false;
    }

    /**
     *
     * @param
     *            $p_sKey
     * @param
     *            $p_iWidth
     * @param
     *            $p_iHeight
     * @param
     *            $p_sExt
     * @param
     *            $p_bCrop
     * @return string ETag memcache key
     */
    protected function getETagCacheKey ($p_sKey, $p_iWidth, $p_iHeight, $p_sExt, $p_bCrop)
    {
        return md5($p_sKey . $p_iWidth . $p_iHeight . $p_sExt . strval($p_bCrop));
    }

    /**
     *
     * @param $p_oFileInfo 下载文件            
     */
    protected function sendDownloadFile ($p_sKey, $p_oFileInfo)
    {
        $ua = $this->getParam('HTTP_USER_AGENT');
        
        $encoded_filename = urlencode($p_oFileInfo['sName'] . '.' . $p_oFileInfo['sExt']);
        $encoded_filename = str_replace("+", "%20", $encoded_filename);
        $oResponse = $this->getResponse();
        
        $oResponse->setHeader('Pragma', 'public');
        $oResponse->setHeader('Expires', 0);
        $oResponse->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
        $oResponse->setHeader('Cache-Control', 'private', false);
        $oResponse->setHeader('Content-Type', $p_oFileInfo['sMimeType']);
        $oResponse->setHeader('Accept-Length', $p_oFileInfo['iSize']);
        
        if (preg_match("/MSIE/", $ua)) {
            $oResponse->setHeader('Content-Disposition', 'attachment;filename=' . $encoded_filename);
        } else 
            if (preg_match("/Firefox/", $ua)) {
                $oResponse->setHeader('Content-Disposition', 'attachment;filename*=utf8\'\'' . $p_oFileInfo['sName'] . '.' . $p_oFileInfo['sExt']);
            } else {
                $oResponse->setHeader('Content-Disposition', 'attachment;filename=' . $p_oFileInfo['sName'] . '.' . $p_oFileInfo['sExt']);
            }
        
        $oResponse->setHeader('Content-Transfer-Encoding', 'binary');
        $oResponse->setHeader('Connection', 'close');
        $oResponse->setBody($p_oFileInfo['sContent']);
        return false;
    }

    /**
     * 获取所有上传的文件信息
     *
     * @return array
     */
    protected function getUploadFiles ()
    {
        $aFiles = $_FILES;
        $aMultiFiles = array();
        
        foreach ($aFiles as $sKey => $mFiles) {
            if (is_array($mFiles['name'])) {
                $iCnt = count($mFiles['name']);
                for ($i = 0; $i < $iCnt; ++ $i) {
                    $aMultiFiles[] = array(
                        'key' => $sKey . '_' . $i,
                        'name' => $mFiles['name'][$i],
                        'tmp_name' => $mFiles['tmp_name'][$i],
                        'error' => $mFiles['error'][$i],
                        'size' => $mFiles['size'][$i]
                    );
                }
            } else {
                $aMultiFiles[] = array(
                    'key' => $sKey,
                    'name' => $mFiles['name'],
                    'tmp_name' => $mFiles['tmp_name'],
                    'error' => $mFiles['error'],
                    'size' => $mFiles['size']
                );
            }
        }
        
        return $aMultiFiles;
    }
}
