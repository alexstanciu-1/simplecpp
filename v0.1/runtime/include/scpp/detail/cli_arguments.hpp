#pragma once

#include "lang/php/cli_context.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/string_t.hpp"

namespace scpp::php {

[[nodiscard]] inline int_t<> cli_argc() {
	return int_t<>(static_cast<std::int64_t>(cli_detail::g_cli_argc));
}

[[nodiscard]] inline mixed_t cli_argv() {
	auto out = table_();
	for (int index = 0; index < cli_detail::g_cli_argc; ++index) {
		const char* raw = cli_detail::g_cli_argv == nullptr ? nullptr : cli_detail::g_cli_argv[index];
		static_cast<void>(out->append(mixed_t(string_t(raw == nullptr ? "" : raw))));
	}
	return mixed_t(std::move(out));
}

[[nodiscard]] inline mixed_t cli_args() {
	return cli_argv();
}

} // namespace scpp::php
