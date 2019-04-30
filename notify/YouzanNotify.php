<?php
/**
 * Created by PhpStorm.
 * User: zhangjunwei
 * Date: 2019-04-29
 * Time: 20:18
 */

namespace OrderTool;


class YouzanNotify extends BaseNotify
{
    public function getOrder($params)
    {
        $orderId = $params['orderId'];
    }
}