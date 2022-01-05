<?php
/**
 * Created by PhpStorm.
 * User: kgd
 * Date: 2021/9/25
 * Time: 11:19
 */

namespace app\services;

use app\common\model\UserDouyin;
use app\common\model\User as Users;
use think\validate\ValidateRule;


class DouYinServices
{


    /**
     * 配置
     * Created by PhpStorm.
     * @return string[]
     */
    static function getConfig()
    {
        $config = [
            'client_secret' => env('douyin.client_secret'),
            'client_key'    => env('douyin.client_key'),
            'grant_type'    => 'authorization_code',
            'api_url'       => 'https://open.douyin.com/',

        ];
        return $config;

    }


    /**
     * 获取access_token
     * Created by PhpStorm.
     * @param $param_data
     * @param $user_id
     * @return mixed
     */
    static function accessToken($param_data, $user_id)
    {

        $r['code']   = 422;
        $r['msg']    = '请求失败';
        $r['result'] = [];

        $config = self::getConfig();

        $client_secret = $config['client_secret'];
        $client_key    = $config['client_key'];
        $grant_type    = $config['grant_type'];
        $code          = $param_data['code'] ?? '';

        $data = array(
            'client_secret' => $client_secret,
            'code'          => $code,
            'grant_type'    => $grant_type,
            'client_key'    => $client_key,
        );

        $api_url = $config['api_url'];
        $url     = $api_url . 'oauth/access_token/';

        $res = _curl($url, $data, false, 'POST');

        $res_array = json_decode($res, true);

        $res_data = $res_array['data'];

        if ($res_data['error_code'] == 0) {

            $douyin_info = UserDouyin::where('user_id', $user_id)->find();

            $time = time();

            if ($douyin_info) {

                if ($res_data['open_id'] != $douyin_info['open_id']) {
                    $r['msg'] = '与原来绑定的抖音号不一致';
                    return $r;
                }

                $user_info = $douyin_info['user_info'];
                $nickname  = $douyin_info['nickname'];
                if (empty($douyin_info['nickname'])) {
                    $info_data = array(
                        'open_id'      => $douyin_info['open_id'],
                        'access_token' => $douyin_info['access_token'],
                    );
                    $res2      = DouYinServices::userInfo($info_data, $user_id);

                    if ($res2['code'] == 200) {
                        $user_info = json_encode($res2['result']);
                        $nickname  = $res2['result']['data']['nickname'];
                    }
                }
                $update_data = array(
                    'user_id'           => $user_id,
                    'code'              => $code,
                    'open_id'           => $res_data['open_id'],
                    'access_token'      => $res_data['access_token'],
                    'refresh_token'     => $res_data['refresh_token'],
                    'access_token_info' => json_encode($res_array),
                    'user_info'         => $user_info,
                    'nickname'          => $nickname,
                    'expires_in_time'   => $time + $res_data['expires_in'],
                );
                $res         = UserDouyin::where('user_id', $user_id)->update($update_data);
            } else {


                $info_data = array(
                    'open_id'      => $res_data['open_id'],
                    'access_token' => $res_data['access_token'],
                );
                $res2      = DouYinServices::userInfo($info_data, $user_id);

                $user_info = '';
                $nickname  = '';
                if ($res2['code'] == 200) {
                    $user_info = json_encode($res2['result']);
                    $nickname  = $res2['result']['data']['nickname'];
                }

                $update_data = array(
                    'user_id'           => $user_id,
                    'code'              => $code,
                    'open_id'           => $res_data['open_id'],
                    'access_token'      => $res_data['access_token'],
                    'refresh_token'     => $res_data['refresh_token'],
                    'access_token_info' => json_encode($res_array),
                    'user_info'         => $user_info,
                    'nickname'          => $nickname,
                    'create_time'       => $time,
                    'update_time'       => $time,
                    'expires_in_time'   => $time + $res_data['expires_in'],
                );
                $res         = UserDouyin::where('user_id', $user_id)->insert($update_data);

            }


            $req_data2 = array(
                'open_id'      => $res_data['open_id'],
                'access_token' => $res_data['access_token'],
            );
            $res_data  = DouYinServices::fansList($req_data2, $user_id);

            if ($res_data['code'] == 200) {

                $total = $res_data['result']['total'] ?? 0;

                if ($total > 0) {
                    Users::where('id', $user_id)->update(['followers_count' => $total]);
                }

            }

            if ($res) {

                $r['code'] = 200;
                $r['msg']  = '请求成功';
            } else {
                $r['msg'] = '网络繁忙，请稍后再试';

            }

        } else {

            $r['code'] = 422;
            $r['msg']  = $res_data['description'] . ',错误码' . $res_data['error_code'];

        }

        return $r;
    }

