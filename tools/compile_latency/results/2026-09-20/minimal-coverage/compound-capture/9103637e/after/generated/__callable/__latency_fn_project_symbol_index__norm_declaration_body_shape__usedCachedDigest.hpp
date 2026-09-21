#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
	template <typename T_usedCachedDigest>
	bool_t& __latency_fn_project_symbol_index__norm_declaration_body_shape__usedCachedDigest(T_usedCachedDigest&& _usedCachedDigest) {
		using _norm_arg_t = std::remove_cv_t<std::remove_reference_t<T_usedCachedDigest>>;
		if constexpr (std::is_same_v<_norm_arg_t, bool_t>) {
			return _usedCachedDigest;
		}
		else if constexpr (std::is_same_v<_norm_arg_t, mixed_t>) {
			if (static_cast<bool>(_usedCachedDigest.is_bool())) {
				return _usedCachedDigest.as_bool_ref();
			}
			throw std::runtime_error("Unsupported runtime kind for normalized parameter $usedCachedDigest");
		} else {
			static_assert(!std::is_same_v<_norm_arg_t, _norm_arg_t>, "Unsupported type for normalized parameter");
		}
	}

}
