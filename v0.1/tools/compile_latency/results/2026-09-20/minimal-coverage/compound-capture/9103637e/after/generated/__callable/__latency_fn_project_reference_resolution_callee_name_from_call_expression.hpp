#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
struct FrontendNodeRow;
string_t __latency_fn_project_reference_resolution_callee_name_from_call_expression(shared_p<FrontendModel> model, const string_t& sourceText, FrontendNodeRow callNode);
}
