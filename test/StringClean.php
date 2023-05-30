<?php

trait StringClean
{
    protected function tabAndEolRemove($value)
    {
        return str_replace([PHP_EOL, "\t"],'', $value);
    }
}

