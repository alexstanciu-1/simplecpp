# Plain Language Tester Spec
Doc Status: supporting

## Purpose

This document defines the first tester pass for the Simple C++ PHP-like language.

The goal is to test the plain language only:

- source-language behavior
- data types
- expressions
- statements
- small combinations of language features

This pass does not test modules or library-heavy features.

## Product Model

The product accepts a restricted PHP-like source language and lowers it to C++.

For this first pass, the tester should think about one rule only:

- a small plain-language program should behave the same before and after code generation

If a simple language example behaves differently after generation, that is a bug.

## Scope

Test only the core language surface.

In scope:

- variables
- scalar values
- nullable values
- arrays
- arithmetic
- string concatenation
- comparisons already covered by existing plain tests
- assignment
- `echo`
- functions
- `return`
- `if`
- `if/else`
- `while`
- `do/while`
- `for`
- `foreach` by value
- constants
- basic classes
- namespaces
- `use`
- basic references

Out of scope:

- filesystem
- JSON
- I/O
- process helpers
- database helpers
- FastCGI
- strict-profile module examples
- generated C++ editing
- string-library helpers such as `strlen`, `substr`, `explode`, `implode`, `trim`, `strtolower`, `strtoupper`

Important:

- string values are in scope
- string library functions are out of scope

## General Testing Rule

Prefer small programs that prove one semantic point.

Good tests:

- short
- explicit
- easy to predict
- easy to rerun

Avoid:

- large mixed-feature programs
- module calls
- library calls outside this plain-language pass
- cases where expected behavior is unclear

## Source Shape

Use normal PHP-like syntax with `<?php`.

Typical files in this project often include:

```php
<?php
declare(strict_types=1);
```

For this tester pass, treat `declare(strict_types=1);` as normal and expected.

## Data Types

### Scalars

Plain scalar coverage should include:

- `int`
- `float`
- `bool`
- `string`

Test:

- initialization
- reassignment
- use in expressions
- use as function arguments
- use as return values

### Nullable Values

Supported plain tests already include nullable scalar forms such as nullable `int`.

Test:

- initialize nullable with a value
- assign `null`
- assign a value again
- pass nullable through a simple variable flow

Do not expand into advanced null-heavy feature combinations unless the case stays small and obvious.

### Arrays

Arrays are an important part of this first pass.

Test these shapes:

- empty array
- packed numeric array
- explicit integer keys
- string keys
- mixed integer and string keys
- nested arrays
- append
- overwrite by key
- read after write

Also test small combinations:

- build an array, then loop it
- build a nested array, then read a deep value
- copy an array, then mutate one side in small cases already represented by existing tests

### Class Values

Basic class instances are in scope.

Test:

- instantiate a class
- set or read a property
- call a method
- read a class constant

Do not treat advanced object behavior as part of this pass.

## Expressions

### Arithmetic

Test:

- addition
- subtraction
- multiplication
- division

Keep operands simple and expected outputs obvious.

### String Concatenation

Test:

- string literal concatenation
- concatenation involving scalar-to-string behavior already used in existing tests

Do not test string helper functions.

### Reads

Test ordinary reads from:

- variables
- array elements
- nested array elements
- object properties
- class constants

## Statements

### Variable Definition and Assignment

Test:

- define a variable
- reassign the variable
- assign from another variable
- assign from an expression

### Echo

Test:

- single value output
- multiple values in one `echo`
- output with explicit newline

### Return

Test:

- direct return of a scalar
- return of an expression
- return from a simple conditional branch

## Functions

Functions are core scope.

Test:

- typed parameters
- typed returns
- default parameters
- local variables inside functions
- calling a function from top-level code

Good combinations:

- arithmetic inside a typed function
- function using local variable then returning
- default parameter plus arithmetic

Keep function tests focused. Avoid large nested logic unless the point is control flow.

## Control Flow

### If and If/Else

Test:

- basic `if`
- basic `if/else`
- condition based on scalar comparison or known supported truthy/falsy shape

### While

Test:

- loop with small counter progression
- loop that clearly terminates

