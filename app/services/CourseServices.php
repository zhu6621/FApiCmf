<?php
/**
 * @return
 *
 * User: chen
 * Date: 2021/5/24 15:21
 */

namespace app\services;

use app\api\model\Course;
use app\api\model\CourseList;
use app\api\model\CourseOrder;
use app\api\model\CurrencyUser;
use app\api\model\NewsCates;
use app\api\model\Users;
use app\ApiBaseController;
use app\services\payment\Alipay2Services;
use think\facade\Db;
use think\facade\Log;


class CourseServices extends ApiBaseController
{


    /**
     * 课程分类
     * Created by PhpStorm.
     * @param $param_data
     * @param $user_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: xiao zhu
     * Date: 2021/12/23
     * Time: 16:07
     */
    public static function catList($param_data, $user_id)
    {
        $cat_id = 6;
        $info[] = array(
            'id'   => -1,
            'name' => '全部'
        );
        $info2  = NewsCates::where('pid', $cat_id)->field('id,name')->select()->toArray();


        $info3[] = array(
            'id'   => 1,
            'name' => '收费'
        );

        $info4[] = array(
            'id'   => 0,
            'name' => '免费'
        );

        $info = array_merge($info, $info2, $info3, $info4);

        $r['code']   = 200;
        $r['msg']    = '请求成功';
        $r['result'] = $info;

        return $r;
    }


    /**
     * 课程类型列表
     * Created by PhpStorm.
     * @param $param_data
     * @return mixed
     * User: xiao zhu
     * Date: 2021/12/31
     * Time: 11:50
     */
    public static function typeList($param_data)
    {
        $info = array(
            0 => array('id' => -1, 'name' => '全部'),
            1 => array('id' => 1, 'name' => '视频'),
            2 => array('id' => 2, 'name' => '文章'),
            3 => array('id' => 3, 'name' => '付费'),
            4 => array('id' => 4, 'name' => '免费'),
        );

        $r['code']   = 200;
        $r['msg']    = '请求成功';
        $r['result'] = $info;

        return $r;
    }

    /**
     * 课程列表
     * Created by PhpStorm.
     * @param $param_data
     * @param $user_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: xiao zhu
     * Date: 2021/12/23
     * Time: 16:07
     */
    public static function list($param_data, $user_id)
    {

        $page  = $param_data['page'] ?? 1;
        $limit = $param_data['limit'] ?? 10;
        $type  = $param_data['type'] ?? -1;

        $where[] = ['up_down', '=', 1];

        switch ($type) {
            case -1:
                break;
            case 3:
                $where[] = ['price', '>', 0];
                break;
            case 4:
                $where[] = ['price', '=', 0];
                break;
            default:
                $where[] = ['type', '=', $type];
        }

        $field = '*';
        $info  = Course::where($where)->page($page)->limit($limit)
            ->field($field)
            ->order('id desc')
            ->select()->toArray();


        foreach ($info as $k => &$v) {

            $order_info = CourseOrder::where('course_id', $v['id'])
                ->where('user_id', $user_id)
                ->where('status', 1)
                ->find();

            $v['is_pay'] = 0;
            if ($order_info) {
                $v['is_pay'] = 1;
            }


            $buy_num      = CourseOrder::where('course_id', $v['id'])
                ->where('status', 1)->count();
            $v['buy_num'] = $v['base_num'] + $buy_num;

        }


        $r['code']   = 200;
        $r['msg']    = '请求成功';
        $r['result'] = $info;

        return $r;
    }


    /**
     * 课程详情
     * Created by PhpStorm.
     * @param $param_data
     * @param $user_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: xiao zhu
     * Date: 2021/12/23
     * Time: 15:59
     */
    public static function details($param_data, $user_id)
    {

        $r['code']   = 422;
        $r['msg']    = '请求失败';
        $r['result'] = [];

        $id = $param_data['id'] ?? 0;

        if ($id == 0) {
            $r['msg'] = '课程错误';
            return $r;
        }

        $info = Course::with('courseList')->where('id', $id)->find();

        if ($info) {

            $order_info = CourseOrder::where('course_id', $info['id'])
                ->where('user_id', $user_id)
                ->where('status', 1)
                ->find();

            $info['is_pay'] = 0;
            if ($order_info) {
                $info['is_pay'] = 1;
            }


            $buy_num = CourseOrder::where('course_id', $info['id'])
                ->where('status', 1)
                ->count();

            $info['buy_num'] = $info['base_num'] + $buy_num;

            foreach ($info['courseList'] as $k => &$v) {

                if ($info['price'] > 0 && $v['is_free'] == 0) {

                    $order_info = CourseOrder::where('course_id', $info['id'])->where('user_id', $user_id)
                        ->where('status', 1)->find();

                    if (empty($order_info)) {

                        if ($info['type'] == 1) {
                            //视频
                            $v['content'] = '';
                            $v['video']   = '';
                        } else {
                            //文章
                            $v['content'] = mb_substr($v['content'], 0, 180, "utf-8");
                        }

                    }

                }

            }

            Course::where('id', $id)->inc('hits', 1)->update();

            $r['code']   = 200;
            $r['msg']    = '请求成功';
            $r['result'] = $info;


        } else {
            $r['msg'] = '课程不存在';

        }

        return $r;
    }

