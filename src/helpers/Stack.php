<?php

/**
 * OnePlugin Media plugin for Craft CMS 3.x
 *
 * OnePlugin Media lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginmedia\helpers;

class Stack
{
    /**
     * @var array
     */
    public $stack = array();

    /**
     * @var int
     */
    public $count = 0;
    
    public function push($val)
    {
        $this->stack[$this->count] = $val;
        $this->count++;
    }
    
    public function pop()
    {
        if ($this->count > 0) {
            $this->count--;
            return $this->stack[$this->count];
        }

        return null;
    }
    
    public function last($n=1)
    {
        $key = $this->count - $n;
    		
        return array_key_exists($key,$this->stack) ? $this->stack[$key] : null;
    }
}
