<?php

namespace Reaction\DI;

use Reaction\Exceptions\InvalidConfigException;

/**
 * Service locator objects must implement this interface
 * @package Reaction\DI
 */
interface ServiceLocatorInterface
{
    /**
     * Returns a value indicating whether the locator has the specified component definition or has instantiated the component.
     * This method may return different results depending on the value of `$checkInstance`.
     *
     * - If `$checkInstance` is false (default), the method will return a value indicating whether the locator has the specified
     *   component definition.
     * - If `$checkInstance` is true, the method will return a value indicating whether the locator has
     *   instantiated the specified component.
     *
     * @param string $id component ID (e.g. `db`).
     * @param bool $checkInstance whether the method should check if the component is shared and instantiated.
     * @return bool whether the locator has the specified component definition or has instantiated the component.
     * @see set()
     */
    public function has($id, $checkInstance = false);

    /**
     * Returns the component instance with the specified ID.
     *
     * @param string $id component ID (e.g. `db`).
     * @param bool   $throwException whether to throw an exception if `$id` is not registered with the locator before.
     * @return \object|null the component of the specified ID. If `$throwException` is false and `$id`
     * is not registered before, null will be returned.
     * @throws InvalidConfigException if `$id` refers to a nonexistent component ID
     * @see has()
     * @see set()
     */
    public function get($id, $throwException = true);

    /**
     * Registers a component definition with this locator.
     *
     * For example,
     *
     * ```php
     * // a class name
     * $locator->set('cache', 'Reaction\Caching\FileCache');
     *
     * // a configuration array
     * $locator->set('db', [
     *     'class' => 'Reaction\Db\Connection',
     *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     *
     * // an anonymous function
     * $locator->set('cache', function ($params) {
     *     return new \Reaction\Caching\FileCache;
     * });
     *
     * // an instance
     * $locator->set('cache', new \Reaction\Caching\FileCache);
     * ```
     *
     * If a component definition with the same ID already exists, it will be overwritten.
     *
     * @param string $id component ID (e.g. `db`).
     * @param mixed $definition the component definition to be registered with this locator.
     * It can be one of the following:
     *
     * - a class name
     * - a configuration array: the array contains name-value pairs that will be used to
     *   initialize the property values of the newly created object when [[get()]] is called.
     *   The `class` element is required and stands for the the class of the object to be created.
     * - a PHP callable: either an anonymous function or an array representing a class method (e.g. `['Foo', 'bar']`).
     *   The callable will be called by [[get()]] to return an object associated with the specified component ID.
     * - an object: When [[get()]] is called, this object will be returned.
     *
     * @throws InvalidConfigException if the definition is an invalid configuration array
     */
    public function set($id, $definition);

    /**
     * Removes the component from the locator.
     * @param string $id the component ID
     */
    public function clear($id);

    /**
     * Returns the list of the component definitions or the loaded component instances.
     * @param bool $returnDefinitions whether to return component definitions instead of the loaded component instances.
     * @return array the list of the component definitions or the loaded component instances (ID => definition or instance).
     */
    public function getComponents($returnDefinitions = true);

    /**
     * Registers a set of component definitions in this locator.
     *
     * This is the bulk version of [[set()]]. The parameter should be an array
     * whose keys are component IDs and values the corresponding component definitions.
     *
     * For more details on how to specify component IDs and definitions, please refer to [[set()]].
     *
     * If a component definition with the same ID already exists, it will be overwritten.
     *
     * The following is an example for registering two component definitions:
     *
     * ```php
     * [
     *     'db' => [
     *         'class' => 'Reaction\Db\Database',
     *     ],
     *     'cache' => [
     *         'class' => 'Reaction\Cache\DbCache',
     *         'db' => 'db',
     *     ],
     * ]
     * ```
     *
     * @param array $components component definitions or instances
     */
    public function setComponents(array $components = []);

    /**
     * Load components after init (Function is called manually).
     * Returns a promise which will be resolved after all components are initialized
     * if components implementing ComponentInitBlockingInterface or immediately if not.
     * Warning: Initializes component one by one in given order
     * @return \Reaction\Promise\ExtendedPromiseInterface
     */
    public function loadComponents();
}