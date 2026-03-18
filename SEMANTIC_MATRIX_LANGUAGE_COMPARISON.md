# SEMANTIC_MATRIX_LANGUAGE_COMPARISON.md

Legend:
- 🟢 SAME
- 🟡 SIMILAR
- 🔴 DIFFERENT
- ⚫ N/A

Scope:
- This comparison is **matrix-aligned**, not syntax-aligned.
- The baseline is the current `SEMANTIC_MATRIX.md` for Simple C++.
- "Same" means meaningfully identical at the semantic rule level.
- "Similar" means there is a close analogue, but important coercion / typing / enforcement differences exist.

| Matrix Area / Rule | Simple C++ Baseline | C++ | Python | JavaScript | PHP | Notes |
|---|---|---:|---:|---:|---:|---|
| Primitive wrapper entry (`bool_t`, `int_t`, `float_t`, `string_t`) | Explicit wrapper entry surface | 🔴 | 🔴 | 🔴 | 🔴 | None of the compared languages require language-visible semantic wrappers for ordinary values. |
| `bool -> int` implicit path | allowed | 🟢 | 🟢 | 🟡 | 🟡 | C++ and Python align closely; JS/PHP also coerce booleans numerically, but inside much broader coercion systems. |
| `int -> float` implicit path | allowed | 🟢 | 🟢 | 🟡 | 🟡 | Python and C++ are closest; JS/PHP collapse numeric categories more aggressively. |
| `float -> int` implicit path forbidden; explicit only | baseline | 🔴 | 🔴 | 🔴 | 🔴 | All four languages permit other conversion idioms or implicit coercion patterns not matching this rule. |
| `string -> bool/int/float` implicit path forbidden; explicit only | baseline | 🔴 | 🔴 | 🔴 | 🔴 | All four differ strongly: C++ stream/constructor world, Python constructors and truthiness, JS coercion, PHP juggling. |
| `null -> nullable/pointer-like only` | allowed | 🟡 | 🔴 | 🔴 | 🔴 | C++ `nullptr` is pointer-oriented, not optional-oriented; Python/JS/PHP null-like values participate much more broadly. |
| `null -> primitive/string` implicit conversion forbidden | baseline | 🟡 | 🔴 | 🔴 | 🔴 | C++ differs because `nullptr` is convertible to `bool`; Python/JS/PHP all have broad null truthiness/coercion behaviors. |
| `null == anything` / `null != anything` allowed | baseline | 🔴 | 🟡 | 🔴 | 🔴 | Python allows broad equality comparisons but different-type objects usually compare unequal; JS/PHP equality rules differ much more. |
| `null` equality true only for semantic-null states | baseline | 🟡 | 🟡 | 🔴 | 🔴 | C++/Python have some analogous null identity/value ideas, but JS/PHP loose equality diverges sharply. |
| `null` relational comparison universally forbidden | baseline | 🟡 | 🟡 | 🔴 | 🔴 | Python often raises for nonsensical ordering; C++ has pointer ordering corner cases; JS/PHP coerce more. |
| unresolved `auto x = null` forbidden | baseline | 🔴 | ⚫ | ⚫ | ⚫ | Python/JS/PHP do not have typed `auto` deduction in this sense. C++ allows `auto x = nullptr`. |
| `int_t + int_t -> int_t` | baseline | 🟢 | 🟢 | 🟡 | 🟡 | JS/PHP numeric systems differ, especially with string interaction and unified number/coercion models. |
| mixed `int_t + float_t -> float_t` | baseline | 🟢 | 🟢 | 🟡 | 🟡 | Python and C++ align closely; JS/PHP are more coercive or unified. |
| `bool` arithmetic forbidden | baseline | 🔴 | 🔴 | 🔴 | 🔴 | C++, Python, JS, and PHP all allow boolean-to-number behavior more broadly than Simple C++. |
| string arithmetic forbidden except concatenation | baseline | 🟡 | 🔴 | 🔴 | 🔴 | C++ has no built-in string arithmetic but `std::string +` exists; Python uses concatenation but not numeric mixing; JS/PHP coerce aggressively in places. |
| vector arithmetic forbidden | baseline | 🟡 | 🟡 | 🔴 | 🔴 | Python lists/JS arrays/PHP arrays all differ; C++ containers have no built-in arithmetic but the semantic model is not the same wrapper contract. |
| `bool_t` supports only equality/inequality | baseline | 🔴 | 🔴 | 🔴 | 🔴 | All four languages allow broader truthiness or numeric/loose comparison behaviors around booleans. |
| numeric comparisons allowed with defined promotion | baseline | 🟢 | 🟢 | 🟡 | 🟡 | JS/PHP again differ because coercion rules are broader and less tightly scoped. |
| `string_t` lexicographic comparison only vs `string_t` | baseline | 🟡 | 🟡 | 🔴 | 🔴 | Python and C++ can be close for strings, but their surrounding type/comparison models are broader. JS/PHP loose comparison rules differ strongly. |
| cross-family primitive comparisons mostly forbidden | baseline | 🔴 | 🟡 | 🔴 | 🔴 | Python is closer because different types usually do not compare equal unless special-cased; JS/PHP are the furthest. |
| object/ownership wrapper identity comparison only | baseline | 🟡 | 🔴 | 🟡 | 🟡 | C++ smart pointers have pointer identity analogues, but the exact wrapper model differs; Python/PHP object comparison semantics are customizable or broader. |
| relational comparison on ownership wrappers forbidden | baseline | 🟡 | 🔴 | 🟡 | 🟡 | C++ raw/smart pointer ordering history differs; Python often errors; PHP/JS object comparison models are not analogous. |
| cross-wrapper ownership comparison forbidden | baseline | 🔴 | 🔴 | ⚫ | ⚫ | The compared languages do not expose the same managed-wrapper families as first-class language constructs. |
| logical operators require semantic-boolean operands | baseline | 🔴 | 🔴 | 🔴 | 🔴 | Python/JS/PHP all accept broader truthy/falsy operand families; C++ allows contextual conversion to bool in more places. |
| `bool_t` usable directly in conditionals | baseline | 🟢 | 🟢 | 🟡 | 🟡 | All support boolean conditions, but JS/PHP also auto-coerce many non-boolean values. |
| comparison results lower to native `bool` and are directly usable in control flow | baseline | 🟡 | 🟢 | 🟢 | 🟢 | Most languages already use native boolean comparison results; the difference is that Simple C++ still keeps a separate semantic wrapper model for ordinary boolean values. |
| direct `int_t` conditions forbidden | baseline | 🔴 | 🔴 | 🔴 | 🔴 | All compared languages allow some numeric-to-boolean conditional path or contextual conversion. |
| direct `float_t` conditions forbidden | baseline | 🔴 | 🔴 | 🔴 | 🔴 | All four languages allow some numeric conditional behavior or bool conversion. |
| direct `string_t` conditions forbidden | baseline | 🔴 | 🔴 | 🔴 | 🔴 | Python, JS, and PHP all have string truthiness rules; C++ offers broader conversion freedom through host types. |
| pointer-like truthiness forbidden at source level; runtime-only contextual bool may exist narrowly | baseline | 🔴 | 🔴 | ⚫ | ⚫ | C++ is closest mechanically because explicit/contextual bool is possible, but Simple C++ treats it as a narrow runtime bridge rather than a source-language rule. |
| same-type plain assignment allowed | baseline | 🟢 | 🟢 | 🟢 | 🟢 | This is common everywhere, though the surrounding type systems differ. |
| cross-wrapper ownership assignment forbidden | baseline | 🔴 | ⚫ | ⚫ | ⚫ | No close analogue in Python/JS/PHP; C++ smart-pointer ecosystem differs. |
| compound assignment only for normalized allowed families | baseline | 🟡 | 🟡 | 🔴 | 🔴 | C++ and Python are somewhat closer; JS/PHP have wider coercive behavior. |
| explicit conversion helpers (`to_int`, `to_float`, `to_bool`, `to_string`) | baseline | 🟡 | 🟡 | 🔴 | 🔴 | C++ and Python have explicit conversion functions/constructors, but not this exact narrow helper contract. |
| invalid explicit string conversions fail at runtime | baseline | 🟡 | 🟡 | 🟡 | 🟡 | All four have some explicit conversion failure modes, but the exact mechanism and accepted values differ. |
| accepted bool strings are narrow (`true`,`false`,`1`,`0`) | baseline | 🔴 | 🔴 | 🔴 | 🔴 | None of the compared languages use this exact normalized accepted set. |
| case/whitespace policy intentionally explicit/open | baseline | 🟡 | 🟡 | 🟡 | 🟡 | All four have language/library-specific behavior, but not the same fixed contract. |
| vector wrapper exists as controlled runtime type | baseline | 🔴 | 🔴 | 🔴 | 🔴 | All compared languages have built-ins/containers, but not the same spec-driven wrapper role. |
| `nullable<T>` exists as explicit wrapper | baseline | 🟡 | ⚫ | ⚫ | ⚫ | C++ `std::optional` is library-level and closest; Python/JS/PHP nullability is not modeled as a generic wrapper type. |
| `nullable<T> == null` empty-state compare | baseline | 🟡 | ⚫ | ⚫ | ⚫ | Close only to C++ optional-like concepts, but not as a language-surface rule. |
| `nullable<T>` relational compare only when both non-null | baseline | 🟡 | ⚫ | ⚫ | ⚫ | Closest analogue is library-level optional behavior in C++; others do not match this wrapper pattern. |
| `shared<T>()`, `unique<T>()`, `weak(x)` helper surface | baseline | 🟡 | ⚫ | ⚫ | ⚫ | Closest only to C++ smart-pointer libraries; not language-level in the same way. |
| `weak<T>(...)` allocation forbidden | baseline | 🟡 | ⚫ | ⚫ | ⚫ | Closest analogue exists only in C++ smart pointers. |
| `shared_p` copy / `unique_p` move / `weak_p` expired-as-null` | baseline | 🟡 | ⚫ | ⚫ | ⚫ | Again mostly comparable only to C++ library smart-pointer semantics. |
| generated code must use only `scpp::` surface types/helpers | baseline | 🔴 | 🔴 | 🔴 | 🔴 | This is a Simple C++ design constraint, not a property of the compared languages themselves. |
| generated code must not expose native types / `std::*` | baseline | 🔴 | ⚫ | ⚫ | ⚫ | Mostly a code-generation rule specific to Simple C++. |
| native interop belongs outside generated language surface | baseline | 🔴 | ⚫ | ⚫ | ⚫ | Specific architectural rule for Simple C++. |
| overloads/templates excluded from generated language surface | baseline | 🔴 | ⚫ | ⚫ | ⚫ | C++ supports both heavily; Python/JS/PHP do not map cleanly to this exact rule. |
| forbidden unsupported operations should fail at compile time where practical | baseline | 🔴 | 🔴 | 🔴 | 🔴 | Python/JS/PHP are runtime languages; C++ differs because many unsupported cases are compile-time errors, but not under the same normalized wrapper contract. |

## Practical Reading Guide

The biggest semantic differences between Simple C++ and the compared languages are concentrated in:
1. `null` behavior
2. conditional truthiness
3. implicit conversion scope
4. object/ownership wrapper discipline
5. generated-code boundary rules

### Closest overall analogue
- **C++** is the closest mechanically, especially for numeric typing and smart-pointer-adjacent concepts, but it is still materially different because Simple C++ intentionally removes many native C++ freedoms.

### Furthest overall analogues
- **JavaScript** and **PHP** are the furthest because loose coercion and truthiness behavior differ sharply from the Simple C++ model.

### Python
- **Python** is often closer than JS/PHP on equality discipline, but it still differs strongly because almost any object has truth value and because there is no equivalent generated-language wrapper boundary.
