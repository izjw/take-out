<?php
/**
 * Created by PhpStorm.
 * User: zhangjunwei
 * Date: 2019-04-29
 * Time: 20:18
 */

namespace TakeOut\Notify;

use TakeOut\Config;
use XHMS\MS\Define\CommonDefine;
use XHMS\MS\Define\StatusDefine;

class YouzanNotify extends BaseNotify
{
    public function handle($params)
    {
        $data = json_decode($params,true);

        switch ($data['type']){
            case 'trade_TradeBuyerPay':
                $result['status'] = 'add';
                $result['data'] = $this->getOrder($data['msg']);
                break;
            default:
                return false;
        }

        return $result;
    }

    public function getOrder($params)
    {
        $data = urldecode($params);
        $data = json_decode($data,true);

        $address_extra = json_decode($data['full_order_info']['address_info']['address_extra'],true);

        $deliveryType = 0;
        switch ($data['full_order_info']['order_info']['express_type']){
            case 0:
                $deliveryType = CommonDefine::DELIVERY_TYPE['送货上门'];
                break;
            case 1:
                $deliveryType = CommonDefine::DELIVERY_TYPE['自提'];
                break;
            case 2:
                $deliveryType = CommonDefine::DELIVERY_TYPE['企业到店买单'];
                break;
            case 9:
                $deliveryType = CommonDefine::DELIVERY_TYPE['无需配送'];
                break;
        }

        $pickUpData = empty($data['full_order_info']['address_info']['self_fetch_info']) ? [] : json_decode($data['full_order_info']['address_info']['self_fetch_info'],true);
        $pickUpTime = empty($pickUpData['user_time']) || empty(strtotime($pickUpData['user_time'])) ? 0 : strtotime($pickUpData['user_time']);

        $result = [
            'trade_order_no' => $data['full_order_info']['order_info']['tid'],//第三方订单号
            'shop_id' => Config::$youzanConf['shopId'],//线上店铺id
            'province' => $data['full_order_info']['address_info']['delivery_province'],//下单省
            'city' => $data['full_order_info']['address_info']['delivery_city'],//下单市
            'area' => $data['full_order_info']['address_info']['delivery_district'],//下单区
            'customer_name' => $data['full_order_info']['buyer_info']['buyer_id'],//订货人id
            'customer_tel' => empty($data['full_order_info']['buyer_info']['buyer_phone']) ? '' : $data['full_order_info']['buyer_info']['buyer_phone'],//订货人手机号
            'receiver_name' => !empty($pickUpData['user_name']) ? $pickUpData['user_name'] : $data['full_order_info']['address_info']['receiver_name'],//收货人姓名
            'receiver_tel' => !empty($pickUpData['user_tel']) ? $pickUpData['user_tel'] : $data['full_order_info']['address_info']['receiver_tel'],//收货人手机号
            'receiver_address' => $data['full_order_info']['address_info']['delivery_address'],//收货地址
            'is_invoice' => 1,//是否开发票
            'invoice' => [
                'title' => '',//发票抬头
                'invoice_no' => '',//开票码
            ],
            'delivery_type' => $deliveryType,//配送方式
            'delivery_start_time' => empty($data['full_order_info']['address_info']['delivery_start_time']) ? strtotime(date('Y-m-d H:00:00').'+3 hour') : strtotime($data['full_order_info']['address_info']['delivery_start_time']),//预计送达时间
            'delivery_end_time' => empty($data['full_order_info']['address_info']['delivery_end_time']) ? strtotime(date('Y-m-d H:00:00').'+4 hour') : strtotime($data['full_order_info']['address_info']['delivery_end_time']),//预计送达时间
            'pick_up_time' => empty($pickUpTime) ? 0 : $pickUpTime,//到店自提时间
            'pick_up_province' => empty($pickUpData['province']) ? '' : $pickUpData['province'],//自提点-省
            'pick_up_city' => empty($pickUpData['city']) ? '' : $pickUpData['city'],//自提点-市
            'pick_up_area' => empty($pickUpData['county']) ? '' : $pickUpData['county'],//自提点-区
            'pick_up_address' => empty($pickUpData['address_detail']) ? '' : $pickUpData['address_detail'],//自提点-地址
            'pick_up_lat_lng' => empty($pickUpData['lat']) || empty($pickUpData['lon']) ? '' : $pickUpData['lat'].','.$pickUpData['lon'],//自提点-经纬度
            'pay_type' => $data['full_order_info']['order_info']['pay_type'],//支付类型
            'pay_state' => 2,//支付状态
            'status' => StatusDefine::STATUS['待处理'],//订单状态
            'create_time' => strtotime($data['full_order_info']['order_info']['created']),//订单创建时间
            'pay_time' => strtotime($data['full_order_info']['order_info']['pay_time']),//订单支付时间
            'origin_price' => $data['full_order_info']['pay_info']['total_fee'] + $data['full_order_info']['pay_info']['post_fee'],//原价
            'actual_price' => $data['full_order_info']['pay_info']['total_fee'] + $data['full_order_info']['pay_info']['post_fee'],//实际价格
            'price' => $data['full_order_info']['pay_info']['payment'] + $data['full_order_info']['pay_info']['post_fee'],//实收价格
            'discount_amount' => empty($data['order_promotion']['order_discount_fee']) ? 0 : $data['order_promotion']['order_discount_fee'],//订单优惠总金额
            'discount_detail' => empty($data['orderActivities']) ? '' : join(',',array_column($params['orderActivities'],'name')),//优惠详情
            'customer_remark' => $data['full_order_info']['remark_info']['buyer_message'],//用户备注
            'lat_lng' => $address_extra['lat'].','.$address_extra['lon'],//经纬度
            'goods' => [],//商品列表
        ];

        foreach ($data['full_order_info']['orders'] as $order) {
            $result['goods'][] = [
                'name' => $order['title'],
                'item_id' => $order['item_id'],
                'sku_id' => $order['sku_id'],
                'num' => $order['num'],
                'is_present' => $order['is_present'] ? 2 : 1,//是否赠品
                'origin_price' => $order['price'],//商品原价
                'price' => $order['discount_price'],//商品单价
            ];
        }

        return $result;
    }
}