    /**
     * 获取用户信息
     * Created by PhpStorm.
     * @param $param_data
     * @param $user_id
     * @return mixed
     */
    static function userInfo($param_data, $user_id)
    {

        $config = self::getConfig();

        $open_id      = $param_data['open_id'] ?? '';
        $access_token = $param_data['access_token'] ?? '';

        $data = array(
            'open_id'      => $open_id,
            'access_token' => $access_token,
        );


        $api_url = $config['api_url'];
        $url     = $api_url . 'oauth/userinfo/';

        $res = _curl($url, $data, false, 'GET');

        $res_array = json_decode($res, true);


        if ($res_array['data']['error_code'] == 0) {
            $r['code']   = 200;
            $r['msg']    = '请求成功';
            $r['result'] = $res_array;
        } else {
            $r['code']   = 422;
            $r['msg']    = '请求失败';
            $r['result'] = $res_array;
        }


        return $r;

    }


    /**
     * 获取用户粉丝数据
     * Created by PhpStorm.
     * @param $param_data
     * @param $user_id
     * @return mixed
     */
    static function fansData($param_data, $user_id)
    {

        $config = self::getConfig();

        $open_id      = $param_data['open_id'] ?? '';
        $access_token = $param_data['access_token'] ?? '';

        $data = array(
            'open_id'      => $open_id,
            'access_token' => $access_token,
        );

        $api_url = $config['api_url'];
        $url     = $api_url . 'fans/data/';

        $url       = $url . '?open_id=' . $open_id . '&access_token=' . $access_token;
        $res_array = geturl($url);

        if ($res_array['data']['error_code'] == 0) {
            $r['code']   = 200;
            $r['msg']    = '请求成功';
            $r['result'] = $res_array;
        } else {
            $r['code']   = 422;
            $r['msg']    = '请求失败';
            $r['result'] = $res_array;
        }


        return $r;

    }


