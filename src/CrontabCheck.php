<?php

namespace NsUtil;

class CrontabCheck {

    public function __construct() {
        
    }

    public static function check($crontabExpresion) {
        // Get current minute, hour, day, month, weekday
        $time = explode(' ', date('i G j n w', strtotime(date('c'))));
        // Split crontab by space
        $crontab = explode(' ', $crontabExpresion);
        // Foreach part of crontab
        foreach ($crontab as $k => &$v) {
            // Remove leading zeros to prevent octal comparison, but not if number is already 1 digit
            $time[$k] = preg_replace('/^0+(?=\d)/', '', $time[$k]);
            // 5,10,15 each treated as seperate parts
            $v = explode(',', $v);
            // Foreach part we now have
            foreach ($v as &$v1) {
                // Do preg_replace with regular expression to create evaluations from crontab
                $v1 = preg_replace(
                        // Regex
                        array(
                            // *
                            '/^\*$/',
                            // 5
                            '/^\d+$/',
                            // 5-10
                            '/^(\d+)\-(\d+)$/',
                            // */5
                            '/^\*\/(\d+)$/'
                        ),
                        // Evaluations
                        // trim leading 0 to prevent octal comparison
                        array(
                            // * is always true
                            'true',
                            // Check if it is currently that time, 
                            $time[$k] . '===\0',
                            // Find if more than or equal lowest and lower or equal than highest
                            '(\1<=' . $time[$k] . ' and ' . $time[$k] . '<=\2)',
                            // Use modulus to find if true
                            $time[$k] . '%\1===0'
                        ),
                        // Subject we are working with
                        $v1
                );
            }
            // Join 5,10,15 with `or` conditional
            $v = '(' . implode(' or ', $v) . ')';
        }
        // Require each part is true with `and` conditional
        $crontabReturned = implode(' and ', $crontab);
        // Evaluate total condition to find if true
        return eval('return ' . $crontabReturned . ';');
    }

}
