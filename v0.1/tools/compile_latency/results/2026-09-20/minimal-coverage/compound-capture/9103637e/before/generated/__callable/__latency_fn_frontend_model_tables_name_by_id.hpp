#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct FrontendNamePayloadRow;
FrontendNamePayloadRow __latency_fn_frontend_model_tables_name_by_id(shared_p<FrontendModel> model, int_t<std::uint32_t> payloadId, shared_p<FrontendModelKernelCounters> counters);
}
