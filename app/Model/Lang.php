<?php

/**
 * 这里先整理那些后期可能后台可编辑的前端静态模块(也便于以后多语言版本，如果有需要)
 * Created by PhpStorm.
 * User: zhangfangxiong
 * Date: 16/04/06
 * Time: 下午2:32
 */
class Model_Lang extends Model_Base
{
    public static function getMenu()
    {
        $aMeta['title'] = '';
        $aMeta['keywords'] = '';
        $aMeta['description'] = '';
        $aMenu = [
            [
                'name' => '首页',
                'url' => 'javascript:;',
            ],
            [
                'name' => '安心体检',
                'url' => 'javascript:;',
            ],
            [
                'name' => '安心保障',
                'url' => 'javascript:;',
            ],
            [
                'name' => '安心理赔',
                'url' => 'javascript:;',
            ],
            [
                'name' => '合作伙伴',
                'url' => 'javascript:;',
            ],
            [
                'name' => '关于我们',
                'url' => 'javascript:;',
            ],
        ];
        return $aMenu;
    }

    public static function getWxMenu()
    {
        $aMenu = [
            [
                'name' => '安心体检',
                'url' => '/wx/appointment/cardlist/',
                'icon' => 'fa fa-3x fa-heart',
            ],
            /**
            [
                'name' => '医疗理赔',
                'url' => 'javascript:;',
                'icon' => 'fa fa-3x fa-lock',
            ],
             */
            [
                'name' => '购买体检套餐',
                'url' => '/wx/list/',
                'icon' => 'fa fa-3x fa-credit-card',
            ],
            /**
            [
                'name' => '安心商旅',
                'url' => 'javascript:;',
                'icon' => 'fa fa-3x fa-plane',
            ],
             */
            [
                'name' => '订单查询',
                'url' => '/wx/orderlist/',
                'icon' => 'fa fa-3x fa-list-alt',
            ],
            /**
            [
                'name' => '在线咨询',
                'url' => 'javascript:;',
                'icon' => 'fa fa-3x fa-question-circle',
            ],
             */
            [
                'name' => '个人信息',
                'url' => '/wx/account/userinfo/',
                'icon' => 'fa fa-3x fa-user',
            ],
        ];
        return $aMenu;
    }

    //共用类(头部尾部的一些)
    public static function getCommonLang($key='')
    {
        $aLang = [
            1=>'个人登录',
            2=>'注册',
            3=>'企业登录',
            4=>'加入收藏夹',
            5=>'您好！',
            6=>'个人中心',
            7=>'我的订单',
            8=>'我的保单',
            9=>'我的积分',
            10=>'个人信息',
            11=>'账户安全',
            12=>'评价管理',
            13=>'用户体系',
            14=>'退出',
            15=>'咨询电话',
            16=>'上海中盈保险经纪有限公司',
            17=>'沪ICP备11037892号',
            18=>'Copyright©2012-2016',
            19=>'中盈保险网',
            20=>'版权所有',
            21=>'返回顶部',
            22=>'关注安心服务号微信',
            23=>'线上咨询单',
            24=>'常见问题',
            25=>'关于我们',
            26=>'合作伙伴',
            27=>'咨询电话',
            28=>'安心服务',
            29=>'产品详情',
            30=>'预约流程',
            31=>'注意事项',
            32=>'门店列表',
            33=>'购物车',
            34=>'立即购买',
            35=>'加入购物车',
            36=>'加入成功',
            37=>'加入失败',
            38=>'添加新卡',
            39=>'进入预约',
            40=>'套餐升级',
            41=>'套餐对比',
            42=>'返回原套餐',
        ];
        return !empty($aLang[$key]) ? $aLang[$key] : $aLang;
    }
    //模块类
    public static function getStaticModule($iType)
    {
        $aModule = [
            1 => [
                [
                    '为什么选择中盈安心服务？'
                ],
                [
                    '传统代理人（败）',
                    '传统代理人一般只能为您提供一家公司产品。',
                    '传统代理人经常为您推荐佣金最丰厚的产品而不是最适合您的产品。',
                    '在理赔环节，传统代理人只能为您提供资料递送的便利，并不能为您真正解决“理赔难”的保险通病。',
                ],
                [
                    '中盈安心服务团队（胜）',
                    '我们优选各家公司优质产品，选择面更广，且产品容易进行比较。',
                    '我们深知一份保险对一个家庭的重要性，我们秉承中立无倾向的立场向您推荐最适合您的险种或者给您最中肯的建议。用我们专业的服务换取您的满意体验！',
                    '我们的专家理赔团队不仅提供理赔的跟进，更为您提供全面的理赔分析。我们始终站在用户的角度，维护您的最大权益。',
                ]
            ],
            2 => [
                '用户看中盈安心服务',
                '因为理赔选择中盈安心服务',
                '我是上海一投保客户，在中盈安心服务给我儿子买了份某保险公司的产品，我儿子因打篮球导致韧带损伤，用了将近两万元的治疗费用。我提交了资料给保险公司，结果保险公司拒赔了，说是投保前就有的旧伤引起的，中盈安心服务理赔人员得知该情况后，咨询了数位骨科专家医生，得知，这种急性前交叉韧带损伤，几天内靠股骨上端出现吸收表现完全可能，而且胫骨残端保留，说明是新伤。
在中盈安心服务的帮助下，跟保险公司理赔部门多次沟通协调，两个月后我终于拿到了全额的赔款！',
            ]
        ];
        return !empty($aModule[$iType]) ? $aModule[$iType] : [];
    }
}