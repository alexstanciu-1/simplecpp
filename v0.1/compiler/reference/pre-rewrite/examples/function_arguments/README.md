# Scalar function arguments
Doc Status: supporting

Three files exercise positional arguments, nested calls, parameter assignment
and independent scalar copies. The executable returns status **42**.

From the repository root:

```sh
php src/main.php examples/function_arguments/project.json --output /tmp/scpp-arguments
/tmp/scpp-arguments
echo $?
```

Changing only `choose` to `return $first;` produces status **17**. The
[native/update proof](../../tests/features/parameter_lowering.php) tests this
body edit in a resident session, including reuse of unchanged caller objects.
This sample uses identity conversions and copyable, no-cleanup scalar values.
The compiler also supports [integer widening](../../docs/details/integer_conversions.md).
