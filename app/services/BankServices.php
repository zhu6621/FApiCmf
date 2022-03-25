<?php


namespace app\services;


use app\api\model\BankList;
use app\api\model\UserAlipay;
use app\api\model\UserBank;
use app\api\model\Users;
use app\api\model\UserWechat;
use app\api\model\WalletCashes;
use think\facade\Db;

class BankServices
{
    /**
     * 获取银行名称列表
     * @return mixed
     * @Date  : 2020-07-27 16:22
     * @author:Red
     */
    static function getBanklist()
    {
        $r['code']   = ERROR1;
        $r['msg']    = '参数错误';
        $r['result'] = [];

        $list = BankList::select();

        if (!empty($list)) {
            $r['code']   = SUCCESS;
            $r['msg']    = '获取数据成功';
            $r['result'] = $list;
        } else {
            $r['msg'] = '暂无数据';
        }
        return $r;
    }

    /**逻辑删除一条支付宝收款信息
     * @param $userId               用户id
     * @param $id                   表id
     * @return mixed
     * @throws \think\db\exception\DbException
     * @Date  : 2020-07-29 10:47
     * @author:Red
     */
    static function deleteAlipay($userId, $id)
    {
        $r['code']   = ERROR1;
        $r['msg']    = '参数错误';
        $r['result'] = [];
        if (isInteger($userId, $id)) {
            $where[] = ['user_id', '=', $userId];
            $where[] = ['id', '=', $id];
            $where[] = ['status', '<>', 2];
            $find    = UserAlipay::where($where)->field('id')->find();
            if (empty($find)) {
                $r['msg'] = "无此支付宝信息";
                return $r;
            }

            $count = WalletCashes::where('user_id', $userId)
                ->where('account_id', $id)
                ->where('type', 'alipay')->count();
            if ($count > 0) {
                $r['msg'] = "该支付宝绑定有提现的订单，不能删除";
                return $r;
            }

            $update = UserAlipay::where('id', $id)->update(['status' => 2]);
            if ($update) {
                $r['msg']  = '删除成功';
                $r['code'] = SUCCESS;
            } else {
                $r['msg'] = '删除失败';
            }
        }
        return $r;
    }

    /**逻辑删除一条银行卡收款信息
     * @param $userId                   用户id
     * @param $id                       表id
     * @return mixed
     * @throws \think\db\exception\DbException
     * @Date  : 2020-07-29 10:52
     * @author:Red
     */
    static function deleteBank($userId, $id)
    {
        $r['code']   = ERROR1;
        $r['msg']    = '参数错误';
        $r['result'] = [];
        if (isInteger($userId, $id)) {
            $where[] = ['user_id', '=', $userId];
            $where[] = ['id', '=', $id];
            $where[] = ['status', '<>', 2];

            $find = UserBank::where($where)->field('id')->find();
            if (empty($find)) {
                $r['msg'] = "无此银行卡信息";
                return $r;
            }

            $count = WalletCashes::where('user_id', $userId)->where('account_id', $id)
                ->where('type', 'bank')->count();
            if ($count > 0) {
                $r['msg'] = "该银行卡绑定有提现的订单，不能删除";
                return $r;
            }
            $update = UserBank::where('id', $id)->update(['status' => 2]);

            if ($update) {
                $r['msg']  = '删除成功';
                $r['code'] = SUCCESS;
            } else {
                $r['msg'] = '删除失败';
            }
        }
        return $r;
    }

    /**
     * 逻辑删除一条微信收款信息
     * @param $userId                       用户id
     * @param $id                           表id
     * @return mixed
     * @throws \think\db\exception\DbException
     * @Date  : 2020-07-29 10:55
     * @author:Red
     */
    static function deleteWechat($userId, $id)
    {
        $r['code']   = ERROR1;
        $r['msg']    = '参数错误';
        $r['result'] = [];
        if (isInteger($userId, $id)) {
            $where[] = ['user_id', '=', $userId];
            $where[] = ['id', '=', $id];
            $where[] = ['status', '<>', 2];
            $find    = UserWechat::where($where)->field('id')->find();
            if (empty($find)) {
                $r['msg'] = "无此微信信息";
                return $r;
            }

            $count = WalletCashes::where('user_id', $userId)
                ->where('account_id', $id)
                ->where('type', 'wechat')->count();
            if ($count > 0) {
                $r['msg'] = "该微信绑定有提现的订单，不能删除";
                return $r;
            }
            $update = UserWechat::where('id', $id)->update(['status' => 2]);
            if ($update) {
                $r['msg']  = '删除成功';
                $r['code'] = SUCCESS;
            } else {
                $r['msg'] = '删除失败';
            }
        }
        return $r;
    }

