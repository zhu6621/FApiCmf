<?php

namespace app\services\payment;

use alipay\AopClient;
use alipay\request\AlipayTradeQueryRequest;
use app\ApiBaseController;
use alipay\request\AlipayFundTransUniTransferRequest;
use alipay\AopCertClient;

/**
 * 支付宝支付
 * Class AlipayServices
 * @package app\services\payment
 * User: xiao zhu
 * Date: 2021/6/23
 * Time: 14:34
 */
class AlipayServices extends ApiBaseController
{

    /**
     * 配置
     * Created by PhpStorm.
     * @return string[]
     * User: xiao zhu
     * Date: 2021/6/23
     * Time: 11:45
     */
    public static function getConfig()
    {

        $config = [
            'app_id'           => '2021002151678585',
            //应用证书路径（要确保证书文件可读）
            'app_cert_path'    => WEB_PATH . "appCertPublicKey.crt",
            //支付宝公钥证书路径（要确保证书文件可读）
            'alipay_cert_path' => WEB_PATH . "alipayCertPublicKey.crt",
            //支付宝根证书路径（要确保证书文件可读）
            'root_cert_path'   => WEB_PATH . "alipayRootCert.crt",
            //你的应用私钥
            'rsa_private_key'  => '',
        ];

        return $config;
    }


    /**
     *
     * Created by PhpStorm.
     * @return string[]
     * User: xiao zhu
     * Date: 2021/8/16
     * Time: 15:43
     */
    public static function getConfig2()
    {

        $config = [
            'app_id'           => '2021003131659269',
            //应用证书路径（要确保证书文件可读）
            'app_cert_path'    => WEB_PATH . "alipay02/appCertPublicKey.crt",
            //支付宝公钥证书路径（要确保证书文件可读）
            'alipay_cert_path' => WEB_PATH . "alipay02/alipayCertPublicKey.crt",
            //支付宝根证书路径（要确保证书文件可读）
            'root_cert_path'   => WEB_PATH . "alipay02/alipayRootCert.crt",
            //你的应用私钥
            'rsa_private_key'  => '',
        ];

        return $config;
    }

    /**
     * 支付宝密钥
     * Created by PhpStorm.
     * @return string[]
     * User: 小朱
     * Date: 2022/11/10
     * Time: 15:59
     */
    public static function getConfig3()
    {

        $config = [
            'gatewayUrl'         => 'https://openapi.alipay.com/gateway.do',
            'appId'              => '2021003127686658',
            'rsaPrivateKey'      => '',
            'alipayrsaPublicKey' => '',

        ];

        return $config;
    }


    /**
     * 转账到支付宝账户
     * Created by PhpStorm.
     * @param $order
     * User: xiao zhu
     * Date: 2021/6/23
     * Time: 11:44
     */
    public static function transfer($info)
    {

        $config = self::getConfig();

        $aop = new AopCertClient();

        $appCertPath        = $config['app_cert_path'];
        $alipayCertPath     = $config['alipay_cert_path'];
        $rootCertPath       = $config['root_cert_path'];
        $aop->gatewayUrl    = "https://openapi.alipay.com/gateway.do";
        $aop->appId         = $config['app_id'];
        $aop->rsaPrivateKey = $config['rsa_private_key'];
        $aop->format        = "json";
        $aop->charset       = "UTF-8";
        $aop->signType      = "RSA2";
        //调用getPublicKey从支付宝公钥证书中提取公钥
        $aop->alipayrsaPublicKey = $aop->getPublicKey($alipayCertPath);
        //是否校验自动下载的支付宝公钥证书，如果开启校验要保证支付宝根证书在有效期内
        $aop->isCheckAlipayPublicCert = true;
        //调用getCertSN获取证书序列号
        $aop->appCertSN = $aop->getCertSN($appCertPath);
        //调用getRootCertSN获取支付宝根证书序列号
        $aop->alipayRootCertSN = $aop->getRootCertSN($rootCertPath);
        $request               = new AlipayFundTransUniTransferRequest ();

        $payee_info = array(
            'identity'      => $info['phone'],
            'identity_type' => 'ALIPAY_LOGON_ID',
            'name'          => $info['name'],
        );

        $out_biz_no = 'sea' . date('YmdHis', time()) . rand(100000, 999999);
        $order      = [
            'out_biz_no'   => $out_biz_no,
            'trans_amount' => fix_number_precision($info['trans_amount']),//$info['trans_amount']
            'biz_scene'    => 'DIRECT_TRANSFER',
            'product_code' => 'TRANS_ACCOUNT_NO_PWD',
            'payee_info'   => json_encode($payee_info)
        ];

        $request->setBizContent(json_encode($order));
        $result = $aop->execute($request);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";

        $resultCode = $result->$responseNode->code;
        if (!empty($resultCode) && $resultCode == 10000) {
            $data = array(
                'out_biz_no'        => $out_biz_no,
                'order_id'          => $result->$responseNode->order_id,
                'pay_fund_order_id' => $result->$responseNode->pay_fund_order_id,
                'trans_date'        => $result->$responseNode->trans_date,
            );
            $data = array(
                'code' => 200,
                'msg'  => '提现成功',
                'data' => $data
            );
        } else {

            $error_info = array(
                'code'     => $result->$responseNode->code,
                'msg'      => $result->$responseNode->msg,
                'sub_code' => $result->$responseNode->sub_code,
                'sub_msg'  => $result->$responseNode->sub_msg,
            );

            $data = array(
                'out_biz_no' => $out_biz_no,
                'info'       => $error_info
            );
            $data = array(
                'code' => 422,
                'msg'  => $result->$responseNode->sub_msg,
                'data' => $data
            );
        }


        return $data;
    }

