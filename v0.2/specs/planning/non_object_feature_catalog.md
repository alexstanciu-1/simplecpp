# Remaining compiler features excluding objects and structs

Doc Status: planning

Updated: 2026-11-07, after the boxed scalar result and conversion decisions.
Scope: active v0.2 PHP compiler, metadata generator and native runtime. This is a
feature-group inventory, not a promise to implement every donor feature or every
standard-library function. No new implementation or catalog-wide test run accompanied
this review. Existing source, tests and owning contracts supply the status evidence.

Classes, structs, fields, methods, inheritance, interfaces, traits, constructors,
destructors and record ownership adapters are excluded from this queue. Their absence
must not be worked around by importing donor structures. Enums and non-object callable
features remain listed because they are separate language concepts.

The older [chapter catalog](compiler_catalog.md) remains a donor navigation map.
This inventory supersedes its older broad pending claims for carriers and containers.
Historical slice documents describe their original boundaries: later string operators,
scalar families and carrier operations supersede some of their deferred-work paragraphs.

## Working baseline

Already available in bounded form:

- Six canonical scalar identities: null, bool, int64 (int), uint8 (byte), float64
  (float), string. Generated conversions, active numeric operators, string comparison
  and concatenation, strict identity, logical operators and checked integer power.
- Typed/inferred locals, assignments, scalar compounds and discarded update values;
  literal constants, namespaces/use, explicit bounded globals.
- Typed functions/calls/returns, trailing literal defaults and mutable reference parameters.
- Braced if/elseif/else, while/do-while/for, bare break/continue.
- Published nullable/result/result_or_false/result_or_bool specializations, checked
  extraction/wrapping, payload operators, conditions and carrier isset.
- vector<int|string>: typed empty locals, converted append, copied indexed reads.
- hash<int|string,int|string>: typed empty locals, converted keyed writes and copied reads.
- Incremental frontend/publication/preparation/emission/native builds, recovery and
  repeated-generation cleanup proofs. New features must participate in these existing owners.

## Remaining catalog

“Runtime available” means an existing bounded native API, not source admission.
“Compiler gap” includes registration, preparation, lowering and incremental proofs.
“Discuss” marks a policy or API boundary requiring review before implementation.

