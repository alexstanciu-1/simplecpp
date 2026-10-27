# Inline runtime values: public signature proposal
Doc Status: planning

Date: 2026-10-27. Status: inline storage approved; signatures and inspection/borrowing
policies below are proposed for review, not implemented or newly normative semantics.
This refines the [runtime public API plan](runtime_public_api.md).

## Approved storage decision

Use target-probed, aligned inline storage containing the actual existing runtime
object for string, in-band error and textbuilder. Generate size/alignment constants
and verify native layout and special-member traits. Constructors, assignment and
destruction operate on real objects behind compiled methods; never substitute raw
byte copying for nontrivial object lifetime. No facade `state*` heap allocation.

Current WSL/Clang measurements: native string 32 bytes/alignment 8, textbuilder
32/8, in-band error 80/8. All three native types have nonthrowing move construction
on this target. These are observations, not portable ABI constants. Also verify
move assignment before promising its noexcept contract. Public object stride grows
relative to an 8-byte pointer facade but removes the separately allocated native
object and allocation metadata. Preserve native short-string storage behavior.

Do not add reference counting or COW to ordinary strings/builders. Copies retain
independent value semantics. In-band error is distinct from thrown runtime_error.
The following exception handle is a separate retained-observation proposal, not a
change to those approved value semantics.

## Signature conventions and ownership

All declarations below are within `namespace scpp`. Names `error_value` and
`exception_info` are proposed C++ names; source-language `error` is unchanged.
`scpp::error` already names a native Result sentinel, so do not reuse it as a class.
Private storage is omitted from the declaration listings: each class has generated,
aligned inline storage and only the access needed by its implementation bridge.
No STL types, templates or raw representation accessors appear in these signatures.

Headers: `scpp/string.hpp`, `scpp/error_value.hpp`, `modules/strings/textbuilder.hpp`,
`modules/source/bytes.hpp`, `scpp/exception_info.hpp`. String, error and exception
inspection belong to core; builder belongs to the existing strings module and spans
to source. This grouping for design does not move module policy into core.
Strings may depend on source for the span-append adapter; source depends on core
string, not on the builder. Core error inspection does not depend on textbuilder.

Byte lengths for general string/builder C++ operations use uint64; collection
indices for exception details/trace use int64. Source-buffer offsets/lengths remain
checked int64 inputs with uint32 lengths, preserving the existing source contract.
Until the concrete uint8 API is available, byte reads return uint32 constrained to
0..255; source metadata continues to expose byte/uint8 through checked adaptation.
No source signature is silently widened by this physical C++ choice.

## String: complete proposed ordinary interface

```cpp
class string final
{
public:
    string();
    explicit string(const char *text);
    string(const char *bytes, uint64 length);
    string(const string &other);
    string(string &&other) noexcept;
    string &operator=(const string &other);
    string &operator=(string &&other) noexcept;
    ~string() noexcept;

    uint64 size() const noexcept;
    uint64 byte_size() const noexcept;
    uint64 byte_capacity() const noexcept;
    uint64 estimated_storage_bytes() const noexcept;
    boolean empty() const noexcept;
    uint32 byte_at(uint64 index) const;
    boolean is_valid_utf8() const;
    uint64 length_cp() const;
    string slice(uint64 start, uint64 end_exclusive) const;
    string substr_cp(uint64 start, uint64 length) const;
    string substr_cp(uint64 start) const;

    boolean equals(const string &other) const;
    boolean equals(const char *text) const;
    boolean is_one_of(const char *vocabulary) const;
    void append(const string &other);
    void swap(string &other) noexcept;
    void clear() noexcept;
    void _unset_() noexcept;
};
string operator+(const string &left, const string &right);
string &operator+=(string &left, const string &right);
boolean operator==(const string &left, const string &right);
boolean operator!=(const string &left, const string &right);
boolean operator<(const string &left, const string &right);
boolean operator<=(const string &left, const string &right);
boolean operator>(const string &left, const string &right);
boolean operator>=(const string &left, const string &right);
uint64 strlen(const string &value);
boolean empty(const string &value);
boolean isset(const string &value);
```

Preserve clamped half-open byte slices and the existing invalid-UTF-8 fallback of
code-point operations. byte_at is checked and never returns a sentinel for an invalid
index. Equality and ordering use the existing shared string authorities, not a new
Unicode comparison. clear and _unset_ share behavior. estimated_storage_bytes retains
the existing estimate, not a promise to report exact allocator consumption.

