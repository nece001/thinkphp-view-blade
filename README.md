# thinkphp视图的blade模板引擎驱动

本包可以让thinkphp的视图使用blade模板引擎

# 重要说明

因为blade的依赖包Dotenv，助手函数文件早于thinkphp的助手函数加载，导致thinkphp的evn()未正常加载，所以使用config/blade.php初始了Dotenv的env()函数，代替了thinkphp的。

config/blade.php只是为了初始化Dotenv的env()函数，其他配置都在thinkphp的配置文件config/view.php中。

# 安装
```bash
composer require nece001/thinkphp-view-blade
```

# 配置

在thinkphp的配置文件config/view.php中添加如下配置：
```php
<?php
// +----------------------------------------------------------------------
// | 模板设置
// +----------------------------------------------------------------------

return [
    // 模板引擎类型使用Think
    'type'          => 'Blade',
    // 默认模板渲染规则 1 解析为小写+下划线 2 全部转换小写 3 保持操作方法
    'auto_rule'     => 1,
    // 模板目录名
    'view_dir_name' => 'view',
    // 模板后缀
    'view_suffix'   => 'html',
    // 编译缓存路径
    'cache_path'    => app()->getRuntimePath() . 'blade' . DIRECTORY_SEPARATOR,
    // 是否调试模式，调试模式下不存编译缓存
    'debug'      => env('APP_DEBUG', false),
];
```

# 文档

[Blade模板引擎文档](https://laravel.com/docs/12.x/blade)