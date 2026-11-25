#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
struct FrontendNodeRow;
string_t __latency_fn_local_body_lowering_variable_name_text(shared_p<FrontendModel> model, const string_t& sourceText, FrontendNodeRow variableNode);
}
