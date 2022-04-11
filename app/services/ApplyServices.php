<?php
/**
 * Created by PhpStorm.
 * User: kgd
 * Date: 2021/9/25
 * Time: 11:19
 */

namespace app\services;

use app\api\model\Config;
use app\api\model\DarenCommissionConfig;
use app\api\model\Expertise;
use app\api\model\Shop;
use app\api\model\ShopMaterial;
use app\api\model\ShopMaterialFolder;
use app\api\model\ShopTask;
use app\api\model\ShopTaskJoin;
use app\api\model\ShopTasks;
use app\api\model\ShopTaskWorks;
use app\api\model\TalentApply;
use app\api\model\TalentApplyExtInfo;
use app\api\model\TalentType;
use app\api\model\UserFollow;
use app\api\model\UserLabel;
use app\api\model\Users;
use app\api\model\VerificationCodes;
use think\facade\Db;

class ApplyServices
{


    /**
     * 达人申请
     * Created by PhpStorm.
     * @param $user_id
     * @param $param_data
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: xiao zhu
     * Date: 2021/12/8
     * Time: 14:13
     */
    static function daRenApply($param_data, $user_id)
    {

        $r['code']   = 422;
        $r['msg']    = '请求失败';
        $r['result'] = [];

        $user_info = Users::where('id', $user_id)->field('followers_count,is_daren')->find();

        if ($user_info['is_daren'] == 1) {
            $r['msg'] = '您已经是达人';
            return $r;
        }

        $apply_info = TalentApply::with('extInfo')->where('user_id', $user_id)->find();

        if ($apply_info && in_array($apply_info['status'], [0, 1])) {
            $r['msg'] = '此帐号已提交申请';
            return $r;
        }

        if (!isset($param_data['idcard_pic1']) || empty($param_data['idcard_pic1'])) {
            $r['msg'] = '请上传身份证正面';
            return $r;
        }

        if (!isset($param_data['idcard_pic1']) || empty($param_data['idcard_pic1'])) {
            $r['msg'] = '请上传身份证背面';
            return $r;
        }


        if (!isset($param_data['name']) || empty($param_data['name'])) {
            $r['msg'] = '请填写姓名';
            return $r;
        }

        if (!isset($param_data['idcard']) || !$param_data['idcard']) {
            $r['msg'] = '请填写身份证';
            return $r;
        }
        if (!isCreditNo($param_data['idcard'])) {
            $r['msg'] = '身份证号码无效';
            return $r;
        }


        if (!isset($param_data['platform_account']) || !$param_data['platform_account']) {
            $r['msg'] = '请填写平台账号';
            return $r;
        }

        if (!isset($param_data['video_commission']) || !$param_data['video_commission']) {
            $r['msg'] = '请填写带货佣金';
            return $r;
        }


        if (!isset($param_data['advantage']) || !$param_data['advantage']) {
            $r['msg'] = '请填写擅长拍摄';
            return $r;
        }

        $talent_type_id = 1;
        $platform_name  = '抖音';
        $from           = 2;

        $daren_config = DarenCommissionConfig::where('start', '<=', $user_info['followers_count'])
            ->where('type', 1)->order('id desc')->find();
        $min_num      = $daren_config['min_num'] ?? 0;
        $max_num      = $daren_config['max_num'] ?? 0;

        if ($param_data['video_commission'] < $min_num || $param_data['video_commission'] > $max_num) {
            $r['msg'] = '带货佣金数量错误，范围为' . $min_num . '-' . $max_num;
            return $r;
        }

        Db::startTrans();
        try {

            $apply_data = array(
                'user_id'        => $user_id,
                'name'           => $param_data['name'],
                'idcard_pic1'    => $param_data['idcard_pic1'],
                'idcard_pic2'    => $param_data['idcard_pic2'],
                'idcard'         => $param_data['idcard'],
                'talent_type_id' => $talent_type_id,
                'from'           => $from,
                'create_time'    => time(),
            );

            $talent_apply_id = TalentApply::insertGetId($apply_data);
            if (!$talent_apply_id) {
                throw new \think\Exception('提交失败');
            }

            $ext_info = array(
                'talent_id'          => $talent_apply_id,
                'operation_platform' => 1,
                'platform_name'      => $platform_name,
                'platform_account'   => $param_data['platform_account'],
                'fans_count'         => $user_info['followers_count'],
                'advantage'          => $param_data['advantage'],
                'video_commission'   => $param_data['video_commission'],
            );

            $extInfo = TalentApplyExtInfo::insert($ext_info);
            if (!$extInfo) {
                throw new \think\Exception('提交失败');
            }

            if ($apply_info) {
                TalentApply::where('id', $apply_info['id'])->delete();
                TalentApplyExtInfo::where('talent_id', $apply_info['id'])->delete();
            }

            $update_data = array(
                'video_commission' => $param_data['video_commission'],
                'expertise'        => $param_data['advantage'],
            );
            $res         = Users::where('id', $user_id)->update($update_data);

            Db::commit();

            $r['code'] = 200;
            $r['msg']  = '提交成功';
            return $r;

        } catch (\Exception $e) {
            Db::rollback();

            $r['msg'] = $e->getMessage();
            return $r;

        }

    }