    /**
     * 课程目录中的内容
     * Created by PhpStorm.
     * @param $param_data
     * @param $user_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: xiao zhu
     * Date: 2021/12/31
     * Time: 16:00
     */
    static function content($param_data, $user_id)
    {

        $r['code']   = 422;
        $r['msg']    = '请求失败';
        $r['result'] = [];

        $course_id = $param_data['course_id'] ?? 0;
        $id        = $param_data['id'] ?? 0;

        if ($course_id == 0) {
            $r['msg'] = '课程错误';
            return $r;
        }

        if ($id == 0) {
            $r['msg'] = '课程目录错误';
            return $r;
        }

        $info = CourseList::with('course')->where('id', $id)->where('course_id', $course_id)->find();

        if ($info) {
            if ($info['course']['price'] > 0 && $info['is_free'] == 0) {

                $order_info = CourseOrder::where('course_id', $course_id)->where('user_id', $user_id)
                    ->where('status', 1)->find();

                if (empty($order_info)) {

                    if ($info['course']['type'] == 1) {
                        //视频
                        $info['content'] = '';
                        $info['video']   = '';
                    } else {
                        //文章
                        $info['content'] = mb_substr($info['content'], 0, 180, "utf-8");
                    }

                }

            }

            CourseList::where('id', $id)->where('course_id', $course_id)->inc('hits')->update();
        }

        $r['code']   = 200;
        $r['msg']    = '请求成功';
        $r['result'] = $info;

        return $r;


    }


    /**
     * 我的课程列表
     * Created by PhpStorm.
     * @param $param_data
     * @param $user_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: xiao zhu
     * Date: 2021/12/23
     * Time: 16:07
     */
    public static function myList($param_data, $user_id)
    {

        $page  = $param_data['page'] ?? 1;
        $limit = $param_data['limit'] ?? 10;

        $course_ids = CourseOrder::where('user_id', $user_id)
            ->where('status', 1)
            ->column('course_id');

        $where[] = ['id', 'in', $course_ids];

        $info = Course::with('courseList')->where($where)->page($page)->limit($limit)
            ->order('id desc')
            ->select()->toArray();

        $r['code']   = 200;
        $r['msg']    = '请求成功';
        $r['result'] = $info;

        return $r;
    }


