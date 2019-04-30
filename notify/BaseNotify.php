<?php
/**
 * Created by PhpStorm.
 * User: zhangjunwei
 * Date: 2019-04-29
 * Time: 20:07
 */

namespace OrderTool;


class BaseNotify implements NotifyInterface
{
    public static $channelToStatus = [];

    protected static $_instance;

    protected $error = '';

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (empty(self::$_instance) || !self::$_instance instanceof static) {
            self::$_instance = new static();
        }
        return self::$_instance;
    }

    public function handle($type, $params)
    {
        // TODO: Implement handle() method.
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }
}