    /**
     * 擅长拍摄列表
     * Created by PhpStorm.
     * @param $param_data
     * @param $user_id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: xiao zhu
     * Date: 2021/12/8
     * Time: 15:09
     */
    static function expertiseList($param_data)
    {

        $info = Expertise::select();

        $r['code']   = 200;
        $r['msg']    = '请求成功';
        $r['result'] = $info;

        return $r;
    }


    /**
     * 抖音授权（申请达人）
     * Created by PhpStorm.
     * @param $param_data
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: xiao zhu
     * Date: 2021/12/17
     * Time: 18:00
     */
    static function accessToken($param_data)
    {
        $r['code']   = 422;
        $r['msg']    = '请求失败';
        $r['result'] = [];

        $phone = $param_data['phone'] ?? '';
        $id    = $param_data['id'] ?? 0;
        $code  = $param_data['code'] ?? '';

        if (empty($phone)) {
            $r['msg'] = '手机号码错误';
            return $r;
        }

        if ($id == 0) {
            $r['msg'] = '非法请求';
            return $r;
        }

        if (empty($code)) {
            $r['msg'] = 'code不能为空';
            return $r;
        }

        $apply_info = TalentApply::where('id', $id)->find();

        if (empty($apply_info)) {
            $r['msg'] = '您还没有申请达人';
            return $r;
        }

        if ($apply_info['status'] != 0) {
            $r['msg'] = '申请信息错误';
            return $r;
        }

        $user_info = Users::where('phone', $phone)->field('id,phone')->find();

        if (empty($user_info)) {
            $r['msg'] = '您还没有注册帐号';
            return $r;
        }

        if ($apply_info['user_id'] != $user_info['id']) {
            $r['msg'] = '用户信息不对应';
            return $r;
        }

        $user_id = $user_info['id'];

        $req_data = array(
            'code' => $code
        );
        $res_data = DouYinServices::accessToken2($req_data, $user_id);

        if ($res_data['code'] == 200) {

            $req_data2 = array(
                'open_id'      => $res_data['result']['open_id'],
                'access_token' => $res_data['result']['access_token'],
            );
            $res_data  = DouYinServices::fansList($req_data2, $user_id);

            if ($res_data['code'] == 200) {

                $total = $res_data['result']['total'] ?? 0;

                if ($total > 0) {
                    TalentApplyExtInfo::where('talent_id', $apply_info['id'])
                        ->update(['fans_count' => $total]);
                }

            }

            $r['code'] = 200;
            $r['msg']  = '抖音授权成功';
            return $r;

        } else {
            return $res_data;
        }
    }

    /**
     * 身份证图片识别
     * Created by PhpStorm.
     * @param $param_data
     * @return mixed
     * User: xiao zhu
     * Date: 2021/12/27
     * Time: 18:12
     */
    static function ocrIdcard($param_data)
    {
        $r['code']   = 422;
        $r['msg']    = '参数错误';
        $r['result'] = [];
        $img         = $param_data['img'];

        $config_info = Config::whereIn('name', ['wechat_app_id', 'wechat_api_key'])
            ->column('value', 'name');

        if (empty($config_info)) {
            $r['msg'] = '配置错误';
            return $r;
        }
        $wx_appid  = $config_info['wechat_app_id'];
        $wx_secret = $config_info['wechat_api_key'];

        $url_gettoken = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $wx_appid . '&secret=' . $wx_secret;
        $gettoken     = geturl($url_gettoken);

        if (!isset($gettoken['access_token']) || !$gettoken['access_token']) {
            $r['msg'] = '获取access_token';
            return $r;
        }
        $url  = 'https://api.weixin.qq.com/cv/ocr/idcard?img_url=' . $img . '&access_token=' . $gettoken['access_token'];
        $info = json_decode(_curl($url), true);

        if (!isset($info['errcode']) || $info['errcode'] != 0) {
            $r['msg'] = '图片识别失败';
            return $r;
        }

        $r['code']   = 200;
        $r['msg']    = '识别成功';
        $r['result'] = $info;
        return $r;
    }

}