<?php

namespace think\view\driver;

use think\contract\TemplateHandlerInterface;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Compilers\BladeCompiler;
use think\App;

/**
 * Blade模板引擎
 * 配置文件：view.php的type设置为："\Nece\Gears\Infrastructure\Blade"
 *
 * @author nece001@163.com
 * @create 2025-09-18 22:53:56
 */
class Blade implements TemplateHandlerInterface
{
    private $app;
    private $blade;
    private $config = [];

    public function __construct(App $app, array $config)
    {
        $this->app = $app;
        $this->config($config);
    }

    public function addNamespace($name, $path)
    {
        $this->blade->getFinder()->addNamespace($name, $path);
    }

    /**
     * 根据auto_rule配置转换模板名称
     * @param string $template
     * @return string
     */
    private function transformTemplateName(string $template): string
    {
        // 获取auto_rule配置值，默认为1
        $autoRule = $this->getConfig('auto_rule') ?? 1;

        // 如果auto_rule为3，则保持原样
        if ($autoRule === 3) {
            return $template;
        }

        // 检查是否包含控制器/操作方法格式，如'index/index'
        if (strpos($template, DIRECTORY_SEPARATOR) !== false || strpos($template, '/') !== false) {
            $parts = explode('/', str_replace(DIRECTORY_SEPARATOR, '/', $template));
            $action = array_pop($parts);

            // 根据auto_rule转换操作方法名
            if ($autoRule === 1) {
                // 转换为小写+下划线格式（驼峰转下划线）
                $action = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $action));
            } else if ($autoRule === 2) {
                // 全部转换为小写
                $action = strtolower($action);
            }

            $parts[] = $action;
            return implode(DIRECTORY_SEPARATOR, $parts);
        }

        return $template;
    }

    /**
     * 检测是否存在模板文件
     * @param  string $template 模板文件或者模板规则
     * @return bool
     */
    public function exists(string $template): bool
    {
        try {
            // 应用模板名称转换规则
            $transformedTemplate = $this->transformTemplateName($template);
            return $this->blade->exists($transformedTemplate);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 渲染模板文件
     * @param  string $template 模板文件
     * @param  array  $data 模板变量
     * @return void
     */
    public function fetch(string $template, array $data = []): void
    {
        // 应用模板名称转换规则
        $transformedTemplate = $this->transformTemplateName($template);
        $content = $this->blade->make($transformedTemplate, $data)->render();
        echo $content;
    }

    /**
     * 渲染模板内容
     * @param  string $content 模板内容
     * @param  array  $data 模板变量
     * @return void
     */
    public function display(string $content, array $data = []): void
    {
        // 生成一个临时模板名称
        $tempViewName = md5($content) . '.blade.php';

        // 获取缓存目录
        $cachePath = $this->config['cache_path'] ?? $this->app->getRuntimePath() . 'blade' . DIRECTORY_SEPARATOR;
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }

        // 将内容写入临时文件
        $tempFilePath = $cachePath . $tempViewName;
        file_put_contents($tempFilePath, $content);

        try {
            // 渲染临时模板
            echo $this->blade->make($tempViewName, $data)->render();
        } finally {
            // 清理临时文件
            if (file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }
        }
    }

    /**
     * 配置模板引擎
     * @param  array $config 参数
     * @return void
     */
    public function config(array $config): void
    {
        $this->config = array_merge($this->config, $config);
        $this->initialize();
    }

    /**
     * 获取模板引擎配置
     * @param  string $name 参数名
     * @return mixed
     */
    public function getConfig(string $name)
    {
        return $this->config[$name] ?? null;
    }

    /**
     * 初始化Blade引擎
     */
    protected function initialize()
    {
        $filesystem = new Filesystem();

        // 创建引擎解析器
        $resolver = new EngineResolver();

        // 获取缓存目录
        $cachePath = $this->config['cache_path'] ?? $this->app->getRuntimePath() . 'blade';
        // print_r($this->config);exit;

        // 创建Blade编译器
        $compiler = new BladeCompiler($filesystem, $cachePath);
        // if ($this->config['debug']) {
        //     $compiler->setCachePath(null);
        // }

        // 注册PHP引擎 - 用于处理.php文件
        $resolver->register('php', function () use ($filesystem) {
            return new PhpEngine($filesystem);
        });

        // 注册Blade引擎 - 用于处理.blade.php文件
        $resolver->register('blade', function () use ($compiler) {
            return new CompilerEngine($compiler);
        });

        // 获取视图配置中的存放视图文件所在的目录
        $viewPaths = [];

        // 获取view_dir_name配置，默认为'view'
        $viewDirName = $this->getConfig('view_dir_name') ?? 'view';

        // 获取基础视图目录
        $baseViewPath = $this->app->getRootPath() . $viewDirName . DIRECTORY_SEPARATOR;
        if (is_dir($baseViewPath)) {
            $viewPaths[] = $baseViewPath;
        }

        // 添加模块视图目录（如果是多模块应用）
        $appPath = $this->app->getAppPath();
        $modules = $this->app->config->get('app.app_map', []);

        if (!empty($modules)) {
            foreach ($modules as $module) {
                $moduleViewPath = $appPath . $module . DIRECTORY_SEPARATOR . $viewDirName . DIRECTORY_SEPARATOR;
                if (is_dir($moduleViewPath)) {
                    $viewPaths[] = $moduleViewPath;
                }
            }
        } else {
            // 单模块应用的视图目录
            $moduleViewPath = $appPath . $viewDirName . DIRECTORY_SEPARATOR;
            if (is_dir($moduleViewPath)) {
                $viewPaths[] = $moduleViewPath;
            }
        }

        // 获取视图配置的扩展名
        $viewSuffix = isset($this->config['view_suffix']) ? $this->config['view_suffix'] : '';
        if ($viewSuffix) {
            $extensions = explode(',', $viewSuffix);
        }

        // 创建视图查找器
        $finder = new FileViewFinder($filesystem, $viewPaths, $extensions);

        // 创建事件调度器
        $dispatcher = new \Illuminate\Events\Dispatcher();

        // 创建Blade工厂
        $this->blade = new Factory($resolver, $finder, $dispatcher);

        // 把配置中给出的扩展名，定义成需要使用CompilerEngine引擎处理
        foreach ($extensions as $extension) {
            $this->blade->addExtension($extension, 'blade');
        }

        // 添加共享数据
        $this->blade->share('app', $this->app);
    }
}
