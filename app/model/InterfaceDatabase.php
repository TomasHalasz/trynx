<?php


interface DatabaseAccessor
{
    function get($name): Nette\Database\Context;
}

