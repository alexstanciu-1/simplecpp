# Compiler coding style

Doc Status: supporting

Applies to handwritten compiler implementation, helpers, tests and tools under
`v0.2/compiler/`. Generated output and historical evidence are excluded. This is
implementation discipline, not a restriction on the language being compiled.
Apply these rules to all handwritten code in this compiler tree and preserve them
in subsequent changes. PHP-specific layout and type rules apply to PHP; Python
host/test harnesses retain Python syntax and four-space indentation.

## PHP types and declarations

- Give every class, interface, trait and enum a directly attached doc comment
  explaining its role, ownership or place in the compiler flow. For AST and fact
  records, include a short source-code example where it clarifies what the record
  represents, followed by the mapping to its fields or facts. Label flavor-specific
  examples and distinguish parsed-only syntax from executable support. Do not add
  examples that merely repeat a class name or imply unimplemented functionality.

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
/** Range 1..0xFFFFFF. */
public int $length /** uint32 */;
public array $tokens /** list<token> */;

public function offset(): int /** uint32 */
{
	return $this->offset;
}
```

- Annotate integer declarations with a reviewed `int8/16/32/64` or
  `uint8/16/32/64` intent. Put parameter annotations after the parameter name,
  before any default value; put return annotations after the PHP return type.
- All inline type annotations contain only the native type or collection shape.
  This applies to properties, constants, enum backing types, parameters, returns
  and any annotated locals. Move explanations, ranges, units, invariants and
  ownership intent above the owning declaration or statement. For properties,
  use a doc comment above the property; do not append prose after it. For example:

  ```php
  /** Keyed by body statement position; native non-owning statement index. */
  public readonly array $locals /** array<uint32,ast_local_declaration> */;
  ```

- Put parameter and return explanations in the function/method's doc comment,
  naming each parameter or the return value so the association remains clear.
  The same rule applies to constructors and closures:

  ```php
  /**
   * $start: token position.
   * $end: exclusive token position.
   */
  public function __construct(int $start /** uint32 */, int $end /** uint32 */)
  {
  }
  ```

- Put a one-line local explanation in a `//` comment immediately above its
  statement. For multiline explanations, use a `/** ... */` doc comment with the
  normal multiline layout. Keep only the type beside the variable:

  ```php
  // Token position.
  $value /** uint32 */ = $this->value();

  /**
   * Token position in the retained generation.
   * Includes the EOF boundary.
   */
  $value /** uint32 */ = $this->value();
  ```

- Storage width and permitted range are separate. Use inline `uint32` plus the 24-bit
  token-length range in the preceding doc comment, not an invented `uint24` storage type. Document narrower
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
| AST start/end and header end | `uint32`; token positions, end exclusive, tied to the root token generation. |
| Source size, byte offsets, exclusive byte ends, scanner position | `uint32`; source size is at most 0xFFFFFFFF and EOF equals source size. |
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

## Native reference ownership

Document retained object references and object collections as owning, shared-owning,
or non-owning in a doc comment above the property. Name the lifetime owner when it
is not clear. For collections, describe element/reference ownership separately from
the container. Enum-valued properties are values and do not need reference ownership.

For example, a function body owns its scope and statement nodes; scope parents,
declaration indexes and resolved declaration links are non-owning. Retained source
generations may be shared. `readonly` prevents reassignment and does not define
ownership. These comments describe native intent; PHP still uses ordinary object
references, and this convention does not introduce PHP `WeakReference` objects.

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
- Inside each class, place member groups in this order. The corresponding markers
  are used only when the size thresholds below are both met:

  1. Constants: `/** CONSTANTS */`
  2. Static properties: `/** STATIC PROPERTIES */`
  3. Instance properties: `/** PROPERTIES */`
  4. Constructor: `/** CONSTRUCTOR */`
  5. Destructor: `/** DESTRUCTOR */`
  6. Instance methods: `/** METHODS */`
  7. Static methods: `/** STATIC METHODS */`

  Always separate nonempty groups with one blank line. Add group markers **only
  when the class has more than two nonempty groups and more than five declared
  members** (both conditions must hold). Count each constant, property, constructor,
  destructor and method as one member; inherited members and trait-use directives
  do not count. Static and instance members belong to their respective groups.

  For qualifying classes, mark every nonempty group, placing the marker before
  any documentation attached to its first member. For smaller classes, omit all
  group markers while keeping the same ordering and blank-line separation. Empty
  groups are always omitted. Group markers do not replace purpose, type or
  ownership documentation.
- Within each method group, order by useful call flow: entry points, then supporting
  helpers. Do not move methods between owners or invent wrappers for presentation
  alone.

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

Run `python3 -B v0.2/compiler/tools/check_style.py` from the repository root.
The local PHP-token-based check covers tabs, multiline blocks, named-function brace
placement, long-function purpose comments, type role comments and type-only inline
annotations, class group ordering/separation and the conditional marker thresholds.
It is registered as `compiler_style` in CTest. Strings and embedded
source samples are excluded from layout checks.

Review grouping, purpose-comment quality, ownership meaning, native widths, method
order and line wrapping manually; the mechanical check does not prove these.
For a formatting pass, compare executable tokens and literal contents, then run
focused behavioral tests as described in the [compiler README](../README.md).
