#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct AnalysisEntryContextRow;
class ProjectCallableContractArtifact;
struct ProjectDependencyGraph;
class ProjectReferenceResolution;
struct ProjectSymbolIndexRow;
AnalysisEntryContextRow __latency_fn_analysis_context_row_from_entry_symbol(ProjectSymbolIndexRow entrySymbol, shared_p<ProjectReferenceResolution> references, shared_p<ProjectCallableContractArtifact> contracts, ProjectDependencyGraph graph);
}
