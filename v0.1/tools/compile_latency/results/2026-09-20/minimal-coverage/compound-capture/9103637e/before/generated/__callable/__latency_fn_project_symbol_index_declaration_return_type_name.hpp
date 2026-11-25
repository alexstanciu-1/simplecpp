#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct FrontendDeclarationPayloadRow;
class FrontendModel;
struct FrontendNodeRow;
string_t __latency_fn_project_symbol_index_declaration_return_type_name(shared_p<FrontendModel> model, const string_t& sourceText, FrontendNodeRow declarationNode, FrontendDeclarationPayloadRow declaration);
}