    /**
     * 更新用户粉丝数据
     * Created by PhpStorm.
     * @param $param_data
     * @param $user_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    static function updateFansData($param_data, $user_id)
    {

        $douyin_info = UserDouyin::where('user_id', $user_id)->find();

        $config = self::getConfig();

        $open_id      = $douyin_info['open_id'];
        $access_token = $douyin_info['access_token'];


        $api_url = $config['api_url'];
        $url     = $api_url . 'fans/data/';

        $req_data = array(
            'open_id'      => $open_id,
            'access_token' => $access_token,
        );
        $url      = $url . '?' . http_build_query($req_data);
        $res      = _curl($url, null, false, 'GET');

        $res_array = json_decode($res, true);

        $res_data = $res_array['data'];


        if ($res_data['error_code'] == 0) {

            if (isset($res_data['fans_data']['all_fans_num'])) {
                $all_fans_num = $res_data['fans_data']['all_fans_num'];

                $label_info = '';
                if (isset($res_data['fans_data']['interest_distributions'])) {

                    foreach ($res_data['fans_data']['interest_distributions'] as $k2 => $v2) {

                        if ($k2 < 3) {
                            $label_info .= $v2 . ',';
                        }
                    }
                }


                //更新粉丝数、粉丝特征
                if ($label_info) {
                    $update = array(
                        'followers_count' => $all_fans_num,
                        'label_info'      => $label_info,
                    );
                } else {
                    $update = array(
                        'followers_count' => $all_fans_num,
                    );
                }

                Users::where('id', $user_id)->update($update);
            }


            $update_douyin = array(
                'fans_data' => json_encode($res_array)
            );
            UserDouyin::where('user_id', $user_id)->update($update_douyin);

            $r['code']   = 200;
            $r['msg']    = '请求成功';
            $r['result'] = $res_array;
        } else {
            $r['code']   = 422;
            $r['msg']    = '请求失败';
            $r['result'] = $res_array;
        }


        return $r;

    }


    /**
     * 上传视频
     * Created by PhpStorm.
     * @param $param_data
     * @param $user_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    static function videoUpload($param_data, $user_id)
    {

        $r['code']   = 422;
        $r['msg']    = '请求失败';
        $r['result'] = [];

        $dy_info = UserDouyin::where('user_id', $user_id)->field('open_id,access_token')->find();

        if (empty($dy_info)) {
            $r['code'] = 422;
            $r['msg']  = '用户还没有抖音授权，请先授权';
            return $r;
        }
        $config = self::getConfig();

        $open_id      = $dy_info['open_id'];
        $access_token = $dy_info['access_token'];


        $video = !empty($param_data['video']) ? $param_data['video'] : $_FILES;


        $api_url = $config['api_url'];
        $url     = $api_url . 'video/upload/';

        $url = $url . '?open_id=' . $open_id . '&access_token=' . $access_token;

        $res_array = curl_upload_file($url, $video, $video['video']['name']);

        if ($res_array['data']['error_code'] == 0) {
            $r['code']   = 200;
            $r['msg']    = '请求成功';
            $r['result'] = $res_array;
        } else {
            $r['code']   = 422;
            $r['msg']    = '上传视频失败_' . $res_array['data']['description'] . '(' . $res_array['data']['error_code'] . ')';
            $r['result'] = $res_array;
        }


        return $r;

    }


    /**
     * 创建视频
     * Created by PhpStorm.
     * @param $param_data
     * @param $user_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    static function videoCreate($param_data, $user_id)
    {

        $r['code']   = 422;
        $r['msg']    = '请求失败';
        $r['result'] = [];


        $video_id = $param_data['video_id'] ?? '';
        $text     = $param_data['text'] ?? '';
        $poi_id   = $param_data['poi_id'] ?? '';

        $dy_info = UserDouyin::where('user_id', $user_id)->field('open_id,access_token')->find();

        if (empty($dy_info)) {
            $r['code'] = 422;
            $r['msg']  = '用户还没有抖音授权，请先授权';
            return $r;
        }
        $config = self::getConfig();

        $open_id      = $dy_info['open_id'];
        $access_token = $dy_info['access_token'];


        $api_url = $config['api_url'];
        $url     = $api_url . 'video/create/';

        $url = $url . '?open_id=' . $open_id . '&access_token=' . $access_token;

        $data = array(
            'video_id' => $video_id,
            'text'     => $text,
            'poi_id'   => $poi_id,
        );

        $res = _curl($url, $data, true, 'POST');

        $res_array = json_decode($res, true);

        if ($res_array['data']['error_code'] == 0) {
            $r['code']   = 200;
            $r['msg']    = '视频创建成功';
            $r['result'] = $res_array;
        } else {
            $r['code']   = 422;
            $r['msg']    = '视频创建失败_' . $res_array['data']['description'] . '(' . $res_array['data']['error_code'] . ')';
            $r['result'] = $res_array;
        }


        return $r;

    }

    /**
     * 查询指定视频数据
     * Created by PhpStorm.
     * @param $param_data
     * @param $user_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    static function videoData($param_data, $user_id)
    {

        $r['code']   = 422;
        $r['msg']    = '请求失败';
        $r['result'] = [];

        $item_ids       = $param_data['item_ids'] ?? '';
        $item_ids_array = explode(",", $item_ids);
        $dy_info        = UserDouyin::where('user_id', $user_id)->field('open_id,access_token')->find();

        if (empty($dy_info)) {
            $r['code'] = 422;
            $r['msg']  = '用户还没有抖音授权，请先授权';
            return $r;
        }
        $config = self::getConfig();

        $open_id      = $dy_info['open_id'];
        $access_token = $dy_info['access_token'];

        $api_url = $config['api_url'];
        $url     = $api_url . 'video/data/';

        $url = $url . '?open_id=' . $open_id . '&access_token=' . $access_token;

        $data = array(
            'item_ids' => $item_ids_array,
        );

        $res = _curl($url, $data, true, 'POST');

        $res_array = json_decode($res, true);

        if ($res_array['data']['error_code'] == 0) {
            $r['code']   = 200;
            $r['msg']    = '请求成功';
            $r['result'] = $res_array;
        } else {
            $r['code']   = 422;
            $r['msg']    = '请求失败';
            $r['result'] = $res_array;
        }


        return $r;

    }


    /**
     * 更新用户粉丝数据
     * Created by PhpStorm.
     * @param $open_id
     * @param $access_token
     * @param $user_id
     * @return mixed
     */
    static function updateFansData2($open_id, $access_token, $user_id)
    {

        $config = self::getConfig();

        $api_url = $config['api_url'];
        $url     = $api_url . 'fans/data/';

        $req_data = array(
            'open_id'      => $open_id,
            'access_token' => $access_token,
        );
        $url      = $url . '?' . http_build_query($req_data);
        $res      = _curl($url, null, false, 'GET');

        $res_array = json_decode($res, true);

        $res_data = $res_array['data'];

        if ($res_data['error_code'] == 0) {

            /*            if (isset($res_data['fans_data']['all_fans_num'])) {
                            $all_fans_num = $res_data['fans_data']['all_fans_num'];
                        }*/

            $label_info = '';
            if (isset($res_data['fans_data']['interest_distributions'])) {

                foreach ($res_data['fans_data']['interest_distributions'] as $k2 => $v2) {

                    if ($k2 < 3) {
                        $label_info .= $v2['item'] . ',';
                    }
                }
            }


            if ($label_info) {
                $update = array(
                    'label_info' => $label_info,
                );
                Users::where('id', $user_id)->update($update);
            }

            $update_douyin = array(
                'fans_data'   => json_encode($res_array),
                'update_time' => time(),
            );
            UserDouyin::where('user_id', $user_id)->update($update_douyin);

            $r['code']   = 200;
            $r['msg']    = '请求成功';
            $r['result'] = $res_array;
        } else {
            $r['code']   = 422;
            $r['msg']    = '请求失败';
            $r['result'] = $res_array;
        }


        return $r;

    }


