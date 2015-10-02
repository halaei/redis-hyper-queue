<?php

namespace Redis\HyperQueue;

class ArraySerialization
{
    public static function unserializeArray(array $items)
    {
        return array_map(
            function($item){
                return unserialize($item);
            },
            $items
        );
    }

    public static function serializeArray(array $items)
    {
        return array_map(
            function($item){
                return serialize($item);
            },
            $items
        );
    }
}