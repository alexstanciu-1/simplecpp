# Versioned stable hashing

Doc Status: planning

## Stage 1 recommendation

Publish the two string operations together once their contract is approved. Use the my-try FNV-1a 64-bit result for both. The string form returns exactly 16 lowercase hexadecimal digits, including leading zeros. The numeric form returns the same unsigned 64 bits. Hash the string's bytes with their explicit length, including embedded NULs, without decoding text. An ordinary string caller needs no public span type. Defer the two byte-span operations with that type.

## Donor contracts and consumers

| Evidence | Finding |
| --- | --- |
| Release donor `d2f63e37e`, `runtime/include/scpp/stable_hash.hpp` | Delegates string hashing to the hash-table key operation. Building identical input with different C++ standard libraries produced different bits. |
| My-try donor `624ab8a2d`, same header | Starts at 14695981039346656037. For each unsigned byte, XOR then multiply by 1099511628211 modulo 2⁶⁴. String and span helpers use the same algorithm. |
| My-try metadata and source fixtures | The string operations are `hash_string(string)` and `stable_hash_string_u64(string)`. Their span counterparts are `hash_bytes` and `stable_hash_bytes_u64`. The compiler fixture exercises native target selection. |
| Archived [compiler-support contract](../../../v0.1/specs/compiler_support_runtime_contract.md) and donor usages | My-try has a native persisted-key formatter with the prefix `stable_hash_u64:fnv1a64:v2:`. The ordinary hex result has no prefix. The formatter appeared in a native test. No persisted reader or writer was identified in the reviewed repository sources. External consumers remain unknown. |
| Current compiler | Generated unit naming uses SHA-256 in `compiler/05_backend/cpp/headers.php`. This audit gives no reason to change compiler cache keys or runtime table hashing. |

## Native probes

Compiled each pinned header against its matching donor runtime with Clang, once with libstdc++ and once with libc++. Checked hex length and agreement with the unsigned result on every input. An independent byte loop agreed with my-try on all five inputs.

| Input bytes | Release with libstdc++ | Release with libc++ | My-try with either |
| --- | --- | --- | --- |
| Empty | `1d4483df7b541425` | `b1dbab591bddbeff` | `cbf29ce484222325` |
| ASCII `abc` | `d3bed96780249ab5` | `35c886a40886c09c` | `e71fa2190541574b` |
| `61 00 62` | `9c366b0051bffaaf` | `b6ac0a0d6bb38429` | `e5d29919042666b2` |
| `ff 80` | `75f26db8d73a8695` | `5b750c9285e7b3bd` | `0a9a2607b6f6e56a` |
| ASCII `bcd` | `bbd3bfcaaf9c53d5` | `ad218f702909446d` | `003f3219133dae62` |

## Implementation acceptance

Use a minimal source caller to verify both return types, all bytes of the embedded-NUL case, leading zeros, and a numeric result above the signed maximum. Record the numeric helper's uint64 dependency in its provider and check the minimal hex-only caller's includes. Before migrating a persisted consumer, establish which format wrote its keys and version or rebuild that consumer's entries. FNV-1a is a noncryptographic fingerprint.

## Stage 1 outcome

Both general string helpers are operational. The compiled-source regression runs both against all five vectors above. It checks PHP general-API visibility, the numeric result's module requirement and the hex caller's include closure. Public byte spans remain deferred.