    /**
     * 上传图片
     * Created by PhpStorm.
     * @param $param_data
     * @param $user_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    static function imageUpload($param_data, $user_id)
    {

        $r['code']   = 422;
        $r['msg']    = '请求失败';
        $r['result'] = [];

        $dy_info = UserDouyin::where('user_id', $user_id)->field('open_id,access_token')->find();

        if (empty($dy_info)) {
            $r['code'] = 422;
            $r['msg']  = '用户还没有抖音授权，请先授权';
            return $r;
        }

        $open_id      = $dy_info['open_id'];
        $access_token = $dy_info['access_token'];

        $image = !empty($param_data['video']) ? $param_data['video'] : $_FILES;

        $res_dy = DouYinServices::imageUploadApi($open_id, $access_token, $image);

        if ($res_dy['code'] != 200) {
            $r['msg'] = $res_dy['msg'];
            return $r;
        }

        $r['code']   = 200;
        $r['msg']    = '上传成功';
        $r['result'] = $res_dy['result'];

        return $r;

    }


    /**
     * 上传图片API
     * Created by PhpStorm.
     * @param $open_id
     * @param $access_token
     * @param $image
     * @return mixed
     */
    static function imageUploadApi($open_id, $access_token, $image)
    {

        $r['code']   = 422;
        $r['msg']    = '请求失败';
        $r['result'] = [];

        $config = self::getConfig();


        $api_url = $config['api_url'];
        $url     = $api_url . 'image/upload/';

        $url = $url . '?open_id=' . $open_id . '&access_token=' . $access_token;

        $res_array = curl_upload_image_file($url, $image, $image['video']['name']);

        if ($res_array['data']['error_code'] == 0) {
            $r['code']   = 200;
            $r['msg']    = '请求成功';
            $r['result'] = $res_array;
        } else {
            $r['code']   = 422;
            $r['msg']    = '上传视频失败';
            $r['result'] = $res_array;
        }


        return $r;

    }


