<?php

namespace NsUtil;

class Html {

    public function __construct() {
        
    }

    public static function hint($text, $position = "top") {
        return ' data-toggle="tooltip" data-placement="' . $position . '" data-html="true" title="' . $text . '" ';
    }

}
