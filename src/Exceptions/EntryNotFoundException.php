<?php

namespace Reaction\DI\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Basic exception thrown when entry not found in DI container
 * @package Reaction\Dep\Exceptions
 */
class EntryNotFoundException extends Exception implements NotFoundExceptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Entry not found in container';
    }
}