#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class AnalysisContext;
struct AnalysisEntryContextRow;
AnalysisEntryContextRow __latency_fn_analysis_context_row_by_id(shared_p<AnalysisContext> context, int_t<std::uint32_t> contextId);
}