| ID | Area | What is missing from the current source compiler | Existing foundation / boundary |
| --- | --- | --- | --- |
| BX-01 | mixed/dynamic scalar values | Implemented for active scalar kinds | Default independent providers expose boxing, value copies and checked scalar conversion at typed boundaries |
| BX-02 | mixed/dynamic containers | Append/reset, populated construction, nested access and runtime-checked boxed keys | Typed empty locals and single-level int/string reads and writes share key normalization with JSON. Native storage owns mixed copy-on-write and dynamic aliasing |
| BX-03 | Boxed operations | Truthiness/probes, further operators and compound/update rules | Numeric addition and ordering use runtime dispatch generated from scalar plans |
| BX-04 | Family boundaries | Explicit mixed↔dynamic scalar/null crossing and checked rejection of container crossing | Native scalar bridges exist. Typed destinations do not authorize implicit family crossing; recursive container conversion remains deferred |
| CT-01 | Collection utility operations | size/count/empty, hash membership/find/remove/clear and int-key append; vector keyed replacement and other existing native methods | Bounded native methods exist; source syntax and helper binding need agreement. Read failure differs: hash at throws, boxed miss returns family-null |
| CT-02 | Collection value boundaries | Ordinary vector/hash copying, assignment, parameters and returns | Native owning copies exist. Passing vector payloads through carriers is already supported and does not imply all ordinary collection boundaries |
| CT-03 | Collection construction/composition | Nonempty literals/data notation, nested containers, broader published element pairs | Contextual empty initializer/type-reference/export path exists; new specializations need payload capability checks, publication and template-free bindings |
| CT-04 | Iteration | foreach over typed and boxed containers, key/value binding and mutation rules | Public traversal is still runtime debt RT-DEBT-01. This needs runtime API work as well as compiler lowering; do not expose private storage |
| CR-01 | Result/error handling | Source error construction/inspection, take, explicit propagation helpers | Native structured error and result storage exist. Current carrier extraction/conditions are already implemented |
| CR-02 | Carrier extensions | Compound/update writes, more payload specializations, nested compositions and direct text/output handling | Packed conversion code and generated payload handlers exist; composition/admission must be checked rather than assumed |
| ST-01 | String API | Source length/search/slicing and other published methods/helpers, indexing and mutation policy | Native string API exists; choose a small source binding slice. data() remains metadata-marked compiler-internal; no source borrowed-pointer API is proposed |
| ST-02 | Text boundaries | Scalar-to-string conversion/formatting, broader echo, interpolation | String equality/order/concatenation already work. General to_string and formatting/output contract remain separate |
| TY-01 | Additional numeric representations | Activate optional integer widths in compiler generation and exercise family rules | Runtime numeric module exists. Byte/uint8 is already core. Additional floats/char representations need a separate contract/runtime inventory; do not assume availability |
| TY-02 | Enums and type naming | Enum declarations/identity/backing/value access; source aliases where admitted by specs | No source-defined enum model yet. Review namespace/import and incremental identity effects; no record implementation required |
| EX-01 | Conditional expressions/probes | Ternary, coalescing/coalescing assignment, broader isset/empty, null conditions | Carrier isset and truthiness are already present. General branch result typing and lazy evaluation need their own preparation path |
| EX-02 | Value-producing mutations | Assignment expressions/chains, prefix/postfix update values | Discarded updates and compound statements already work. Review evaluation-order debt before adding snapshots or sequencing |
| EX-03 | Constant expressions | General constant/default initializers and safe constant evaluation | Current literal-only constants/defaults and scalar occurrence facts are not a general constexpr evaluator |
| CF-01 | Control-flow extensions | Unbraced statements, omitted/comma for clauses, switch/match where specified, numbered/labelled transfers | Existing loops/branches and bare break/continue are the baseline. These are separate slices, not prerequisites for boxed values |
| FN-01 | Function signatures/calls | Variadics, named arguments, nonliteral defaults, trailing commas and signature inference | Existing typed signatures, defaults and calls can be extended; donor admission is not automatic authorization |
| FN-02 | References | Local aliases, read-only references, reference returns/defaults and escaping-borrow rules | Mutable whole-storage reference parameters already work. Collection interior references need separate lifetime/storage policy |
| FN-03 | Callable values | Function values, callable signatures, closures and captures | Not implemented. Reference captures require explicit escape/lifetime analysis; no object subsystem should be silently imported |
| FN-04 | Overloads/generics | User-defined overload selection and user templates | Runtime metadata specialization is not general source template support. Keep outside boxed integration |
| ER-01 | Exceptions | throw/try/catch/finally and cleanup across exits | Native runtime throws already occur; test-only C++ catch fixtures are not source-language exception support. Exception hierarchy work may cross the excluded object boundary |
| SY-01 | Globals/declarations | Direct global writes, string/collection globals and initialization lifetime/order; broader import/export cases | Bounded scalar globals exist; exact mutable reference calls can already mutate global storage. General constant evaluation is EX-03 |
| RT-01 | JSON | Source decode/encode bindings and result<mixed|dynamic> integration | Native JSON module and boxed results exist; family-specific decode and error handling make this a useful boxed integration consumer |
| RT-02 | Other runtime functions | Source datetime bindings; filesystem/IO/process/math/string library inventory and bindings | Native datetime exists. Other library families need individual runtime/metadata audits; legacy builtin availability is not v0.2 support |
| AS-01 | Async/generators | async/await, generators/yield and related lifetime/execution model | No active source path identified in this review; separate contracts and runtime assessment, not prerequisites here |
| IN-01 | Integration debt | Dependency-module declaration import/version validation, richer runtime callable ingestion, export visibility | Module loading and public type exports exist; dependency scanning/version checks were deliberately deferred |
| IN-02 | Continuing discipline | Incremental watches/reset/retirement for every added feature; native MT phase ownership; evaluation-order debt | Existing infrastructure is not missing or a proposed rewrite. Native threading and timing optimizations stay separate |

## Suggested direction before records

1. **BX-01 and first BX-03 operations implemented.** Scalar/null construction, copies, typed conversions, numeric addition and ordering use the common provider/export path.
2. **BX-02/BX-04: boxed containers and boundaries.** Agree the smallest empty construction
   spelling and source key policy, then integrate reads/writes and explicit family crossing.
   Prove mixed copy isolation, dynamic alias-visible mutation and independent binding replacement.
