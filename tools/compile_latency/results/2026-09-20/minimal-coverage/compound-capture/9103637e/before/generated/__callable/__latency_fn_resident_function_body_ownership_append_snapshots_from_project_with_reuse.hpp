#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
class ProjectFrontendModel;
class ProjectSymbolIndex;
void __latency_fn_resident_function_body_ownership_append_snapshots_from_project_with_reuse(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previousReport, shared_p<ProjectFrontendModel> project, shared_p<ProjectSymbolIndex> symbols, int_t<std::uint32_t> ownerRunId);
}
