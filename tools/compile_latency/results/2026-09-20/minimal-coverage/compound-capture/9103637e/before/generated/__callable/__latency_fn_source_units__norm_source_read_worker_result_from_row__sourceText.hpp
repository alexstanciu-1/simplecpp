#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
	template <typename T_sourceText>
	string_t& __latency_fn_source_units__norm_source_read_worker_result_from_row__sourceText(T_sourceText&& _sourceText) {
		using _norm_arg_t = std::remove_cv_t<std::remove_reference_t<T_sourceText>>;
		if constexpr (std::is_same_v<_norm_arg_t, string_t>) {
			return _sourceText;
		}
		else if constexpr (std::is_same_v<_norm_arg_t, mixed_t>) {
			if (static_cast<bool>(_sourceText.is_string())) {
				return _sourceText.as_string_ref();
			}
			throw std::runtime_error("Unsupported runtime kind for normalized parameter $sourceText");
		} else {
			static_assert(!std::is_same_v<_norm_arg_t, _norm_arg_t>, "Unsupported type for normalized parameter");
		}
	}

}
