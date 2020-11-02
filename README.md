# tp6-doc
swagger的接口文档注释太难写，封装一个好写注释的

## 安装
```php
composer require leruge/tp6-doc
```

## 使用说明
1. 下载完成以后会自动在config目录生成一个doc.php配置文件，如果没有请手动创建
```php
return [
    // 文档标准
    'swagger' => '2.0', // 目前仅支持2.0
    'title' => '文档名称',
    'version' => '1.0.1',
    'debug' => true, // 打开调式模式则每次都生成一次文档

    // code字段标识含义
    'code_desc' => [
        1 => '请求成功',
        0 => '请求失败'
    ],

    // 需要生成文档的控制
    'controller' => [
        \app\controller\Foo::class,
    ],
];
```

## 注释说明
```php
/**
     * @title 接口文档
     * @url /api/demo
     * @method post
     *
     * @param name:phone type:string require:1 desc:手机号 default:17000000001
     *
     * @return count:数量（单个字段）
     * @return user_info:一条数据@!
     * @user_info id:用户ID nickname:昵称
     * @return article_list:文章列表，多条数据@
     * @article_list is:文章ID title:标题
     */
```

## 类和方法
1. 接口以类为单位进行自动分组
1. 类的注释只有title
1. 方法的注释必须有title、url、method，参数param和响应值return，看你自己需求

## 访问地址
1. 访问地址为：你的域名+doc