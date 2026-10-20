# Strict vector helpers
Doc Status: normative

Core exposes these helpers with one exactly inferred element type `T`:

| Source form | Result | Input contract |
| --- | --- | --- |
| `vector_reserve(values, capacity)` | `void` | Writable `vector<T>&`, `int` |
| `vector_capacity(values)` | `int` | Unary non-retaining observation of `vector<T>` |
| `vector_resize(values, count, fill)` | `void` | Writable `vector<T>&`, `int`, exact copyable `T` |
| `vector_filled(count, fill)` | `vector<T>` | `int`, copyable `T` |
| `vector_clear(values)` | `void` | Writable `vector<T>&` |
| `vector_clear_keep_capacity(values)` | `void` | Writable `vector<T>&` |
| `vector_compact(values)` | `void` | Writable `vector<T>&` |
| `vector_compact(values, capacity)` | `void` | Writable `vector<T>&`, `int` |

Reserve ensures capacity at least the requested amount without changing size or
contents. Resize preserves the retained prefix and fills new elements with copies
of the evaluated fill value. Filled constructs the requested number of copies.
The fill type must be copyable even for zero count or shrinking resize; explicit
move does not bypass the provider's copy requirement. Copies follow the element's
normal value/handle semantics, not an unconditional deep clone.

Both clear names destroy elements and retain capacity. Unary compact requests
shrinking without promising that the implementation releases capacity. Explicit
compact preserves every element, with capacity at least the requested amount and
at least the current size. It never truncates contents.

Negative counts/capacities throw the existing `value_error`. The provider leaves
the input vector unchanged on that negative-value path; ordinary argument
expressions have already run and their effects are not undone. Other allocation
failures are not covered by this guarantee.

Arguments use ordinary left-to-right evaluation and value snapshots. In particular,
an aliased fill value is captured before provider mutation/reallocation. Mutation
requires writable stable storage; observation can accept readonly storage and
supported temporaries. Noncopyable elements remain usable with the non-filling
helpers when their existing runtime operations support them.

Bindings use the existing public `scpp/lang/php.hpp` provider header. This source
contract does not authorize indexed mutation extensions or legacy fixture changes.
