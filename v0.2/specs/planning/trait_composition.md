# Trait composition in the bootstrap compiler

Doc Status: planning

The [inheritance contract](../../../specs/inheritance_v0_2.md#trait-composition) owns language semantics.

## Mandatory bootstrap support

Every row is mandatory for porting the compiler. Status refers to the v0.2 proof.

| Capability | Compiler use and acceptance criterion | Status |
| --- | --- | --- |
| Compose trait fields and methods into a class | `ast_declaration` contributes name, namespace, duplicate storage and operations together. Each consumer owns its members. | Direct composition proven |
| Preserve visibility, field types, defaults and readonly rules | Shared declaration fields and private specialization caches retain their authored contracts. Check ordinary readonly initialization and write rejection on contributed fields. | Proven on all three flavors for constructor initialization, defaults, rejected writes, and public/private/protected access with incremental recovery |
| Let trait methods use `$this` and host members | Collection traits call host hooks and `Cpp_Generator` uses host emitter state and methods. Check a private hook independently on two consumers. | Counter proof |
| Declare required methods | `ordered_keyed_store` declares abstract hooks. Check the effective signature after composition and inheritance, including an inherited implementation. | Pending |
| Resolve `parent::` through the containing class | `ast_string_part_range` extends its host’s inherited source-position behavior. Test one origin in hosts with different parents. | Pending |
| Allow a host method to replace a trait method | A declaration customizes one operation while keeping the rest. Define ordinary method precedence in the owning spec. | Required contract change |
| Alias a trait method and change the alias’s visibility | `name as private namespace_name` must retain the original implementation under a private name alongside the host’s own `name`. | Required adaptation |
| Diagnose member conflicts clearly | Two independent traits contributing conflicting fields or methods must identify the conflicting members and their origins. | Full diagnostic proof pending |

## Implementation order

1. Direct composition. A class or struct owns fields, method identities and prepared bodies contributed by a direct use. Each contributed member points back to its trait source for tokens, diagnostics and lexical names. Test Counter in two units, independent state, add/remove, failure and fresh recovery. Emit through ordinary record and method lowering.
2. Prove lexical lookup and consumer binding independently. Resolve an imported free function at the trait origin and a private hook on the consumer. Exercise a failure unique to one consumer.
3. Prove readonly and access enforcement, then bind `parent::` against the consumer’s resolved base.
4. Settle ordinary host-method replacement and alias visibility together in the language contract. Resolve contributions and aliases before scheduling bodies; diagnose unresolved competing contributions with both origins.
5. Extend the use graph. Resolve nested uses in their originating scope, reject cycles and duplicate direct uses, and deduplicate diamonds by origin identity. Observe intermediate traits even when they contribute no members themselves.
6. Add abstract requirements separately from concrete contributions. Multiple obligations can share one name. Reuse directional callable checks, access rules and interface bridges. Static fields belong to each direct consumer. Static calls distinguish the consumer, its parent and the called type.
7. Select lifecycle methods before scheduling bodies. A local constructor or destructor takes precedence over one contribution. Two distinct competing contributions still conflict. Use ordinary constructor and cleanup rules.

Consumer count does not select a public ABI. Later, measure sharing receiver-independent bodies through a private free function. Bodies with field access or consumer dispatch initially stay in their consumer unit. Changes to an origin can invalidate a consumer. Adding another consumer alone must leave existing consumers reusable.

## PHP reference

PHP allows a class to use several traits and a trait to use other traits. A locally authored method takes precedence over a contributed method, which takes precedence over an inherited one. Conflicting trait methods can be selected with `insteadof`. An `as` adaptation can introduce an additional name or change visibility. PHP also supports abstract methods, static members, properties and constants. The published Simple C++ contract currently rejects ordinary same-category collisions. The mandatory host replacement and private alias above require a contract revision. PHP compatibility is reviewed feature by feature. [PHP manual](https://www.php.net/manual/en/language.oop5.traits.php)
