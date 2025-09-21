<?php

// 由于Blade模板引擎依赖的env()函数，早于thinkphp的助手文件被加载，导致thinkphp的env()函数失效。
// 因此先初始Blade的env()函数可以读取.env文件，替代thinkphp的env()函数。
// 目前只发现evn()函数重名。
try {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (\Exception $e) {
    echo PHP_EOL, 'Blade模板引擎不存在：', $e->getMessage(), PHP_EOL;
    echo '请手动删除配置文件：' . __FILE__, PHP_EOL;
}
return [];