    /**
     * 转账到支付宝账户(定时任务)
     * Created by PhpStorm.
     * @param $order
     * User: xiao zhu
     * Date: 2021/6/23
     * Time: 11:44
     */
    public static function transfer2($info)
    {

        $config = self::getConfig2();
        $aop    = new AopCertClient();

        $appCertPath        = $config['app_cert_path'];
        $alipayCertPath     = $config['alipay_cert_path'];
        $rootCertPath       = $config['root_cert_path'];
        $aop->gatewayUrl    = "https://openapi.alipay.com/gateway.do";
        $aop->appId         = $config['app_id'];
        $aop->rsaPrivateKey = $config['rsa_private_key'];
        $aop->format        = "json";
        $aop->charset       = "UTF-8";
        $aop->signType      = "RSA2";
        //调用getPublicKey从支付宝公钥证书中提取公钥
        $aop->alipayrsaPublicKey = $aop->getPublicKey($alipayCertPath);
        //是否校验自动下载的支付宝公钥证书，如果开启校验要保证支付宝根证书在有效期内
        $aop->isCheckAlipayPublicCert = true;
        //调用getCertSN获取证书序列号
        $aop->appCertSN = $aop->getCertSN($appCertPath);
        //调用getRootCertSN获取支付宝根证书序列号
        $aop->alipayRootCertSN = $aop->getRootCertSN($rootCertPath);
        $request               = new AlipayFundTransUniTransferRequest ();

        if (isMobile($info['phone'])) {
            $account_type = 1;
        } elseif (strpos($info['phone'], '@')) {
            $account_type = 2;
        } else {
            $account_type = 3;
        }

        if ($account_type == 3) {
            //支付宝账号对应的支付宝唯一用户号
            $identity_type = 'ALIPAY_USER_ID';

            $payee_info = array(
                'identity'      => $info['phone'],
                'identity_type' => $identity_type,
            );
        } else {
            //支付宝登录号，支持邮箱和手机号格式
            $identity_type = 'ALIPAY_LOGON_ID';

            $payee_info = array(
                'identity'      => $info['phone'],
                'identity_type' => $identity_type,
                'name'          => $info['name'],
            );
        }

        $out_biz_no = $info['out_biz_no'];
        $order      = [
            'out_biz_no'   => $out_biz_no,
            'trans_amount' => $info['trans_amount'],//fix_number_precision($info['trans_amount'])
            'biz_scene'    => 'DIRECT_TRANSFER',
            'product_code' => 'TRANS_ACCOUNT_NO_PWD',
            'payee_info'   => json_encode($payee_info)
        ];

        $request->setBizContent(json_encode($order));
        $result = $aop->execute($request);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";

        $resultCode = $result->$responseNode->code;
        if (!empty($resultCode) && $resultCode == 10000) {
            $data = array(
                'out_biz_no'        => $out_biz_no,
                'order_id'          => $result->$responseNode->order_id,
                'pay_fund_order_id' => $result->$responseNode->pay_fund_order_id,
                'trans_date'        => $result->$responseNode->trans_date,
            );
            $data = array(
                'code' => 200,
                'msg'  => '提现成功',
                'data' => $data
            );
        } else {

            $error_info = array(
                'code'     => $result->$responseNode->code,
                'msg'      => $result->$responseNode->msg,
                'sub_code' => $result->$responseNode->sub_code,
                'sub_msg'  => $result->$responseNode->sub_msg,
            );

            $data = array(
                'out_biz_no' => $out_biz_no,
                'info'       => $error_info
            );
            $data = array(
                'code' => 422,
                'msg'  => $result->$responseNode->sub_msg,
                'data' => $data
            );
        }


        return $data;
    }


    /**
     * 订单查询
     * Created by PhpStorm.
     * @throws \think\Exception
     * User: 小朱
     * Date: 2022/11/10
     * Time: 16:02
     */
    static function tradeQuery($param_data){

        $r['code']=422;
        $r['msg']='请求失败';
        $r['data']=[];

        $config = self::getConfig3();

        $aop = new AopClient ();
        $aop->gatewayUrl = $config['gatewayUrl'];
        $aop->appId = $config['appId'];
        $aop->rsaPrivateKey = $config['rsaPrivateKey'];
        $aop->alipayrsaPublicKey=$config['alipayrsaPublicKey'];
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';

        $out_trade_no=$param_data['out_trade_no']??'';
        $request = new AlipayTradeQueryRequest();
        $content = array(
            'out_trade_no' =>$out_trade_no,
        );
        $content = json_encode($content);
        $request->setBizContent($content);

        $result = $aop->execute($request);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode   = $result->$responseNode->code;
        if (!empty($resultCode) && $resultCode == 10000) {

            $info=array(
                    'buyer_logon_id'=>$result->$responseNode->buyer_logon_id
                );
            $r['code']=200;
            $r['msg']='请求失败';
            $r['data']=$info;

        }

        return $r;

    }


}