3. **CR-01 plus RT-01: usable errors and JSON.** Agree whether take/error inspection comes
   first or as a small prerequisite. JSON is a practical end-to-end consumer, not permission
   to bypass result handling.
4. **CT-01/CT-04: helpers and traversal.** Utility methods can land independently; foreach
   must wait for an agreed public traversal contract.

This order is a proposal, not an implementation goal or authorization. BX-03 operators
can be grouped after the boxed boundaries are stable instead of forcing all boxed behavior
into the first slice. Structs are not a prerequisite for the existing boxed runtime.

## Generated metadata and lowering for mixed/dynamic

The [scalar result and destination contract](../../../specs/dynamic_types.md#v02-scalar-results-and-typed-destinations--2026-11-07)
settles runtime kind dispatch, context-aware conversion, mixed arithmetic result types
and explicit scalar operand casts. Operators with a fixed scalar result retain that
type across admitted boxed forms. Generated preparation takes the result from the
operator contract and lowering returns it directly, e.g. bool for ordering. Proof of a payload kind may simplify lowering while
preserving the source expression type.

Both families activate by default. Each has its own runtime submodule and public
include header. The scalar kinds are null, bool, int64, float64 and string.
Byte/uint8 values use conversion to int64 for boxing. The box has one integer tag.

The boxed metadata for scalar dispatch lists the supported kinds. The generator
derives casts, operator admission, promotions and result types from the existing
scalar plans and applies the agreed boxed rules. Maintain one source for each
permission. Generated PHP preparation and C++ lowering may specialize common cases.

Generator mechanics for the scalar slice:

1. Connect each supported kind to its existing runtime factory/getter. For binary
   operations, use the actual left and right scalar identities in their source order.
   Knowing one operand type eliminates one runtime tag check.
2. Generate direct PHP registration and preparation handlers. Parsing can reuse the
   normal named-type and operator syntax. For each admitted operation and known peer,
   precompute its source result type, scalar execution paths and lowering binding. Preparation can
   select a fact on the existing AST without scanning metadata or enumerating kinds.
3. Generate the corresponding concrete C++ dispatch from those same plans. A helper
   for a boxed left operand and a known int right operand needs only one tag switch.
   Reuse the selected scalar promotion, checked conversion and arithmetic bindings.
   Emit concrete public signatures and keep dispatch bodies in the runtime implementation.
   Lowering consumes the approved binding and honors operand evaluation and lazy RHS rules.
4. Repeated branches in generated output are acceptable when they shorten preparation
   or emitted application code. Measure preparation, generated code size and Clang
   timings before expanding specializations across destinations and operand positions.

## Decisions to settle for mixed/dynamic

- Which minimal construction syntax expresses an empty dynamic container distinctly from null?
- Which source key normalization/checking rules apply before the existing runtime key API?
- Which first failure observation is sufficient before source error handling lands?

The higher-level [separated-family amendment](../../../specs/dynamic_types.md#v02-separated-mixeddynamic-families--2026-10-05)
is authoritative: both families contain scalar/null values and own-family containers;
dynamic is not the old object-only/shared-mixed representation. Cross-family container
conversion rejects, including empty containers. Null mixed may bootstrap on write;
null dynamic needs explicit container construction. Strong dynamic cycles remain a
known runtime reclamation debt, not a reason to silently change identity semantics.

## Evidence and owning references

- [Generated compiler registry](../../compiler/generated/types/registry.php): current canonical identities and published generic specializations.
- [Parser](../../compiler/03_parse/parser.php), [type definitions](../../compiler/types/), [compiler tests](../../compiler/tests/): active admission and existing proof inventory.
- [Scalar completion](../../compiler/docs/scalar_completion.md), [carrier values](../../compiler/docs/carrier_values.md), [vector operations](../../compiler/docs/vector_operations.md), [hash operations](../../compiler/docs/hash_operations.md).
- [Boxed API](../boxed_slice.md), [mixed storage](../mixed_storage.md), [dynamic storage](../dynamic_storage.md), [boxed results](../boxed_results.md), [JSON](../json_module.md), [datetime](../datetime_module.md).
- [Array semantics](../../../specs/array_semantics.md), [data construction](../../../specs/data_initialization.md), [count/empty/isset](../../../specs/count_empty_isset_contract.md).
- [Donor catalog](../../../compiler/my-try/docs/catalog/README.md): topic inventory only; mined structures require discussion before adoption.