The native std::string/string_view constructors, native_value and release_native
are private bridge operations. Owned transfer uses the public move operations.
This list covers ordinary value operations and the compiler's existing concrete
string conveniences. General string algorithms (replace, split, formatting, etc.)
remain separately owned module functions; they are not silently added as members.
Cross-family casts/operations are generated when their other types are selected.

Pointer inputs are host interop borrows, not memory-safe lifetime tracking. Callers
must supply valid memory for the call. Preserve null-to-empty for the single-pointer
constructor. For length-bearing input, null is accepted only with zero length;
embedded NULs are data. equals(text) and vocabulary input require valid NUL-terminated
pointers. is_one_of preserves exact, case-sensitive, space-separated matching.
Validate host-size conversions before touching memory. Public APIs return no raw
string pointer/view, so string movement/mutation cannot invalidate an escaped view.

## Textbuilder: complete proposed ordinary interface

```cpp
class byte_span;
class textbuilder final
{
public:
    textbuilder();
    textbuilder(const textbuilder &other);
    textbuilder(textbuilder &&other) noexcept;
    textbuilder &operator=(const textbuilder &other);
    textbuilder &operator=(textbuilder &&other) noexcept;
    ~textbuilder() noexcept;

    void reserve(uint64 byte_capacity);
    uint64 capacity() const noexcept;
    uint64 byte_length() const noexcept;
    void append(const char *text);
    void append(const char *bytes, uint64 length);
    void append(const string &value);
    void append_int(int64 value);
    void append_bool(boolean value);
    void append_number(int64 value);
    void append_number(uint64 value);
    void append_byte_span(const byte_span &span);
    boolean equals(const char *text) const;
    void clear() noexcept;
    string to_string() const;
    string take_string();
};
```

append_int and signed append_number use one implementation. Boolean formatting
preserves true -> "1", false -> no bytes. Raw append borrows only during the call;
append(text) treats null as empty, matching the existing compiler adapter's
string_t construction. Length-bearing append follows string input rules. Literal
append does not construct a temporary string. Number append uses a bounded stack
buffer and the existing decimal spelling; capacity growth can still allocate.

to_string returns an independent copy directly into final result storage.
take_string transfers contents and leaves the builder empty; it does not promise
the old capacity remains in the builder. clear preserves reusable capacity under
the existing native buffer contract. Native builder copying is supported; do not
inherit the compiler facade's deleted copy operations. Compiler finish() can forward
to to_string during migration. Pool roundtrip test helpers are not public API.

## Byte-span borrowing and the minimum usable owner API

Merely forward-declaring a span is insufficient for an independently usable path.
This explicitly adds the bounded owner/span pair to the proposed signature scope;
source line indices, locations and hashing functions remain separately owned APIs.

```cpp
class byte_span;
class source_buffer final
{
public:
    source_buffer();
    source_buffer(const source_buffer &other);
    source_buffer(source_buffer &&other);
    source_buffer &operator=(const source_buffer &other);
    source_buffer &operator=(source_buffer &&other);
    ~source_buffer() noexcept;

    static source_buffer take(string &value);
    string release();
    uint32 byte_size() const;
    boolean empty() const noexcept;
    uint32 byte_at(int64 offset) const;
    string slice(int64 offset, int64 length) const;
    byte_span span(int64 offset, int64 length) const;
};
class byte_span final
{
public:
    byte_span();
    byte_span(const source_buffer &owner, int64 offset, int64 length);
    byte_span(const byte_span &other);
    byte_span(byte_span &&other) noexcept;
    byte_span &operator=(const byte_span &other);
    byte_span &operator=(byte_span &&other) noexcept;
    ~byte_span() noexcept;

    uint32 byte_size() const;
    uint32 byte_at(int64 offset) const;
    string to_string() const;
};
```

Both use inline storage for their native handle objects; this does not eliminate
native source-owner state/control-block allocations. Probe their traits independently.
source_buffer moves are deliberately not noexcept: native movement can allocate
fresh ownership state to invalidate old spans. Copies create independent bytes
and invalidation state. take empties its string argument on success. release,
replacement, destruction and move-from invalidate old spans; self-assignment is a
no-op. Preserve the existing operation's failure/cleanup sequencing, not an invented
transactional guarantee around consuming input.

A span does not keep source bytes alive. Every query/read/to_string/builder append
validates its owner, even for a zero-length span. Default and expired spans throw;
a valid zero-length span is different. Invalid append must fail before changing the
builder. Valid append validates once, borrows the byte range synchronously, and
copies it directly into the builder without an intermediate string or retained
span. Builder contents remain valid after owner destruction. No public view()/data()
escape and no raw-pointer span constructor. Concurrent owner invalidation/access is
outside the existing contract; this facade does not add a thread-safety promise.

