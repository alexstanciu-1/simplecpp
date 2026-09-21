#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct SourceBufferRow;
int_t<std::uint32_t> __latency_fn_frontend_model_tables_append_source_buffer(shared_p<FrontendModel> model, SourceBufferRow row, shared_p<FrontendModelKernelCounters> counters);
}
