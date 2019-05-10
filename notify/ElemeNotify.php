<?php
/**
 * Created by PhpStorm.
 * User: zhangjunwei
 * Date: 2019-04-29
 * Time: 20:18
 */

namespace TakeOut\Notify;

use Tools\Map\QQMapTool;
use XHMS\MS\Define\CommonDefine;
use XHMS\MS\Define\StatusDefine;

class ElemeNotify extends BaseNotify
{
    public function handle($params)
    {
        $params = json_decode($params,true);

        $result = [];
        switch ($params['type']){
            case 10:
                $result['status'] = 'add';
                $result['data'] = $this->getOrder($params['message']);
                break;
            case 100:
                $result['status'] = 'error';
                $result['data'] = $this->getOrder($params['message']);
                break;
            default:
                return false;
        }

        return $result;
    }

    public function getOrder($params)
    {
        $params = json_decode($params,true);

        $province = $city = $area = '';

        list($lng,$lat) = explode(',',$params['deliveryGeo']);
        $location = $lat.','.$lng;

        $res = QQMapTool::getInstance()->getAddressByLocation($location);
        if($res['status'] == 0){
            $province = $res['result']['address_component']['province'];
            $city = $res['result']['address_component']['city'];
            $area = $res['result']['address_component']['district'];
        }

        $deliveryType = 0;
        switch ($params['orderBusinessType']){
            case 0:
                $deliveryType = CommonDefine::DELIVERY_TYPE['送货上门'];
                break;
            case 1:
                $deliveryType = CommonDefine::DELIVERY_TYPE['自提'];
                break;
            case 2:
                $deliveryType = CommonDefine::DELIVERY_TYPE['企业到店买单'];
                break;
        }
        $pickUpTime = strtotime($params['pickUpTime']);
        $result = [
            'trade_order_no' => $params['id'],//第三方订单号
            'shop_id' => $params['shopId'],//线上店铺id
            'province' => $province,//下单省
            'city' => $city,//下单市
            'area' => $area,//下单区
            'customer_name' => '',//订货人id
            'customer_tel' => '',//订货人手机号
            'receiver_name' => $params['consignee'],//收货人姓名
            'receiver_tel' => join('#',$params['phoneList']),//收货人手机号
            'receiver_address' => $params['deliveryPoiAddress'],
            'is_invoice' => $params['invoiced'] ? 2 : 1,//是否开发票
            'invoice' => [
                'title' => $params['invoice']??'',//发票抬头
                'invoice_no' => $params['taxpayerId']??'',//开票码
            ],
            'delivery_type' => $deliveryType,//配送方式
            'delivery_start_time' => empty($params['deliverTime']) ? strtotime(date('Y-m-d H:00:00').'+3 hour') : strtotime($params['deliverTime']),//预计送达时间
            'delivery_end_time' => empty($params['deliverTime']) ? strtotime(date('Y-m-d H:00:00').'+4 hour') : strtotime($params['deliverTime']),//预计送达时间
            'pick_up_time' => empty($pickUpTime) ? 0 : $pickUpTime,//到店自提时间
            'pick_up_province' => '',//自提点-省
            'pick_up_city' => '',//自提点-市
            'pick_up_area' => '',//自提点-区
            'pick_up_address' => empty($pickUpData['address_detail']) ? '' : $pickUpData['address_detail'],//自提点-地址
            'pick_up_lat_lng' => empty($pickUpData['lat']) || empty($pickUpData['lon']) ? '' : $pickUpData['lat'].','.$pickUpData['lon'],//自提点-经纬度
            'pay_type' => 0,//支付类型
            'pay_state' => 2,//支付状态
            'status' => StatusDefine::STATUS['待处理'],//订单状态
            'create_time' => strtotime($params['createdAt']),//订单创建时间
            'pay_time' => strtotime($params['activeAt']),//订单支付时间
            'origin_price' => $params['originalPrice'],//原价
            'actual_price' => $params['originalPrice'],//实际价格
            'price' => $params['totalPrice'],//实收价格
            'discount_amount' => $params['hongbao'] + $params['activityTotal'],//订单优惠总金额
            'discount_detail' => empty($params['orderActivities']) ? '' : join(',',array_column($params['orderActivities'],'name')),//优惠详情
            'customer_remark' => $params['description'],//用户备注
            'lat_lng' => $location,//经纬度
            'goods' => [],//商品列表
        ];

        foreach ($params['groups'] as $group) {
            if(in_array($group['type'],['extra'])) continue;
            foreach ($group['items'] as $order) {
                $result['goods'][] = [
                    'name' => $order['name'],
                    'item_id' => $order['vfoodId'],
                    'sku_id' => $order['id'],
                    'num' => $order['quantity'],
                    'is_present' => $group['type'] == 'normal' ? 2 : 1,//是否赠品
                    'origin_price' => $order['price'],//商品单价
                    'price' => $order['price'],//商品单价
                ];
            }
        }

        return $result;
    }

    public function relieveOAuth($params)
    {
        $params = json_decode($params,true);
        $result = [
            'shopId' => $params['shopId'],
            'relieveOAuthTime' => $params['relieveOAuthTime'],//解除授权时间
        ];

        return $result;
    }
}