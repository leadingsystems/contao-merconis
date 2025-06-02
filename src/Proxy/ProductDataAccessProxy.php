<?php

namespace LeadingSystems\MerconisBundle\Proxy;

use ArrayAccess;
use Countable;
use Iterator;
use Merconis\Core\ls_shop_product;
use Merconis\Core\ls_shop_variant;

// use OutOfBoundsException; // Uncomment if choosing to throw exceptions for current() when invalid

class ProductDataAccessProxy implements ArrayAccess, Iterator, Countable
{
    /**
     * The array being proxied, passed by reference.
     * @var array
     */
    private array $data;

    /**
     * True copy of the array being proxied, NOT a reference.
     * This array is used to check whether the referenced data array has been changed.
     * @var array
     */
    private array $originalData;

    /**
     * A snapshot of keys from the proxied array, used for iteration.
     * This list is established when `rewind()` is called (e.g., at the start of a `foreach` loop)
     * and remains fixed for the duration of that iteration sequence. Modifications to the
     * underlying data (either via this proxy or externally) will be reflected by `offsetGet()`
     * and `current()`, and `valid()` will ensure that stale keys are not accessed.
     * @var array<int, string|int> An indexed array of keys from the data.
     */
    private array $keys;
    private ls_shop_variant|ls_shop_product $productOrVariant;

    /**
     * Constructor.
     *
     * @param array &$arr The array to be proxied. It is taken by reference,
     *                    meaning external modifications to the original array
     *                    will be reflected in this proxy.
     */
    public function __construct(array &$arr, ls_shop_product|ls_shop_variant &$productOrVariant)
    {
        $this->data = &$arr;
        $this->originalData = $arr;
        // Initialize keys and iterator's internal pointer for $this->keys.
        // rewind() is the designated method for this setup.
        $this->rewind();
        $this->productOrVariant = $productOrVariant;
    }

    // ArrayAccess implementations

    /**
     * Checks if an offset exists.
     * @param mixed $offset An offset to check for.
     * @return bool True on success or false on failure.
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    /**
     * Retrieves an offset.
     * @param mixed $offset The offset to retrieve.
     * @return mixed Can return all value types.
     */
    public function offsetGet(mixed $offset): mixed
    {
        if (isset($this->productOrVariant->modifiedDataKeys[$offset])) {
            $this->productOrVariant->loadCustomizer();

            /*
             * The value of a data offset might have been changed before but than changed back to the original value.
             * In this case, we don't want access to this data offset to trigger loading the customizer anymore.
             */
            if ($this->data[$offset] === $this->originalData[$offset]) {
                unset($this->productOrVariant->modifiedDataKeys[$offset]);
            }
        }
        return $this->data[$offset] ?? null;
    }

    /**
     * Assigns a value to the specified offset.
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value The value to set.
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->productOrVariant->modifiedDataKeys[$offset] = true;
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
        // Note: $this->keys is NOT updated here.
        // This ensures that an ongoing iteration (e.g., a foreach loop)
        // operates on a consistent snapshot of keys taken at the start of the loop (via rewind()).
        // New keys added during an iteration will not be visited in that same loop.
        // This behavior is common for many iterators when the underlying collection is modified.
    }

    /**
     * Unsets an offset.
     * @param mixed $offset The offset to unset.
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
        // Note: $this->keys is NOT updated here.
        // If a key that was part of the current iteration's key snapshot is unset,
        // the `valid()` method will correctly identify it as no longer valid during iteration,
        // and the loop will skip over it or terminate as appropriate.
    }

    // Iterator implementations

    /**
     * Rewinds the Iterator to the first element.
     * This is called at the beginning of a `foreach` loop.
     * It rebuilds the list of keys ($this->keys) to iterate over based on the current state of $this->data.
     * @return void
     */
    public function rewind(): void
    {
        $this->keys = array_keys($this->data);
        reset($this->keys); // Resets the internal pointer of $this->keys
    }

    /**
     * Returns the current element.
     * This method is called by `foreach` after `valid()` has returned true.
     * @return mixed Can return all value types. Returns false if the iterator is invalid and current() is called directly.
     */
    public function current(): mixed
    {
        if ($this->valid()) {
            // $this->key() will return the current key from $this->keys' internal pointer.
            // $this->valid() has already confirmed this key exists in $this->data.
            $key = $this->key();
            return $this->data[$key];
        }
        // This path is typically not hit by `foreach` loops, as they check valid() first.
        // It handles direct calls to current() when the iterator is not in a valid state.
        return false;
        // Alternatively, for stricter error handling on direct invalid calls:
        // throw new \OutOfBoundsException("Cannot get current element: iterator is not valid.");
    }

    /**
     * Returns the key of the current element.
     * @return mixed Returns the key (string or int) of the current element from $this->data,
     *               or false if the iterator is invalid (e.g., past the end of the $this->keys array).
     */
    public function key(): mixed
    {
        // The `current()` function, when used on an array like $this->keys, returns the *value*
        // at that array's internal pointer. Since $this->keys stores the actual keys
        // from $this->data, this correctly returns the data key.
        // If the pointer is invalid (e.g., past end of array, or array is empty), current() returns false.
        return current($this->keys);
    }

    /**
     * Moves the current position to the next element in $this->keys.
     * @return void
     */
    public function next(): void
    {
        next($this->keys);
    }

    /**
     * Checks if the current position in $this->keys is valid AND if that key still exists in $this->data.
     * @return bool True if the current position is valid, false otherwise.
     */
    public function valid(): bool
    {
        $currentDataKey = $this->key(); // Get the key candidate from $this->keys' internal pointer.

        // A key is valid if:
        // 1. The $this->keys array's internal pointer points to a valid element (i.e., $currentDataKey is not false).
        // 2. This key (which was snapshotted at rewind()) still exists in the potentially modified $this->data array.
        return $currentDataKey !== false && array_key_exists($currentDataKey, $this->data);
    }

    // Countable implementation

    /**
     * Counts elements of an object.
     * @return int The custom count as an integer.
     */
    public function count(): int
    {
        return count($this->data);
    }
}