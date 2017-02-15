# universe
Descent Framework Standard PHP Functions.

## Standard PHP Functions

Similar to the Standard PHP Library, this package provides additional
PHP functions at the global scope which are used at the core of the 
Descent Framework.

### Implemented global scope array functions

- `array_fetch` - resolves a query path based array value
- `array_extend` - extends an given array by the provided query path and value
- `array_exclude` - removes a value based on the provided query path
- `array_normalize` - normalizes a array based on query path notation

See [Functions.php](functions.php).
 
##### What is query path?

Query paths are notations of a chain of array keys to directly access
deep array values in a regular array.

##### Do query path based array functions work on arrays with query path notation?

No, you have to normalize those array. There is a function for such
scenarios build in: `array_normalize`.

##### Do query path based array functions work directly on the source array?

No, all functions do create a copy of the provided array.

### Implemented global scope callback functions

- `encloseCallback` - creates a Closure of a provided callable, automatically uses `Closure::fromCallable` when available
- `encloseCallbackPattern` - calls `encloseCallback` after the provided callback pattern (separated by the provided method separator and enhanced by the provided namespace) was converted to a callback.
