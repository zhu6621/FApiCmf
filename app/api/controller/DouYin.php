<?php
/**
 * Created by PhpStorm.
 * User: kgd
 * Date: 2021/10/27
 * Time: 10:59
 */

namespace app\api\controller;


use fast\Random;
use think\facade\Validate;
use think\facade\Config;
use app\common\library\Ems;
use app\common\library\Sms;
use app\common\controller\Api;
use app\services\DouYinServices;


/**
 * 抖音接口
 * Class DouYin
 * @package app\api\controller
 * User: xiao zhu
 * Date: 2022/1/5
 * Time: 15:26
 */
class DouYin extends Api
{
    protected $noNeedLogin = ['poiSearch'];
    protected $noNeedRight = '*';

    /**
     * 抖音授权
     * Created by PhpStorm.
     * @throws \Exception
     */
    public function accessToken()
    {

        $param_data = $this->request->param();
        $user_id    = $this->auth->id;

        $res_data = DouYinServices::accessToken($param_data, $user_id);

        if ($res_data['code'] == 200) {
            $this->success('授权成功');
        } else {
            $this->error($res_data['msg']);

        }

    }


    /**
     * 获取用户信息
     * Created by PhpStorm.
     * @throws \Exception
     */
    public function userInfo()
    {

        $param_data = $this->request->param();
        $user_id    = $this->auth->id;

        $res_data = DouYinServices::userInfo($param_data, $user_id);

        if ($res_data['code'] == 200) {
            $this->success('请求成功', $res_data['result']);
        } else {
            $this->error($res_data['msg'], $res_data['result']);
        }

    }


    /**
     * 更新用户粉丝数据
     * Created by PhpStorm.
     * @throws \Exception
     */
    public function updateFansData()
    {
        $param_data = $this->request->param();
        $user_id    = $this->auth->id;

        $res_data = DouYinServices::updateFansData($param_data, $user_id);

        if ($res_data['code'] == 200) {
            $this->success('请求成功', $res_data['result']);
        } else {
            $this->error($res_data['msg'], $res_data['result']);
        }

    }

    /**
     * 上传视频
     * Created by PhpStorm.
     * @throws \Exception
     */
    public function videoUpload()
    {

        $param_data = $this->request->param();

        $video = input('post.video');

        $param_data['video'] = $video;

        $user_id  = $this->auth->id;
        $res_data = DouYinServices::videoUpload($param_data, $user_id);

        if ($res_data['code'] == 200) {
            $this->success('上传成功', $res_data['result']);
        } else {
            $this->error($res_data['msg'], $res_data['result']);
        }

    }

    /**
     * 创建视频
     * Created by PhpStorm.
     * @throws \Exception
     */
    public function videoCreate()
    {

        $param_data = $this->request->param();

        $video = input('post.video');

        $param_data['video'] = $video;

        $user_id  = $this->auth->id;
        $res_data = DouYinServices::videoCreate($param_data, $user_id);

        if ($res_data['code'] == 200) {
            $this->success('上传成功', $res_data['result']);
        } else {
            $this->error($res_data['msg'], $res_data['result']);
        }

    }


    /**
     * 查询指定视频数据
     * Created by PhpStorm.
     * @throws \Exception
     */
    public function videoData()
    {

        $param_data = $this->request->param();

        $user_id  = $this->auth->id;
        $res_data = DouYinServices::videoData($param_data, $user_id);

        if ($res_data['code'] == 200) {
            $this->success('上传成功', $res_data['result']);
        } else {
            $this->error($res_data['msg'], $res_data['result']);
        }

    }


