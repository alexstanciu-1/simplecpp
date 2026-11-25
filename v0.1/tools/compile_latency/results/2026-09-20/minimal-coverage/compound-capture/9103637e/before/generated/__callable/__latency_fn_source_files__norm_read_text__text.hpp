#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
	template <typename T_text>
	string_t& __latency_fn_source_files__norm_read_text__text(T_text&& _text) {
		using _norm_arg_t = std::remove_cv_t<std::remove_reference_t<T_text>>;
		if constexpr (std::is_same_v<_norm_arg_t, string_t>) {
			return _text;
		}
		else if constexpr (std::is_same_v<_norm_arg_t, mixed_t>) {
			if (static_cast<bool>(_text.is_string())) {
				return _text.as_string_ref();
			}
			throw std::runtime_error("Unsupported runtime kind for normalized parameter $text");
		} else {
			static_assert(!std::is_same_v<_norm_arg_t, _norm_arg_t>, "Unsupported type for normalized parameter");
		}
	}

}
