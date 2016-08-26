<?php

class Controller_Admin_Clear extends Controller_Admin_Base
{

    /**
     * 清除缓存
     */
    public function cacheAction ()
    {
        $sType = $this->getParam('type');
        $oCache = Util_Common::getCache($sType);
        $oCache->flush();
        return $this->showMsg('清除成功！', true);
    }

    /**
     * 清除缓存
     */
    public function redisAction ()
    {
        $sType = $this->getParam('type');
        $oRedis = Util_Common::getRedis($sType);
        $oRedis->flushdb();
        return $this->showMsg('清除成功！', true);
    }
}