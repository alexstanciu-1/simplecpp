#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ProjectReferenceResolution;
struct ProjectReferenceResolutionRow;
vector_t<ProjectReferenceResolutionRow> __latency_fn_project_dependency_graph_dependent_references_for_target(shared_p<ProjectReferenceResolution> references, int_t<std::uint32_t> targetSymbolId);
}
