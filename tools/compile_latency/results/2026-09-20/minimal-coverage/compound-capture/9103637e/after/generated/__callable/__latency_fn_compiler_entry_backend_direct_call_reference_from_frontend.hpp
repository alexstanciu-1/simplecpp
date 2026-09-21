#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class ProjectReferenceResolution;
struct ProjectReferenceResolutionRow;
class ProjectSymbolIndex;
struct ProjectSymbolIndexRow;
ProjectReferenceResolutionRow __latency_fn_compiler_entry_backend_direct_call_reference_from_frontend(shared_p<ProjectReferenceResolution> references, shared_p<ProjectSymbolIndex> symbols, shared_p<FrontendModel> model, const string_t& sourceText, ProjectSymbolIndexRow symbol);
}
