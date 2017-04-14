<?php
namespace app\news\validate;

use think\Validate;

class Backend extends Validate
{
    protected $rule = [
        "title|标题" => "require",
        "content|内容" => "require",
    ];
}