    /**
     * 删除一条收款信息
     * @param $userId               用户id
     * @param $id                   表id
     * @param $type                 类型：wechat/alipay/bank
     * @return mixed
     * @throws \think\db\exception\DbException
     * @Date  : 2020-07-29 10:59
     * @author:Red
     */
    static function deleteInfo($param_data, $userId)
    {

        $id   = $param_data['id'];
        $type = $param_data['type'];

        $r['code']   = ERROR1;
        $r['msg']    = '参数错误';
        $r['result'] = [];
        if (isInteger($userId, $id) && in_array($type, ['wechat', 'alipay', 'bank'])) {
            switch ($type) {
                case 'wechat':
                    $r = BankServices::deleteWechat($userId, $id);
                    break;
                case 'alipay':
                    $r = BankServices::deleteAlipay($userId, $id);
                    break;
                case 'bank':
                    $r = BankServices::deleteBank($userId, $id);
                    break;
            }
        }
        return $r;
    }

    /**
     * 获取用户微信的全部收款记录
     * @param $userId           用户id
     * @return array
     * @Date  : 2020-07-29 11:10
     * @author:Red
     */
    static function getWechatList($userId)
    {
        if (isInteger($userId)) {
            $where[] = ['user_id', '=', $userId];
            $where[] = ['status', '<>', 2];

            $list = UserWechat::where($where)->field('id,truename,wechat,wechat_pic,status')->select();
            if (!empty($list)) {
                //TODO 把文件id转url链接
                foreach ($list as &$value) {
                    $value['wechat_pic'] = !empty($value['wechat_pic']) ? imgUrlConvert($value['wechat_pic']) : '';
                }
                return $list;
            }
        }
        return [];
    }

    /**
     * 获取用户的支付宝收款信息
     * @param $userId           用户id
     * @return array
     * @Date  : 2020-07-29 11:12
     * @author:Red
     */
    static function getAlipayList($userId)
    {
        if (isInteger($userId)) {
            $where[] = ['user_id', '=', $userId];
            $where[] = ['status', '<>', 2];
            $list    = UserAlipay::where($where)->field('id,truename,alipay,alipay_pic,status')->select();
            if (!empty($list)) {
                foreach ($list as &$value) {
                    $value['alipay_pic'] = !empty($value['alipay_pic']) ? imgUrlConvert($value['alipay_pic']) : '';
                }
                //TODO 把文件id转url链接
                return $list;
            }
        }
        return [];
    }

    /**
     * 获取用户的银行卡收款信息列表
     * @param $userId           用户id
     * @return array
     * @Date  : 2020-07-29 11:21
     * @author:Red
     */
    static function getUserBankList($userId)
    {
        if (isInteger($userId)) {
            $where[] = ['user_id', '=', $userId];
            $where[] = ['status', '<>', 2];
            $list    = UserBank::where($where)->field('id,truename,bankname,bankadd,bankcard,status')->select();

            if (!empty($list)) {

                $banklist = BankList::column('name', 'id');

                foreach ($list as &$value) {
                    $value['bankname'] = isset($banklist[$value['bankname']]) ? $banklist[$value['bankname']] : '';
                }
                return $list;
            }
        }
        return [];
    }

    /**
     * 获取用户的所有收款列表
     * @param $userId               用户id
     * @return mixed
     * @Date  : 2020-07-29 11:27
     * @author:Red
     */
    static function getList($userId)
    {
        $array['wechat'] = BankServices::getWechatList($userId);
        $array['alipay'] = BankServices::getAlipayList($userId);
        $array['bank']   = BankServices::getUserBankList($userId);
        $r['code']       = SUCCESS;
        $r['msg']        = "获取数据成功";
        $r['result']     = $array;
        return $r;
    }

    /**
     * 添加一条支付宝收款信息
     * @param     $userId               用户id
     * @param     $truename             真实姓名
     * @param     $alipay               支付宝账号
     * @param     $alipayPic            链接id
     * @param int $status               状态:0不启用，1启用
     * @return mixed
     * @Date  : 2020-07-29 14:43
     * @author:Red
     */
    static function addAlipay($userId, $truename, $alipay, $alipayPic, $status = 1)
    {
        $r['code']   = ERROR1;
        $r['msg']    = "参数错误";
        $r['result'] = [];
        if (isInteger($userId) && !empty($truename) && !empty($alipay) && !empty($alipayPic) && in_array($status, [0, 1])) {
            Db::startTrans();
            try {
                $count = UserAlipay::where('user_id', $userId)->where('status', '<>', 2)->count();
                if ($count >= 10) {
                    throw new \Exception("最多只能添加10条支付宝收款信息");
                }
                $data['user_id']    = $userId;
                $data['truename']   = $truename;
                $data['alipay']     = $alipay;
                $data['alipay_pic'] = $alipayPic;
                $data['add_time']   = time();
                $data['status']     = $status;


                $id = UserAlipay::insert($data);
                if ($id) {
                    $r['code'] = SUCCESS;
                    $r['msg']  = '添加成功';
                } else {
                    throw new \Exception("添加支付宝收款信息失败");
                }
                Db::commit();
            } catch (\Exception $exception) {
                $r['msg'] = $exception->getMessage();
                Db::rollback();
            }
        }
        return $r;
    }

