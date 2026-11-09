# PHP-flavor lowering catalog

Doc Status: supporting

Review snapshot of the v0.2 compiler on `v0.2/develop-php`, including the working-tree vector replacement and native-argument changes. Examples use the default runtime publication on an LP64 target where `scpp::int64` is `long`. They describe current output, including redundant casts and temporaries.

Each row was prepared as a PHP-flavor program and emitted through `Cpp_Program::emit`. **Given** introduces indented setup lines. The C++ excerpt omits setup statements inside the entry body, except in the whole-unit example. The project rows show both the caller and the declaration owner. The only filename abbreviation is `lib.hpp` for the generated SHA-256 unit-header basename. Line breaks and indentation are normalized for the tables. Synthesized identifiers and literal spellings are preserved.

The **header** column distinguishes direct runtime includes in the example’s implementation unit from declarations actually emitted into an application `.hpp`. **No unit header** means the program emitted no application header for that row. Runtime includes are listed once per example, including those needed by setup. Every entry unit also includes `<scpp/entry.hpp>` and emits the target assertion; those common lines are shown below. A source use of a runtime type does not, by itself, publish an application declaration.

All 48 examples were emitted with the PHP frontend, compiled and linked with Clang against the generated runtime, and executed successfully with the expected entry exit code of 8. Most examples append `return 8;` as harness setup. The tables show the relevant emitted statements.

