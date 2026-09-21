#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct SourceUnitTableRow;
class TokenStream;
shared_p<FrontendModel> __latency_fn_frontend_model_builder_parse_source_unit(SourceUnitTableRow sourceUnit, const string_t& source, shared_p<TokenStream> tokens, shared_p<FrontendModelKernelCounters> counters);
}
