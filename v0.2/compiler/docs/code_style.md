# Compiler coding style

Doc Status: supporting

Applies to handwritten compiler implementation, helpers, tests and tools under
`v0.2/compiler/`. Generated output and historical evidence are excluded. This is
implementation discipline, not a restriction on the language being compiled.
Existing files are not claimed to comply fully; apply these rules to new and
touched code without turning a bounded task into a whole-tree formatting pass.

## PHP types and declarations

- Use `declare(strict_types=1);` and `namespace scpp\compiler;` for compiler code.
  Data types use lowercase `snake_case`; workers use `Capitalized_Snake_Case`.
  Use descriptive `snake_case` members and functions, consistent with their owner.
- Mark concrete compiler classes `final`; use abstract classes or interfaces for
  extension points. Keep overridden return types identical, including nullability.
  Give a required or more specialized view a distinct accessor name. This convention
  does not change the source language's covariance contract.
- Declare properties explicitly in the class body. Constructors take ordinary
  parameters and assign fields explicitly: **no constructor property promotion**.
  Preserve explicit types, visibility and `readonly` where applicable.
- Use explicit parameter, return and property types. Keep required state nonnullable;
  model real absence explicitly. Check host failure/union results before using a
  narrower form. Prefer strict comparisons and keep null, false and empty distinct.
- Ordinary PHP arrays are allowed. Document meaningful element/key types where
  native PHP declarations cannot express them. Do not import converter annotations,
  mandatory storage wrappers, `object_cast` patterns or block-local declaration
  restrictions solely to satisfy the retired conversion workflow.
- Qualify external exceptions and constants; use short names for same-namespace
  types. Use explicit object receivers and owning-class qualification for static
  members. Do not introduce ambiguous implicit state access.

## Native size and collection intent

Keep native representation intent visible beside class properties and function/
method parameters and returns. Use adjacent comments, for example:

```php
public int $offset /** uint32 */;
public int $length /** uint32; range 1..0xFFFFFF */;
public array $tokens /** list<token> */;

public function offset(): int /** uint32 */
{
	return $this->offset;
}
```

- Annotate integer declarations with a reviewed `int8/16/32/64` or
  `uint8/16/32/64` intent. Put parameter annotations after the parameter name,
  before any default value; put return annotations after the PHP return type.
- Storage width and permitted range are separate. Use `uint32` plus the 24-bit
  token-length range, not an invented `uint24` storage type. Document narrower
  ranges and sentinel values when they matter.
- Put numeric enum backing intent once beside its declaration; do not repeat it
  at every typed use. String-backed `flavor` retains its PHP labels: no native
  numeric mapping or backing width has been selected yet.
- Annotate constants when width/range or collection shape matters. A scalar
  constant uses the same placement before `=`; enum cases inherit their enum's
  intent. Do not introduce constants merely to add annotations.
- Arrays need element/key or record-shape comments: `list<token>`, `list<uint32>`,
  `array<string,mixed>` or `array{start:uint32,end:uint32}`. These describe contents,
  not a choice of native vector/hash implementation. Standard PHPDoc may coexist
  for IDEs; keep the descriptions consistent. Do not duplicate known class types
  or guess the byte size of strings, objects, references or containers.
- Leave ordinary locals alone unless a particular range distinction needs
  explanation. Do not impose the donor converter's annotation grammar or tools.

These comments document valid-value/native-port intent, not PHP allocation sizes,
runtime coercions or proof of native portability. Current validation remains the
authority for accepted values. Invalid signed or oversized inputs must still be
rejected: a future native port must validate at its boundary before narrowing,
and use wider intermediates where sums or lookahead could overflow.

Current reviewed bounds:

| Data | Intent and reason |
| --- | --- |
| Source size, byte offsets, exclusive span ends, scanner position | `uint32`; source size is at most 0xFFFFFFFF and EOF equals source size. |
| Token length | `uint32`, restricted to 1..0xFFFFFF. |
| Token-list indexes and parser cursor | `uint32`; positive-length, nonoverlapping tokens limit count to source size. The cursor can equal count at EOF. |
| Line-start elements | `uint32` byte offsets. The list count itself can reach 0x100000000 for an all-newline maximum-size source. |
| Diagnostic line/column | `uint64`, range 1..0x100000000 because positions are one-based and include EOF. |
| Token kind | `uint8` intent, IDs 1..127 with zero reserved. |
| Assignment intent | `uint8` intent, current IDs 1..2. |
| CLI return | `int32` process exit status, current values 0..2. |

