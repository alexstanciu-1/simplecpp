#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class ProjectReferenceResolution;
struct ProjectReferenceResolutionRow;
class ProjectSymbolIndex;
struct ProjectSymbolIndexRow;
ProjectReferenceResolutionRow __latency_fn_project_reference_resolution_append_direct_call_from_frontend(shared_p<ProjectReferenceResolution> artifact, shared_p<ProjectSymbolIndex> symbols, shared_p<FrontendModel> model, const string_t& sourceText, ProjectSymbolIndexRow fromSymbol);
}
