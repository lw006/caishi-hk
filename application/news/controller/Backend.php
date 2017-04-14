<?php
namespace app\news\controller;

class Backend extends \app\admin\controller\Base
{
    // 方法黑名单
    protected static $blacklist = [];

    protected function filter(&$map)
    {
        if ($this->request->param("title")) {
            $map['title'] = ["like", "%" . $this->request->param("title") . "%"];
        }
        if ($this->request->param("content")) {
            $map['content'] = ["like", "%" . $this->request->param("content") . "%"];
        }
    }
}