    /**
     * 发布图片
     * Created by PhpStorm.
     * @param $param_data
     * @param $user_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    static function imageCreate($param_data, $user_id)
    {

        $r['code']   = 422;
        $r['msg']    = '请求失败';
        $r['result'] = [];

        $dy_info = UserDouyin::where('user_id', $user_id)->field('open_id,access_token')->find();

        if (empty($dy_info)) {
            $r['code'] = 422;
            $r['msg']  = '用户还没有抖音授权，请先授权';
            return $r;
        }

        $open_id      = $dy_info['open_id'];
        $access_token = $dy_info['access_token'];

        $image_id = $param_data['video_id'];
        $text     = $param_data['text'];

        $res_dy = DouYinServices::imageCreateApi($open_id, $access_token, $image_id, $text);

        if ($res_dy['code'] != 200) {
            $r['msg']    = $res_dy['msg'];
            $r['result'] = $res_dy['result'];
            return $r;
        }

        $r['code']   = 200;
        $r['msg']    = '上传成功';
        $r['result'] = $res_dy['result'];

        return $r;

    }


    /**
     * 发布图片API
     * Created by PhpStorm.
     * @param $open_id
     * @param $access_token
     * @param $image_id
     * @param $text
     * @return mixed
     */
    static function imageCreateApi($open_id, $access_token, $image_id, $text)
    {

        $r['code']   = 422;
        $r['msg']    = '请求失败';
        $r['result'] = [];

        $config = self::getConfig();

        $api_url = $config['api_url'];
        $url     = $api_url . 'image/create/';

        $url = $url . '?open_id=' . $open_id . '&access_token=' . $access_token;

        $data = array(
            'image_id' => $image_id,
            'text'     => $text,
        );

        $res = _curl($url, $data, true, 'POST');

        $res_array = json_decode($res, true);

        if ($res_array['data']['error_code'] == 0) {
            $r['code']   = 200;
            $r['msg']    = '请求成功';
            $r['result'] = $res_array;
        } else {
            $r['code']   = 422;
            $r['msg']    = '发布图片失败';
            $r['result'] = $res_array;
        }


        return $r;

    }


    /**
     * 抖音token有效期
     * Created by PhpStorm.
     * @param $param_data
     * @param $user_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    static function expiresIn($param_data, $user_id)
    {

        $r['code']   = 422;
        $r['msg']    = '请求失败';
        $r['result'] = [];

        $dy_info = UserDouyin::where('user_id', $user_id)->find();

        if (empty($dy_info)) {
            $r['code'] = 450;
            $r['msg']  = '您还没有绑定抖音帐号';
            return $r;
        }

        //提前一天通知过期
        $is_expire = false;
        if ($dy_info['expires_in_time'] - 86400 < time()) {
            $is_expire = true;
        }
        $res_array   = array(
            'nickname'        => $dy_info['nickname'] ?? '',
            'expires_in_time' => $dy_info['expires_in_time'],
            'is_expire'       => $is_expire,
        );
        $r['code']   = 200;
        $r['msg']    = '请求成功';
        $r['result'] = $res_array;

        return $r;


    }

    /**
     * 查询POI信息
     * Created by PhpStorm.
     * @param $param_data
     * @return mixed
     */
    static function poiSearch($param_data)
    {
        $r['code']   = 422;
        $r['msg']    = '请求失败';
        $r['result'] = [];

        $config = self::getConfig();

        $api_url = $config['api_url'];
        $url     = $api_url . '/oauth/client_token/';

        $data = array(
            'client_key'    => $config['client_key'],
            'client_secret' => $config['client_secret'],
            'grant_type'    => 'client_credential',
        );

        $res = _curl($url, $data, false, 'POST');

        $res_array = json_decode($res, true);

        if ($res_array['data']['error_code'] == 0) {

            $keyword = $param_data['keyword'] ?? '';
            $city    = $param_data['city'] ?? '';
            $count   = 10;

            $req_data = array(
                'access_token' => $res_array['data']['access_token'],
                'count'        => $count,
                'keyword'      => $keyword,
                'city'         => $city,
            );

            $url = $api_url . 'poi/search/keyword/?';

            $url = $url . http_build_query($req_data);

            $res_array2 = geturl($url);

            $r['code']   = 200;
            $r['msg']    = '请求成功';
            $r['result'] = $res_array2;

        } else {
            $r['code']   = 422;
            $r['msg']    = '请求失败';
            $r['result'] = $res_array;
        }


        return $r;

    }


