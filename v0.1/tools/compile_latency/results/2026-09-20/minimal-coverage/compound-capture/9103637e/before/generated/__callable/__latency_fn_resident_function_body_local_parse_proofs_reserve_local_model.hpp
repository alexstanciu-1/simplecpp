#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct ResidentFunctionBodyParseSliceRow;
void __latency_fn_resident_function_body_local_parse_proofs_reserve_local_model(shared_p<FrontendModel> model, ResidentFunctionBodyParseSliceRow slice, shared_p<FrontendModelKernelCounters> counters);
}
