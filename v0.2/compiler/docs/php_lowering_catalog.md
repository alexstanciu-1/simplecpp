# PHP-flavor lowering catalog

Doc Status: supporting

Review snapshot of the v0.2 compiler on `v0.2/develop-php`. Examples use the default runtime publication on an LP64 target where `scpp::int64` is `long`. They describe current output, including redundant casts and temporaries.

Each row was prepared as a PHP-flavor program and emitted through `Cpp_Program::emit`. **Given** introduces indented setup lines. The C++ excerpt omits setup statements inside the entry body, except in the whole-unit example. The project rows show both the caller and the declaration owner. The only filename abbreviation is `lib.hpp` for the generated SHA-256 unit-header basename. Line breaks and indentation are normalized for the tables. Synthesized identifiers and literal spellings are preserved.

The **header** column distinguishes direct runtime includes in the example’s implementation unit from declarations actually emitted into an application `.hpp`. **No unit header** means the program emitted no application header for that row. Runtime includes are listed once per example, including those needed by setup. Every entry unit also includes `<scpp/entry.hpp>` and emits the target assertion; those common lines are shown below. A source use of a runtime type does not, by itself, publish an application declaration.

All 70 examples are checked against the PHP frontend’s emitted implementation and header columns. Most examples append `return 8;` as harness setup. The tables show the relevant emitted statements.

