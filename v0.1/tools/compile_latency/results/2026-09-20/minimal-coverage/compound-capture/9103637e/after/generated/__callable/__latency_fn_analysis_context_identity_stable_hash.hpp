#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct AnalysisEntryContextRow;
class ProjectSymbolIndex;
int_t<std::uint64_t> __latency_fn_analysis_context_identity_stable_hash(AnalysisEntryContextRow row, shared_p<ProjectSymbolIndex> symbols);
}