Source integer literals remain exact strings until semantic analysis chooses and
validates their source-language type. Do not annotate those spellings as host integers.
If a bound is unknown, record that uncertainty and its owner rather than selecting
a width by analogy. Revisit annotations when the owning contract changes.

## Layout

- Use tabs for block indentation, displayed at four columns. Indent continuation
  lines at least one additional tab beyond the first line, including parameter
  lists, calls and multiline conditions. Avoid alignment to distant parentheses.
- Put named function and method opening braces on their own line, including
  constructors and inline definitions.
- For other blocks, put the opening brace on the header line when the body has
  at most five counted lines; otherwise put it on its own line. This includes
  classes, namespaces, control blocks and anonymous functions/lambdas.
- Every block body is multiline, including single-statement and empty bodies.
  Closing braces occupy their own line; enclosing punctuation such as `};` may
  follow. C++ braced value initializers are expressions, not block bodies.
- Start `else`, `elseif`, `catch`, `finally` and the trailing `while` of a `do`
  block on a new line after the preceding closing brace.
- Count nonblank code lines, including nested blocks but excluding the body's
  own braces and comment-only lines. Code plus a comment counts as one code line.
- Put one statement per line, except clauses in a `for` header. Separate functions
  and distinct logical steps with one blank line; group by purpose, not frequency.
- Prefer lines within 120 columns. Wrap at meaningful boundaries and preserve
  literals, embedded sample programs and heredoc contents exactly.
- Parenthesize each logical operand of `&&`/`||`, except direct calls. Explicitly
  group mixed logical operators. Group arithmetic comparison operands with three
  or more terms; two-term grouping is preferred.

## Comments and method ordering

- Functions/methods longer than five counted body lines need a purpose documentation
  comment immediately above the declaration. Type annotations alone do not count.
  Short methods need comments when intent is unclear.
- Explain non-obvious purpose, contracts, invariants, ownership or control flow
  above the relevant declaration/group. Do not narrate obvious statements.
- Multiline block comments/docblocks put `/*` or `/**` on its own line, text on
  subsequent lines prefixed with aligned ` *`, and ` */` on its own line.
  Single-line comments and type annotations may remain on one line.
- Order methods by useful call flow: construction/initialization, entry points,
  supporting helpers. Preserve an already clear order. Do not move methods between
  owners or invent wrappers for presentation alone.

## Representation and processing

Each data type owns its representation and local invariants. Constructors,
destructors, copy/move operations, accessors, checked conversions and small local
queries may remain on it. Workers own stage algorithms and policy. Follow the
[compiler ownership rules](../AGENTS.md#data-and-processing-ownership); do not
interpret this separation as requiring passive records or companion helper classes.

## When handwritten C++ is introduced

These rules record the donor's relevant port conventions; they do not authorize
starting the port or constrain current PHP to converter-compatible syntax.

- Indent everything inside a namespace one level, including declarations,
  definitions and comments. Nested namespace blocks add another level.
- Keep pointers/references next to the declarator: `const string &value`,
  `const char *bytes`. Preserve include order and include only what is needed.
- Qualify current-instance fields and method calls with `this->`. Member
  declarations and constructor member-initializer names remain bare as C++ requires.
  Qualify static members with their owning class. Locals, parameters and free
  functions have no `this->` prefix; preserve `*this` where appropriate.
- Use friendship only when a worker needs privileged access. Prefer the normal
  interface when sufficient; no blanket friendship or public mutable state merely
  to bypass access control. Friends must preserve the data type's invariants.
- Apply emitted-code conventions in the owning generator, then regenerate.
  Never edit generated files or dependency snapshots as the final style fix.

## Provenance and verification

Consolidated from the donor [PHP style](../../../compiler/my-try/docs/code_style.md)
and [C++ style](../../../compiler/my-try-cpp/docs/code_style.md), with the explicit
property rule and current PHP-first direction taking precedence. Their converter,
build, storage and lifecycle requirements are not imported by these style rules.

Review grouping, purpose comments, ownership and method order manually. A generic
formatter does not enforce all these conventions. There is no imported local style
checker; use focused behavioral tests when changing executable code, as described
in the [compiler README](../README.md).
