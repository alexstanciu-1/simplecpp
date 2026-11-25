#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct SourceUnitTableRow;
class TokenStream;
void __latency_fn_frontend_model_builder_reserve_model_for_tokens(shared_p<FrontendModel> model, SourceUnitTableRow sourceUnit, shared_p<TokenStream> tokens, shared_p<FrontendModelKernelCounters> counters);
}
