#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct FrontendLiteralPayloadRow;
struct FrontendNodeRow;
class ProjectReferenceResolution;
struct ProjectReferenceResolutionRow;
int_t<std::uint32_t> __latency_fn_project_reference_resolution_append_actual_argument(shared_p<ProjectReferenceResolution> artifact, ProjectReferenceResolutionRow& reference, FrontendNodeRow argumentNode, FrontendLiteralPayloadRow argumentLiteral, int_t<std::uint16_t> position);
}
