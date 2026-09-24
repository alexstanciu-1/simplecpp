# Issue #242 simple wrapper slice
Doc Status: planning

Authority: `specs/compiler_storage.md`. Only the replacement wrappers are current.
The implementation replaces the previous storage-specific code; existing runtime
containers, shared_p and the local PHP compiler work area are unchanged.

## Implemented

`Storage<T>` wraps a vector of shared handles with tombstones and a live count.
`Keyed_Storage<T>` wraps a hash index plus insertion-order list. Shared collection
state implements assignment aliasing; shared record handles implement identity and
lifetime. Small common validation helpers avoid a native virtual hierarchy.
There are no views, hooks, policy flags or dense position directories.

The core hash currently lacks the allocation-failure guarantees this contract
needs; repairing it would widen the task into a separate ownership area. This
slice uses standard hash facilities with local ordering support instead. No broad
performance/layout project or compiler-consumer rewrite is included.

## Native validation

From the repository root:

```sh
cmake -S runtime -B /tmp/scpp-storage-build -DSCPP_WITH_MYSQLI=OFF
cmake --build /tmp/scpp-storage-build --target test_compiler_storage test_compiler_string_storage test_compiler_shared_records -j2
ctest --test-dir /tmp/scpp-storage-build -R '^scpp_test_compiler_(storage|string_storage|shared_records)$' --output-on-failure
php tests/tools/run_tests.php run --suite=runtime --test=runtime_compiler_00 --jobs=3
```

The three fixtures prove numeric holes and exhaustion boundary, exact string keys
and ordering, assignment aliasing, noncopyable record identity, old-handle lifetime,
null/constructor validation, growth, reserve, allocation failure recovery, and
acyclic last-handle destruction. Exhaustion tests exercise the same pre-write
extent check as append, without attempting an INT64_MAX-sized allocation.
Numeric/key/capacity coercion rejection is a native compile-time check; source
runtime key diagnostics remain pending.

## PHP/native behavior comparison

The PHP fixture loads the current reference helpers read-only from an explicitly
supplied path. It uses ordinary host PHP, not PHS. The helper files belong to the
local unpublished compiler checkpoint described in issue #242; they are not
assumed to exist in a fresh remote checkout. For the local reference:

```sh
g++ -std=c++23 -O2 -Wall -Wextra -Werror -Iruntime/include tests/runtime/compiler/parity/native_storage.cpp -o /tmp/scpp-storage-parity
/tmp/scpp-storage-parity > /tmp/storage-native.txt
php tests/runtime/compiler/parity/storage.php /home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/helpers > /tmp/storage-php.txt
diff -u /tmp/storage-php.txt /tmp/storage-native.txt
```

This compares positions, holes/count, alias edits, retained handles, required-member
failures, null/negative-capacity rejection, exact keys (hex encoded), duplicate
rejection and insertion order. Native sanitizers and fault injection separately
check lifetime/allocation behavior that PHP comparison cannot prove.

## Remaining acceptance work

Source/converter declarations, construction, methods, subscripts/append,
isset/unset, count/is_empty and keyed foreach bindings are all pending, as is an
end-to-end native execution of the compiler's real consumers. The native fixtures
and host-PHP comparison are not evidence of converter integration. Keep #242 open.
