#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct SourceBufferRow;
SourceBufferRow __latency_fn_frontend_model_tables_source_buffer_by_id(shared_p<FrontendModel> model, int_t<std::uint32_t> sourceBufferId, shared_p<FrontendModelKernelCounters> counters);
}
