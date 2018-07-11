<?php

namespace Reaction\DI;

/**
 * Basic trait that can be used by other classes implementing Reaction\DI\InjectionPossibleInterface
 * @package Reaction\DI
 */
trait BaseInjectionPossibleTrait
{
    /**
     * Perform object(s) injection
     * @param array|object $objects
     */
    public function inject($objects)
    {
        if (!is_array($objects)) {
            $objects = [$objects];
        }
        $rules = $this->getInjectionRules();
        $rulesStrict = $this->getInjectionRulesStrict();
        foreach ($objects as $object) {
            $binding = $this->getRuleMatch($object, $rulesStrict, true);
            if ($binding === null) {
                $binding = $this->getRuleMatch($object, $rules);
            }
            if (is_string($binding)) {
                if ($this->isInjectionCall($binding)) {
                    $binding = substr($binding, 0, -2);
                    $this->{$binding}($object);
                } else {
                    $this->{$binding} = $object;
                }
            }
        }
    }

    /**
     * Injection rules (by implementing interface or parent class too).
     * You must return key => value array with class names and this object properties|methods to assign.
     * Order matter.
     *
     * ```
     *  return [
     *      'Reaction\RequestApplicationInterface' => 'app',
     *  ];
     * ```
     * or
     * ```
     *  return [
     *      'Reaction\RequestApplicationInterface' => 'setApp()',
     *  ];
     * ```
     *
     * @return array
     */
    protected function getInjectionRules()
    {
        return [];
    }

    /**
     * Injection rules (strict by object class name).
     * You must return key => value array with class names and this object properties|methods to assign.
     * Order matter.
     *
     * ```
     *  return [
     *      'Reaction\RequestApplication' => 'app',
     *  ];
     * ```
     * or
     * ```
     *  return [
     *      'Reaction\RequestApplication' => 'setApp()',
     *  ];
     * ```
     *
     * @return array
     */
    protected function getInjectionRulesStrict()
    {
        return [];
    }

    /**
     * Get matching rule binding
     * @param object $object Object to check (injection)
     * @param array  $rules  Rules to check
     * @param bool   $strict Perform strict check for class name
     * @return string|null
     */
    private function getRuleMatch($object, $rules = [], $strict = false)
    {
        if (empty($rules)) {
            return null;
        }
        $objectClass = get_class($object);
        foreach ($rules as $className => $binding) {
            $match = $strict ? $className === $objectClass : $object instanceof $className;
            if ($match) {
                return $binding;
            }
        }
        return null;
    }

    /**
     * Check that binding is the method to call
     * @param string $value
     * @return bool
     */
    private function isInjectionCall($value)
    {
        return strlen($value) > 2 && substr_compare($value, '()', -2, 2) === 0;
    }
}