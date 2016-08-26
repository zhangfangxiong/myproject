<?php
/**
 * 自媒体后台处理
 *
 *  User: pancke@qq.com
 *  Date: 2015-10-28
 *  Time: 下午4:29:42
 */

class Controller_Cmd_Media extends Controller_Cmd_Base
{
    /**
     * 计算阅读量
     */
    public function statavgreadAction ()
    {
        Model_Media::query('UPDATE t_crawl_weixin w, t_media m SET w.iOrder=1 WHERE w.sAccount=m.sOpenName');
        
        Model_Media::query('UPDATE t_media SET iReadAvgNum=0');
        
        $sLast30Day = strtotime('-30day');
        Model_Media::query('UPDATE t_media m,t_crawl_weixin w SET iReadAvgNum=(SELECT AVG(iReadNum) FROM t_crawl_weixin_article WHERE iWeixinID=w.iWeixinID AND iPublishTime>'.$sLast30Day.' ORDER BY iReadNum DESC LIMIT 30) WHERE iMediaType=1 AND  m.sOpenName=w.sAccount');
    }
    
    /**
     * 修改自增加的数据
     */
    public function alterautoAction ()
    {
        $iAutoID = date('Ymd000001');
        $aTable = array('t_ad', 't_ad_media', 't_finance');
        foreach ($aTable as $sTable) {
            $sSQL = 'ALTER TABLE `' . $sTable . '` AUTO_INCREMENT=' . $iAutoID;
            Model_Media::query($sSQL);
        }
    }
    
    /**
     * 统计抓取数据
     */
    public function statcrawlAction ()
    {
        $iLast1Day = strtotime('-1day');
        $iWeixinCreateCnt = Model_Media::query('SELECT COUNT(*) FROM t_crawl_weixin WHERE iCreateTime>=' . $iLast1Day, 'one');
        $iWeixinUpdateCnt = Model_Media::query('SELECT COUNT(*) FROM t_crawl_weixin WHERE iUpdateTime>=' . $iLast1Day, 'one');
        $iArticeCreateCnt = Model_Media::query('SELECT COUNT(*) FROM t_crawl_weixin_article WHERE iCreateTime>=' . $iLast1Day, 'one');
        $iArticeUpdateCnt = Model_Media::query('SELECT COUNT(*) FROM t_crawl_weixin_article WHERE iUpdateTime>=' . $iLast1Day, 'one');
        
        $sBody = "
    今天数据抓取结果如下：<br>\n
    微信公众号新增：${iWeixinCreateCnt}个<br>\n
    微信公众号更新：${iWeixinUpdateCnt}个<br>\n
    微信文章新增：${iArticeCreateCnt}个<br>\n
        ";
        echo $sBody;

        $aTo = array(
            'david@51wom.com',
            'james@51wom.com',
            'keynes@51wom.com',
            'tony@51wom.com',
            '170800485@qq.com',
            'viven@51wom.com'
        );
        Util_Mail::send('tony@51wom.com', '51wom数据抓取结果('.date('Y-m-d').')', $sBody);
    }
}
