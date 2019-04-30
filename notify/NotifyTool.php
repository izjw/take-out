<?php
/**
 * Created by PhpStorm.
 * User: zhangjunwei
 * Date: 2019-04-29
 * Time: 20:35
 */

namespace OrderTool;


class NotifyTool
{
    protected $error = '';

    /**
     *
     * @param $platform
     * @param array $data
     * author: 张峻玮
     * date:2019-04-29
     */
    public function getNotifyClass($platform)
    {
        switch ($platform){
            case NotifyConfig::CHANNEL_ELEME:
                $class = ElemeNotify::getInstance();
                break;
            case NotifyConfig::CHANNEL_YOUZAN:
                $class = YouzanNotify::getInstance();
                break;
            default:
                $this->error = '平台不存在';
                return false;
        }

        return $class;
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }
}