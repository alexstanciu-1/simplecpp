# Struct-copy method reuse probe
Doc Status: planning

Revision 8c105a473. probe.phs executes with output 7:9 in ordinary and concrete
v0.2; the same source is rejected by v0.1 before native compilation. Legacy
entrypoint is source/main.phs, strict profile, scpp build --no-stan. The first
legacy attempt used a wrong entrypoint key; the corrected run is the evidence here.
No generated C++ was patched. These are direct native-source probes, not converted
PHP protocol acceptance or a full bootstrap. See the COPY-01 reuse audit for the
pending converter-owned helper proposal and source-only initialization candidates.