    /**
     * 转换抖音视频链接
     * Created by PhpStorm.
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function videoUrl()
    {

        $param_data = $this->request->param();

        $user_id  = $this->auth->id;
        $res_data = DouYinServices::videoData($param_data, $user_id);

        if ($res_data['code'] == 200) {

            $url = $res_data['result']['data']['list'][0]['share_url'];

            $data = array(
                'url' => $url,
            );
            $this->success('请求成功', $data);
        } else {
            $this->error($res_data['msg']);
        }

    }


    /**
     * 上传图片
     * Created by PhpStorm.
     * @throws \Exception
     */
    public function imageUpload()
    {

        $param_data = $this->request->param();

        $video = input('post.video');

        $param_data['video'] = $video;

        $user_id  = $this->auth->id;
        $res_data = DouYinServices::imageUpload($param_data, $user_id);

        if ($res_data['code'] == 200) {
            $this->success('上传成功', $res_data['result']);
        } else {
            $this->error($res_data['msg'], $res_data['result']);
        }

    }


    /**
     * 发布图片
     * Created by PhpStorm.
     * @throws \Exception
     */
    public function imageCreate()
    {

        $param_data = $this->request->param();

        $user_id  = $this->auth->id;
        $res_data = DouYinServices::imageCreate($param_data, $user_id);

        if ($res_data['code'] == 200) {
            $this->success('上传成功', $res_data['result']);
        } else {
            $this->error($res_data['msg'], $res_data['result']);
        }

    }

    /**
     * 抖音token有效期
     * Created by PhpStorm.
     * @throws \Exception
     */
    public function expiresIn()
    {
        $param_data = $this->request->param();

        $user_id  = $this->auth->id;
        $res_data = DouYinServices::expiresIn($param_data, $user_id);

        if ($res_data['code'] == 200) {
            $this->success('请求成功', $res_data['result']);
        } else {
            $this->error($res_data['msg'], $res_data['result']);
        }
    }


    /**
     * 查询POI信息
     * Created by PhpStorm.
     * @throws \Exception
     */
    public function poiSearch()
    {

        $param_data = $this->request->param();

        $res_data = DouYinServices::poiSearch($param_data);

        if ($res_data['code'] == 200) {
            $this->success('请求成功', $res_data['result']);
        } else {
            $this->error($res_data['msg'], $res_data['result']);
        }
    }


    /**
     * 分片初始化上传
     * Created by PhpStorm.
     * @throws \Exception
     */
    public function videoPartInit()
    {

        $param_data = $this->request->param();

        $user_id  = $this->auth->id;
        $res_data = DouYinServices::videoPartInit($param_data, $user_id);

        if ($res_data['code'] == 200) {
            $this->success('请求成功', $res_data['result']);
        } else {
            $this->error($res_data['msg'], $res_data['result']);
        }

    }

    /**
     * 分片上传视频
     * Created by PhpStorm.
     * @throws \Exception
     */
    public function videoPartUpload()
    {

        $param_data = $this->request->param();

        $user_id  = $this->auth->id;
        $res_data = DouYinServices::videoPartUpload($param_data, $user_id);

        if ($res_data['code'] == 200) {
            $this->success('请求成功', $res_data['result']);
        } else {
            $this->error($res_data['msg'], $res_data['result']);
        }

    }

    /**
     * 分片上传完成
     * Created by PhpStorm.
     * @throws \Exception
     */
    public function videoPartComplete()
    {

        $param_data = $this->request->param();

        $user_id  = $this->auth->id;
        $res_data = DouYinServices::videoPartComplete($param_data, $user_id);

        if ($res_data['code'] == 200) {
            $this->success('请求成功', $res_data['result']);
        } else {
            $this->error($res_data['msg'], $res_data['result']);
        }

    }


    /**
     * 获取粉丝列表
     * Created by PhpStorm.
     * @throws \Exception
     */
    public function fansList()
    {

        $param_data = $this->request->param();

        $user_id  = $this->auth->id;
        $res_data = DouYinServices::fansList($param_data, $user_id);

        if ($res_data['code'] == 200) {
            $this->success('请求成功', $res_data['result']);
        } else {
            $this->error($res_data['msg'], $res_data['result']);
        }

    }


}