# First native compiler build — 2026-10-25
Doc Status: planning

Input: successful PHP-hosted publication /tmp/compiler-bootstrap-layout-requirements-01,
layout implementation committed as a7ab8666c. Compiler executable is built at -O0;
application output policy remains the published policy. Initial build terminates after
7:33.34 / 1063852 KiB RSS with Native process interrupted, no Clang diagnostic.
A terminal, same-cache host-instrumented retry identifies compilation of the 15,303,391
byte / 76,924 line specializations/containers.cpp. The child output records timed_out
true, signal 9, exit -1. Native_CPP_Build::invoke_program hardcodes 120000 ms per action.
This is an observed action deadline, not evidence of out-of-memory or semantic failure.

The next retry uses only a temporary host-side copy of that build worker with a
600000 ms allowance and subprocess outcome logging. Production compiler source,
published C++, cache ownership and all correctness checks remain unchanged. Existing
completed objects are reused. Its outcome and native execution remain outstanding.

Extended retry is terminal: 3:43.08 / 2026416 KiB RSS. Output exit=1,
timed_out=false, stopped=false, signal=0. Clang reports three errors in two transport
cases: scpp::cast<string_t> on nullable<concrete string>, and str::split's native
vector<string_t> result being constructed directly as vector<concrete string>.
The latter produces both a return-type assertion and a constructor mismatch. The
600-second allowance exposed compiler diagnostics rather than a deadline. Existing
252 cached object files remain usable; no native compiler executable is published.
Do not add the cached retry duration to claim a clean native build time.
