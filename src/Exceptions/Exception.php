<?php

namespace Reaction\DI\Exceptions;

use Psr\Container\ContainerExceptionInterface;

/**
 * Basic DI Container exception
 * @package Reaction\Dep\Exceptions
 */
class Exception extends \Reaction\Exceptions\Exception implements ContainerExceptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'DI Container exception';
    }
}