### Do/While

Test:

- one case where body runs once
- one case where body runs multiple times

### For

Test:

- small counting loop
- explicit initialization, condition, update

### Foreach By Value

Test:

- iterate a small vector-style array
- echo each item

For this first pass, prefer by-value iteration only.

## Constants

Test:

- top-level constant read
- class constant read

Keep constant cases direct and simple.

## Classes

Only basic class behavior belongs here.

Test:

- construction
- property read/write
- instance method call
- class constant access

Good combinations:

- create object, set property, print property
- method that returns a scalar
- object used inside a small top-level flow

Avoid:

- inheritance-heavy cases
- advanced visibility edge cases
- large object graphs

## Namespaces and Use

Test:

- declare a namespace
- define a class inside a namespace
- access a class by full name
- import with `use`
- basic namespace aliasing

Keep namespace tests structural, not advanced.

## References

References are in scope only at the basic level already covered by existing tests.

Test:

- local reference alias
- simple reference assignment

Good example shape:

- create a scalar
- bind a reference
- modify through the alias
- verify the source changed

Do not expand into complex reference graphs in this first pass.

## Combination Rules

The tester should not only test isolated atoms. Small combinations matter.

Preferred combinations:

- typed variable + arithmetic + `echo`
- function + typed parameter + return
- loop + array read
- nested array + deep read
- class + method call + `echo`
- namespace + class + static call
- reference + reassignment + readback

Avoid over-combining. A good combined test usually uses 2 to 4 ideas, not 8.

## Expected Behavior Rules

Use these expectations:

- values should stay stable across simple assignment and readback
- array writes should be readable later in the same program
- nested reads should return the expected deep value
- control-flow branches should take the expected path
- loops should produce the expected count and order
- methods and functions should return the expected value
- namespace resolution should pick the intended symbol
- reference aliasing should update the bound value in basic cases

## Failure Types To Look For

Report any of these:

- wrong output value
- missing output
- extra output
- wrong line order
- branch took the wrong path
- loop ran too many or too few times
- array element readback is wrong
- nested array access is wrong
- function return is wrong
- method call result is wrong
- namespace resolution is wrong
- reference update does not propagate correctly

Also report:

- a plain-language case only works when rewritten into a different style
- a small obvious case fails unpredictably
- a basic combination works in one form but breaks in a nearly identical form

## Suggested Coverage Order

Use this order:

1. variables and scalar types
2. `echo`
3. arithmetic and concatenation
4. functions and returns
5. `if` and loops
6. arrays
7. constants
8. classes
9. namespaces and `use`
10. references

## Repo Anchors

If you need examples of the intended scope, the best existing anchors are:

- `tests/php/variables/level_01/`
- `tests/php/types/bool/level_01/`
- `tests/php/types/int/level_01/`
- `tests/php/types/float/level_01/`
- `tests/php/types/string/level_01/`
- `tests/php/types/nullable/level_01/`
- `tests/php/operators/level_01/`
- `tests/php/output/level_01/`
- `tests/php/functions/level_01/`
- `tests/php/control_flow/level_01/`
- `tests/php/arrays/level_01/`
- `tests/php/constants/level_01/`
- `tests/php/classes/level_01/`
- `tests/php/namespaces/level_01/`
- `tests/php/use/level_01/`
- `tests/php/references/level_01/`

Do not use these for this pass:

- `tests/php/filesystem/`
- `tests/php/io/`
- `tests/php/json/`
- `tests/php/process/`
- `tests/php/strings/`

## Reporting Format

When reporting a bug, include:

- title
- exact source code
- expected output
- actual output
- whether the issue is stable or intermittent
- smallest repro version
- feature area

Feature area should be one of:

- variables
- types
- operators
- output
- functions
- control_flow
- arrays
- constants
- classes
- namespaces
- use
- references

## Bottom Line

This first-pass tester spec is about core language confidence.

Test the small language building blocks first, then test small combinations of them.

Exclude modules and string-library helpers.

If a short plain-language program produces the wrong result, that is a valid bug.
