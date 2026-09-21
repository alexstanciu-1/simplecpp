#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct SourceUnitTableRow;
class TokenStream;
shared_p<FrontendModel> __latency_fn_frontend_model_builder_parse_source_unit_with_node_segment_policy(SourceUnitTableRow sourceUnit, const string_t& source, shared_p<TokenStream> tokens, shared_p<FrontendModelKernelCounters> counters, int_t<std::uint32_t> segmentThreshold, int_t<std::uint32_t> segmentCapacity);
}
