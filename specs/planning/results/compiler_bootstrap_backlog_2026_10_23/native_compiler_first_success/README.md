# First native compiler build and execution
Doc Status: planning

Candidate b3ef5d2f0 (same production code as the native-transport bootstrap
snapshot). PHP-hosted publication built successfully at -O0: 8:30.13 wall,
2419340 KiB peak RSS. 244 actions compiled, 6 reused, 3 layouts reused, linked.
This is a measured cached retry, not a claim of clean build time. Production
120-second native deadline remains unchanged; build_recipe.py records the approved
temporary host-only 600-second allowance and outcome instrumentation.

The executable compiled scalar, shared identity and string-container programs;
those outputs were built and executed with expected results 42, 9 and a||b|.
Recipes and command records preserve all paths and observations. Shared runner
used CLI fallback because FPM socket creation was unavailable in the sandbox.
This establishes first-generation compiler execution. Second-generation publication,
build and execution are separate goal requirements.
