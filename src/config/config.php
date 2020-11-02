<?php
/**
 * @title 接口文档配置
 * @author Leruge
 * @email leruge@163.com
 * @qq 305530751
 */
return [
    // 文档标准
    'swagger' => '2.0', // 目前仅支持2.0
    'title' => '文档名称',
    'version' => '1.0.1',
    'debug' => true, // 打开调式模式则每次都生成一次文档关闭则不会生成，只会使用以前生成的，所以开发环境打开即可

    // code字段标识含义
    'code_desc' => [],

    // 需要生成文档的控制
    'controller' => [],
];