## In-band error value

```cpp
class error_value final
{
public:
    error_value();
    error_value(const error_value &other);
    error_value(error_value &&other) noexcept;
    error_value &operator=(const error_value &other);
    error_value &operator=(error_value &&other) noexcept;
    ~error_value() noexcept;

    static const error_value no_error;
    static error_value make(const string &message,
                            int64 line = 0, const string &file = string());
    boolean has_error() const noexcept;
    explicit operator bool() const noexcept;
    boolean operator!() const noexcept;
    string get_message() const;
    int64 get_line() const noexcept;
    string get_file() const;
};
```

Default/no_error is false; make always creates a real error, even with empty fields.
Getters return owned values; no mutable reference can invalidate a Result failure.
No-error getters return empty message/file and zero line. Source error identity and
Result/take contracts stay unchanged. Private adapters may inspect existing native
fields without copying; public owned-string getters may allocate for long strings.
No implicit conversion between this value and a thrown exception is introduced.

## Proposed exception inspection: retain, inspect lazily, rethrow unchanged

```cpp
class exception_info final
{
public:
    exception_info(const exception_info &other) noexcept;
    exception_info &operator=(const exception_info &other) noexcept;
    ~exception_info() noexcept;

    string message() const;
    string code() const;
    string component() const;
    string operation() const;
    int64 detail_count() const;
    string detail_key(int64 index) const;
    string detail_value(int64 index) const;
    int64 trace_count() const;
    string trace_module(int64 index) const;
    string trace_address(int64 index) const;
    [[noreturn]] void rethrow() const;
};
exception_info capture_runtime_exception();
```

Recommendation: store the existing active exception through a private native
exception_ptr in target-probed inline storage. No public default constructor and
no destructive move operation: copies, including rvalue copying, retain the same
exception. The factory is the only creation path, so every public instance denotes
a retained runtime_error. Capture does not eagerly copy strings/details/trace or
recapture a backtrace. The handle remains valid outside the catch; returned strings
are independent copies. Exception-runtime allocation during capture is implementation
dependent and must be measured; there is no added heap-allocated facade state.

Inside a catch, capture obtains the active exception and verifies its actual type
using existing C++ exception machinery. A foreign exception is rethrown unchanged;
no wrapping, slicing or catch-all conversion into a runtime error. With no active
exception, throw a diagnosed runtime misuse error (proposed code
`no_active_exception`) rather than executing a bare throw and terminating. Indices
are checked; invalid indices throw without altering the retained exception. rethrow
preserves the original exception object/type and captured diagnostics. It throws
the retained exception, not whichever exception happens to be currently active.

The public surface does not expose std::exception_ptr, std::runtime_error, STL
containers or a new directly catchable exception class. Existing typed/standard-base
catches continue working in compatibility code. Trace entries expose the already
captured module/address data; symbolization and presentation stay in the host layer.
This inspection API is native-runtime integration, not a new source-language
exception-handling construct. The exact name/misuse policy remain review decisions.

## Implementation gates after signature approval

- Lock value layout/lifecycle probes, overload admission and header dependencies;
  validate the promised move traits and avoid guessing ABI constants.
- Implement the whole selected ordinary interface, with generated concrete
  declarations forwarding to existing semantic authorities. Reuse names and native
  recipes; update physical bindings without creating new source identities.
- Keep core independent of strings/source modules; builder/span adaptation belongs
  to the strings/source module dependency path, not generic core error machinery.
- Test long and short values, embedded NULs, self-copy/move/append, growth/transfer,
  code-point fallback, errors with empty messages and failure cleanup.
- Prove default/empty/expired spans, copy/move invalidation, append-after-validation,
  and result survival after owner destruction. Preserve native control-block costs.
- Prove retained exception lifetime, lazy inspection, bounds, original rethrow,
  foreign exception propagation and outside-catch misuse. Check details and trace,
  not just message/code.
- Count allocations and copies at O0/O2; compare inline storage against today's
  facade allocations. Long-value copies, Result snapshots, native source ownership
  and exception-runtime allocations are not claimed to disappear.
- Prove transitive template-free headers, static/shared linkage, sanitizers and
  focused compile/incremental costs before migrating compiler consumers.

No C++ implementation or existing runtime behavior is changed by this document.
