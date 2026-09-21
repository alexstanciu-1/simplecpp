#include <scpp/lang/php.hpp>
#include "__types/FrontendModel.hpp"
#include "__callable/__latency_fn_project_symbol_index__norm_parameter_list_count__signatureParameterShape.hpp"
#include "__callable/__latency_fn_project_symbol_index_parameter_list_count__exec.hpp"
#pragma once
namespace scpp {
class FrontendModel;
	template <typename T_signatureParameterShape>
	int_t<std::uint32_t> __latency_fn_project_symbol_index_parameter_list_count(shared_p<FrontendModel> model, int_t<std::uint32_t> parameterListNodeId, int_t<std::uint32_t>& firstParameterTypeRefId, T_signatureParameterShape&& _signatureParameterShape) {
	string_t& signatureParameterShape = __latency_fn_project_symbol_index__norm_parameter_list_count__signatureParameterShape(std::forward<T_signatureParameterShape>(_signatureParameterShape));
		return __latency_fn_project_symbol_index_parameter_list_count__exec(model, parameterListNodeId, firstParameterTypeRefId, signatureParameterShape);
	}

}
