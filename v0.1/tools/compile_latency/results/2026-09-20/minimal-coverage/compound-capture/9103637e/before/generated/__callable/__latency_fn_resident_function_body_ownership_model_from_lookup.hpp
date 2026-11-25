#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
class FrontendModel;
class ProjectFrontendModel;
shared_p<FrontendModel> __latency_fn_resident_function_body_ownership_model_from_lookup(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<ProjectFrontendModel> project, vector_t<int_t<std::uint32_t>>& modelIndexIds, int_t<std::uint32_t> sourceUnitId);
}
