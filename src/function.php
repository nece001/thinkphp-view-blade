<?php

use think\facade\Route;
use think\facade\Request;
use think\route\Url as UrlBuild;

if (!function_exists('route')) {
    /**
     * Url生成
     * @param string      $url    路由地址
     * @param array       $vars   变量
     * @param bool|string $suffix 生成的URL后缀
     * @param bool|string $domain 域名
     * @return UrlBuild
     */
    function route(string $url = '', array $vars = [], $suffix = true, $domain = false): UrlBuild
    {
        return Route::buildUrl($url, $vars)->suffix($suffix)->domain($domain);
    }
}

if (!function_exists('csrf_field')) {
    /**
     * 获取Token令牌
     * @param string $name 令牌名称
     * @param mixed  $type 令牌生成方法
     * @return string
     */
    function csrf_field(string $name = '__token__', string $type = 'md5'): string
    {
        return Request::buildToken($name, $type);
    }
}

if (!function_exists('method_field')) {
    /**
     * 获取Token令牌
     * @param string $name 令牌名称
     * @param mixed  $type 令牌生成方法
     * @return string
     */
    function method_field(string $name = ''): string
    {
        if ($name) {
            return '<input type="hidden" name="_method" value="' . $name . '">';
        }
        return '';
    }
}
