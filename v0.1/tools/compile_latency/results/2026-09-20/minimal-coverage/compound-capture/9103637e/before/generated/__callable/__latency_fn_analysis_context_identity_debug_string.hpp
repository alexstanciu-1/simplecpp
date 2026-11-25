#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct AnalysisEntryContextRow;
class ProjectSymbolIndex;
string_t __latency_fn_analysis_context_identity_debug_string(AnalysisEntryContextRow row, shared_p<ProjectSymbolIndex> symbols);
}
