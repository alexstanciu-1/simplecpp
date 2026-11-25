# my-try preparation diagnostic review — 2026-10-17
Doc Status: supporting

Review only; no repository files changed. Evidence is from the current working tree at HEAD 8471657979ff4e2c621dc79fa8a10a8643a39558, including its pre-existing local changes. PHP-hosted compiler; Clang 18.1.3. No native compiler rebuild, runtime execution, broad regression suite, or incremental recovery audit.

## Method

Each case has a separate main.phs. probe.php calls Compiler::init(), sync(), prepare(), then cpp(). A prepared.txt marker records successful preparation independently of emission. Successful emission is saved through CPP_Output::encode(), then materialized using the repository's cpp_artifacts utility. Every emitted translation unit is checked separately with:

    clang++ -std=c++20 -Werror=return-type -DSCPP_LANGUAGE_TARGET_PHP=1 -I REPO/runtime/include -I CASE/cpp -fsyntax-only UNIT.cpp

The repository's CommandRunner and 12-slot run_tasks pool capture each command, exit status, stdout and stderr in batch*/logs. The initial automatic FPM attempt could not bind its socket in the sandbox and used the documented CLI fallback; subsequent batches explicitly selected CLI. Headers not used by any translation unit are not independently instantiated. 'Clang passed' therefore does not establish every unused declaration is valid.

Re-run a batch with a fresh output name:

    MY_TRY_PHP_EXECUTOR=cli python3 specs/planning/results/compiler_diagnostic_review_2026_10_18/run.py cases2.json rerun2

## Finding 1: unsupported typed hash key families pass preparation

    $s hash<int, float>;
    return 0;

Also reproduced with bool instead of float, and with a function parameter:

    function f(hash<int, float> $s): void {}
    return 0;

Preparation and emission succeed. Clang fails with:

    static assertion failed: hash_t<T, T_KEY> requires a supported typed key family

Evidence: batch2/hash_bad_key and batch3/hash_bool, batch3/hash_float_parameter. Per-case result.json contains diagnostics; cpp/ contains the exact generated files.

Cause: runtime/metadata/modules/core.json:35 requires only hashable and comparable for Key. compiler/my-try/03_parse/types/register.php:42 grants both facts to bool and float. Those facts do not establish that the runtime's typed_hash_key contract accepts the type. The mismatch is at template formation/capability registration, not C++ spelling. The runtime rejects these types in runtime/include/scpp/support/hash_t.hpp:248. The ordinary int/string key boundary is documented in specs/array_semantics.md:139.

Controls: hash<int,string> and hash<int,int> prepare, emit and pass Clang. hash<int,void>, hash<int,vector<int>> and hash<int,Key> for a plain struct Key reject during preparation.

## Finding 2: ownership wrappers admit unusable void payloads

Separate programs:

    $s shared<void>;
    return 0;

    $s unique<void>;
    return 0;

Preparation and emission succeed. Clang reports cannot form a reference to void from shared_p<void>/unique_p<void>. The unique case also fails the standard library's deletion constraints. No dereference, allocation or use of the variable is needed.

Also reproduced through a function signature:

    function f(shared<void> $s): void {}
    return 0;

Evidence: batch2/shared_void, batch2/unique_void and batch3/shared_void_parameter.

Cause: compiler/my-try/03_parse/types/register.php:118 registers an empty Target requirement set for ownership wrappers. The shared application path (04_analyze/prepare/semantics/types.php:179) does not establish an additional admissible target contract before interning the type. This is a missing formation restriction for the current wrappers. It should not be repaired merely by requiring copyability: valid pointees may be noncopyable. Whether void ownership should ever become a language feature is separate; the current representation cannot implement these accepted programs.

Controls: shared<int> and unique<int> prepare, emit and pass Clang. Direct void locals/parameters, vector<void>, nullable<void> and result<void> already reject in preparation. Bare weak<void> passed Clang in this probe and is not included in this finding.

## Additional preparation gap: duplicate type declarations fail later, before Clang

    class C {}
    class C {}
    return 0;

Preparation succeeds; cpp() fails with Duplicate C++ artifact identity or path. Empty duplicate interfaces behave the same way. These are NOT counted as Clang leaks. Evidence: batch1/duplicate_type and batch2/duplicate_interface.

Unused duplicate methods, properties, function parameters/local declarations and constants have checks in the inspected paths; duplicate record identities do not receive equivalent eager validation. Record preparation starts at 04_analyze/prepare/semantics/declarations.php:151. Named-type use checks ambiguity, but the reproducer never uses C.

## Assessment limits

This is an adversarial sample, not a statistical correctness score. Some exploratory inputs failed parsing because the probe syntax was unsupported; those are not claimed as meaningful semantic rejection coverage. Unused static method bodies are demand-prepared by design, so their non-diagnosis without a call is not counted as a Clang leak. The move-twice probe is not classified as invalid; empty ownership after a move can be a legitimate state.

The confirmed findings concern type formation. Many ordinary declaration, reference, return-completion, abstract-storage and destructor-access errors were rejected before emission in these batches. A complete review would need broader operation matrices and incremental failure/recovery tests.
