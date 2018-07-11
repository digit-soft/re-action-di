<?php

namespace Reaction\DI;

use Closure;
use Reaction;
use Reaction\Base\ComponentInitBlockingInterface;
use Reaction\Helpers\ArrayHelper;
use Reaction\Base\Component;
use Reaction\Exceptions\InvalidConfigException;
use Reaction\Helpers\ReflectionHelper;

/**
 * ServiceLocator implements a [service locator](http://en.wikipedia.org/wiki/Service_locator_pattern).
 *
 * To use ServiceLocator, you first need to register component IDs with the corresponding component
 * definitions with the locator by calling [[set()]] or [[setComponents()]].
 * You can then call [[get()]] to retrieve a component with the specified ID. The locator will automatically
 * instantiate and configure the component according to the definition.
 *
 * For example,
 *
 * ```php
 * $locator = new \Reaction\DI\ServiceLocator;
 * $locator->setComponents([
 *     'db' => [
 *         'class' => 'Reaction\Db\Database',
 *         'dsn' => 'sqlite:path/to/file.db',
 *     ],
 *     'cache' => [
 *         'class' => 'Reaction\Cache\DbCache',
 *         'db' => 'db',
 *     ],
 * ]);
 *
 * $db = $locator->get('db');  // or $locator->db
 * $cache = $locator->get('cache');  // or $locator->cache
 * ```
 *
 * For more details and usage information on ServiceLocator, see the [guide article on service locators](guide:concept-service-locator).
 *
 * @property array $components The list of the component definitions or the loaded component instances (ID =>
 * definition or instance).
 * @property bool  $componentsAutoloadEnabled
 */
class ServiceLocator extends Component implements ServiceLocatorInterface
{
    /**
     * @var array shared component instances indexed by their IDs
     */
    protected $_components = [];
    /**
     * @var array component definitions indexed by their IDs
     */
    protected $_definitions = [];
    /**
     * @var bool
     */
    protected $_checkComponentsAutoload;

    /**
     * Getter magic method.
     * This method is overridden to support accessing components like reading properties.
     * @param string $name component or property name
     * @return mixed the named property value
     * @throws InvalidConfigException
     * @throws \Reaction\Exceptions\UnknownPropertyException
     */
    public function __get($name)
    {
        if ($this->has($name)) {
            return $this->get($name);
        }

        return parent::__get($name);
    }

    /**
     * Checks if a property value is null.
     * This method overrides the parent implementation by checking if the named component is loaded.
     * @param string $name the property name or the event name
     * @return bool whether the property value is null
     */
    public function __isset($name)
    {
        if ($this->has($name)) {
            return true;
        }

        return parent::__isset($name);
    }

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
    public function has($id, $checkInstance = false)
    {
        return $checkInstance ? isset($this->_components[$id]) : isset($this->_definitions[$id]);
    }

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
    public function get($id, $throwException = true)
    {
        if (isset($this->_components[$id])) {
            return $this->_components[$id];
        }

        if (isset($this->_definitions[$id])) {
            $definition = $this->_definitions[$id];
            if (is_object($definition) && !$definition instanceof Closure) {
                return $this->_components[$id] = $definition;
            }
            $params = [];
            if (is_array($definition) && isset($definition[0])) {
                $config = is_array($definition[0]) ? $definition[0] : ['class' => $definition[0]];
                unset($definition[0]);
                if (isset($definition[1])) {
                    $params = (array)$definition[1];
                    unset($definition[1]);
                }
                $definition = ArrayHelper::merge($definition, $config);
            }
            return $this->_components[$id] = Reaction::create($definition, $params);
        } elseif ($throwException) {
            throw new InvalidConfigException("Unknown component ID: $id");
        }

        return null;
    }

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
    public function set($id, $definition)
    {
        unset($this->_components[$id]);

        if ($definition === null) {
            unset($this->_definitions[$id]);
            return;
        }

        //Extract definition from DI Definition
        if ($definition instanceof Definition) {
            $definition = $definition->dumpArrayDefinition();
        }

        if (is_object($definition) || is_callable($definition, true)) {
            // an object, a class name, or a PHP callable
            $this->_definitions[$id] = $definition;
        } elseif (is_array($definition)) {
            // a configuration array
            if (isset($definition['class'])) {
                $this->_definitions[$id] = $definition;
            } else {
                throw new InvalidConfigException("The configuration for the \"$id\" component must contain a \"class\" element.");
            }
        } else {
            throw new InvalidConfigException("Unexpected configuration type for the \"$id\" component: " . gettype($definition));
        }
        //Check components autoloading possibility
        if ($this->componentsAutoloadEnabled) {
            $this->checkComponentAutoload($id);
        }
    }

