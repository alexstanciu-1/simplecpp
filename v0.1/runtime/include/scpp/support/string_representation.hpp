#pragma once
#include "scpp/string_t.hpp"

namespace scpp {
// Physical byte interface; semantic behavior remains in string_t.
struct string_representation final {
	static string_t construct(const char* bytes, std::size_t size) {
		return string_t(std::string_view(bytes, size));
	}
	static const char* data(const string_t& value) noexcept { return value.native_value().data(); }
	static std::size_t size(const string_t& value) noexcept { return value.byte_size(); }
	static void append(string_t& target, const string_t& value) { target.append(value); }
};
}