    /**
     * 分片初始化上传
     * Created by PhpStorm.
     * @param $param_data
     * @param $user_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    static function videoPartInit($param_data, $user_id)
    {

        $r['code']   = 422;
        $r['msg']    = '请求失败';
        $r['result'] = [];

        $user_douyin = UserDouyin::where('user_id', $user_id)->find();

        if (empty($user_douyin)) {

            $r['msg'] = '您还没有绑定抖音帐号';
            return $r;
        }

        $data = array(
            'open_id'      => $user_douyin['open_id'],
            'access_token' => $user_douyin['access_token'],
        );
        $res  = DouYinServices::videoPartInitApi($data);

        return $res;


    }

    /**
     * 分片初始化上传Api
     * Created by PhpStorm.
     * @param $param_data
     * @return mixed
     */
    static function videoPartInitApi($param_data)
    {

        $r['code']   = 422;
        $r['msg']    = '请求失败';
        $r['result'] = [];

        $config = self::getConfig();

        $api_url = $config['api_url'];
        $url     = $api_url . 'video/part/init/?';

        $data = array(
            'open_id'      => $param_data['open_id'],
            'access_token' => $param_data['access_token'],
        );

        $url = $url . http_build_query($data);

        $res = _curl($url, null, false, 'POST');

        $res_array = json_decode($res, true);

        if ($res_array['data']['error_code'] == 0) {

            $res_data = $res_array['data'];

            $result              = array();
            $result['upload_id'] = $res_data['upload_id'];

            $r['code']   = 200;
            $r['msg']    = '初始化上传成功';
            $r['result'] = $result;

        } else {
            $r['code']   = 422;
            $r['msg']    = '初始化上传失败_' . $res_array['data']['error_code'] . '_' . $res_array['data']['description'];
            $r['result'] = $res_array;
        }


        return $r;
    }

    /**
     * 分片上传视频
     * Created by PhpStorm.
     * @param $param_data
     * @param $user_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    static function videoPartUpload($param_data, $user_id)
    {

        $r['code']   = 422;
        $r['msg']    = '请求失败';
        $r['result'] = [];

        $user_douyin = UserDouyin::where('user_id', $user_id)->find();

        if (empty($user_douyin)) {

            $r['msg'] = '您还没有绑定抖音帐号';
            return $r;
        }

        $data = array(
            'open_id'      => $user_douyin['open_id'],
            'access_token' => $user_douyin['access_token'],
            'upload_id'    => $param_data['upload_id'],
            'part_number'  => $param_data['part_number'],
        );

        $video = !empty($param_data['video']) ? $param_data['video'] : $_FILES;

        $res = DouYinServices::videoPartUploadApi($data, $video);

        return $res;


    }


    /**
     * 分片上传视频Api
     * Created by PhpStorm.
     * @param $param_data
     * @param $video
     * @return mixed
     */
    static function videoPartUploadApi($param_data, $video)
    {

        $r['code']   = 422;
        $r['msg']    = '请求失败';
        $r['result'] = [];

        $config = self::getConfig();

        $api_url = $config['api_url'];
        $url     = $api_url . 'video/part/upload/?';

        $data = array(
            'open_id'      => $param_data['open_id'],
            'access_token' => $param_data['access_token'],
            'upload_id'    => $param_data['upload_id'],
            'part_number'  => $param_data['part_number'],
        );

        $url = $url . http_build_query($data);

        $res_array = curl_upload_file($url, $video, $video['video']['name']);

        if ($res_array['data']['error_code'] == 0) {
            $r['code']   = 200;
            $r['msg']    = '分片上传视频成功';
            $r['result'] = $res_array;
        } else {
            $r['code']   = 422;
            $r['msg']    = '分片上传视频失败_' . $res_array['data']['error_code'] . '_' . $res_array['data']['description'];
            $r['result'] = $res_array;
        }


        return $r;
    }

