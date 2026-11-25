#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class AnalysisContext;
struct AnalysisEntryContextRow;
class ProjectCallableContractArtifact;
struct ProjectDependencyGraph;
class ProjectReferenceResolution;
struct ProjectSymbolIndexRow;
AnalysisEntryContextRow __latency_fn_analysis_context_append_entry(shared_p<AnalysisContext> context, ProjectSymbolIndexRow entrySymbol, shared_p<ProjectReferenceResolution> references, shared_p<ProjectCallableContractArtifact> contracts, ProjectDependencyGraph graph);
}
