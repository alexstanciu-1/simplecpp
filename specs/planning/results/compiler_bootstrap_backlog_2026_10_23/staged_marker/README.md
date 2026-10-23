# INIT-02 bounded class-marker checkpoint
Doc Status: planning

Base revision 78acc50bd plus this slice. Native proof /tmp/staged-marker-05:
PHP and legacy/ordinary/concrete pass; native tasks 22.33/24.39/21.32 seconds.
Expected output: early:null:9:rhs:9:1:7. Unchanged conversion preserves bytes/mtime.
Final structural rejection run passes 30 target/case combinations in 0.21 seconds.
The final case-insensitive generated-name check was added after the bootstrap;
it affects marked owners only, absent from the production compiler input.

Bootstrap /tmp/compiler-bootstrap-staged-marker-01: 80.10 seconds, 343808 KiB
peak RSS, 314 MiB PHP heap. Failure object exactly matches type_children checkpoint.
No successful whole-compiler native build. CLI fallback used because FPM could not
bind its sandbox socket. Native legacy proof intentionally uses --no-stan.

Earlier attempts: raw generated nullable spelling caused v0.1 parameter extraction
before the guard; normalized nullable<T> method/property declarations fixed it.
A rejection test initially expected staged-specific wording for operators already
rejected by the parser; expectations now preserve that existing parser boundary.
Struct-copy integration, struct owners and broader INIT-02 acceptance remain open.
