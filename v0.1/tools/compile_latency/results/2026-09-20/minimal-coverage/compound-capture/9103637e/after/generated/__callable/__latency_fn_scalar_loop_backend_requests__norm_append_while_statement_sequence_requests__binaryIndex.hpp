#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
	template <typename T_binaryIndex>
	int_t<>& __latency_fn_scalar_loop_backend_requests__norm_append_while_statement_sequence_requests__binaryIndex(T_binaryIndex&& _binaryIndex) {
		using _norm_arg_t = std::remove_cv_t<std::remove_reference_t<T_binaryIndex>>;
		if constexpr (std::is_same_v<_norm_arg_t, int_t<>>) {
			return _binaryIndex;
		}
		else if constexpr (std::is_same_v<_norm_arg_t, mixed_t>) {
			if (static_cast<bool>(_binaryIndex.is_int())) {
				throw std::runtime_error("Unsupported runtime kind for normalized parameter $binaryIndex");
			}
			throw std::runtime_error("Unsupported runtime kind for normalized parameter $binaryIndex");
		} else {
			static_assert(!std::is_same_v<_norm_arg_t, _norm_arg_t>, "Unsupported type for normalized parameter");
		}
	}

}
