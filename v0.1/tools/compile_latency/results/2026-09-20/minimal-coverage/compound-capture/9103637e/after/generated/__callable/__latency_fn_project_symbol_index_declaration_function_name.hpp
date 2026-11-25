#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct FrontendDeclarationPayloadRow;
class FrontendModel;
string_t __latency_fn_project_symbol_index_declaration_function_name(shared_p<FrontendModel> model, const string_t& sourceText, FrontendDeclarationPayloadRow declaration);
}
