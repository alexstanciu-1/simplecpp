#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct FrontendDeclarationPayloadRow;
class FrontendModel;
string_t __latency_fn_project_symbol_index_declaration_body_shape__exec(shared_p<FrontendModel> model, const string_t& sourceText, FrontendDeclarationPayloadRow declaration, int_t<std::uint32_t>& bodyLengthOut, int_t<std::uint32_t>& walkRowsOut, bool_t& usedCachedDigest);
}
