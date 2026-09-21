#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
	template <typename T_returned>
	bool_t& __latency_fn_llvm_text_from_plan__norm_append_after_return_branch_boundary__returned(T_returned&& _returned) {
		using _norm_arg_t = std::remove_cv_t<std::remove_reference_t<T_returned>>;
		if constexpr (std::is_same_v<_norm_arg_t, bool_t>) {
			return _returned;
		}
		else if constexpr (std::is_same_v<_norm_arg_t, mixed_t>) {
			if (static_cast<bool>(_returned.is_bool())) {
				return _returned.as_bool_ref();
			}
			throw std::runtime_error("Unsupported runtime kind for normalized parameter $returned");
		} else {
			static_assert(!std::is_same_v<_norm_arg_t, _norm_arg_t>, "Unsupported type for normalized parameter");
		}
	}

}
