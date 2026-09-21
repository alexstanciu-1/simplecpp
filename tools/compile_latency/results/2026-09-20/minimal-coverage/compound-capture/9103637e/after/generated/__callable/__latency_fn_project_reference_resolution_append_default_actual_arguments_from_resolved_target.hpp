#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ProjectReferenceResolution;
struct ProjectReferenceResolutionRow;
class ProjectSymbolIndex;
void __latency_fn_project_reference_resolution_append_default_actual_arguments_from_resolved_target(shared_p<ProjectReferenceResolution> artifact, ProjectReferenceResolutionRow& reference, shared_p<ProjectSymbolIndex> symbols);
}
