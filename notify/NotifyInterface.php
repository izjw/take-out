<?php
/**
 * Created by PhpStorm.
 * User: zhangjunwei
 * Date: 2019-04-29
 * Time: 20:06
 */

namespace TakeOut\Notify;


interface NotifyInterface
{
    /**
     *
     * @param $type
     * @param $params
     * author: 张峻玮
     * date:2019-04-29
     */
    public function handle($params);
}