    /**
     * 添加银行卡收款信息
     * @param     $userId           用户id
     * @param     $truename         真实姓名
     * @param     $bankId           银行名称表id
     * @param     $bankadd          支行
     * @param     $bankcard         银行卡号
     * @param int $status           状态:0不启用，1启用
     * @return mixed
     * @Date  : 2020-07-29 14:52
     * @author:Red
     */
    static function addBank($userId, $truename, $bankId, $bankadd, $bankcard, $status = 1)
    {
        $r['code']   = ERROR1;
        $r['msg']    = "参数错误";
        $r['result'] = [];
        if (isInteger($userId, $bankId) && !empty($truename) && !empty($bankadd) && !empty($bankcard) && in_array($status, [0, 1])) {
            Db::startTrans();
            try {

                $bankname = BankList::where('id', $bankId)->field('name')->find();
                if (empty($bankname) || empty($bankname['name'])) throw new \Exception("银行信息有误");

                $count = UserBank::where('user_id', $userId)->where('status', '<>', 2)->count();
                if ($count >= 10) {
                    throw new \Exception("最多只能添加10条银行卡收款信息");
                }
                $data['user_id']  = $userId;
                $data['truename'] = $truename;
                $data['bankname'] = $bankId;
                $data['bankadd']  = $bankadd;
                $data['bankcard'] = $bankcard;
                $data['add_time'] = time();
                $data['status']   = $status;

                $id = UserBank::insert($data);
                if ($id) {
                    $r['code'] = SUCCESS;
                    $r['msg']  = "添加成功";
                } else {
                    throw new \Exception("添加银行卡收款信息失败");
                }
                Db::commit();
            } catch (\Exception $exception) {
                $r['msg'] = $exception->getMessage();
                Db::rollback();
            }
        }
        return $r;
    }

    /**
     * @param     $userId           用户id
     * @param     $truename         真实姓名
     * @param     $wechat           微信号
     * @param     $wechatPic        二维码链接id
     * @param int $status           状态:0不启用，1启用
     * @return mixed
     * @Date  : 2020-07-29 14:55
     * @author:Red
     */
    static function addWechat($userId, $truename, $wechat, $wechatPic, $status = 1)
    {
        $r['code']   = ERROR1;
        $r['msg']    = "参数错误";
        $r['result'] = [];
        if (isInteger($userId) && !empty($truename) && !empty($wechat) && !empty($wechatPic) && in_array($status, [0, 1])) {
            Db::startTrans();
            try {

                $count = UserWechat::where('user_id', $userId)->where('status', '<>', 2)->count();
                if ($count >= 10) {
                    throw new \Exception("最多只能添加10条微信收款信息");
                }
                $data['user_id']    = $userId;
                $data['truename']   = $truename;
                $data['wechat']     = $wechat;
                $data['wechat_pic'] = $wechatPic;
                $data['add_time']   = time();
                $data['status']     = $status;


                $id = UserWechat::insert($data);
                if ($id) {
                    $r['code'] = SUCCESS;
                    $r['msg']  = '添加成功';
                } else {
                    throw new \Exception("添加微信收款信息失败");
                }
                Db::commit();
            } catch (\Exception $exception) {
                $r['msg'] = $exception->getMessage();
                Db::rollback();
            }
        }
        return $r;
    }

    /**
     * 添加一条收款方式
     * @param      $userId              用户id
     * @param      $pwd                 支付密码
     * @param      $type                收款类型:alipay/wechat/bank
     * @param      $account             账号
     * @param      $pic                 收款二维码文件id
     * @param      $truename            真名
     * @param null $bankId              银行名称id
     * @param null $bankadd             银行支行
     * @param int  $status              状态:0不启用，1启用
     * @return mixed
     * @Date  : 2020-07-29 15:06
     * @author:Red
     */
    static function addPayment($param_data, $userId)
    {

        $r['code']   = ERROR1;
        $r['msg']    = "参数错误";
        $r['result'] = [];

        $pay_password = $param_data['pay_password'];
        $type         = $param_data['type'];
        $truename     = $param_data['truename'];
        $bankId       = $param_data['bank_id'] ?? 0;
        $bankadd      = $param_data['bankadd'] ?? '';
        $account      = $param_data['account'];
        $status       = 1;
        $pic          = $param_data['pic'] ?? '';

        if (isInteger($userId) && !empty($pay_password) && in_array($type, ['wechat', 'alipay', 'bank']) && !empty($account)) {

            $user_info = Users::where('id', $userId)->field('pay_password')->find();
            $check     = passwordVerify($pay_password, $user_info['pay_password']);
            if (!$check) {
                $r['msg'] = '密码错误';
                return $r;
            }
            switch ($type) {
                case 'alipay':
                    $r = BankServices::addAlipay($userId, $truename, $account, $pic, $status);
                    break;
                case 'wechat':
                    $r = BankServices::addWechat($userId, $truename, $account, $pic, $status);
                    break;
                case 'bank':
                    $r = BankServices::addBank($userId, $truename, $bankId, $bankadd, $account, $status);
                    break;
            }
        }
        return $r;

    }
}