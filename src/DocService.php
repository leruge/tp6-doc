<?php
declare (strict_types = 1);

namespace leruge;

use think\Route;


class DocService
{
    public function boot(Route $route)
    {
        $route->get('doc', '\\leruge\\DocController@index');
        $docPath = public_path() . 'swagger' . DIRECTORY_SEPARATOR;
        if (!is_dir($docPath)) {
            mkdir($docPath, 0777, true);
            $source = __DIR__ . DIRECTORY_SEPARATOR . 'swagger' . DIRECTORY_SEPARATOR;
            $handle = dir($source);
            while($entry = $handle->read()) {
                if(($entry != '.')&&($entry != '..')){
                    if(is_file($source . $entry)){
                        copy($source . $entry, $docPath . $entry);
                    }
                }
            }
        }
    }
}