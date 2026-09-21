#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
class ProjectSymbolIndex;
void __latency_fn_project_symbol_index_record_profile_metrics(shared_p<CompilerProjectRunReport>& report, shared_p<ProjectSymbolIndex> index);
}
