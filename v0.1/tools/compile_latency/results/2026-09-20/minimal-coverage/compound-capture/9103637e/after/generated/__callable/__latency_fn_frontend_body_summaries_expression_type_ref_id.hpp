#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct FrontendNodeRow;
int_t<std::uint32_t> __latency_fn_frontend_body_summaries_expression_type_ref_id(shared_p<FrontendModel> model, FrontendNodeRow node, shared_p<FrontendModelKernelCounters> counters);
}
