<?php

namespace Collection;

use ReflectionClass;
use RuntimeException;

/**
 * Class ArrayList
 * @package Collection
 */
class ArrayList implements Lists
{

    /** @const int */
    const NOT_FOUND = -1;
    /** @var array */
    private $array = [];
    /** @var int */
    private $size = 0;
    /** @var string */
    protected $type = null;

    /**
     * ArrayList constructor.
     * @param array $elements
     */
    public function __construct($elements = [])
    {
        $this->addAll($elements);
    }

    public function add($element)
    {
        $this->checkType($element);
        $this->array[$this->size] = $element;
        ++$this->size;
    }

    public function addAt($index, $element)
    {
        $this->checkType($element);
        if (isset($this->array[$index])) {
            $pos = $this->size - 1;
            while ($pos >= $index) {
                $this->array[$pos + 1] = $this->array[$pos];
                --$pos;
            }
            ++$this->size;
        } else {
            for ($pos = $this->size; $pos < $index; $pos++) {
                $this->array[$pos] = null;
            }
            $this->size = $index + 1;
        }
        $this->array[$index] = $element;
    }

    public function addAll($elements)
    {
        foreach ($elements as $element) {
            $this->add($element);
        }
    }

    public function addAllAt($index, $elements)
    {
        $pos = $index;
        foreach ($elements as $element) {
            $this->addAt($pos, $element);
            ++$pos;
        }
    }

    public function get($index)
    {
        return $this->array[$index];
    }

    public function remove($element)
    {
        $index = $this->indexOf($element);
        if ($index === self::NOT_FOUND) return;
        $this->removeAt($index);
    }

    public function removeAt($index)
    {
        if ($index >= $this->size) {
            $message = "Trying to delete an element that doesn't exists. ";
            $message .= "Index: " . $index . ", Size: " . $this->size . ".";
            throw new RuntimeException($message);
        }
        for ($i = $index; $i < $this->size - 1; $i++) {
            $this->array[$i] = $this->array[$i + 1];
        }
        unset($this->array[$this->size - 1]);
        $pos = $this->size - 2;
        $continue = true;
        while ($pos >= 0 && $continue) {
            if ($this->array[$pos] == null) {
                unset($this->array[$pos]);
            } else {
                $continue = false;
            }
            --$pos;
        }
        if (count($this->array) === 0) {
            $this->size = 0;
        } else {
            $this->size = array_reverse(array_keys($this->array))[0] + 1;
        }
    }

    public function clear()
    {
        $this->array = [];
        $this->size = 0;
    }

    public function size()
    {
        return $this->size;
    }

    public function isEmpty()
    {
        return $this->size === 0;
    }

    public function toArray()
    {
        return $this->array;
    }

    public function containts($element)
    {
        foreach ($this->array as $item) {
            $equalityMethod = true;
            if (!($item instanceof Equality) || !($element instanceof Equality)) {
                $equalityMethod = false;
            }
            if ($equalityMethod) {
                if ($element->equals($item)) {
                    return true;
                }
            } else {
                if ($element === $item) {
                    return true;
                }
            }
        }
        return false;
    }

    public function indexOf($element)
    {
        foreach ($this->array as $index => $item) {
            $equalityMethod = true;
            if (!($item instanceof Equality) || !($element instanceof Equality)) {
                $equalityMethod = false;
            }
            if ($equalityMethod) {
                if ($element->equals($item)) {
                    return intval($index);
                }
            } else {
                if ($element === $item) {
                    return intval($index);
                }
            }
        }
        return ArrayList::NOT_FOUND;
    }

    /**
     * @param callable $comparator
     * @param bool $ascendent
     */
    public function sort($comparator = null, $ascendent = true)
    {
        if ($comparator === null) {
            if (gettype($this->type) !== 'object') {
                throw new RuntimeException('ArrayList.sort must receive a Comparator or a callable instance with basic types.');
            } else if (!($this->type instanceof Comparable)) {
                throw new RuntimeException('ArrayList.sort must receive a Comparator or a callable instance or implement Comparable.');
            }
            usort($this->array, function(Comparable $o1, Comparable $o2) {
                return $o1->compareTo($o2);
            });
            if (!$ascendent) {
                $this->array = array_reverse($this->array);
            }
            return;
        }
        if (gettype($comparator) !== 'object') {
            if (!($comparator instanceof Comparator) || get_class($comparator) !== 'Closure') {
                throw new RuntimeException('ArrayList.sort must receive a Comparator or a callable instance.');
            }
        }
        if ($comparator instanceof Comparator) {
            usort($this->array, function($o1, $o2) use ($comparator) {
                return $comparator->compareTo($o1, $o2);
            });
        } else {
            usort($this->array, $comparator);
        }
        if (!$ascendent) {
            $this->array = array_reverse($this->array);
        }
    }

    public function forEachDo(callable $callable)
    {
        array_walk($this->array, $callable);
    }

    public function filter(callable $callable)
    {
        return new ArrayList(array_filter($this->array, $callable));
    }

    public function map(callable $callable)
    {
        return new ArrayList(array_map($callable, $this->array));
    }

    public function reduce(callable $callable, $initialValue)
    {
        return array_reduce($this->array, $callable, $initialValue);
    }

    private function checkType($element) {
        if ($element === null) return;
        if ($this->type === null) {
            $this->type = gettype($element);
            if ($this->type === 'object') {
                $className = get_class($element);
                $reflectionClass = new ReflectionClass($className);
                $this->type = $reflectionClass->newInstanceWithoutConstructor();
            }
        }
        if (gettype($element) === 'object') {
            if (!($element instanceof $this->type)) {
                $message = "All elements on the List must be type " . get_class($this->type);
                $message .= ". Trying to add " . get_class($element) . ".";
                throw new RuntimeException($message);
            }
        } else if (gettype($element) !== $this->type) {
            $message = "All elements on the List must be type " . get_class($this->type);
            $message .= ". Trying to add " . gettype($element) . ".";
            throw new RuntimeException($message);
        }
    }

}