[Scalars and conversions](#locals-literals-and-conversions) · [Expressions](#expressions-and-updates) · [Strings and output](#strings-and-output) · [Control flow](#control-flow-and-entry) · [Vectors and hashes](#published-vectors-and-hashes) · [Carriers and boxed values](#carriers-and-boxed-values) · [Functions and project headers](#functions-namespaces-and-project-units)

## How to trace a row

Runtime metadata and publication select available identities, members, conversion rules and providers. The API generator produces runtime declarations/implementations and compiler type handlers. Compiler preparation resolves names and types, approves conversions and records the necessary facts. C++ lowering uses those facts and handlers; project header planning publishes declarations observed across units. Pure control flow and source function resolution are compiler-owned. [Metadata](../../runtime/metadata/runtime.json) · [Publication](../../runtime/metadata/publication.json) · [Compiler generation](../../tools/api_generator/php/render_compiler.php) · [Preparation](../04_analyze/prepare/prepare.php) · [C++ emitter](../05_backend/cpp/emit.php) · [Header planner](../05_backend/cpp/headers.php)

For proposed corrections and the rationale for retaining guards, see the [native lowering inventory](../../specs/planning/native_cpp_lowering.md). The catalog covers representative supported families. It does not imply support for arbitrary PHP, all type/operator combinations, or every optional publication.

## Locals, literals and conversions

Direct integer literal initialization uses the normalized decimal value when preparation approves identity or proves the integer fits the destination. This applies to locals with explicit or inferred source types, for-loop declarations, and globals. The signed minimum keeps its safe spelling where the parser admits it as a literal. Arithmetic operands, assignments and exact-overload arguments use their own lowering paths.

Locals and parameters use their function-local storage ordinal (for example, `var_i_0`). Exported names encode source namespaces with `_ns_`. Functions have a trailing underscore (`Lib_ns_id_`), globals have a `var_` prefix, and global reads use `::`. An individual byte that could produce a reserved identifier or the namespace delimiter is escaped as `Z` plus two hex digits. A source `Z` is escaped the same way.

The table excerpts omit the enclosing application namespace (`App` by default). The C++ examples omit `[[maybe_unused]]` for readability. The compiler still emits the attribute to suppress unused-variable warnings.

```text
+------------------------------+--------------------------------------------------------------------+----------------------------------+----------------------------------------+
| PHP-flavor pattern           | Lowered C++ code                                                   | Lowered C++ header code          | Generation and lowering                |
+==============================+====================================================================+==================================+========================================+
| $i int = 8;                  | scpp::int64 var_i_0 = 8;                                           | .cpp includes                    | The explicit C++ storage type supplies |
| $j = $i;                     | scpp::int64 var_j_1 = var_i_0;                                     | #include <scpp/core/scalars.hpp> | the integer representation.            |
|                              |                                                                    | No unit header.                  | Preparation infers the second local    |
|                              |                                                                    |                                  | from the first.                        |
+------------------------------+--------------------------------------------------------------------+----------------------------------+----------------------------------------+
| $b byte = 255;               | scpp::uint8 var_b_0 = 255;                                         | .cpp includes                    | The generated byte handler proves the  |
| $ok bool = true;             | scpp::bool_t var_ok_1 = true;                                      | #include <scpp/core/scalars.hpp> | literal fits during preparation. The   |
|                              |                                                                    | No unit header.                  | bool export supplies the native alias. |
+------------------------------+--------------------------------------------------------------------+----------------------------------+----------------------------------------+
| $f float = 1.5;              | scpp::float64 var_f_0 = 0x1.8000000000000p0;                       | .cpp includes                    | The scalar exports name both types.    |
| $n null = null;              | scpp::null_t var_n_1 = nullptr;                                    | #include <scpp/core/scalars.hpp> | Literal handlers preserve binary64 and |
|                              |                                                                    | No unit header.                  | emit nullptr for source null.          |
+------------------------------+--------------------------------------------------------------------+----------------------------------+----------------------------------------+
| $x = 9;                      | scpp::int64 var_x_0 = 9;                                           | .cpp includes                    | Preparation binds both occurrences to  |
| $x = 4;                      | var_x_0 = 4LL;                                                     | #include <scpp/core/scalars.hpp> | one storage ID. Assignment consumes    |
|                              |                                                                    | No unit header.                  | its approved conversion.               |
+------------------------------+--------------------------------------------------------------------+----------------------------------+----------------------------------------+
| lib                          | scpp::uint8 var_b_0 = scpp::checked_int64_to_uint8(number_());     | .cpp includes                    | The call returns the exact scpp::int64 |
| function number(): int {     |                                                                    | #include <scpp/conversions.hpp>  | parameter type. Lowering passes it     |
|     return 9;                |                                                                    | #include <scpp/core/scalars.hpp> | directly to the checked helper, which  |
| }                            |                                                                    | #include "lib.hpp"               | verifies the byte range at runtime.    |
| main                         |                                                                    | lib.hpp                          |                                        |
| $b byte = number();          |                                                                    | #pragma once                     |                                        |
|                              |                                                                    | #include <scpp/core/scalars.hpp> |                                        |
|                              |                                                                    | ::scpp::int64 number_();         |                                        |
+------------------------------+--------------------------------------------------------------------+----------------------------------+----------------------------------------+
| lib                          | scpp::int64 var_i_0 = scpp::checked_float64_to_int64(fraction_()); | .cpp includes                    | The floating-to-integer binding calls  |
| function fraction(): float { |                                                                    | #include <scpp/conversions.hpp>  | the checked runtime helper. The exact  |
|     return 1.5;              |                                                                    | #include <scpp/core/scalars.hpp> | double argument passes directly.       |
| }                            |                                                                    | #include "lib.hpp"               |                                        |
| main                         |                                                                    | lib.hpp                          |                                        |
| $i int = fraction();         |                                                                    | #pragma once                     |                                        |
|                              |                                                                    | #include <scpp/core/scalars.hpp> |                                        |
|                              |                                                                    | ::scpp::float64 fraction_();     |                                        |
+------------------------------+--------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                        | scpp::int64 var_x_1 = (static_cast<scpp::int64>(var_flag_0));      | .cpp includes                    | The cast syntax requests a conversion  |
|     $flag bool = true;       |                                                                    | #include <scpp/core/scalars.hpp> | during preparation. The integer        |
| $x int = (int)$flag;         |                                                                    | No unit header.                  | handler spells bool-to-integer         |
|                              |                                                                    |                                  | conversion.                            |
+------------------------------+--------------------------------------------------------------------+----------------------------------+----------------------------------------+
| const int LIMIT = 7;         | scpp::int64 var_x_0 = 7LL;                                         | .cpp includes                    | The compiler resolves the constant and |
| $x = LIMIT;                  |                                                                    | #include <scpp/core/scalars.hpp> | inlines its prepared initializer. It   |
|                              |                                                                    | No unit header.                  | emits no constant storage.             |
+------------------------------+--------------------------------------------------------------------+----------------------------------+----------------------------------------+
```

## Expressions and updates

```text
+--------------------------+---------------------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| PHP-flavor pattern       | Lowered C++ code                                                                                              | Lowered C++ header code          | Generation and lowering                |
+==========================+===============================================================================================================+==================================+========================================+
| Given                    | scpp::int64 var_x_2 = (var_a_0 + (var_b_1 * 2LL));                                                            | .cpp includes                    | Integer operator metadata admits the   |
|     $a int = 6;          |                                                                                                               | #include <scpp/core/scalars.hpp> | native operations. Lowering keeps      |
|     $b int = 2;          |                                                                                                               | No unit header.                  | precedence and takes the direct path   |
| $x = $a + $b * 2;        |                                                                                                               |                                  | for trivial operands.                  |
+--------------------------+---------------------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                    | scpp::int64 var_x_2 = scpp::int64_divide(var_a_0, var_b_1);                                                   | .cpp includes                    | Safe reads with identity conversions   |
|     $a int = 6;          | scpp::int64 var_y_3 = scpp::int64_remainder(var_a_0, var_b_1);                                                | #include <scpp/arithmetic.hpp>   | go directly to the checked helpers.    |
|     $b int = 2;          |                                                                                                               | #include <scpp/core/scalars.hpp> | Both operations can still throw.       |
| $x = $a / $b;            |                                                                                                               | No unit header.                  |                                        |
| $y = $a % $b;            |                                                                                                               |                                  |                                        |
+--------------------------+---------------------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                    | scpp::int64 var_x_2 = ((var_a_0 & var_b_1) ^ (~(var_b_1)));                                                   | .cpp includes                    | Generated integer handlers select      |
|     $a int = 6;          |                                                                                                               | #include <scpp/core/scalars.hpp> | bitwise operations; the emitter        |
|     $b int = 2;          |                                                                                                               | No unit header.                  | retains expression grouping.           |
| $x = ($a & $b) ^ ~$b;    |                                                                                                               |                                  |                                        |
+--------------------------+---------------------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                    | scpp::int64 var_x_2 = scpp::int64_shift_left(var_a_0, var_b_1);                                               | .cpp includes                    | Shift and power bindings select        |
|     $a int = 6;          | scpp::int64 var_y_3 = scpp::int64_power(var_a_0, var_b_1);                                                    | #include <scpp/arithmetic.hpp>   | runtime helpers that implement the     |
|     $b int = 2;          |                                                                                                               | #include <scpp/core/scalars.hpp> | admitted integer contracts.            |
| $x = $a << $b;           |                                                                                                               | No unit header.                  |                                        |
| $y = $a ** $b;           |                                                                                                               |                                  |                                        |
+--------------------------+---------------------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                    | scpp::int64 var_x_2 = static_cast<scpp::int64>(((var_a_0 > var_b_1)) - ((var_a_0 < var_b_1)));                | .cpp includes                    | Integer <=> reads distinct locals or   |
|     $a int = 6;          | scpp::bool_t var_ok_3 = (var_a_0 != var_b_1);                                                                 | #include <scpp/core/scalars.hpp> | parameters directly when conversions   |
|     $b int = 2;          |                                                                                                               | No unit header.                  | are identity. Calls are evaluated once |
| $x = $a <=> $b;          |                                                                                                               |                                  | into snapshots. Same-type integer !==  |
| $ok = $a !== $b;         |                                                                                                               |                                  | uses native != on these locals.        |
+--------------------------+---------------------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                    | scpp::float64 var_x_2 = (static_cast<scpp::float64>(var_a_0) + var_f_1);                                      | .cpp includes                    | The numeric family plan selects the    |
|     $a int = 6;          | scpp::bool_t var_ok_3 = (static_cast<scpp::float64>(var_a_0) < var_f_1);                                      | #include <scpp/core/scalars.hpp> | mixed execution type or comparison     |
|     $f float = 2.5;      |                                                                                                               | No unit header.                  | helper and its operand conversions.    |
| $x = $a + $f;            |                                                                                                               |                                  |                                        |
| $ok = $a < $f;           |                                                                                                               |                                  |                                        |
+--------------------------+---------------------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                    | scpp::bool_t var_ok_2 = (((!((var_a_0 != 0))))                  || ((((var_a_0 != 0)) && ((var_b_1 != 0))))); | .cpp includes                    | Condition handlers select truth tests. |
|     $a int = 6;          |                                                                                                               | #include <scpp/core/scalars.hpp> | Native logical operators retain lazy   |
|     $b int = 2;          |                                                                                                               | No unit header.                  | RHS evaluation.                        |
| $ok = !$a || ($a && $b); |                                                                                                               |                                  |                                        |
+--------------------------+---------------------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                    |                                                                                                               | .cpp includes                    | The integer handler supplies native    |
|     $a int = 6;          |                                                                                                               | #include <scpp/core/scalars.hpp> | compound operators. For plain locals   |
|     $b int = 2;          |                                                                                                               | No unit header.                  | with identity conversions, the emitter |
| $a += $b;                | var_a_0 += var_b_1;                                                                                           |                                  | writes the operator directly. A call   |
| $a -= $b;                | var_a_0 -= var_b_1;                                                                                           |                                  | that can mutate the destination needs  |
| $a *= $b;                | var_a_0 *= var_b_1;                                                                                           |                                  | its value saved before the call.       |
| $a &= $b;                | var_a_0 &= var_b_1;                                                                                           |                                  | Unary updates use their type handlers. |
| $a |= $b;                | var_a_0 |= var_b_1;                                                                                           |                                  |                                        |
| $a ^= $b;                | var_a_0 ^= var_b_1;                                                                                           |                                  |                                        |
| ++$a;                    | ++var_a_0;                                                                                                    |                                  |                                        |
| $b--;                    | var_b_1--;                                                                                                    |                                  |                                        |
+--------------------------+---------------------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
```

## Strings and output

```text
+------------------------+-------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| PHP-flavor pattern     | Lowered C++ code                                                        | Lowered C++ header code          | Generation and lowering                |
+========================+=========================================================================+==================================+========================================+
| $s string = "a\0b";    | scpp::string var_s_0 = scpp::string{"\141\000\142", 3};                 | .cpp includes                    | The string export supplies the owning  |
|                        |                                                                         | #include <scpp/core/scalars.hpp> | type. The literal renderer emits exact |
|                        |                                                                         | #include <scpp/string.hpp>       | bytes and their length.                |
|                        |                                                                         | No unit header.                  |                                        |
+------------------------+-------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                  | var_s_0 = (var_s_0 + scpp::string{"\041", 1});                          | .cpp includes                    | The string local and literal feed the  |
|     $s string = "hi";  | scpp::bool_t var_ok_1 = ((var_s_0 == scpp::string{"\150\151\041", 3})); | #include <scpp/core/scalars.hpp> | runtime operators directly. The        |
| $s = $s . "!";         |                                                                         | #include <scpp/string.hpp>       | literal carries its byte length.       |
| $ok = $s === "hi!";    |                                                                         | No unit header.                  |                                        |
+------------------------+-------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                  | scpp::echo_bytes("\101", 1);                                            | .cpp includes                    | Output bindings select literal bytes,  |
|     $n int = 8;        | scpp::echo_int(var_n_0);                                                | #include <scpp/core/scalars.hpp> | integer output and a retained string   |
|     $s string = "ok";  | {                                                                       | #include <scpp/output.hpp>       | reference for the data/size pair.      |
| echo "A", $n, $s;      |     const auto& output_value = var_s_1;                                 | #include <scpp/string.hpp>       |                                        |
|                        |     scpp::echo_bytes(output_value.data(), output_value.size());         | No unit header.                  |                                        |
|                        | }                                                                       |                                  |                                        |
+------------------------+-------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                  | scpp::int64 var_n_1 = scpp::str::byte_length(var_s_0);                  | .cpp includes                    | Runtime metadata publishes the unary   |
|     $s string = "abc"; |                                                                         | #include <scpp/core/scalars.hpp> | function. Preparation resolves its     |
| $n = strlen($s);       |                                                                         | #include <scpp/string.hpp>       | signature and lowering emits the       |
|                        |                                                                         | No unit header.                  | symbol and provider include.           |
+------------------------+-------------------------------------------------------------------------+----------------------------------+----------------------------------------+
```

## Control flow and entry

```text
+----------------------------------+-----------------------------------------------------------+----------------------------------+----------------------------------------+
| PHP-flavor pattern               | Lowered C++ code                                          | Lowered C++ header code          | Generation and lowering                |
+==================================+===========================================================+==================================+========================================+
| Given                            | if (var_a_0 < var_b_1) {                                  | .cpp includes                    | Preparation checks the condition and   |
|     $a int = 6;                  |     scpp::echo_int(1LL);                                  | #include <scpp/core/scalars.hpp> | arm scopes. Lowering emits an ordinary |
|     $b int = 2;                  | }                                                         | #include <scpp/output.hpp>       | C++ branch.                            |
| if ($a < $b) {                   | else {                                                    | No unit header.                  |                                        |
|     echo 1;                      |     scpp::echo_int(2LL);                                  |                                  |                                        |
| } else {                         | }                                                         |                                  |                                        |
|     echo 2;                      |                                                           |                                  |                                        |
| }                                |                                                           |                                  |                                        |
+----------------------------------+-----------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                            | while (var_a_0 > 0LL) {                                   | .cpp includes                    | Loop preparation resolves the          |
|     $a int = 6;                  |     var_a_0--;                                            | #include <scpp/core/scalars.hpp> | transfers. The condition and updates   |
|     $b int = 2;                  |     if ((!((var_a_0 != 0)))) {                            | No unit header.                  | reuse their scalar handlers.           |
| while ($a > 0) {                 |         break;                                            |                                  |                                        |
|     $a--;                        |     }                                                     |                                  |                                        |
|     if (!$a) {                   |     continue;                                             |                                  |                                        |
|         break;                   | }                                                         |                                  |                                        |
|     } continue;                  |                                                           |                                  |                                        |
| }                                |                                                           |                                  |                                        |
+----------------------------------+-----------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                            | do {                                                      | .cpp includes                    | The loop emitter places the test after |
|     $a int = 6;                  |     var_a_0--;                                            | #include <scpp/core/scalars.hpp> | the lowered body.                      |
|     $b int = 2;                  | } while (var_a_0 > 0LL);                                  | No unit header.                  |                                        |
| do {                             |                                                           |                                  |                                        |
|     $a--;                        |                                                           |                                  |                                        |
| } while ($a > 0);                |                                                           |                                  |                                        |
+----------------------------------+-----------------------------------------------------------+----------------------------------+----------------------------------------+
| for ($i int = 0; $i < 3; $i++) { | for (scpp::int64 var_i_0 = 0; var_i_0 < 3LL; var_i_0++) { | .cpp includes                    | Preparation creates header-local       |
|     echo $i;                     |     scpp::echo_int(var_i_0);                              | #include <scpp/core/scalars.hpp> | storage. Lowering shares the ordinary  |
| }                                | }                                                         | #include <scpp/output.hpp>       | declaration, condition and update      |
|                                  |                                                           | No unit header.                  | handlers.                              |
+----------------------------------+-----------------------------------------------------------+----------------------------------+----------------------------------------+
| return 8;                        | return static_cast<int>(8LL);                             | .cpp includes                    | Entry preparation proves the process   |
|                                  |                                                           | #include <scpp/core/scalars.hpp> | exit range. The entry emitter supplies |
|                                  |                                                           | No unit header.                  | the native int result.                 |
+----------------------------------+-----------------------------------------------------------+----------------------------------+----------------------------------------+
| lib                              | return scpp::entry_exit_checked(number_());               | .cpp includes                    | An unknown source int return uses the  |
| function number(): int {         |                                                           | #include <scpp/core/scalars.hpp> | selected target’s checked exit         |
|     return 8;                    |                                                           | #include "lib.hpp"               | boundary.                              |
| }                                |                                                           | lib.hpp                          |                                        |
| main                             |                                                           | #pragma once                     |                                        |
| return number();                 |                                                           | #include <scpp/core/scalars.hpp> |                                        |
|                                  |                                                           | ::scpp::int64 number_();         |                                        |
+----------------------------------+-----------------------------------------------------------+----------------------------------+----------------------------------------+
```

## Published vectors and hashes

```text
+-------------------------------+-------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| PHP-flavor pattern            | Lowered C++ code                                                                                | Lowered C++ header code          | Generation and lowering                |
+===============================+=================================================================================================+==================================+========================================+
| $v vector<int> = [];          | scpp::vector_int64 var_v_0 = scpp::vector_int64{};                                              | .cpp includes                    | Publication registers the concrete     |
|                               |                                                                                                 | #include <scpp/core/scalars.hpp> | vector specialization. Its generated   |
|                               |                                                                                                 | #include <scpp/vectors.hpp>      | handler emits the exported type and    |
|                               |                                                                                                 | No unit header.                  | empty constructor.                     |
+-------------------------------+-------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                         | (var_v_0).append(static_cast<scpp::int64>(8LL));                                                | .cpp includes                    | The vector specialization binds append |
|     $v vector<int> = [];      | scpp::int64 var_x_1 = (var_v_0).at(static_cast<scpp::int64>(0LL));                              | #include <scpp/core/scalars.hpp> | and value-copy at. These paths still   |
| $v[] = 8;                     |                                                                                                 | #include <scpp/vectors.hpp>      | normalize their native arguments.      |
| $x = $v[0];                   |                                                                                                 | No unit header.                  |                                        |
+-------------------------------+-------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                         | var_v_0.set(var_i_1, var_x_2);                                                                  | .cpp includes                    | Both operands already have the         |
|     $v vector<int> = [];      |                                                                                                 | #include <scpp/core/scalars.hpp> | canonical parameter representation.    |
|     $v[] = 0;                 |                                                                                                 | #include <scpp/vectors.hpp>      | The generated set binding emits one    |
|     $i int = 0;               |                                                                                                 | No unit header.                  | member call.                           |
|     $x int = 8;               |                                                                                                 |                                  |                                        |
| $v[$i] = $x;                  |                                                                                                 |                                  |                                        |
+-------------------------------+-------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                         | var_v_0.set(0L, 9L);                                                                            | .cpp includes                    | Preparation proves the conversion;     |
|     $v vector<int> = [];      |                                                                                                 | #include <scpp/core/scalars.hpp> | exact-argument lowering folds the      |
|     $v[] = 0;                 |                                                                                                 | #include <scpp/vectors.hpp>      | literal and selects the target-native  |
| $v[0] = 9.75;                 |                                                                                                 | No unit header.                  | suffix.                                |
+-------------------------------+-------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                         | var_v_0.set(0L, static_cast<scpp::int64>(var_flag_1));                                          | .cpp includes                    | The canonical set overload requires an |
|     $v vector<int> = [];      |                                                                                                 | #include <scpp/core/scalars.hpp> | exact integer argument. The bool local |
|     $v[] = 0;                 |                                                                                                 | #include <scpp/vectors.hpp>      | therefore needs conversion.            |
|     $flag bool = true;        |                                                                                                 | No unit header.                  |                                        |
| $v[0] = $flag;                |                                                                                                 |                                  |                                        |
+-------------------------------+-------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| lib                           | var_v_0.set(0L, scpp::checked_float64_to_int64(fraction_()));                                   | .cpp includes                    | The checked conversion binding         |
| function fraction(): float {  |                                                                                                 | #include <scpp/conversions.hpp>  | preserves runtime range and nonfinite  |
|     return 1.5;               |                                                                                                 | #include <scpp/core/scalars.hpp> | checks before set enters its bounds-   |
| }                             |                                                                                                 | #include <scpp/vectors.hpp>      | checked body.                          |
| main                          |                                                                                                 | #include "lib.hpp"               |                                        |
| Given                         |                                                                                                 | lib.hpp                          |                                        |
|     $v vector<int> = [];      |                                                                                                 | #pragma once                     |                                        |
|     $v[] = 0;                 |                                                                                                 | #include <scpp/core/scalars.hpp> |                                        |
| $v[0] = fraction();           |                                                                                                 | ::scpp::float64 fraction_();     |                                        |
+-------------------------------+-------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                         | scpp::int64 var_n_1 = (var_v_0).size();                                                         | .cpp includes                    | The published collection type supplies |
|     $v vector<int> = [];      | scpp::bool_t var_b_2 = (var_v_0).empty();                                                       | #include <scpp/core/scalars.hpp> | the result identities and member       |
| $n = count($v);               |                                                                                                 | #include <scpp/vectors.hpp>      | spellings. Preparation also watches    |
| $b = empty($v);               |                                                                                                 | No unit header.                  | for shadowing functions.               |
+-------------------------------+-------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| $h hash<int,string> = [];     | scpp::hash_int64_string var_h_0 = scpp::hash_int64_string{};                                    | .cpp includes                    | The generated specialization maps      |
|                               |                                                                                                 | #include <scpp/core/scalars.hpp> | source hash<Value,Key> to a concrete   |
|                               |                                                                                                 | #include <scpp/hashes.hpp>       | exported type.                         |
|                               |                                                                                                 | No unit header.                  |                                        |
+-------------------------------+-------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                         | {                                                                                               | .cpp includes                    | The hash lowering snapshots the key    |
|     $h hash<int,string> = []; |     const auto write_key = scpp::string{"\153", 1};                                             | #include <scpp/core/scalars.hpp> | and converted value for set; the read  |
| $h["k"] = 7;                  |     const auto write_value = 7LL;                                                               | #include <scpp/hashes.hpp>       | calls the throwing value-copy          |
| $x = $h["k"];                 |     (var_h_0).set(static_cast<scpp::string>(write_key), static_cast<scpp::int64>(write_value)); | #include <scpp/string.hpp>       | accessor.                              |
|                               | }                                                                                               | No unit header.                  |                                        |
|                               | scpp::int64 var_x_1 = (var_h_0).at(static_cast<scpp::string>(scpp::string{"\153", 1}));         |                                  |                                        |
+-------------------------------+-------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
```

## Carriers and boxed values

```text
+--------------------------------------------+-----------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| PHP-flavor pattern                         | Lowered C++ code                                                                              | Lowered C++ header code          | Generation and lowering                |
+============================================+===============================================================================================+==================================+========================================+
| $a ?int = null;                            | scpp::nullable_int64 var_a_0 = ((void)(nullptr), scpp::nullable_int64{});                     | .cpp includes                    | Carrier publication supplies concrete  |
| $b result<int> = 7;                        | scpp::result_int64 var_b_1 = scpp::result_int64{static_cast<scpp::int64>(7LL)};               | #include <scpp/carriers.hpp>     | state and payload types. Preparation   |
|                                            |                                                                                               | #include <scpp/core/scalars.hpp> | selects empty versus value             |
|                                            |                                                                                               | No unit header.                  | construction.                          |
+--------------------------------------------+-----------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                                      | scpp::int64 var_x_1 = (var_a_0).value();                                                      | .cpp includes                    | Preparation approves payload           |
|     $a nullable<int> = 8;                  |                                                                                               | #include <scpp/carriers.hpp>     | extraction; the generated carrier      |
| $x int = $a;                               |                                                                                               | #include <scpp/core/scalars.hpp> | binding checks the value state.        |
|                                            |                                                                                               | No unit header.                  |                                        |
+--------------------------------------------+-----------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                                      | scpp::result_int64 var_b_1 = scpp::result_int64{static_cast<scpp::int64>((var_a_0).value())}; | .cpp includes                    | Preparation approves carrier           |
|     $a nullable<int> = 8;                  |                                                                                               | #include <scpp/carriers.hpp>     | conversion. Lowering extracts the      |
| $b result<int> = $a;                       |                                                                                               | #include <scpp/core/scalars.hpp> | checked payload and constructs the     |
|                                            |                                                                                               | No unit header.                  | destination.                           |
+--------------------------------------------+-----------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| $r result_or_false<bool> = false_sentinel; | scpp::result_or_false_bool_t var_r_0 = scpp::result_or_false_bool_t::from_false();            | .cpp includes                    | Only the payload state counts as       |
| $b = isset($r);                            | scpp::bool_t var_b_1 = (var_r_0).has_value();                                                 | #include <scpp/carriers.hpp>     | present for this carrier. The compiler |
|                                            |                                                                                               | #include <scpp/core/scalars.hpp> | emits its published value predicate    |
|                                            |                                                                                               | No unit header.                  | directly, evaluating the operand once. |
+--------------------------------------------+-----------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| $x mixed = 8;                              | scpp::mixed var_x_0 = scpp::mixed::from_integer(static_cast<scpp::int64>(8LL));               | .cpp includes                    | The mixed binding boxes the scalar and |
| $y int = $x;                               | scpp::int64 var_y_1 = scpp::boxed_mixed_typed_destination_int64(var_x_0);                     | #include <scpp/core/scalars.hpp> | selects typed-destination extraction.  |
|                                            |                                                                                               | #include <scpp/mixed.hpp>        | The runtime owns tag checks.           |
|                                            |                                                                                               | No unit header.                  |                                        |
+--------------------------------------------+-----------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                                      | scpp::dynamic var_y_1 = scpp::boxed_add_dynamic_int64(var_x_0, 2LL);                          | .cpp includes                    | The helpers read the dynamic local by  |
|     $x dynamic = 8;                        | scpp::bool_t var_b_2 = scpp::boxed_less_dynamic_int64(var_x_0, 3LL);                          | #include <scpp/core/scalars.hpp> | reference and take the literal by      |
| $y = $x + 2;                               |                                                                                               | #include <scpp/dynamic.hpp>      | value. Runtime dispatch checks the     |
| $b = $x < 3;                               |                                                                                               | No unit header.                  | stored payload kind.                   |
+--------------------------------------------+-----------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                                      | {                                                                                             | .cpp includes                    | The boxed type constructs the runtime  |
|     $x mixed = [];                         |     const auto write_key = scpp::boxed_key(scpp::string{"\153", 1});                          | #include <scpp/core/scalars.hpp> | key, boxes the RHS and dispatches      |
| $x["k"] = 8;                               |     const auto write_value = scpp::mixed::from_integer(static_cast<scpp::int64>(8LL));        | #include <scpp/mixed.hpp>        | keyed storage/read operations.         |
| $v = $x["k"];                              |     (var_x_0).source_set(write_key, write_value);                                             | #include <scpp/string.hpp>       |                                        |
|                                            | }                                                                                             | No unit header.                  |                                        |
|                                            | scpp::mixed var_v_1 = (var_x_0).source_read(scpp::boxed_key(scpp::string{"\153", 1}));        |                                  |                                        |
+--------------------------------------------+-----------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
```

## Functions, namespaces and project units

```text
+------------------------------------------+---------------------------------------------------------------------+--------------------------------------------------+----------------------------------------+
| PHP-flavor pattern                       | Lowered C++ code                                                    | Lowered C++ header code                          | Generation and lowering                |
+==========================================+=====================================================================+==================================================+========================================+
| lib                                      | main                                                                | .cpp includes                                    | The compiler resolves the function     |
| function twice(int $x): int {            | twice_(4LL);                                                        | #include <scpp/core/scalars.hpp>                 | declared in the other source unit. The |
|     return $x * 2;                       | lib                                                                 | #include "lib.hpp"                               | callee header exposes its parameter    |
| }                                        | ::scpp::int64 twice_(::scpp::int64 var_x_0)                         | lib.cpp includes                                 | and return aliases.                    |
| main                                     | {                                                                   | #include <scpp/core/scalars.hpp>                 |                                        |
| twice(4);                                |     return (var_x_0 * 2LL);                                         | #include "lib.hpp"                               |                                        |
|                                          | }                                                                   | lib.hpp                                          |                                        |
|                                          |                                                                     | #pragma once                                     |                                        |
|                                          |                                                                     | #include <scpp/core/scalars.hpp>                 |                                        |
|                                          |                                                                     | ::scpp::int64 twice_(::scpp::int64 var_x_0);     |                                        |
+------------------------------------------+---------------------------------------------------------------------+--------------------------------------------------+----------------------------------------+
| lib                                      | main                                                                | .cpp includes                                    | Preparation checks exact writable      |
| function bump(int &$x): void {           | bump_(var_x_0);                                                     | #include <scpp/core/scalars.hpp>                 | storage. The C++ reference parameter   |
|     $x++;                                | lib                                                                 | #include "lib.hpp"                               | binds directly to the caller’s local.  |
| }                                        | void bump_(::scpp::int64 &var_x_0)                                  | lib.cpp includes                                 |                                        |
| main                                     | {                                                                   | #include <scpp/core/scalars.hpp>                 |                                        |
| Given                                    |     var_x_0++;                                                      | #include "lib.hpp"                               |                                        |
|     $x int = 1;                          | }                                                                   | lib.hpp                                          |                                        |
| bump($x);                                |                                                                     | #pragma once                                     |                                        |
|                                          |                                                                     | #include <scpp/core/scalars.hpp>                 |                                        |
|                                          |                                                                     | void bump_(::scpp::int64 &var_x_0);              |                                        |
+------------------------------------------+---------------------------------------------------------------------+--------------------------------------------------+----------------------------------------+
| lib                                      | main                                                                | .cpp includes                                    | The caller gets the default literal    |
| function greet(string $s = "hi"): void { | greet_(scpp::string{"\150\151", 2});                                | #include <scpp/core/scalars.hpp>                 | from the declaration. Its owning       |
|     echo $s;                             | lib                                                                 | #include <scpp/string.hpp>                       | string directly initializes the        |
| }                                        | void greet_(::scpp::string var_s_0)                                 | #include "lib.hpp"                               | single by-value parameter.             |
| main                                     | {                                                                   | lib.cpp includes                                 |                                        |
| greet();                                 |     {                                                               | #include <scpp/output.hpp>                       |                                        |
|                                          |         const auto& output_value = var_s_0;                         | #include <scpp/string.hpp>                       |                                        |
|                                          |         scpp::echo_bytes(output_value.data(), output_value.size()); | #include "lib.hpp"                               |                                        |
|                                          |     }                                                               | lib.hpp                                          |                                        |
|                                          | }                                                                   | #pragma once                                     |                                        |
|                                          |                                                                     | #include <scpp/string.hpp>                       |                                        |
|                                          |                                                                     | void greet_(::scpp::string var_s_0);             |                                        |
+------------------------------------------+---------------------------------------------------------------------+--------------------------------------------------+----------------------------------------+
| lib                                      | main                                                                | .cpp includes                                    | File-local import resolution selects   |
| namespace Lib;                           | scpp::int64 var_x_0 = Lib_ns_id_(1LL);                              | #include <scpp/core/scalars.hpp>                 | the qualified declaration. Mangling    |
| function id(int $x): int {               | lib                                                                 | #include "lib.hpp"                               | uses the canonical identity; the alias |
|     return $x;                           | ::scpp::int64 Lib_ns_id_(::scpp::int64 var_x_0)                     | lib.cpp includes                                 | generates no wrapper.                  |
| }                                        | {                                                                   | #include <scpp/core/scalars.hpp>                 |                                        |
| main                                     |     return var_x_0;                                                 | #include "lib.hpp"                               |                                        |
| use function Lib\id as pick;             | }                                                                   | lib.hpp                                          |                                        |
| $x = pick(1);                            |                                                                     | #pragma once                                     |                                        |
|                                          |                                                                     | #include <scpp/core/scalars.hpp>                 |                                        |
|                                          |                                                                     | ::scpp::int64 Lib_ns_id_(::scpp::int64 var_x_0); |                                        |
+------------------------------------------+---------------------------------------------------------------------+--------------------------------------------------+----------------------------------------+
| lib                                      | main                                                                | .cpp includes                                    | A cross-unit global read watches its   |
| global $N int = 7;                       | scpp::int64 var_x_0 = ::App::var_N;                                 | #include <scpp/core/scalars.hpp>                 | stable symbol. The owner emits         |
| main                                     | lib                                                                 | #include "lib.hpp"                               | constinit storage and publishes an     |
| $x = $N;                                 | constinit scpp::int64 var_N = 7;                                    | lib.cpp includes                                 | extern declaration.                    |
|                                          |                                                                     | #include <scpp/core/scalars.hpp>                 |                                        |
|                                          |                                                                     | #include "lib.hpp"                               |                                        |
|                                          |                                                                     | lib.hpp                                          |                                        |
|                                          |                                                                     | #pragma once                                     |                                        |
|                                          |                                                                     | #include <scpp/core/scalars.hpp>                 |                                        |
|                                          |                                                                     | extern ::scpp::int64 var_N;                      |                                        |
+------------------------------------------+---------------------------------------------------------------------+--------------------------------------------------+----------------------------------------+
| Given                                    | void local_only_();                                                 | .cpp includes                                    | Same-unit calls use a private-to-unit  |
|     function local_only(): void {        | void local_only_()                                                  | #include <scpp/core/scalars.hpp>                 | prototype in the .cpp. Header          |
|         return;                          | {                                                                   | No unit header.                                  | publication requires an external       |
|     }                                    |     return;                                                         |                                                  | observer.                              |
| local_only();                            | }                                                                   |                                                  |                                        |
|                                          | int scpp_entry_body()                                               |                                                  |                                        |
|                                          | {                                                                   |                                                  |                                        |
|                                          |     local_only_();                                                  |                                                  |                                        |
|                                          |     return static_cast<int>(8LL);                                   |                                                  |                                        |
|                                          | }                                                                   |                                                  |                                        |
+------------------------------------------+---------------------------------------------------------------------+--------------------------------------------------+----------------------------------------+
```

## Common project entry

The frontend selected an entry target with 31 value bits for these examples. Each entry implementation contains the following lines.

```cpp
#include <scpp/entry.hpp>
SCPP_REQUIRE_ENTRY_TARGET(31);
```

The separate adapter generated by the project emitter is common to all rows.

```cpp
// Generated adapter for the explicitly selected entry.
namespace App {
int scpp_entry_body();
}

int main() { return ::App::scpp_entry_body(); }
```
