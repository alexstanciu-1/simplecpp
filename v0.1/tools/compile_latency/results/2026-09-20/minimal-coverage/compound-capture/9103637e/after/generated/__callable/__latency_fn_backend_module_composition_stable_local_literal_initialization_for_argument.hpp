#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
struct ProjectReferenceActualArgumentRow;
struct ProjectSymbolIndexRow;
bool_t __latency_fn_backend_module_composition_stable_local_literal_initialization_for_argument(ProjectSymbolIndexRow symbol, shared_p<FrontendModel> model, const string_t& sourceText, ProjectReferenceActualArgumentRow argument, int_t<std::uint32_t>& localTypeRefId, int_t<std::int32_t>& initialValue);
}