    /**
     * 购买课程
     * Created by PhpStorm.
     * @param $param_data
     * @param $user_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: xiao zhu
     * Date: 2021/12/23
     * Time: 17:24
     */
    public static function buy($param_data, $user_id)
    {

        $r['code']   = 422;
        $r['msg']    = '请求失败';
        $r['result'] = [];

        $id           = $param_data['id'] ?? 0;
        $pay_type     = $param_data['pay_type'] ?? 'balance';
        $pay_password = $param_data['pay_password'] ?? '';
        $openid       = $param_data['openid'] ?? '';


        if ($id == 0) {
            $r['msg'] = '课程错误';
            return $r;
        }

        if (!in_array($pay_type, ['alipay', 'wechat', 'balance'])) {
            $r['msg'] = '支付方式错误';
            return $r;
        }

        if ($pay_type == 'balance') {

            if (empty($pay_password)) {
                $r['msg'] = '请输入支付密码';
                return $r;
            }
            $user_info = Users::where('id', $user_id)->field('pay_password')->find();

            $passwordVerify = passwordVerify($pay_password, $user_info['pay_password']);

            if (!$passwordVerify) {
                $r['msg'] = '支付密码错误';
                return $r;
            }
        }

        $info = Course::where('id', $id)->find();

        if ($info) {

            if ($info['price'] > 0) {

                $order_info = CourseOrder::where('course_id', $id)
                    ->where('user_id', $user_id)
                    ->where('status', 1)
                    ->find();

                if ($order_info) {

                    $r['msg'] = '该课程您已购买';
                    return $r;
                } else {

                    Db::startTrans();
                    try {

                        $ip           = get_client_ip_extend();
                        $out_trade_no = createOrderNo('course');
                        $content      = '购买课程《' . $info['title'] . '》';

                        if ($pay_type == 'alipay') {

                            $insert_data = array(
                                'pay_type'     => $pay_type,
                                'out_trade_no' => $out_trade_no,
                                'course_id'    => $id,
                                'course_name'  => $info['title'],
                                'amount'       => $info['price'],
                                'user_id'      => $user_id,
                                'content'      => $content,
                                'from'         => $param_data['client'],
                                'ip'           => $ip,
                                'create_time'  => time(),
                            );

                            $order_id = CourseOrder::insertGetId($insert_data);


//                            $order_info = [
//                                'out_trade_no' => $out_trade_no,
//                                'total_amount' => $info['price'],
//                                'subject'      => $content,
//                            ];

                            $order_info = [
                                //'body'            => $content,
                                'subject'         => $content,
                                'out_trade_no'    => $out_trade_no,
                                'total_amount'    => $info['price'],
                                //'product_code'    => 'QUICK_MSECURITY_PAY',
                                //'timeout_express' => '30m',
                            ];

                            $res_data = Alipay2Services::pay(json_encode($order_info));


                            $r['result'] = $res_data;

                        } elseif ($pay_type == 'wechat') {

                            if (empty($openid)) {
                                throw new \Exception('请先授权微信');
                            }

                            $insert_data = array(
                                'pay_type'     => $pay_type,
                                'out_trade_no' => $out_trade_no,
                                'course_id'    => $id,
                                'course_name'  => $info['title'],
                                'amount'       => $info['price'],
                                'user_id'      => $user_id,
                                'content'      => $content,
                                'from'         => $param_data['client'],
                                'ip'           => $ip,
                                'create_time'  => time(),
                            );

                            $order_id = CourseOrder::insertGetId($insert_data);

                            $pay_channel = 'wx_lite';
                            $result      = AdapayServices::wechatPay($openid, $out_trade_no, $info['price'], $content, $pay_channel);

                            Log::write($result);
                            if ($result['code'] != 200) {
                                throw new \Exception($result['msg']);
                            }

                            $r['result'] = ['client' => $result['data'], 'out_trade_no' => $out_trade_no, 'id' => $order_id, 'payType' => $pay_type];

                        } else {

                            $currency_user = CurrencyUser::getCurrencyUser($user_id, 1);
                            $balance       = $currency_user['num'];

                            if ($info['price'] > $balance) {
                                $r['msg'] = '您的余额不足';
                                return $r;
                            }

                            $insert_data = array(
                                'pay_type'     => $pay_type,
                                'out_trade_no' => $out_trade_no,
                                'course_id'    => $id,
                                'course_name'  => $info['title'],
                                'status'       => 1,
                                'amount'       => $info['price'],
                                'user_id'      => $user_id,
                                'content'      => $content,
                                'from'         => $param_data['client'],
                                'ip'           => $ip,
                                'create_time'  => time(),
                            );


                            $order_id = CourseOrder::insertGetId($insert_data);


                            if (!$order_id) {
                                Db::rollback();
                                $r['msg'] = '购买失败';
                                return $r;
                            }

                            $res = CurrencyUser::operatingCurrency($user_id, 1, $info['price'], 15, 'out', false, 0, $order_id);

                            //插入消息
                            $msg_content='您购买了课程《' . $info['title'] . '》';
                            MessageServices::insert($user_id,0,0,10,$msg_content);
                            if ($res['code'] != 200) {
                                Db::rollback();
                                $r['msg'] = '操作资产失败';
                                return $r;
                            }
                        }

                        Db::commit();

                        $r['code'] = 200;
                        $r['msg']  = '操作成功';
                        return $r;

                    } catch (\Exception $e) {
                        Db::rollback();

                        $r['msg'] = $e->getMessage();
                        return $r;

                    }


                }


            } else {

                $r['msg'] = '该课程免费，不用购买';
                return $r;

            }

            $r['code']   = 200;
            $r['msg']    = '请求成功';
            $r['result'] = $info;


        } else {
            $r['msg'] = '课程不存在';

        }

        return $r;
    }


    /**
     * 更新订单
     * Created by PhpStorm.
     * @param $order
     * @param $data
     * @return mixed
     * User: xiao zhu
     * Date: 2021/12/31
     * Time: 17:43
     */
    static function updateOrder($order, $data)
    {

        try {

            $update = array(
                'status'   => 1,
                'trade_no' => $data['trade_no'],
            );
            $res    = CourseOrder::where('id', $order['id'])->update($update);

            //插入消息
            $msg_content='您购买了课程《' . $order['course_name'] . '》';
            MessageServices::insert($order['user_id'],0,0,10,$msg_content);

            if (!$res) {
                throw new \Exception('操作异常');
            }
        } catch (\Exception $exception) {
            $r['msg'] = $exception->getMessage();
        }


        $r['code'] = 200;
        $r['msg']  = '操作成功';

        return $r;

    }
}