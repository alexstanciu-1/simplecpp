#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
	template <typename T_signatureParameterShape>
	string_t& __latency_fn_project_symbol_index__norm_parameter_list_count__signatureParameterShape(T_signatureParameterShape&& _signatureParameterShape) {
		using _norm_arg_t = std::remove_cv_t<std::remove_reference_t<T_signatureParameterShape>>;
		if constexpr (std::is_same_v<_norm_arg_t, string_t>) {
			return _signatureParameterShape;
		}
		else if constexpr (std::is_same_v<_norm_arg_t, mixed_t>) {
			if (static_cast<bool>(_signatureParameterShape.is_string())) {
				return _signatureParameterShape.as_string_ref();
			}
			throw std::runtime_error("Unsupported runtime kind for normalized parameter $signatureParameterShape");
		} else {
			static_assert(!std::is_same_v<_norm_arg_t, _norm_arg_t>, "Unsupported type for normalized parameter");
		}
	}

}
