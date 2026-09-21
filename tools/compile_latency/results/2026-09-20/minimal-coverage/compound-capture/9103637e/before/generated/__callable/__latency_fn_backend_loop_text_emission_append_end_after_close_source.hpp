#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendLoopTextEmissionState;
void __latency_fn_backend_loop_text_emission_append_end_after_close_source(str::text_builder& lines, shared_p<BackendLoopTextEmissionState>& state, int_t<std::uint32_t> currentSourceRowId);
}
