#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
struct FrontendNodeRow;
class ProjectReferenceResolution;
struct ProjectReferenceResolutionRow;
void __latency_fn_project_reference_resolution_append_actual_arguments_from_call_expression(shared_p<ProjectReferenceResolution> artifact, ProjectReferenceResolutionRow& reference, shared_p<FrontendModel> model, FrontendNodeRow callNode);
}
