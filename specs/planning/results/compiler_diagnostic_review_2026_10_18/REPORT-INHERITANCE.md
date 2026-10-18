# Inheritance, interfaces, constructors and destructors — 2026-10-17
Doc Status: supporting

Review only; no repository files changed. Same current checkout and PHP-hosted compiler as REPORT.md. Batches 7–10 use the shared 12-slot runner with CLI PHP and Clang 18.1.3, checking every emitted translation unit separately with -fsyntax-only. No native compiler rebuild or execution claim.

## 1. Abstract inline constructor parameters escape preparation

    abstract struct A { abstract function f(): int; }
    class C { function __construct(A $a) {} }
    return 0;

Preparation and emission succeed. Clang rejects the constructor definition: parameter type 'record_A' is an abstract class. C need not be instantiated. Also reproduced with abstract class A and value<A> parameter, with an inherited constructor, and with a constructor body that invokes a method on the parameter.

Evidence: batch7/ctor_abstract_param, batch7/ctor_abstract_value_param, batch8/ctor_abstract_inherited, batch9/abstract_ctor_body. Each directory contains main.phs, prepared.txt, artifacts.json, cpp/ and result.json; command logs are in each batch's logs/.

Owner: 04_analyze/prepare/semantics/constructors.php, Constructor_Preparation::signature, ordinary parameter branch. It resolves the type and calls require_value_type but omits require_concrete_value. Ordinary Declaration_Preparation::parameter_contract does require_concrete_value for by-value parameters. Thus the same invalid parameter is rejected for an ordinary function and accepted for a constructor.

Controls: ordinary function taking abstract A by value rejects in preparation; concrete struct constructor input compiles; abstract class A passed as its default shared handle compiles. An abstract struct with no pure virtual methods also escapes the constructor check but passes Clang (batch8/ctor_empty_abstract); this is additional evidence of the semantic omission, not another Clang failure.

## 2. A public struct constructor with its own value type as sole parameter generates an illegal C++ constructor

    struct S { function __construct(S $other) {} }
    return 0;

Preparation and emission succeed. Clang reports: copy constructor must pass its first argument by reference.

Generated declaration: record_S(record_S local_other).

Evidence: batch9/ctor_self_value. No instance or call is needed. A two-parameter version and a private version pass Clang: the extra source parameter or compiler-only access key prevents the signature from being interpreted as an illegal C++ copy constructor. A class taking its normal shared handle also passes.

Owner: constructor signature admission and direct native-constructor mapping in 05_backend/cpp/declarations.php. This is an unestablished source-to-native constructor-shape guarantee. Do not automatically infer that the source language must forbid an ordinary constructor with this parameter; it may need an alternate lowering. The current compiler neither rejects it nor represents it successfully. No policy change or fix was made.

## 3. Void-expression returns in constructors/destructors pass ordinary return preparation but are illegal in their emitted C++ special members

Constructor:

    function cleanup(): void {}
    class C { function __construct() { return cleanup(); } }
    $c = new C();
    return 0;

Destructor:

    function cleanup(): void {}
    class C { function __destruct() { return cleanup(); } }
    return 0;

Both prepare and emit. Clang reports respectively that a constructor/destructor must not return a void expression. Both class and struct variants reproduce it; destructor instantiation is unnecessary because its emitted definition is invalid.

Evidence: batch9/dtor_void_control; batch10/ctor_void_return, dtor_void_minimal, ctor_struct_void, dtor_struct_void.

Owner: shared Body_Preparation::prepare_return admits void-to-void forwarding; CPP_Generator::generate_return emits return <expression>; declarations.php maps these bodies into native special members. Constructors/destructors need an explicit contract or suitable rendering for this case. This is not a value return: cleanup() has type void. The existing destructor spec explicitly allows fallthrough and bare return, while preparation guidance describes forwarding for every source callable; do not treat the current emitted C++ restriction as settled source semantics.

Controls: cleanup(); return; passes in both constructor and destructor (batch10/ctor_bare_control, dtor_bare_control). Ordinary void function forwarding passed in the previous operations batch.

## Other results

No separate Clang leak found in the sampled interface/inheritance contract checks. Preparation rejected missing interface implementations, incompatible returns/reference access, private implementations, duplicate direct interfaces, final-base extension, cyclic ancestry, class/struct mixing, property redeclaration and unsafe abstract instantiation. Interface diamonds, redundant ancestor views, inherited final-method forwarding, reabstracted methods with concrete descendants, and unique ownership through interface calls passed Clang.

Constructor access, required parent arguments, repeated/late parent construction, $this/parent instance use before base initialization, noncopyable set: inputs and inherited set: rebinding were rejected. Consuming unique inputs, inherited unique constructors, protected parent calls and private constructor factories compiled.

Destructor checks rejected static/abstract/final forms, declared return types, parameters, explicit destructor calls, private base cleanup, inaccessible field cleanup and noncopyable record copying. These are sampled proofs, not exhaustive correctness or runtime lifetime claims.

## Reproduce

    MY_TRY_PHP_EXECUTOR=cli python3 specs/planning/results/compiler_diagnostic_review_2026_10_18/run.py cases7.json rerun7
    MY_TRY_PHP_EXECUTOR=cli python3 specs/planning/results/compiler_diagnostic_review_2026_10_18/run.py cases8.json rerun8
    MY_TRY_PHP_EXECUTOR=cli python3 specs/planning/results/compiler_diagnostic_review_2026_10_18/run.py cases9.json rerun9
    MY_TRY_PHP_EXECUTOR=cli python3 specs/planning/results/compiler_diagnostic_review_2026_10_18/run.py cases10.json rerun10

Output names must be fresh. Harness details and exact Clang flags are in REPORT.md. All findings distinguish successful preparation, successful emission and actual Clang rejection.
