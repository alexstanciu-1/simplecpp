# Trait composition in the bootstrap compiler

Doc Status: planning

The [inheritance contract](../inheritance_v0_2.md#trait-composition) owns language semantics.

## Mandatory bootstrap support

Every row is mandatory for porting the compiler. Status refers to the v0.2 proof.

| Capability | Compiler use and acceptance criterion | Status |
| --- | --- | --- |
| Compose trait fields and methods into a class | `ast_declaration` contributes name, namespace, duplicate storage and operations together. Each consumer owns its members. | Direct composition proven |
| Preserve visibility, field types, defaults and readonly rules | Shared declaration fields and private specialization caches retain their authored contracts. Check ordinary readonly initialization and write rejection on contributed fields. | Proven on all three flavors for constructor initialization, defaults, rejected writes, and public/private/protected access with incremental recovery |
| Let trait methods use `$this` and host members | Collection traits call host hooks and `Cpp_Generator` uses host emitter state and methods. Check a private hook independently on two consumers. | Counter proof |
| Declare required methods | `ordered_keyed_store` declares abstract hooks. Check the effective signature after composition and inheritance, including an inherited implementation. | Concrete consumers proven for local, inherited and contributed hooks; abstract slots, descendant overrides and sibling recovery proven |
| Resolve `parent::` through the containing class | `ast_string_part_range` extends its host’s inherited source-position behavior. One trait calls different parent implementations in two consumers. | Proven in all three flavors, including reparenting, missing and inaccessible methods, and incremental recovery |
| Allow a host method to replace a trait method | A declaration customizes one operation while keeping the rest. Consumer-authored ordinary instance methods take precedence. | Proven in all three flavors |
| Alias a trait method and change the alias’s visibility | `name as private namespace_name` must retain the original implementation under a private name alongside the host’s own `name`. | Private named aliases proven in all three flavors |
| Diagnose member conflicts clearly | Two independent traits contributing conflicting fields or methods must identify the conflicting members and their origins. | Both origin locations and recovery proven in all three flavors |

## Implementation order

1. Direct composition. A class or struct owns fields, method identities and prepared bodies contributed by a direct use. Each contributed member points back to its trait source for tokens, diagnostics and lexical names. Test Counter in two units, independent state, add/remove, failure and fresh recovery. Emit through ordinary record and method lowering.
2. Prove lexical lookup and consumer binding independently. Resolve an imported free function at the trait origin and a private hook on the consumer. Exercise a failure unique to one consumer.
3. Prove readonly and access enforcement, then bind `parent::` against the consumer’s resolved base.
4. Settle ordinary host-method replacement and alias visibility together in the language contract. Resolve contributions and aliases before scheduling bodies; diagnose unresolved competing contributions with both origins.
5. Extend the use graph. Resolve nested uses in their originating scope, reject cycles and duplicate direct uses, and deduplicate diamonds by origin identity. Retain intermediate origins even when they supply no members. Proven on all three flavors with namespace imports, a diamond, cycles, deletion, retargeting and fresh-build recovery. Named adaptations remain scoped to a single direct use in a class or struct.
6. Add abstract requirements separately from concrete contributions. Multiple obligations can share one name. Reuse directional callable checks, access rules and interface bridges. Static fields belong to each direct consumer. Static calls distinguish the consumer, its parent and the called type.
7. Select lifecycle methods before scheduling bodies. A local constructor or destructor takes precedence over one contribution. Two distinct competing contributions still conflict. Use ordinary constructor and cleanup rules.

Consumer count does not select a public ABI. Later, measure sharing receiver-independent bodies through a private free function. Bodies with field access or consumer dispatch initially stay in their consumer unit. Changes to an origin can invalidate a consumer. Adding another consumer alone must leave existing consumers reusable.

## PHP reference

PHP allows a class to use several traits and a trait to use other traits. A locally authored method takes precedence over a contributed method, which takes precedence over an inherited one. Conflicting trait methods can be selected with `insteadof`. An `as` adaptation can introduce an additional name or change visibility. PHP also supports abstract methods, static members, properties and constants. Simple C++ supports local ordinary method precedence and private named aliases. Competing trait contributions remain errors even when the host provides its own method. PHP compatibility is reviewed feature by feature. [PHP manual](https://www.php.net/manual/en/language.oop5.traits.php)

## Completion backlog

Each item uses separate origin and consumer files and compares failed and recovered resident generations with fresh compilation in all three flavors.

| Order | Deliverable | Acceptance |
| --- | --- | --- |
| T1 | Contribution conflicts | Identify both owners and source positions for conflicting fields and methods, preserve a valid diamond, remove and restore a conflict. |
| T2 | Member constants | Compose ordinary typed literal declarations, retain origin tokens and visibility, reject invalid unused constants. The bootstrap constant evaluator currently accepts literals. |
| T3 | Static properties | Use the ordinary static-storage path. Two direct consumers have separate storage; a descendant shares its declaring consumer’s storage. |
| T4 | Static methods | Retain lexical source when parsing called-type instances. Exercise self, parent and late-static selection plus an edit to the trait body. |
| T5 | Constructors | Select one lifecycle contribution before preparation. Test defaults, parent initialization, local precedence, competing traits and ordinary initialization failure. Follow supported record constructor syntax; set-parameter syntax needs an ordinary constructor implementation first. |
| T6 | Destructors | Ordinary user-authored record destructor support is a prerequisite. Prove nonthrowing admission, access, exactly-once cleanup, copy/move behavior and member/base order before applying trait selection. |

The six bootstrap slices are implemented. The [member proof](../../compiler/tests/trait_members_native.py)
covers contribution conflicts, independent static storage, called-type dispatch and
constructor selection. The [lifecycle proof](../../compiler/tests/trait_lifecycle_native.py)
covers native destruction, copy/move, failed construction and incremental recovery.
The ordinary constructor grammar still bounds the supported parameter forms.
