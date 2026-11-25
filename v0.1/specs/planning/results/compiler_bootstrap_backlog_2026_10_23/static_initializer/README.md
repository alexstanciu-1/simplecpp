# Static initializer emission checkpoint
Doc Status: supporting

Candidate: parent 6feb5f031 plus the static-initializer emitter/test patch;
source_hashes.json identifies the bootstrap source. Reuse the existing prepared
initializer and conversion emission inside a captureless function scope, so nested
sequencing lambdas remain valid at namespace scope. No static syntax expansion.

Restored uint32 endpoint static initialization passes the extracted allocator/
revision fixture on PHP, legacy native, ordinary v0.2 and concrete prelowering.
Static uint32, nullable int, string and default vector storage also execute in both
v0.2 modes. Existing static_members.php retained checks pass. The old qualified
static member fixture fails required-local analysis identically with the unchanged
parent emitter; regression fixture reconciliation remains separate.

Bootstrap: 74.18 seconds wall, 342952 KiB peak RSS, 312 MiB PHP heap. Conversion
succeeds; preparation still first rejects syntax_node constructor initialization.
Diagnostic set unchanged. No whole-compiler native success claimed.
