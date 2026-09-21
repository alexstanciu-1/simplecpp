#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
	template <typename T_hashA>
	int_t<>& __latency_fn_deterministic_work_ordering__norm_stable_hash_mix_row_value__hashA(T_hashA&& _hashA) {
		using _norm_arg_t = std::remove_cv_t<std::remove_reference_t<T_hashA>>;
		if constexpr (std::is_same_v<_norm_arg_t, int_t<>>) {
			return _hashA;
		}
		else if constexpr (std::is_same_v<_norm_arg_t, mixed_t>) {
			if (static_cast<bool>(_hashA.is_int())) {
				throw std::runtime_error("Unsupported runtime kind for normalized parameter $hashA");
			}
			throw std::runtime_error("Unsupported runtime kind for normalized parameter $hashA");
		} else {
			static_assert(!std::is_same_v<_norm_arg_t, _norm_arg_t>, "Unsupported type for normalized parameter");
		}
	}

}
