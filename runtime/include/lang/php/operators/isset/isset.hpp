#pragma once

#include "operators/isset/isset.hpp"
#include "scpp/runtime_error.hpp"

#include <utility>

namespace scpp::php {

template <typename... Args>
inline auto isset(Args &&...args) {
	return ::scpp::isset(std::forward<Args>(args)...);
}

inline bool isset_probe_handles_runtime_error(const ::scpp::runtime_error &error) {
	const auto &code = error.code();
	return code == "invalid_nullable_unwrap_empty"
		|| code == "invalid_shared_arrow_null"
		|| code == "invalid_unique_arrow_null";
}

template <typename EvalFn>
inline bool_t isset_eval(EvalFn &&eval_fn) {
	try {
		return ::scpp::isset(std::forward<EvalFn>(eval_fn)());
	} catch (const ::scpp::runtime_error &error) {
		if (isset_probe_handles_runtime_error(error)) {
			return bool_t(false);
		}
		throw;
	}
}

} // namespace scpp::php
