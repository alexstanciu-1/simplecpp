#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct FrontendLiteralPayloadRow;
class FrontendModel;
class FrontendModelKernelCounters;
int_t<std::uint32_t> __latency_fn_frontend_model_tables_append_literal_payload(shared_p<FrontendModel> model, FrontendLiteralPayloadRow row, shared_p<FrontendModelKernelCounters> counters);
}
