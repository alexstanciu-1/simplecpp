#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
	template <typename T_activeIfEndLabel>
	string_t& __latency_fn_llvm_text_from_plan__norm_append_after_return_branch_boundary__activeIfEndLabel(T_activeIfEndLabel&& _activeIfEndLabel) {
		using _norm_arg_t = std::remove_cv_t<std::remove_reference_t<T_activeIfEndLabel>>;
		if constexpr (std::is_same_v<_norm_arg_t, string_t>) {
			return _activeIfEndLabel;
		}
		else if constexpr (std::is_same_v<_norm_arg_t, mixed_t>) {
			if (static_cast<bool>(_activeIfEndLabel.is_string())) {
				return _activeIfEndLabel.as_string_ref();
			}
			throw std::runtime_error("Unsupported runtime kind for normalized parameter $activeIfEndLabel");
		} else {
			static_assert(!std::is_same_v<_norm_arg_t, _norm_arg_t>, "Unsupported type for normalized parameter");
		}
	}

}
