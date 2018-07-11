<?php

namespace Reaction\DI;

use Psr\Container\ContainerInterface;
use Reaction\DI\Exceptions\InvalidConfigException;
use Reaction\DI\Exceptions\NotInstantiableException;

/**
 * DI Container with extended functionality
 * @package Reaction\Dep
 */
interface ExtendedContainerInterface extends ContainerInterface
{
    /**
     * Returns a value indicating whether the given name corresponds to a registered singleton.
     * @param string $class class name, interface name or alias name
     * @param bool   $checkInstance whether to check if the singleton has been instantiated.
     * @return bool whether the given name corresponds to a registered singleton. If `$checkInstance` is true,
     * the method should return a value indicating whether the singleton has been instantiated.
     */
    public function hasSingleton($class, $checkInstance = false);

    /**
     * Registers a class definition with this container.
     *
     * For example,
     *
     * ```php
     * // register a class name as is. This can be skipped.
     * $container->set('Reaction\Db\Database');
     *
     * // register an interface
     * // When a class depends on the interface, the corresponding class
     * // will be instantiated as the dependent object
     * $container->set('Reaction\Mail\MailInterface', 'Reaction\Swiftmailer\Mailer');
     *
     * // register an alias name. You can use $container->get('foo')
     * // to create an instance of Connection
     * $container->set('foo', 'Reaction\Db\Database');
     *
     * // register a class with configuration. The configuration
     * // will be applied when the class is instantiated by get()
     * $container->set('Reaction\Db\Database', [
     *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     *
     * // register an alias name with class configuration
     * // In this case, a "class" element is required to specify the class
     * $container->set('db', [
     *     'class' => 'Reaction\Db\Database',
     *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     *
     * // register a PHP callable
     * // The callable will be executed when $container->get('db') is called
     * $container->set('db', function ($container, $params, $config) {
     *     return new \Reaction\Db\Database($config);
     * });
     * ```
     *
     * If a class definition with the same name already exists, it will be overwritten with the new one.
     * You may use [[has()]] to check if a class definition already exists.
     *
     * @param string $class class name, interface name or alias name
     * @param mixed  $definition the definition associated with `$class`. It can be one of the following:
     *
     * - a PHP callable: The callable will be executed when [[get()]] is invoked. The signature of the callable
     *   should be `function ($container, $params, $config)`, where `$params` stands for the list of constructor
     *   parameters, `$config` the object configuration, and `$container` the container object. The return value
     *   of the callable will be returned by [[get()]] as the object instance requested.
     * - a configuration array: the array contains name-value pairs that will be used to initialize the property
     *   values of the newly created object when [[get()]] is called. The `class` element stands for the
     *   the class of the object to be created. If `class` is not specified, `$class` will be used as the class name.
     * - a string: a class name, an interface name or an alias name.
     * @param array  $params the list of constructor parameters. The parameters will be passed to the class
     * constructor when [[get()]] is called.
     * @return $this the container itself
     * @throws InvalidConfigException
     */
    public function set($class, $definition = [], array $params = []);

    /**
     * Registers a class definition with this container and marks the class as a singleton class.
     *
     * This method is similar to [[set()]] except that classes registered via this method will only have one
     * instance. Each time [[get()]] is called, the same instance of the specified class will be returned.
     *
     * @param string $class class name, interface name or alias name
     * @param mixed  $definition the definition associated with `$class`. See [[set()]] for more details.
     * @param array  $params the list of constructor parameters. The parameters will be passed to the class
     * constructor when [[get()]] is called.
     * @return $this the container itself
     * @see set()
     * @throws InvalidConfigException
     */
    public function setSingleton($class, $definition = [], array $params = []);

    /**
     * Create new object instance
     * @param string $class
     * @param array $params
     * @param array $config
     * @return object
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    public function create($class, $params = [], $config = []);

    /**
     * Create new object instance or get if set in definitions
     * @param string $class
     * @param array $params
     * @param array $config
     * @return object
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    public function getOrCreate($class, $params = [], $config = []);

    /**
     * Removes the definition for the specified name.
     * @param string $class class name, interface name or alias name
     */
    public function clear($class);

    /**
     * Invoke a callback with resolving dependencies in parameters.
     *
     * This methods allows invoking a callback and let type hinted parameter names to be
     * resolved as objects of the Container. It additionally allow calling function using named parameters.
     *
     * For example, the following callback may be invoked using the Container to resolve the formatter dependency:
     *
     * ```php
     * $formatString = function($string, \Reaction\I18n\Formatter $formatter) {
     *    // ...
     * }
     * $container->invoke($formatString, ['string' => 'Hello World!']);
     * ```
     *
     * This will pass the string `'Hello World!'` as the first param, and a formatter instance created
     * by the DI container as the second param to the callable.
     *
     * @param callable $callback callable to be invoked.
     * @param array    $params The array of parameters for the function.
     * This can be either a list of parameters, or an associative array representing named function parameters.
     * @return mixed the callback return value.
     * @throws InvalidConfigException if a dependency cannot be resolved or if a dependency cannot be fulfilled.
     * @throws NotInstantiableException If resolved to an abstract class or an interface
     */
    public function invoke(callable $callback, $params = []);

    /**
     * {@inheritdoc}
     */
    public function getDefinitions();

    /**
     * Registers class definitions within this container.
     *
     * @param array $definitions array of definitions. There are two allowed formats of array.
     * The first format:
     *  - key: class name, interface name or alias name. The key will be passed to the [[set()]] method
     *    as a first argument `$class`.
     *  - value: the definition associated with `$class`. Possible values are described in
     *    [[set()]] documentation for the `$definition` parameter. Will be passed to the [[set()]] method
     *    as the second argument `$definition`.
     *
     * Example:
     * ```php
     * $container->setDefinitions([
     *     'Reaction\Web\Request' => 'app\components\Request',
     *     'Reaction\Web\Response' => [
     *         'class' => 'app\components\Response',
     *         'format' => 'json'
     *     ],
     *     'foo\Bar' => function () {
     *         $qux = new Qux;
     *         $foo = new Foo($qux);
     *         return new Bar($foo);
     *     }
     * ]);
     * ```
     *
     * The second format:
     *  - key: class name, interface name or alias name. The key will be passed to the [[set()]] method
     *    as a first argument `$class`.
     *  - value: array of two elements. The first element will be passed the [[set()]] method as the
     *    second argument `$definition`, the second one — as `$params`.
     *
     * Example:
     * ```php
     * $container->setDefinitions([
     *     'foo\Bar' => [
     *          ['class' => 'app\Bar'],
     *          [Instance::of('baz')]
     *      ]
     * ]);
     * ```
     *
     * @see ExtendedContainerInterface::set() to know more about possible values of definitions
     * @throws InvalidConfigException
     */
    public function setDefinitions(array $definitions);

    /**
     * Registers class definitions as singletons within this container by calling [[setSingleton()]].
     *
     * @param array $singletons array of singleton definitions. See [[setDefinitions()]]
     * for allowed formats of array.
     *
     * @see setDefinitions() for allowed formats of $singletons parameter
     * @see setSingleton() to know more about possible values of definitions
     * @throws InvalidConfigException
     */
    public function setSingletons(array $singletons);

    /**
     * Resolve class name from alias or definition
     * @param string|array|mixed $definition
     * @param string|array|mixed $parent
     * @return null|string
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    public function resolveClassName($definition, $parent = null);
}