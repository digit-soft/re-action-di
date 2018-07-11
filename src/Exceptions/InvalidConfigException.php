<?php

namespace Reaction\DI\Exceptions;

use Psr\Container\ContainerExceptionInterface;

/**
 * {@inheritdoc}
 */
class InvalidConfigException extends \Reaction\Exceptions\InvalidConfigException implements ContainerExceptionInterface
{
}