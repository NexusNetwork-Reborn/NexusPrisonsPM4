<?php
declare(strict_types=1);

namespace libs\utils;

class ReflectionUtils {

    /**
     * Used by the below functions to find properties and methods
     *
     * @param string $class_name The name of the starting class
     * @param string $name The name of the thing being searched for
     * @param string $method_name The method to be called for the list to search in
     *
     * @return string The name of the class
     */
    private static function resolveClass(string $class_name, string $name, string $method_name): string {
        $class = new \ReflectionClass($class_name);
        do {
            foreach($class->$method_name() as $compare) {
                if($compare->class === $class->getName() && $compare->name === $name) {
                    return $class->getName();
                }
            }
            $class = $class->getParentClass();
        }
        while($class);
        throw new \Exception("Unable to find class for {$name}");
    }

    /**
     * Search for the class name that a property is defined in. This is useful
     * as the reflection API needs that and not the class of the actual object.
     *
     * @param string $class_name The name of the starting class
     * @param string $property_name The name of the property to search for
     *
     * @return string The name of the owner class
     */
    private static function resolvePropertyClass(string $class_name, string $property_name): string {
        return static::resolveClass($class_name, $property_name, 'getProperties');
    }

    /**
     * Search for the class name that a method is defined in. This is useful
     * as the reflection API needs that and not the class of the actual object.
     *
     * @param string $class_name The name of the starting class
     * @param string $method_name The name of the method to search for
     *
     * @return string The name of the owner class
     */
    private static function resolveMethodClass(string $class_name, string $method_name): string {
        return static::resolveClass($class_name, $method_name, 'getMethods');
    }

    /**
     * Gets the value of an object property
     *
     * @param mixed $object The object to get the value from or the class name for static properties
     * @param string $property_name The name of the property
     *
     * @return mixed The value
     */
    public static function getProperty($object, string $property_name) {
        $class_name = (is_object($object)) ? get_class($object) : $object;
        $class_name = static::resolvePropertyClass($class_name, $property_name);
        $property = new \ReflectionProperty($class_name, $property_name);
        $property->setAccessible(true);
        if(is_object($object)) {
            return $property->getValue($object);
        }
        else {
            $class = $property->getDeclaringClass();
            $values = $class->getStaticProperties();
            return $values[$property_name];
        }
    }

    /**
     * Sets the value of an object property
     *
     * @param mixed $object The object to update or the name of the class for static properties
     * @param string $property_name The name of the property
     * @param mixed $value The new value to set
     *
     * @return void
     */
    public static function setProperty($object, string $property_name, $value) {
        $class_name = (is_object($object)) ? get_class($object) : $object;
        $class_name = static::resolvePropertyClass($class_name, $property_name);
        $property = new \ReflectionProperty($class_name, $property_name);
        $property->setAccessible(true);
        $property->setValue((is_object($object)) ? $object : null, $value);
    }

    /**
     * Gets the closure of a method that can then be called or passed to a function as a callback
     *
     * @param mixed $object The object or class name that the method is from
     * @param string $method_name The name of the method
     *
     * @return \Closure The function
     */
    public static function getMethod($object, string $method_name): \Closure {
        $class_name = (is_object($object)) ? get_class($object) : $object;
        $class_name = static::resolveMethodClass($class_name, $method_name);
        $method = new \ReflectionMethod($class_name, $method_name);
        $method->setAccessible(true);
        $target = (is_object($object)) ? $object : null;
        return $method->getClosure($target);
    }

    /**
     * Executes a method on a class or object. If a string is given for the first
     * parameter then it will be treated as a class name and the method will be
     * called statically.
     *
     * @param mixed $object The object or class name that the function is from
     * @param string $method_name The name of the method to be called
     * @param array $args The arguments to pass to the function
     *
     * @return mixed The value returned by the function
     */
    public static function callMethod($object, string $method_name, array $args = []) {
        return static::getMethod($object, $method_name)(...$args);
    }
}