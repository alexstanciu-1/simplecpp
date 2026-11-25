#include <scpp/lang/php.hpp>
#include "__types/FrontendDeclarationPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__callable/__latency_fn_project_symbol_index__norm_declaration_body_shape__usedCachedDigest.hpp"
#include "__callable/__latency_fn_project_symbol_index_declaration_body_shape__exec.hpp"
#pragma once
namespace scpp {
struct FrontendDeclarationPayloadRow;
class FrontendModel;
	template <typename T_usedCachedDigest>
	string_t __latency_fn_project_symbol_index_declaration_body_shape(shared_p<FrontendModel> model, const string_t& sourceText, FrontendDeclarationPayloadRow declaration, int_t<std::uint32_t>& bodyLengthOut, int_t<std::uint32_t>& walkRowsOut, T_usedCachedDigest&& _usedCachedDigest) {
	bool_t& usedCachedDigest = __latency_fn_project_symbol_index__norm_declaration_body_shape__usedCachedDigest(std::forward<T_usedCachedDigest>(_usedCachedDigest));
		return __latency_fn_project_symbol_index_declaration_body_shape__exec(model, sourceText, declaration, bodyLengthOut, walkRowsOut, usedCachedDigest);
	}

}
