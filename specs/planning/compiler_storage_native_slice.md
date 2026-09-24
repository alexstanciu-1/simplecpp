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
Numeric/key/capacity coercion rejection is a native compile-time check. Source
method misuse is also checked by STAN; no mixed/dynamic key coercion is provided.

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

## Strict source binding acceptance

Run the actual strict PHS consumer and positive/negative source tests:

```sh
python3 tests/tools/test_scpp_compiler_storage.py
php tests/tools/test_scpp_compiler_storage_typing.php
```

Or run the checked-in consumer directly:

```sh
cd tests/tools/fixtures/compiler_storage
php ../../../../bin/scpp.php run --build-runtime
```

Expected final output: `compiler storage source bindings: ok`.
The project enables strict PHP and the header-only compiler module. STAN remains
enabled; no generated C++ edits or validation bypasses are used. The fixture proves
fields/locals/parameters/returns, static roots, nested collections, aliases, old
handle lifetime, holes, exact keys/order and runtime capacity. Its separate factory
also checks collection return linkage across source files. The test driver proves
runtime missing-member/null/duplicate/negative-capacity rejection and compile-time
wrong-key/type/arity restrictions from PHS inputs.

Focused regressions:

```sh
php generators/php/bin/check_pre_tokenizer_regressions.php
php tests/tools/test_scpp_construction_references.php
php tests/tools/test_scpp_collection_typing.php
php tests/tools/run_tests.php run --suite=runtime --test=runtime_compiler_00 --jobs=3
```

Remaining limits are documented in `specs/compiler_storage.md`: explicit typed
locals are needed for collection members or returns whose declarations are only
in another file. No project-wide inference was added. Executable-PHP portability
annotations/conversion and real compiler-consumer integration remain separate work.
