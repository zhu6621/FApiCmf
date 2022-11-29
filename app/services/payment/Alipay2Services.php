<?php

namespace app\services\payment;

use app\api\model\sea\SeaConfig;
use app\api\model\user\Config;
use app\ApiBaseController;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Log;
use think\cache\driver\Redis;

/**
 * 支付宝支付
 * Class Alipay2Services
 * @package app\services\payment
 * User: xiao zhu
 * Date: 2021/8/5
 * Time: 21:24
 */
class Alipay2Services extends ApiBaseController
{


    /**
     * 配置
     * Created by PhpStorm.
     * @return array
     * User: xiao zhu
     * Date: 2021/8/5
     * Time: 21:24
     */
    public static function getConfig()
    {

        $redis =new Redis();
        $pay_config=$redis->get('pay_config');

        if(empty($pay_config)){
            $key_array    = ['alipay_appid', 'alipay_public_key', 'alipay_private_key', 'app_id_sea', 'mch_id', 'api_key'];
            $pay_config = Config::whereIn('key', $key_array)->column('value', 'key');
            $redis->set('pay_config',$pay_config,300);
        }

        $sea_url=$redis->get('sea_url_pay');

        if(empty($sea_url)){
            $sea_url=SeaConfig::where('key','sea_url')->value('value');
            $redis->set('sea_url_pay',$sea_url,300);
        }


        $config = [
            'app_id'         => $pay_config['alipay_appid'],
            'notify_url'     => $sea_url.'/api/alipay/notify',
            'return_url'     => $sea_url,
            'ali_public_key' =>  $pay_config['alipay_public_key'],
            'private_key'    =>  $pay_config['alipay_private_key'],
            'log'            => [
                'file' => WEB_PATH . '../runtime/log/alipay.log',
            ],

        ];

        return $config;
    }

    /**
     * 支付
     * Created by PhpStorm.
     * @return false|string
     * User: xiao zhu
     * Date: 2021/8/5
     * Time: 21:27
     */
    public static function pay($order)
    {

        $config = self::getConfig();

        $alipay = Pay::alipay($config)->app($order);

        return $alipay->getContent();// laravel 框架中请直接 `return $alipay`

    }


}