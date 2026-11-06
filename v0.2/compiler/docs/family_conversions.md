# Active scalar family conversions

Doc Status: supporting

The [agreed model](../../specs/planning/scalar_families_and_conversions.md) now supplies
conversions for active int64, uint8 (byte), bool and float64 (float) identities.
Null and string retain identity conversion only in this slice.

## Ownership and generated path

Runtime metadata `compiler.conversion_families` replaces the concrete conversion list.
Seven directional family rules select algorithms and allowed contexts. Generation
expands these rules over active canonical definitions into twelve concrete numeric
conversions. Integer representation ranges select widening versus checked narrowing.
Per-type `conversion_binding` references name the runtime representation needed by
checked conversions; bool/float/byte are no longer special excluded pairs.

The transient rows feed the existing validator and specialized PHP type renderer.
Preparation calls generated methods directly: no family interpretation, conversion
path search or pair matrix is retained in a compilation session. Existing operator
metadata independently grants promotion permission; cast availability does not grant it.
Runtime generation removes numeric exclusions and retains `deferred_text_casts` for uint8.

## Admissions and boundaries

Explicit casts and typed destinations now support byte ↔ bool and byte ↔ float,
including locals, parameters, trailing literal defaults and returns. Supported typed
globals/constants use the same rules. Ordinary assignment remains same-identity-only;
references require exact identity, with byte and uint8 identical. Existing checked
integer compound write-back, operator promotions and truthiness remain unchanged.

Float-to-byte truncates toward zero, then requires a finite result in [0, 256).
Thus 255.9 becomes 255 and -0.9 becomes 0; -1, 256, NaN and infinities fail.
Generated constant checking derives the exclusive upper bound from representation
width; dynamic checking uses the named runtime constructor binding. Numeric-to-bool
uses zero/nonzero, including NaN as true. Integer-to-float permits rounding.

No AST, preparation-fact, conversion-enum, observer or queue structure changes.
Only metadata ownership and transient generator definition/plan fields change.
Optional numeric widths, floating resize formats, char semantics, text conversions,
string globals and additional operator admissions remain separate work.

## Verification

`tests/family_conversions.php` covers all three flavors, rejection boundaries,
no-edit retries, deletion/re-addition, unchanged caller AST reuse and recovery versus
fresh generated artifacts. The native proof runs 24 executables, including a C++
observer that checks fractional/nonfinite boundaries and all 256 byte values.
Existing casts, floating/scalar, parameter/default/reference, compound and mixed
numeric tests remain passing. Generator proofs cover 19 accepted/537 rejected scalar
cases, 130 accepted/247 rejected runtime cases, and 17 accepted/37 rejected CLI cases.

Final proof: `v0.2/build/family-conversions-proof-2026-11-06-final/results.json`.
O2/g0, libc++, Ninja8, mold, no PCH/LTO; prebuilt runtime, fresh traced app objects.
Single observations below are verification timings, not flavor performance rankings;
generator validation was also running during this proof.

| Flavor | Native wall ms | Clang frontend ms | LLVM backend ms | Link command ms |
| --- | ---: | ---: | ---: | ---: |
| cpp | 313.13 | 47.86 | 77.40 | 111.00 |
| php | 158.06 | 16.61 | 17.83 | 75.00 |
| js | 373.34 | 51.79 | 67.58 | 141.00 |

Frontend/LLVM trace work and linker elapsed time are separate measurements; they do not sum to native wall time.
