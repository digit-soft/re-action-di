<?php

namespace Reaction\DI;

/**
 * Interface InjectionPossibleInterface.
 * Classes implementing this interface can process outer data injections
 * @package Reaction\DI
 */
interface InjectionPossibleInterface
{
    /**
     * Perform objects injection
     * @param array|object $objects
     */
    public function inject($objects);
}