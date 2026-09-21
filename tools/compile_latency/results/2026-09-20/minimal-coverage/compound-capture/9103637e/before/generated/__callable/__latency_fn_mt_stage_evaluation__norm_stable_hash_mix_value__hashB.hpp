#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
	template <typename T_hashB>
	int_t<>& __latency_fn_mt_stage_evaluation__norm_stable_hash_mix_value__hashB(T_hashB&& _hashB) {
		using _norm_arg_t = std::remove_cv_t<std::remove_reference_t<T_hashB>>;
		if constexpr (std::is_same_v<_norm_arg_t, int_t<>>) {
			return _hashB;
		}
		else if constexpr (std::is_same_v<_norm_arg_t, mixed_t>) {
			if (static_cast<bool>(_hashB.is_int())) {
				throw std::runtime_error("Unsupported runtime kind for normalized parameter $hashB");
			}
			throw std::runtime_error("Unsupported runtime kind for normalized parameter $hashB");
		} else {
			static_assert(!std::is_same_v<_norm_arg_t, _norm_arg_t>, "Unsupported type for normalized parameter");
		}
	}

}
