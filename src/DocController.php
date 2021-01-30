<?php
declare (strict_types = 1);

namespace leruge;


class DocController
{
    /**
     * @var array 注释数据
     */
    private array $params = [];

    /**
     * @title 接口文档
     * @url /doc
     */
    public function index()
    {
        $config = [
            // 文档标准
            'swagger' => '2.0',
            'title' => '文档名称',
            'version' => '1.0.1',
            'debug' => true, // 打开调式模式则每次都生成一次文档
            'code_desc' => [], // code标识含义

            // 需要生成文档的控制
            'controller' => [],
        ];
        $config = array_replace_recursive($config, config('doc'));
        // 测试
        $controllerArray = $config['controller'];
        // 接口数据
        $apiUrlArray = [];
        foreach ($controllerArray as $controller) {
            try {
                $reflectionClass = new \ReflectionClass($controller);
            } catch (\ReflectionException $e) {
                continue;
            }
            $docClassBlock = $reflectionClass->getDocComment();
            $this->params = [];
            $docClassParse = $this->parse($docClassBlock);
            // 类注释解析失败，跳出本次循环，进行下一次
            if (!$docClassParse) {
                continue;
            }
            $tag = $this->params['title'] ?? $reflectionClass->getName();
            $methodArray = $reflectionClass->getMethods();
            // 方法注释
            foreach ($methodArray as $v) {
                $docMethodBlock = $v->getDocComment();
                $this->params = [];
                $docMethodParse = $this->parse($docMethodBlock);
                // 过滤掉不符合的param
                $this->params['param'] = $this->params['param'] ?? [];
                foreach ($this->params['param'] as $k1 => $v1) {
                    if (!is_array($v1)) {
                        unset($this->params[$k1]);
                    }
                }

                // 方法注释解析失败，跳出本次循环
                if (!$docMethodParse) {
                    continue;
                }
                // 方法注释中title、url、method都存在才使用这个注释，否则跳过
                if (!empty($this->params['title']) && !empty($this->params['url']) && !empty($this->params['method'])) {
                    $parameters = [];
                    foreach ($this->params['param'] as $k2 => $v2) {
                        if (!empty($v2['name']) && !empty($v2['type']) && !empty($v2['desc'])) {
                            $parameters[] = [
                                'name' => $v2['name'],
                                'type' => $v2['type'],
                                'required' => $v2['require'] ? true : false,
                                'description' => $v2['desc'],
                                'default' => $v2['default'] ?? null,
                                'in' => 'formData'
                            ];
                        }
                    }
                    // 过滤掉不符合的return并组装响应数据
                    $this->params['return'] = $this->params['return'] ?? [];
                    $resData = $this->parseResData($this->params['return']);
                    $apiUrlArray[$this->params['url']] = [
                        $this->params['method'] => [
                            'tags' => [$tag],
                            'summary' => $this->params['title'],
                            'parameters' => $parameters,
                            'responses' => [
                                'response' => [
                                    'description' => '响应结果',
                                    'schema' => [
                                        'properties' => [
                                            'code' => ['example' => $config['code_desc']],
                                            'msg' => ['example' => '提示信息'],
                                            'data' => [
                                                'properties' => $resData
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ];
                } else {
                    continue;
                }
            }
        }

        $swaggerData = [
            'swagger' => $config['swagger'],
            'info' => ['title' => $config['title'], 'version' => $config['version']],
            'securityDefinitions' => ['token' => ['type' => 'apiKey', 'name' => 'Authorization', 'in' => 'header']],
            'security' => [['token' => 'Bearer']],
            'paths' => $apiUrlArray
        ];
        if ($config['debug']) {
            $filename = public_path() . 'swagger' . DIRECTORY_SEPARATOR . 'swagger.json';
            file_put_contents($filename, json_encode($swaggerData));
            return redirect('/swagger/index.html');
        } else {
            return redirect('/swagger/index.html');
        }
    }

    /**
     * @param $return 响应注释
     */
    private function parseResData($return)
    {
        $resData = [];
        foreach ($return as $k3 => $v3) {
            if (!is_array($v3)) {
                unset($this->params[$k3]);
            } else {
                $key = key($v3);
                $value = $v3[$key];
                if (substr((string)$value, -2) == '@!') {
//                    halt($value); name 图片地址
                    if (!empty($this->params[$key])) {
                        if (is_array($this->params[$key])) {
                            $array = [];
                            foreach ($this->params[$key] as $k4 => $v4) {
                                // demo Demo@!
                                if (substr((string)$v4, -2) == '@!') {
                                    if (!empty($this->params[$k4])) {
                                        if (is_array($this->params[$k4])) {
                                            $array1 = [];
                                            foreach ($this->params[$k4] as $k5 => $v5) {
                                                $array1[$k5] = ['example' => $v5];
                                            }
                                            $array[$k4] = [
                                                'properties' => $array1
                                            ];
                                        } else {
                                            $array[$k4] = ['example' => $this->params[$k4]];
                                        }
                                    }
                                } elseif (substr($v4, -1) == '@') {
                                    if (!empty($this->params[$k4])) {
                                        if (is_array($this->params[$k4])) {
                                            $object = [];
                                            foreach ($this->params[$k4] as $k5 => $v5) {
                                                $object[$k5] = ['example' => $v5];
                                            }
                                            $array[$k4] = [
                                                'items' => ['properties' => $object]
                                            ];
                                        } else {
                                            $array[$k4] = [
                                                'items' => ['example' => $this->params[$k4]]
                                            ];
                                        }
                                    }
                                } else {
                                    $array[$k4] = ['example' => $v4];
                                }
                            }
                            $resData[$key] = [
                                'properties' => $array
                            ];
                        } else {
                            $resData[$key] = ['example' => $this->params[$key]];
                        }
                    }
                } elseif (substr((string)$value, -1) == '@') {
                    if (!empty($this->params[$key])) {
                        if (is_array($this->params[$key])) {
                            $object = [];
                            foreach ($this->params[$key] as $k4 => $v4) {
                                if (substr((string)$v4, -2) == '@!') {
                                    if (!empty($this->params[$k4])) {
                                        if (is_array($this->params[$k4])) {
                                            $array1 = [];
                                            foreach ($this->params[$k4] as $k5 => $v5) {
                                                $array1[$k5] = ['example' => $v5];
                                            }
                                            $array[$k4] = [
                                                'properties' => $array1
                                            ];
                                        } else {
                                            $array[$k4] = ['example' => $this->params[$k4]];
                                        }
                                    }
                                } elseif (substr($v4, -1) == '@') {
                                    if (!empty($this->params[$k4])) {
                                        if (is_array($this->params[$k4])) {
                                            $object = [];
                                            foreach ($this->params[$k4] as $k5 => $v5) {
                                                $object[$k5] = ['example' => $v5];
                                            }
                                            $array[$k4] = [
                                                'items' => ['properties' => $object]
                                            ];
                                        } else {
                                            $array[$k4] = [
                                                'items' => ['example' => $this->params[$k4]]
                                            ];
                                        }
                                    }
                                } else {
                                    $array[$k4] = ['example' => $v4];
                                }
                            }
                            $resData[$key] = [
                                'items' => ['properties' => $array]
                            ];
                        } else {
                            $resData[$key] = ['example' => $value];
                        }
                    }
                } else {
                    $resData[$key] = ['example' => $value];
                }
            }
        }
        return $resData;
    }

    /**
     * @param string $doc
     * @return bool 解析成功或者失败
     */
    private function parse($doc = '') {
        if (!$doc) {
            return false;
        }
        // 匹配符合格式的注释
        if (preg_match('#^/\*\*(.*)\*/#s', $doc, $comment) === false) {
            return false;
        }
        $comment = trim($comment[1]);
        // 获取每一个注释，放入数组中
        if (preg_match_all('#^\s*\*(.*)#m', $comment, $lines) === false) {
            return false;
        }
        return $this->parseLines($lines[1]);
    }

    /**
     * @param array $lines 每一个注释的数组
     * @return bool 解析成功或者失败
     */
    private function parseLines(array $lines) {
        try {
            foreach ($lines as $line) {
                $this->parseLine($line);
            }
        } catch (\Throwable $e) {
            return false;
        }
        return true;
    }

    /**
     * @param string $line 每一行注释
     */
    private function parseLine($line) {
        $line = trim($line);
        if (strpos($line, '@') === 0) {
            if (strpos($line, ' ') > 0) {
                $param = substr($line, 1, strpos($line, ' ') - 1);
                $value = substr($line, strlen($param) + 2);
            } else {
                $param = substr($line, 1);
                $value = '';
            }
            $this->setParam($param, $value);
        }
    }

    private function setParam($param, $value) {
        if ($param != 'title' && $param != 'url' && $param != 'method') {
            $value = $this->formatParam($value);
        }
        if($param == 'return' || $param == 'param'){
            $this->params[$param][] = $value;
        } else {
            $this->params[$param] =  $value;
        }
    }

    private function formatParam($string) {
        $string = $string . ' ';
        if(preg_match_all('/(\w+):(.*?)[\s\n]/s', $string, $matches)){
            $param = [];
            foreach ($matches[1] as $key => $value){
                $param[$matches[1][$key]] = $matches[2][$key];
            }
            return $param;
        } else {
            return trim($string);
        }
    }
}
