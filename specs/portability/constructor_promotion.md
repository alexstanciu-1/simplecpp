# Portable constructor inputs
Doc Status: supporting

PHP property promotion supplies an explicit association between a constructor input
and its property:

```php
public function __construct(public switch_label_kind $label_kind) {}
```

The converter declares the property separately. For `--target=v0.2` it emits
`__construct(set:$label_kind)`, using the property's type and the existing native
initialization contract. Parameter defaults remain on the parameter, not on the
property. Ordinary typed parameters stay ordinary; the converter does not infer
initialization from arbitrary body assignments. The legacy target retains separate
typed parameters and initial body assignments, which remain sufficient for the
proved root-class cases.

Computed body assignments require an honest initial value when the native contract
needs one. For example, declare `public int $value = 0;` before computing
`$this->value = $argument + 1;`. Enum defaults select an explicit existing case;
the converter invents neither an unset case nor an implicit first-case rule.
Native default-construction behavior and later assignment remain distinct.

The focused proof is `tests/portability/constructor_promotion.py --results FRESH`.
It exercises explicit enum inputs/defaults, private promotion, mixed ordinary/set
parameters, argument/body ordering, independent parameter reassignment, computed
fields and failure before successful construction. It also constructs the actual
compiler switch_label in PHP. Native fixture execution is not a native rebuild of
the whole compiler or its AST hierarchy.

## Bounded inherited-constructor contract

Promotion may accompany a leading standalone `parent::__construct(...)` call when
the parent and argument evaluation cannot observe child state or publish the
partially constructed receiver. Authors must audit that requirement; the converter
does not infer parent behavior. PHP initializes promoted fields before the body;
native base construction precedes child initialization. Required behavior must be
independent of that difference, including failures and callbacks. Exact cleanup
timing is not promised.

For the legacy target, the converter leaves this leading parent call ahead of its
own generated promotion assignments so the generator recognizes the base initializer.
Comments and whitespace may precede the call. No authored statement is reordered;
a later, conditional or embedded parent call is not hoisted. v0.2 continues using
`set:` parameters and its existing base-construction machinery. This introduces no
Simple C++ semantics and does not infer initialization from body assignments.

The focused fixture proves a required promoted object with a nondefault parent,
shared identity after mutation, and parent field initialization across PHP, legacy,
and both v0.2 modes. Root constructor ordering and failure proofs remain covered.

The facade's `same_exception` helper now uses v0.2 shared-object identity admission.
The production source_initialization.py portability fixture executes same/distinct
exception comparisons through PHP, legacy and both v0.2 modes. Constructor failure
proofs remain separate; this does not prove every exception-facade operation.

## Required objects created by a helper

When a shared initialization helper allocates required object fields, an existing
source-only alternative is to accept those objects as promoted constructor inputs.
Move duplicate field declarations out of traits into their consuming owners. A
concrete factory can allocate fresh inputs and invoke the constructor; the body
then configures already-initialized objects without publishing an incomplete node.
The compiler Callable_Syntax adaptation proves this shape through focused extracted
production constructors on both routes, with actual host scope/reuse checks.
See `tests/portability/callable_construction.py` and the dated callable_construction
evidence. This does not authorize helper-body inference or nullable substitutes.
Use a descriptive factory name such as fresh_node: an authored create method hides
the legacy generator's unqualified create<T> allocation helper.

## Required access receivers already available at allocation

The compiler's index_node and field_access_node accept their required base through
promoted expression_node constructor inputs. Parser access_suffix passes the same
existing receiver at the original allocation point; subsequent token consumption,
index parsing, span finalization and collection remain in order. No nullable backing
is needed for an input already available before construction. The source fixture
`tests/portability/access_base_construction.py` proves exact receiver identity and
child/maintenance traversal through PHP, legacy and both v0.2 output modes; actual
parser/indexing tests cover retained recovery separately.

## Token handles used during construction

Parser_Run, Token_Cleanup and collected_file promote their existing token_list
input. Parser_Run retains the supplied handle before its body; token cleanup,
append, retained-range reset and cursor placement remain in their original order.
Those operations mutate the same token list without observing the new worker or
rebinding the constructor parameter. collected_file retains its explicit set_tokens
operation for later updates. This is input promotion, not staged-field conversion.

`tests/portability/tokens_construction.py` checks extracted production constructors
and token operations across routes. Its Parser_Run extraction stops after cursor
placement, with an AST-cleanup stand-in; actual token_cleanup, token_generations
and indexing host tests cover whole-parser retention and recovery separately.
