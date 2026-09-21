#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
struct FrontendTypeSyntaxPayloadRow;
string_t __latency_fn_project_symbol_index_type_syntax_name(shared_p<FrontendModel> model, const string_t& sourceText, FrontendTypeSyntaxPayloadRow typeSyntax);
}
