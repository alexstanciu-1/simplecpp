#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct FrontendNamePayloadRow;
int_t<std::uint32_t> __latency_fn_frontend_model_tables_append_name_payload(shared_p<FrontendModel> model, FrontendNamePayloadRow row, shared_p<FrontendModelKernelCounters> counters);
}