[Scalars and conversions](#locals-literals-and-conversions) · [Expressions](#expressions-and-updates) · [Strings and output](#strings-and-output) · [Control flow](#control-flow-and-entry) · [Vectors and hashes](#published-vectors-and-hashes) · [Carriers and boxed values](#carriers-and-boxed-values) · [Functions and project headers](#functions-namespaces-and-project-units)

## How to trace a row

Runtime metadata and publication select available identities, members, conversion rules and providers. The API generator produces runtime declarations/implementations and compiler type handlers. Compiler preparation resolves names and types, approves conversions and records the necessary facts. C++ lowering uses those facts and handlers; project header planning publishes declarations observed across units. Pure control flow and source function resolution are compiler-owned. [Metadata](../../runtime/metadata/runtime.json) · [Publication](../../runtime/metadata/publication.json) · [Compiler generation](../../tools/api_generator/php/render_compiler.php) · [Preparation](../04_analyze/prepare/prepare.php) · [C++ emitter](../05_backend/cpp/emit.php) · [Header planner](../05_backend/cpp/headers.php)

For proposed corrections and the rationale for retaining guards, see the [native lowering inventory](../../specs/planning/native_cpp_lowering.md). The catalog covers representative supported families. It does not imply support for arbitrary PHP, all type/operator combinations, or every optional publication.

## Locals, literals and conversions

Direct integer literal initialization uses the normalized decimal value when preparation approves identity or proves the integer fits the destination. This applies to locals with explicit or inferred source types, for-loop declarations, and globals. The signed minimum keeps its safe spelling where the parser admits it as a literal. Arithmetic operands, assignments and exact-overload arguments use their own lowering paths.

Locals and parameters use their function-local storage ordinal (for example, `var_i_0`). Exported names encode source namespaces with `_ns_`. Functions have a trailing underscore (`Lib_ns_id_`), globals have a `var_` prefix, and global reads use `::`. An individual byte that could produce a reserved identifier or the namespace delimiter is escaped as `Z` plus two hex digits. A source `Z` is escaped the same way.

Application struct and field identifiers preserve source spelling and case in C++.
The backend groups structs in a generated record namespace inside the configured
app namespace to keep names such as `point` and `owner_point` available even
when the compiler also generates an ownership helper. A source namespace gets
its own record namespace. Names reserved by the native target use a reversible
escape. Inline values use `.`; a shared owner's inline `operator->()` returns
the address of the payload in its allocation.

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
|     $a int = 6;          | scpp::int64 var_y_3 = (var_a_0 + var_a_0);                                                                    | #include <scpp/core/scalars.hpp> | native operations. Lowering keeps      |
|     $b int = 2;          | scpp::int64 var_z_4 = ((var_a_0 + var_a_0) + (var_a_0 + var_a_0));                                            | No unit header.                  | precedence and emits safe repeated     |
| $x = $a + $b * 2;        |                                                                                                               |                                  | sums directly, including nested sums.  |
| $y = $a + $a;            |                                                                                                               |                                  |                                        |
| $z = ($a+$a) + ($a+$a);  |                                                                                                               |                                  |                                        |
+--------------------------+---------------------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                    | var_total_0 = (var_total_0 + line_(var_price_1, 3LL));                                                        | .cpp includes                    | The native integer addition reads the  |
|     $total int=8;        |                                                                                                               | #include <scpp/core/scalars.hpp> | local once. The scalar value arguments |
|     $price int=2;        |                                                                                                               | No unit header.                  | cannot mutate it, and the source call  |
|     function line(       |                                                                                                               |                                  | is emitted once.                       |
|         int $p,int $q    |                                                                                                               |                                  |                                        |
|     ):int {              |                                                                                                               |                                  |                                        |
|         return $p*$q;    |                                                                                                               |                                  |                                        |
|     }                    |                                                                                                               |                                  |                                        |
| $total = $total +        |                                                                                                               |                                  |                                        |
|     line($price, 3);     |                                                                                                               |                                  |                                        |
+--------------------------+---------------------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                    | scpp::int64 var_x_2 = scpp::int64_divide(var_a_0, var_b_1);                                                   | .cpp includes                    | Identity-converted operands use the    |
|     $a int = 6;          | scpp::int64 var_y_3 = scpp::int64_remainder(var_a_0, var_b_1);                                                | #include <scpp/arithmetic.hpp>   | checked helpers in native call order.  |
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
| Given                    | scpp::int64 var_x_2 = (((var_a_0 > var_b_1)) - ((var_a_0 < var_b_1)));                                        | .cpp includes                    | The integer comparison yields -1, 0 or |
|     $a int = 6;          | scpp::bool_t var_ok_3 = (var_a_0 != var_b_1);                                                                 | #include <scpp/core/scalars.hpp> | 1. The typed local widens that result. |
|     $b int = 2;          |                                                                                                               | No unit header.                  | Exact arguments retain their int64     |
| $x = $a <=> $b;          |                                                                                                               |                                  | type. Calls use snapshots; distinct    |
| $ok = $a !== $b;         |                                                                                                               |                                  | local reads can be repeated.           |
+--------------------------+---------------------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                    | scpp::float64 var_x_2 = (static_cast<scpp::float64>(var_a_0) + var_f_1);                                      | .cpp includes                    | The numeric family plan selects the    |
|     $a int = 6;          | scpp::bool_t var_ok_3 = (static_cast<scpp::float64>(var_a_0) < var_f_1);                                      | #include <scpp/core/scalars.hpp> | mixed execution type or comparison     |
|     $f float = 2.5;      |                                                                                                               | No unit header.                  | helper and its operand conversions.    |
| $x = $a + $f;            |                                                                                                               |                                  |                                        |
| $ok = $a < $f;           |                                                                                                               |                                  |                                        |
+--------------------------+---------------------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                    | scpp::bool_t var_ok_2 = !(var_a_0 != 0) || (var_a_0 != 0 && var_b_1 != 0);                                    | .cpp includes                    | Condition handlers select truth tests. |
|     $a int = 6;          |                                                                                                               | #include <scpp/core/scalars.hpp> | Native logical operators retain lazy   |
|     $b int = 2;          |                                                                                                               | No unit header.                  | RHS evaluation.                        |
| $ok = !$a || ($a && $b); |                                                                                                               |                                  |                                        |
+--------------------------+---------------------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                    | var_a_0 += 1LL;                                                                                               | .cpp includes                    | The constant is emitted as its value.  |
|     const int EXTRA=1;   | var_a_0 += (static_cast<scpp::int64>((static_cast<scpp::uint8>(2LL))));                                       | #include <scpp/core/scalars.hpp> | Both literal casts are proven safe.    |
|     $a int=6;            |                                                                                                               | No unit header.                  | The integer binding supplies +=.       |
| $a += EXTRA;             |                                                                                                               |                                  |                                        |
| $a += (int)(byte)2;      |                                                                                                               |                                  |                                        |
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

### Eager scalar operands and stable destinations

```text
+-------------------------+-----------------------------------------------------------------+----------------------------------+-----------------------------------------+
| PHP-flavor pattern      | Lowered C++ code                                                | Lowered C++ header code          | Generation and lowering                 |
+=========================+=================================================================+==================================+=========================================+
| Given                   | scpp::int64 var_x_0 = (scpp::int64_divide(8LL, 2LL) + 1LL);     | .cpp includes                    | The generated binding takes two values. |
|     function f(): int { | scpp::int64 var_y_1 = scpp::int64_power(f_(), f_());            | #include <scpp/arithmetic.hpp>   | Each call supplies one argument.        |
|         return 2;       |                                                                 | #include <scpp/core/scalars.hpp> | The literal needs no sequencing.        |
|     }                   |                                                                 | No unit header.                  |                                         |
| $x = (8 / 2) + 1;       |                                                                 |                                  |                                         |
| $y = f() ** f();        |                                                                 |                                  |                                         |
+-------------------------+-----------------------------------------------------------------+----------------------------------+-----------------------------------------+
| Given                   | scpp::float64 var_x_2 = scpp::float64_divide(var_a_0, var_b_1); | .cpp includes                    | The helper owns the divisor check.      |
|     $a float = 6.0;     |                                                                 | #include <scpp/arithmetic.hpp>   |                                         |
|     $b float = 2.0;     |                                                                 | #include <scpp/core/scalars.hpp> |                                         |
| $x = $a / $b;           |                                                                 | No unit header.                  |                                         |
+-------------------------+-----------------------------------------------------------------+----------------------------------+-----------------------------------------+
| Given                   | scpp::bool_t var_x_2 = (var_a_0 != var_b_1);                    | .cpp includes                    | Distinct boolean locals already hold    |
|     $a bool = true;     |                                                                 | #include <scpp/core/scalars.hpp> | the two truth values.                   |
|     $b bool = false;    |                                                                 | No unit header.                  |                                         |
| $x = ($a xor $b);       |                                                                 |                                  |                                         |
+-------------------------+-----------------------------------------------------------------+----------------------------------+-----------------------------------------+
| Given                   | var_a_0 = scpp::int64_divide(var_a_0, var_b_1);                 | .cpp includes                    | Reading the RHS preserves the old       |
|     $a int = 6;         |                                                                 | #include <scpp/arithmetic.hpp>   | destination. A mutating call can        |
|     $b int = 2;         |                                                                 | #include <scpp/core/scalars.hpp> | require saving that value.              |
| $a /= $b;               |                                                                 | No unit header.                  |                                         |
+-------------------------+-----------------------------------------------------------------+----------------------------------+-----------------------------------------+
```

### Zero-parameter instance methods

```text
+---------------------------------------+-------------------------------+----------------------------------+---------------------------------------+
| PHP-flavor pattern                    | Lowered C++ code              | Lowered C++ header code          | Generation and lowering               |
+=======================================+===============================+==================================+=======================================+
| Given                                 | method_5_Point_bump(var_p_0); | .cpp includes                    | The method takes the inline record    |
|     struct Point {                    |                               | #include <scpp/core/scalars.hpp> | by reference and updates it in place. |
|         public int $x;                |                               | No unit header.                  |                                       |
|         public function bump():void { |                               |                                  |                                       |
|             $this->x+=1;              |                               |                                  |                                       |
|         }                             |                               |                                  |                                       |
|     }                                 |                               |                                  |                                       |
|     $p Point;                         |                               |                                  |                                       |
| $p->bump();                           |                               |                                  |                                       |
+---------------------------------------+-------------------------------+----------------------------------+---------------------------------------+
```

## Strings and output

```text
+------------------------+-------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| PHP-flavor pattern     | Lowered C++ code                                                        | Lowered C++ header code          | Generation and lowering                |
+========================+=========================================================================+==================================+========================================+
| $s string = "a\0b";    | scpp::string var_s_0 = scpp::string{"a\0b", 3};                         | .cpp includes                    | The string export supplies the owning  |
|                        |                                                                         | #include <scpp/string.hpp>       | type. The literal renderer emits exact |
|                        |                                                                         | No unit header.                  | bytes and their length.                |
|                        |                                                                         |                                  |                                        |
+------------------------+-------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                  | var_s_0 = (var_s_0 + scpp::string{"!", 1});                             | .cpp includes                    | The string local and literal feed the  |
|     $s string = "hi";  | scpp::bool_t var_ok_1 = ((var_s_0 == scpp::string{"hi!", 3}));          | #include <scpp/core/scalars.hpp> | runtime operators directly. The        |
| $s = $s . "!";         |                                                                         | #include <scpp/string.hpp>       | literal carries its byte length.       |
| $ok = $s === "hi!";    |                                                                         | No unit header.                  |                                        |
+------------------------+-------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                  | scpp::string var_s_2 = (var_a_0 + var_b_1);                             | .cpp includes                    | Concatenation takes its left operand   |
|     $a string = "a";   | scpp::bool_t var_ok_3 = ((var_a_0 < var_b_1));                          | #include <scpp/core/scalars.hpp> | by value. Comparison reads both        |
|     $b string = "b";   |                                                                         | #include <scpp/string.hpp>       | strings by reference. Neither sibling  |
| $s = $a . $b;          |                                                                         | No unit header.                  | can mutate the other here.             |
| $ok = $a < $b;         |                                                                         |                                  |                                        |
+------------------------+-------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                  | scpp::echo_bytes("A", 1);                                               | .cpp includes                    | Output bindings select literal bytes,  |
|     $n int = 8;        | scpp::echo_int(var_n_0);                                                | #include <scpp/core/scalars.hpp> | integer output and borrowed strings.   |
|     $s string = "ok";  | scpp::echo_string(var_s_1);                                             | #include <scpp/output.hpp>       |                                        |
| echo "A", $n, $s;      |                                                                         | #include <scpp/string.hpp>       |                                        |
|                        |                                                                         | No unit header.                  |                                        |
+------------------------+-------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                  | scpp::int64 var_n_1 = scpp::str::byte_length(var_s_0);                  | .cpp includes                    | Runtime metadata publishes the unary   |
|     $s string = "abc"; |                                                                         | #include <scpp/core/scalars.hpp> | function. Preparation resolves its     |
| $n = strlen($s);       |                                                                         | #include <scpp/string.hpp>       | signature and lowering emits the       |
|                        |                                                                         | No unit header.                  | symbol and provider include.           |
+------------------------+-------------------------------------------------------------------------+----------------------------------+----------------------------------------+
```

### Runtime call arguments

```text
+---------------------------------------+------------------------------------------------------+----------------------------------+-------------------------------------+
| PHP-flavor pattern                    | Lowered C++ code                                     | Lowered C++ header code          | Generation and lowering             |
+=======================================+======================================================+==================================+=====================================+
| Given                                 | scpp::string var_s_0 = str_repeat(label_(), 2LL);    | .cpp includes                    | The adapter borrows the returned    |
|     function label():string {         |                                                      | #include <scpp/core/scalars.hpp> | string through the call. The count  |
|         return "xy";                  |                                                      | #include <scpp/php/string.hpp>   | literal passes by value.            |
|     }                                 |                                                      | #include <scpp/string.hpp>       |                                     |
| $s=str_repeat(label(),2);             |                                                      | No unit header.                  |                                     |
+---------------------------------------+------------------------------------------------------+----------------------------------+-------------------------------------+
| Given                                 | scpp::string var_t_1 = ([&]() {                      | .cpp includes                    | The first argument saves the string |
|     function change(string &$s):int { |     const auto argument_25_0 = var_s_0;              | #include <scpp/core/scalars.hpp> | before change updates the original. |
|         $s="new";                     |     return str_repeat(argument_25_0,                 | #include <scpp/php/string.hpp>   | The scalar count passes directly.   |
|         return 2;                     |         change_(var_s_0));                           | #include <scpp/string.hpp>       |                                     |
|     }                                 | }());                                                | No unit header.                  |                                     |
|     $s string="old";                  |                                                      |                                  |                                     |
| $t=str_repeat($s,change($s));         |                                                      |                                  |                                     |
+---------------------------------------+------------------------------------------------------+----------------------------------+-------------------------------------+
| Given                                 | scpp::string var_s_0 = ([&]() {                      | .cpp includes                    | The two producers run once in       |
|     function text():string {          |     const auto argument_24_0 = text_();              | #include <scpp/core/scalars.hpp> | source order. The width literal     |
|         return "abc";                 |     const auto argument_24_2 = pad_();               | #include <scpp/php/string.hpp>   | passes directly to the adapter.     |
|     }                                 |     return str_pad(argument_24_0, 6LL,               | #include <scpp/string.hpp>       |                                     |
|     function pad():string {           |         argument_24_2);                              | No unit header.                  |                                     |
|         return "-";                   | }());                                                |                                  |                                     |
|     }                                 |                                                      |                                  |                                     |
| $s=str_pad(text(),6,pad());           |                                                      |                                  |                                     |
+---------------------------------------+------------------------------------------------------+----------------------------------+-------------------------------------+
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
|     $b int = 2;                  |     if (!(var_a_0 != 0)) {                                | No unit header.                  | reuse their scalar handlers.           |
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
| return 8;                        | return 8;                                                 | No unit header.                  | Entry preparation proves the process   |
|                                  |                                                           |                                  | exit range. The entry emitter supplies |
|                                  |                                                           |                                  | the native int result.                 |
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
|                               |                                                                                                 | #include <scpp/vectors.hpp>      | vector specialization. Its generated   |
|                               |                                                                                                 | No unit header.                  | handler emits the exported type and    |
|                               |                                                                                                 |                                  | empty constructor.                     |
+-------------------------------+-------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                         | (var_v_0).append(8L);                                                                           | .cpp includes                    | Append and at use the published        |
|     $v vector<int> = [];      | scpp::int64 var_x_1 = (var_v_0).at(0L);                                                         | #include <scpp/core/scalars.hpp> | parameter type to select the native    |
| $v[] = 8;                     |                                                                                                 | #include <scpp/vectors.hpp>      | integer literal suffix.                |
| $x = $v[0];                   |                                                                                                 | No unit header.                  |                                        |
+-------------------------------+-------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                         |                                                                                                 | .cpp includes                    | Local reads already have the declared  |
|     $v vector<int> = [];      |                                                                                                 | #include <scpp/core/scalars.hpp> | parameter type. Integer <=> widens its |
|     $v[] = 0;                 |                                                                                                 | #include <scpp/vectors.hpp>      | result to that same type for an exact  |
|     $i int = 0;               |                                                                                                 | No unit header.                  | argument. The set binding passes it    |
|     $x int = 8;               |                                                                                                 |                                  | directly.                              |
| $v[$i] = $x;                  | var_v_0.set(var_i_1, var_x_2);                                                                  |                                  |                                        |
| $v[$i] = $x <=> $i;           | var_v_0.set(var_i_1, static_cast<scpp::int64>(((var_x_2 > var_i_1)) - ((var_x_2 < var_i_1))));  |                                  |                                        |
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
|                               |                                                                                                 | #include <scpp/hashes.hpp>       | source hash<Value,Key> to a concrete   |
|                               |                                                                                                 | No unit header.                  | exported type.                         |
|                               |                                                                                                 |                                  |                                        |
+-------------------------------+-------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                         | var_h_0.set(scpp::string{"k", 1}, 7L);                                                          | .cpp includes                    | The generated set binding takes the    |
|     $h hash<int,string> = []; | scpp::int64 var_x_1 = var_h_0.at(scpp::string{"k", 1});                                         | #include <scpp/core/scalars.hpp> | typed key and value directly. The at   |
| $h["k"] = 7;                  |                                                                                                 | #include <scpp/hashes.hpp>       | binding returns a copy and throws      |
| $x = $h["k"];                 |                                                                                                 | #include <scpp/string.hpp>       | when the key is absent.                |
|                               |                                                                                                 | No unit header.                  |                                        |
+-------------------------------+-------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
```

## Carriers and boxed values

```text
+--------------------------------------------+----------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| PHP-flavor pattern                         | Lowered C++ code                                                                                   | Lowered C++ header code          | Generation and lowering                |
+============================================+====================================================================================================+==================================+========================================+
| $a ?int = null;                            | scpp::nullable_int64 var_a_0 = ((void)(nullptr), scpp::nullable_int64{});                          | .cpp includes                    | Carrier publication supplies concrete  |
| $b result<int> = 7;                        | scpp::result_int64 var_b_1 = scpp::result_int64{static_cast<scpp::int64>(7LL)};                    | #include <scpp/carriers.hpp>     | state and payload types. Preparation   |
|                                            |                                                                                                    | #include <scpp/core/scalars.hpp> | selects empty versus value             |
|                                            |                                                                                                    | No unit header.                  | construction.                          |
+--------------------------------------------+----------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                                      | scpp::int64 var_x_1 = (var_a_0).value();                                                           | .cpp includes                    | Preparation approves payload           |
|     $a nullable<int> = 8;                  |                                                                                                    | #include <scpp/carriers.hpp>     | extraction; the generated carrier      |
| $x int = $a;                               |                                                                                                    | #include <scpp/core/scalars.hpp> | binding checks the value state.        |
|                                            |                                                                                                    | No unit header.                  |                                        |
+--------------------------------------------+----------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                                      | scpp::result_int64 var_b_1 = scpp::result_int64{static_cast<scpp::int64>((var_a_0).value())};      | .cpp includes                    | Preparation approves carrier           |
|     $a nullable<int> = 8;                  |                                                                                                    | #include <scpp/carriers.hpp>     | conversion. Lowering extracts the      |
| $b result<int> = $a;                       |                                                                                                    | #include <scpp/core/scalars.hpp> | checked payload and constructs the     |
|                                            |                                                                                                    | No unit header.                  | destination.                           |
+--------------------------------------------+----------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| $r result_or_false<bool> = false_sentinel; | scpp::result_or_false_bool_t var_r_0 = scpp::result_or_false_bool_t::from_false();                 | .cpp includes                    | Only the payload state counts as       |
| $b = isset($r);                            | scpp::bool_t var_b_1 = (var_r_0).has_value();                                                      | #include <scpp/carriers.hpp>     | present for this carrier. The compiler |
|                                            |                                                                                                    | #include <scpp/core/scalars.hpp> | emits its published value predicate    |
|                                            |                                                                                                    | No unit header.                  | directly, evaluating the operand once. |
+--------------------------------------------+----------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| $x mixed = 8;                              | scpp::mixed var_x_0 = scpp::mixed::from_integer(8L);                                               | .cpp includes                    | The mixed binding boxes the scalar and |
| $y int = $x;                               | scpp::int64 var_y_1 = scpp::boxed_mixed_typed_destination_int64(var_x_0);                          | #include <scpp/core/scalars.hpp> | selects typed-destination extraction.  |
|                                            |                                                                                                    | #include <scpp/mixed.hpp>        | The runtime owns tag checks.           |
|                                            |                                                                                                    | No unit header.                  |                                        |
+--------------------------------------------+----------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                                      | scpp::mixed var_sum_2 = scpp::boxed_add_mixed_mixed(var_x_0, var_y_1);                             | .cpp includes                    | The published helper checks the tags.  |
|     $x mixed = 3;                          | scpp::bool_t var_before_3 = scpp::boxed_less_int64_mixed(2LL, var_x_0);                            | #include <scpp/core/scalars.hpp> | Both locals can be borrowed directly.  |
|     $y mixed = 4;                          |                                                                                                    | #include <scpp/mixed.hpp>        | A literal can also precede a box.      |
| $sum = $x + $y;                            |                                                                                                    | No unit header.                  |                                        |
| $before = 2 < $x;                          |                                                                                                    |                                  |                                        |
+--------------------------------------------+----------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                                      | scpp::dynamic var_y_1 = scpp::boxed_add_dynamic_int64(var_x_0, 2LL);                               | .cpp includes                    | The helpers read the dynamic local by  |
|     $x dynamic = 8;                        | scpp::bool_t var_b_2 = scpp::boxed_less_dynamic_int64(var_x_0, 3LL);                               | #include <scpp/core/scalars.hpp> | reference and take the literal by      |
| $y = $x + 2;                               |                                                                                                    | #include <scpp/dynamic.hpp>      | value. Runtime dispatch checks the     |
| $b = $x < 3;                               |                                                                                                    | No unit header.                  | stored payload kind.                   |
+--------------------------------------------+----------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                                      | var_x_0.source_set(scpp::boxed_key(scpp::string{"k", 1}), scpp::mixed::from_integer(8L));          | .cpp includes                    | The boxed type constructs the runtime  |
|     $x mixed = [];                         | scpp::mixed var_v_1 = var_x_0.source_read(scpp::boxed_key(scpp::string{"k", 1}));                  | #include <scpp/core/scalars.hpp> | key, boxes the RHS and dispatches      |
| $x["k"] = 8;                               |                                                                                                    | #include <scpp/mixed.hpp>        | keyed storage/read operations.         |
| $v = $x["k"];                              |                                                                                                    | #include <scpp/string.hpp>       |                                        |
|                                            |                                                                                                    | No unit header.                  |                                        |
+--------------------------------------------+----------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
| Given                                      | var_x_0.source_set(scpp::boxed_key(scpp::string{"k", 1}), scpp::dynamic::from_integer(number_())); | .cpp includes                    | C++ chooses the order of key checking  |
|     $x dynamic = [];                       |                                                                                                    | #include <scpp/core/scalars.hpp> | and the call to number(). Both must    |
|     function number(): int {               |                                                                                                    | #include <scpp/dynamic.hpp>      | succeed before source_set runs.        |
|         return 8;                          |                                                                                                    | #include <scpp/string.hpp>       |                                        |
|     }                                      |                                                                                                    | No unit header.                  |                                        |
| $x["k"] = number();                        |                                                                                                    |                                  |                                        |
+--------------------------------------------+----------------------------------------------------------------------------------------------------+----------------------------------+----------------------------------------+
```

Mixed and dynamic use the same argument lowering for their scalar factories. In the keyed write above, `8L` already has the factory’s `scpp::int64` parameter type on this target. An `int` local passes directly and a byte local needs widening. A checked float-to-integer conversion or explicit boxed-to-integer extraction already returns the factory parameter type. The runtime validates the key before the store. C++ chooses the order of key validation and RHS evaluation, so RHS effects may occur even when the key is invalid.

## Free calls with scalar literal arguments

```text
+-----------------------------------------+---------------------------------------------+----------------------------------+----------------------------------------+
| PHP-flavor pattern                      | Lowered C++ code                            | Lowered C++ header code          | Generation and lowering                |
+=========================================+=============================================+==================================+========================================+
| Given                                   | scpp::int64 var_x_0 = sum_(number_(), 2LL); | .cpp includes                    | The integer literal cannot observe the |
|     function number(): int {            | scpp::int64 var_y_1 = sum_(2LL, number_()); | #include <scpp/core/scalars.hpp> | call or fail during parameter setup.   |
|         return 4;                       |                                             | No unit header.                  | Each invocation evaluates number once. |
|     }                                   |                                             |                                  |                                        |
|     function sum(int $a, int $b): int { |                                             |                                  |                                        |
|         return $a + $b;                 |                                             |                                  |                                        |
|     }                                   |                                             |                                  |                                        |
| $x = sum(number(), 2);                  |                                             |                                  |                                        |
| $y = sum(2, number());                  |                                             |                                  |                                        |
+-----------------------------------------+---------------------------------------------+----------------------------------+----------------------------------------+
```

## Ordered free calls

```text
+---------------------------------------+---------------------------------------------------+----------------------------------+-----------------------------------+
| PHP-flavor pattern                    | Lowered C++ code                                  | Lowered C++ header code          | Generation and lowering           |
+=======================================+===================================================+==================================+===================================+
| Given                                 | scpp::int64 var_y_1 = ([&]() -> scpp::int64 {     | .cpp includes                    | The first argument captures 8.    |
|     function sum(int $a,int $b):int { |     scpp::int64 argument_43_0 = var_x_0;          | #include <scpp/core/scalars.hpp> | The final argument changes x to 9 |
|         return $a+$b;                 |     return sum_(argument_43_0, change_(var_x_0)); | No unit header.                  | and supplies 2 directly to sum.   |
|     }                                 | }());                                             |                                  |                                   |
|     function change(int &$x):int {    |                                                   |                                  |                                   |
|         $x=9;                         |                                                   |                                  |                                   |
|         return 2;                     |                                                   |                                  |                                   |
|     }                                 |                                                   |                                  |                                   |
|     $x int=8;                         |                                                   |                                  |                                   |
| $y=sum($x,change($x));                |                                                   |                                  |                                   |
+---------------------------------------+---------------------------------------------------+----------------------------------+-----------------------------------+
| Given                                 | scpp::int64 var_x_0 = ([&]() -> scpp::int64 {     | .cpp includes                    | The literal needs no capture.     |
|     function mark(int $v):int {       |     scpp::int64 argument_34_1 = mark_(2LL);       | #include <scpp/core/scalars.hpp> | The saved result of mark(2) keeps |
|         return $v;                    |     return pick_(1LL, argument_34_1, mark_(3LL)); | No unit header.                  | both calls in source order.       |
|     }                                 | }());                                             |                                  |                                   |
|     function pick(int $a,int $b,      |                                                   |                                  |                                   |
|         int $c):int {                 |                                                   |                                  |                                   |
|         return $b;                    |                                                   |                                  |                                   |
|     }                                 |                                                   |                                  |                                   |
| $x=pick(1,mark(2),mark(3));           |                                                   |                                  |                                   |
+---------------------------------------+---------------------------------------------------+----------------------------------+-----------------------------------+
| Given                                 | ([&]() -> void {                                  | .cpp includes                    | Only the value argument saves 8.  |
|     function write_sum(int $a,        |     scpp::int64 argument_46_0 = var_x_0;          | #include <scpp/core/scalars.hpp> | The reference names the same slot |
|         int &$x,int $b):void {        |     return write_sum_(argument_46_0, var_x_0,     | No unit header.                  | that change updates.              |
|         $x=$a+$b;                     |         change_(var_x_0));                        |                                  |                                   |
|     }                                 | }());                                             |                                  |                                   |
|     function change(int &$x):int {    |                                                   |                                  |                                   |
|         $x=90;                        |                                                   |                                  |                                   |
|         return 2;                     |                                                   |                                  |                                   |
|     }                                 |                                                   |                                  |                                   |
|     $x int=8;                         |                                                   |                                  |                                   |
| write_sum($x,$x,change($x));          |                                                   |                                  |                                   |
+---------------------------------------+---------------------------------------------------+----------------------------------+-----------------------------------+
```

## Type-qualified static calls

```text
+---------------------------------------------------------------+-----------------------------------------+----------------------------------+--------------------------------------+
| PHP-flavor pattern                                            | Lowered C++ code                        | Lowered C++ header code          | Generation and lowering              |
+===============================================================+=========================================+==================================+======================================+
| Given                                                         | method_7_Counter_advance(var_x_0, 2LL); | .cpp includes                    | The resolved static method takes the |
|     struct Counter {                                          |                                         | #include <scpp/core/scalars.hpp> | original local by reference. The     |
|         public static function advance(int &$x,int $b):void { |                                         | No unit header.                  | integer literal initializes its      |
|             $x+=$b;                                           |                                         |                                  | by-value parameter directly.         |
|         }                                                     |                                         |                                  |                                      |
|     }                                                         |                                         |                                  |                                      |
|     $x int=8;                                                 |                                         |                                  |                                      |
| Counter::advance($x,2);                                       |                                         |                                  |                                      |
+---------------------------------------------------------------+-----------------------------------------+----------------------------------+--------------------------------------+
```

## Inline and shared construction

```text
+--------------------------------------------------------+--------------------------------------------------------------------------------------------+----------------------------------+------------------------------------+
| PHP-flavor pattern                                     | Lowered C++ code                                                                           | Lowered C++ header code          | Generation and lowering            |
+========================================================+============================================================================================+==================================+====================================+
| Given                                                  | scpp_records::Pair var_p_0 = scpp_records::Pair(static_cast<scpp::uint8>(7LL), number_()); | .cpp includes                    | The byte conversion is proven.     |
|     struct Pair {                                      | owner_Pair var_q_1 = owner_Pair::make(static_cast<scpp::uint8>(7LL), number_());           | #include <scpp/core/scalars.hpp> | Each call evaluates number once.   |
|         public function __construct(byte $a,int $b) {} |                                                                                            | #include <scpp/owner_access.hpp> | The shared factory allocates after |
|     }                                                  |                                                                                            | No unit header.                  | its parameters are initialized.    |
|     function number():int { return 4; }                |                                                                                            |                                  |                                    |
| $p Pair(7,number());                                   |                                                                                            |                                  |                                    |
| $q=new Pair(7,number());                               |                                                                                            |                                  |                                    |
+--------------------------------------------------------+--------------------------------------------------------------------------------------------+----------------------------------+------------------------------------+
```

## Inline method arguments

```text
+-----------------------------------------------------+--------------------------------------+----------------------------------+----------------------------------------+
| PHP-flavor pattern                                  | Lowered C++ code                     | Lowered C++ header code          | Generation and lowering                |
+=====================================================+======================================+==================================+========================================+
| Given                                               | method_3_Acc_add(var_a_0, 3LL, 2LL); | .cpp includes                    | The inline local supplies the method’s |
|     struct Acc {                                    |                                      | #include <scpp/core/scalars.hpp> | record reference. Both scalar literals |
|         public int $n;                              |                                      | No unit header.                  | can initialize parameters directly.    |
|         public function add(int $x,int $y=2):void { |                                      |                                  |                                        |
|             $this->n=$x+$y;                         |                                      |                                  |                                        |
|         }                                           |                                      |                                  |                                        |
|     }                                               |                                      |                                  |                                        |
|     $a Acc;                                         |                                      |                                  |                                        |
| $a->add(3);                                         |                                      |                                  |                                        |
+-----------------------------------------------------+--------------------------------------+----------------------------------+----------------------------------------+
```

## Shared method arguments

```text
+---------------------------------------------------+-------------------------------------------------------------+----------------------------------+--------------------------------------+
| PHP-flavor pattern                                | Lowered C++ code                                            | Lowered C++ header code          | Generation and lowering              |
+===================================================+=============================================================+==================================+======================================+
| Given                                             | ([&]() -> void {                                            | .cpp includes                    | The local owner keeps the allocation |
|     struct Acc {                                  |     auto receiver_owner = var_a_0;                          | #include <scpp/core/scalars.hpp> | alive. Dereferencing it checks the   |
|         public int $n;                            |     auto&& receiver_payload = *receiver_owner.operator->(); | #include <scpp/owner_access.hpp> | receiver before argument evaluation. |
|         public function add(int $x,int $y):void { |     return method_3_Acc_add(receiver_payload, 3LL, 2LL);    | No unit header.                  | Both integer literals pass directly. |
|             $this->n=$x+$y;                       | }());                                                       |                                  |                                      |
|         }                                         |                                                             |                                  |                                      |
|     }                                             |                                                             |                                  |                                      |
|     $a=new Acc();                                 |                                                             |                                  |                                      |
| $a->add(3,2);                                     |                                                             |                                  |                                      |
+---------------------------------------------------+-------------------------------------------------------------+----------------------------------+--------------------------------------+
```

## Temporary inline method receivers

```text
+---------------------------------------------------+------------------------------------------------------------+----------------------------------+-------------------------------------+
| PHP-flavor pattern                                | Lowered C++ code                                           | Lowered C++ header code          | Generation and lowering             |
+===================================================+============================================================+==================================+=====================================+
| Given                                             | ([&]() -> void {                                           | .cpp includes                    | The binding evaluates the factory   |
|     struct Point {                                |     auto&& receiver_payload = method_5_Point_make();       | #include <scpp/core/scalars.hpp> | once and keeps its returned record  |
|         public int $n;                            |     return method_5_Point_add(receiver_payload, 3LL, 2LL); | No unit header.                  | alive through the method body. Both |
|         public static function make():Point {     | }());                                                      |                                  | literals pass directly.             |
|             $p Point;                             |                                                            |                                  |                                     |
|             return $p;                            |                                                            |                                  |                                     |
|         }                                         |                                                            |                                  |                                     |
|         public function add(int $x,int $y):void { |                                                            |                                  |                                     |
|             $this->n=$x+$y;                       |                                                            |                                  |                                     |
|         }                                         |                                                            |                                  |                                     |
|     }                                             |                                                            |                                  |                                     |
| Point::make()->add(3,2);                          |                                                            |                                  |                                     |
+---------------------------------------------------+------------------------------------------------------------+----------------------------------+-------------------------------------+
```

## Functions, namespaces and project units

The same single-reference call spelling applies inside a forwarding function. In
`function forward(int &$x): void { bump($x); }`, the call emits
`bump_(var_x_0);`. Preparation has already verified the parameter's storage type,
and both functions refer to the caller's original variable.


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
| lib                                      | main                                                                | .cpp includes                                    | The caller resolves the explicit or    |
| function greet(string $s = "hi"): void { | greet_(scpp::string{"hi", 2});                                      | #include <scpp/string.hpp>                       | default argument and applies the       |
|     echo $s;                             | greet_(scpp::string{"hi", 2});                                      | #include "lib.hpp"                               | prepared conversion. The parameter     |
| }                                        | lib                                                                 | lib.cpp includes                                 | takes the resulting string by value.   |
| main                                     | void greet_(::scpp::string var_s_0)                                 | #include <scpp/output.hpp>                       |                                        |
| greet();                                 | {                                                                   | #include <scpp/string.hpp>                       |                                        |
| greet("hi");                             |     scpp::echo_string(var_s_0);                                     | #include "lib.hpp"                               |                                        |
|                                          | }                                                                   | lib.hpp                                          |                                        |
|                                          |                                                                     | #pragma once                                     |                                        |
|                                          |                                                                     | #include <scpp/string.hpp>                       |                                        |
|                                          |                                                                     | void greet_(::scpp::string var_s_0);             |                                        |
|                                          |                                                                     |                                                  |                                        |
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
| Given                                    | void local_only_();                                                 | No unit header.                                  | Same-unit calls use a private-to-unit  |
|     function local_only(): void {        | void local_only_()                                                  |                                                  | prototype in the .cpp. Header          |
|         return;                          | {                                                                   |                                                  | publication requires an external       |
|     }                                    |     return;                                                         |                                                  | observer.                              |
| local_only();                            | }                                                                   |                                                  |                                        |
|                                          | int scpp_entry_body()                                               |                                                  |                                        |
|                                          | {                                                                   |                                                  |                                        |
|                                          |     local_only_();                                                  |                                                  |                                        |
|                                          |     return 8;                                                       |                                                  |                                        |
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
