<?php

namespace Redis\HyperQueue;

class PriorityItem
{
    /**
     * @var mixed the data of the item
     */
    public $value;

    /**
     * @var float the lower, the score the higher the priority, the sooner item will be popped
     */
    public $score;

    /**
     * @var int unique id of the item (generated on push)
     */
    public $id;

    function __construct($value, $score = 0, $id = null)
    {
        $this->value = $value;

        if ($score instanceof \DateTime) {
            $score = $score->getTimestamp();
        }

        $this->score = $score;
        $this->id = $id;
    }
}