    /**
     * 分片上传完成
     * Created by PhpStorm.
     * @param $param_data
     * @param $user_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    static function videoPartComplete($param_data, $user_id)
    {

        $r['code']   = 422;
        $r['msg']    = '请求失败';
        $r['result'] = [];

        $user_douyin = UserDouyin::where('user_id', $user_id)->find();

        if (empty($user_douyin)) {

            $r['msg'] = '您还没有绑定抖音帐号';
            return $r;
        }

        $data = array(
            'open_id'      => $user_douyin['open_id'],
            'access_token' => $user_douyin['access_token'],
            'upload_id'    => $param_data['upload_id'],
        );


        $res = DouYinServices::videoPartCompleteApi($data);

        return $res;


    }


    /**
     * 分片上传完成Api
     * Created by PhpStorm.
     * @param $param_data
     * @param $video
     * @return mixed
     */
    static function videoPartCompleteApi($param_data)
    {

        $r['code']   = 422;
        $r['msg']    = '请求失败';
        $r['result'] = [];

        $config = self::getConfig();

        $api_url = $config['api_url'];
        $url     = $api_url . 'video/part/complete/?';

        $data = array(
            'open_id'      => $param_data['open_id'],
            'access_token' => $param_data['access_token'],
            'upload_id'    => $param_data['upload_id'],
        );

        $url = $url . http_build_query($data);

        $res = _curl($url, null, false, 'POST');

        $res_array = json_decode($res, true);

        if ($res_array['data']['error_code'] == 0) {

            $res_data = $res_array['data'];

            $result = $res_data['video'];

            $r['code']   = 200;
            $r['msg']    = '分片上传完成成功';
            $r['result'] = $result;
        } else {
            $r['code']   = 422;
            $r['msg']    = '分片上传完成失败_' . $res_array['data']['error_code'] . '_' . $res_array['data']['description'];
            $r['result'] = $res_array;
        }


        return $r;
    }

    /**
     *  获取粉丝列表
     * Created by PhpStorm.
     * @param $param_data
     * @param $user_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    static function fansList($param_data, $user_id)
    {

        $r['code']   = 422;
        $r['msg']    = '请求失败';
        $r['result'] = [];

        $user_douyin = UserDouyin::where('user_id', $user_id)->find();

        if (empty($user_douyin)) {

            $r['msg'] = '您还没有绑定抖音帐号';
            return $r;
        }

        $data = array(
            'open_id'      => $user_douyin['open_id'],
            'access_token' => $user_douyin['access_token'],
            'cursor'       => $param_data['cursor'] ?? 0,
            'count'        => $param_data['count'] ?? 10,
        );

        $res = DouYinServices::fansListApi($data);

        return $res;


    }


    /**
     *获取粉丝列表Api
     * Created by PhpStorm.
     * @param $param_data
     * @return mixed
     */
    static function fansListApi($param_data)
    {

        $r['code']   = 422;
        $r['msg']    = '请求失败';
        $r['result'] = [];

        $config = self::getConfig();

        $api_url = $config['api_url'];
        $url     = $api_url . 'fans/list/?';

        $data = array(
            'open_id'      => $param_data['open_id'],
            'access_token' => $param_data['access_token'],
            'cursor'       => $param_data['cursor'],
            'count'        => $param_data['count'],
        );

        $url = $url . http_build_query($data);

        $res = _curl($url, null, false, 'POST');

        $res_array = json_decode($res, true);

        if ($res_array['data']['error_code'] == 0) {

            $res_data = $res_array['data'];

            $r['code']   = 200;
            $r['msg']    = '获取粉丝列表成功';
            $r['result'] = $res_data;

        } else {
            $r['code']   = 422;
            $r['msg']    = '获取粉丝列表失败_' . $res_array['data']['error_code'] . '_' . $res_array['data']['description'];
            $r['result'] = $res_array;
        }


        return $r;
    }

}