# Native async lowering proof

Doc Status: supporting

The fixture exercises hand-written C++ in the proposed emitter shape against the
v0.1 async runtime. A concrete synchronous entry drives the scheduler.
The parent coroutine emits each echo operand as a separate statement, with the
await in the parent itself. The child suspends on a positive timer.

The trace verifies that the first operand is emitted before the child call,
that the child starts and resumes once, and that success emits the value and
newline. False AND and true OR skip the right-hand factory itself. On failure
after suspension, the exception reaches the synchronous caller, only the echo
prefix survives, and both coroutine locals are destroyed exactly once.

From the repository root:

```sh
clang++ -std=c++23 -O2 -g0 -Wall -Wextra -pthread -I runtime/include \
  v0.2/tests/async_lowering.cpp runtime/include/scpp/async_core.cpp \
  -o /tmp/scpp-async-lowering
/tmp/scpp-async-lowering
```

GCC can run the same command with `g++`. For a sanitizer run, replace
`-O2 -g0` with `-O1 -g -fsanitize=address,undefined -fno-sanitize-recover=all`
and run with `ASAN_OPTIONS=detect_leaks=1`.

The fixture keeps the coroutine and synchronous entry in one translation unit.
Cross-unit publication, compiler integration and frame allocation cost are
separate proof steps.
