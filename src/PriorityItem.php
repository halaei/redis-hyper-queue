<?php

namespace Redis\HyperQueue;

class PriorityItem
{
    /**
     * @var mixed the data of the item
     */
    public $value;

    /**
     * @var float the lower the niceness the higher the priority
     */
    public $niceness;

    /**
     * @var int unique id of the item (generated on push)
     */
    public $id;

    function __construct($value, $niceness = 0, $id = null)
    {
        $this->value = $value;
        $this->niceness = $niceness;
        $this->id = $id;
    }
}