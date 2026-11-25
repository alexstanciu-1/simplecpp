#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
	template <typename T_name>
	string_t& __latency_fn_control_flow_dataflows__norm_statement_local_name_and_type_ref__name(T_name&& _name) {
		using _norm_arg_t = std::remove_cv_t<std::remove_reference_t<T_name>>;
		if constexpr (std::is_same_v<_norm_arg_t, string_t>) {
			return _name;
		}
		else if constexpr (std::is_same_v<_norm_arg_t, mixed_t>) {
			if (static_cast<bool>(_name.is_string())) {
				return _name.as_string_ref();
			}
			throw std::runtime_error("Unsupported runtime kind for normalized parameter $name");
		} else {
			static_assert(!std::is_same_v<_norm_arg_t, _norm_arg_t>, "Unsupported type for normalized parameter");
		}
	}

}
