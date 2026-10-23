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

## Open inherited-constructor boundary

Promotion combined with an explicit `parent::__construct(...)` is not yet proved
portable. Legacy expansion places assignments before that call; its generator
recognizes a base initializer only at the first statement. The native base constructor
also runs before child property initialization, unlike PHP promotion's placement
before the authored body. Moving the call could change child-state observations.
Source adaptation versus a bounded no-child-observation convention remains under
discussion. This slice does not reorder parent calls or claim that combination.

Exception-facade integration is a separate pending boundary: the facade's
`same_exception` helper currently reaches v0.2 integer checking for shared-object
identity. Constructor failure proofs use checked arithmetic and do not establish
that unrelated facade's full admission.
