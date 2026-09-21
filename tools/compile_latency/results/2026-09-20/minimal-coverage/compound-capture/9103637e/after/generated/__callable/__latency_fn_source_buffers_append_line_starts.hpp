#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
void __latency_fn_source_buffers_append_line_starts(shared_p<FrontendModel> model, int_t<std::uint32_t> sourceBufferId, const string_t& sourceText, shared_p<FrontendModelKernelCounters> counters);
}
