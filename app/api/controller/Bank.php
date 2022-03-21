<?php


namespace app\api\controller;


use app\ApiBaseController;
use app\services\BankServices;

class Bank extends ApiBaseController
{
    /**
     * 获取银行卡列表
     * @throws \Exception
     * @Date  : 2020-08-31 18:48
     * @author:Red
     */
    public function banklist()
    {

        //接收的数据
        $param_data = $this->param_data;
        $user_id    = $this->uid;

        $res_data = BankServices::getBanklist($param_data, $user_id);

        if ($res_data['code'] == 200) {
            $this->jsonEncrypt(200, '请求成功', $res_data['result']);
        } else {
            $this->jsonEncrypt(422, $res_data['msg']);
        }
    }

    /**
     * 删除一条收款信息
     * @throws \Exception
     * @Date  : 2020-08-31 19:43
     * @author:Red
     */
    public function delete()
    {

        //接收的数据
        $param_data = $this->param_data;
        $user_id    = $this->uid;

        $res_data = BankServices::deleteInfo($param_data, $user_id);

        if ($res_data['code'] == 200) {
            $this->jsonEncrypt(200, '请求成功');
        } else {
            $this->jsonEncrypt(422, $res_data['msg']);
        }

    }

    /**
     * 获取用户的收款信息列表
     * @throws \Exception
     * @Date  : 2020-08-31 19:49
     * @author:Red
     */
    public function getList()
    {

        $user_id = $this->uid;

        $res_data = BankServices::getList($user_id);

        if ($res_data['code'] == 200) {
            $this->jsonEncrypt(200, '请求成功', $res_data['result']);
        } else {
            $this->jsonEncrypt(422, $res_data['msg']);
        }

    }

    /**
     * 新增收款信息
     * @throws \Exception
     * @Date  : 2020-09-01 11:13
     * @author:Red
     */
    public function addInfo()
    {

        //接收的数据
        $param_data = $this->param_data;
        $user_id    = $this->uid;

        $res_data = BankServices::addPayment($param_data, $user_id);

        if ($res_data['code'] == 200) {
            $this->jsonEncrypt(200, '请求成功');
        } else {
            $this->jsonEncrypt(422, $res_data['msg']);
        }

    }
}