    /**
     * Removes the component from the locator.
     * @param string $id the component ID
     */
    public function clear($id)
    {
        unset($this->_definitions[$id], $this->_components[$id]);
    }

    /**
     * Returns the list of the component definitions or the loaded component instances.
     * @param bool $returnDefinitions whether to return component definitions instead of the loaded component instances.
     * @return array the list of the component definitions or the loaded component instances (ID => definition or instance).
     */
    public function getComponents($returnDefinitions = true)
    {
        return $returnDefinitions ? $this->_definitions : $this->_components;
    }

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
     *         'class' => 'Reaction\Caching\DbCache',
     *         'db' => 'db',
     *     ],
     * ]
     * ```
     *
     * @param array $components component definitions or instances
     */
    public function setComponents(array $components = [])
    {
        $this->sortComponentByDependencies($components);
        foreach ($components as $id => $component) {
            $this->set($id, $component);
        }
    }

    /**
     * Load components after init (Function is called manually).
     * Returns a promise which will be resolved after all components are initialized
     * if components implementing ComponentInitBlockingInterface or immediately if not.
     * Warning: Initializes component one by one in given order
     * @return Reaction\Promise\ExtendedPromiseInterface
     */
    public function loadComponents() {
        $promises = [];
        foreach ($this->_definitions as $componentName => $definition) {
            if (isset($this->_components[$componentName])) {
                $component = $this->_components[$componentName];
            } else {
                $className = Container::getDefaultContainer()->resolveClassName($definition);
                if (null === $className || !ReflectionHelper::isImplements($className, 'Reaction\Base\ComponentAutoloadInterface')) {
                    continue;
                }
                /** @var Component $component */
                $component = $this->get($componentName);
            }
            if ($component instanceof ComponentInitBlockingInterface && !$component->isInitialized()) {
                $promises[] = new Reaction\Promise\LazyPromise(function() use ($component) {
                    return $component->initComponent()->then(null, function() { return true; });
                });
                if (Reaction::isDebug()) {
                    Reaction::$app->loop->addTimer(3, function() use ($component, $componentName) {
                        if (!$component->isInitialized()) {
                            Reaction::error("Component '{name}' ({className}) initialization is to long (more then 3 sec.)", [
                                'name' => $componentName,
                                'className' => get_class($component),
                            ]);
                        }
                    });
                }
            }
        }
        if (!empty($promises)) {
            return \Reaction\Promise\allInOrder($promises);
        } else {
            return \Reaction\Promise\resolve(true);
        }
    }

    /**
     * Check for component auto loading possibility
     * @param string $componentName
     * @return null|object
     */
    protected function checkComponentAutoload($componentName) {
        if (!isset($this->_definitions[$componentName]) || isset($this->_components[$componentName])) {
            return null;
        }
        $className = Container::getDefaultContainer()->resolveClassName($this->_definitions[$componentName]);
        if (null !== $className && ReflectionHelper::isImplements($className, 'Reaction\Base\ComponentAutoloadInterface')) {
            $component = $this->get($componentName);
            return $component;
        }
        return null;
    }

    /**
     * Check that components autoloading is enabled
     * @return bool
     */
    protected function getComponentsAutoloadEnabled() {
        if (!isset($this->_checkComponentsAutoload)) {
            $this->_checkComponentsAutoload = $this instanceof ServiceLocatorAutoloadInterface;
        }
        return $this->_checkComponentsAutoload;
    }

    /**
     * Sort components by their dependencies. Used for ComponentInitBlockingInterface loading
     * @param array $components
     * @see setComponents()
     * @see loadComponents()
     */
    protected function sortComponentByDependencies(&$components = [])
    {
        $dependencies = [];
        foreach ($components as $id => $definition) {
            $dependency = is_array($definition) ? ArrayHelper::remove($components[$id], 'dependsOn', []) : [];
            if (empty($dependency)) {
                continue;
            }
            $dependencies[$id] = (array)$dependency;
        }
        uksort($components, function($a, $b) use ($dependencies) {
            $aDepends = isset($dependencies[$a]) && in_array($b, $dependencies[$a]);
            $bDepends = isset($dependencies[$b]) && in_array($a, $dependencies[$b]);
            if ($aDepends && $bDepends) {
                $class = get_called_class();
                throw new InvalidConfigException("Circular dependency detected between '$a' and '$b' in '$class'");
            } elseif ($aDepends) {
                return 1;
            } elseif ($bDepends) {
                return -1;
            } else {
                return 0;
            }
        });
    }
}
