#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct FrontendLiteralPayloadRow;
class FrontendModel;
class FrontendModelKernelCounters;
struct FrontendNodeRow;
bool_t __latency_fn_frontend_body_summaries_ready_literal_from_node(shared_p<FrontendModel> model, FrontendNodeRow node, FrontendLiteralPayloadRow& literal, shared_p<FrontendModelKernelCounters> counters);
}
