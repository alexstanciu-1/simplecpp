#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/result_or_false.hpp"
#include "scpp/string_t.hpp"
#include "scpp/support/php_resource.hpp"

#include <array>
#include <cerrno>
#include <cstdio>
#include <cstring>
#include <memory>
#include <string>
#include <utility>
#include <vector>

namespace scpp::php {

struct fopen_mode_info final {
	const char *native_mode = nullptr;
	bool readable = false;
	bool writable = false;
	bool append = false;
};

[[nodiscard]] inline fopen_mode_info parse_fopen_mode(const string_t &mode) {
	const auto &native = mode.native_value();
	if (native == "r" || native == "rb") {
		return {native.c_str(), true, false, false};
	}
	if (native == "r+" || native == "rb+" || native == "r+b") {
		return {native.c_str(), true, true, false};
	}
	if (native == "w" || native == "wb") {
		return {native.c_str(), false, true, false};
	}
	if (native == "w+" || native == "wb+" || native == "w+b") {
		return {native.c_str(), true, true, false};
	}
	if (native == "a" || native == "ab") {
		return {native.c_str(), false, true, true};
	}
	if (native == "a+" || native == "ab+" || native == "a+b") {
		return {native.c_str(), true, true, true};
	}
	throw std::runtime_error("fopen(): unsupported mode \"" + native + "\"");
}

[[nodiscard]] inline falseable_resource_handle_t fopen(const string_t &path, const string_t &mode) {
	const auto mode_info = parse_fopen_mode(mode);
	FILE *fp = std::fopen(path.native_value().c_str(), mode_info.native_mode);
	if (fp == nullptr) {
		return false_sentinel;
	}
	auto native = std::make_shared<file_resource_t>(fp, path, mode, mode_info.readable, mode_info.writable, mode_info.append);
	return resource_handle_t(std::move(native));
}

[[nodiscard]] inline nullable<int_t> fseek(const falseable_resource_handle_t &resource, const int_t &offset, const int_t &whence = int_t(SEEK_SET)) {
	auto &file = require_file_resource(resource, "fseek");
	const auto status = std::fseek(file.native_handle(), static_cast<long>(offset.native_value()), static_cast<int>(whence.native_value()));
	if (status != 0) {
		return null;
	}
	std::clearerr(file.native_handle());
	return int_t(0);
}

[[nodiscard]] inline result_or_false<int_t> ftell(const falseable_resource_handle_t &resource) {
	auto &file = require_file_resource(resource, "ftell");
	const auto position = std::ftell(file.native_handle());
	if (position < 0L) {
		return false_sentinel;
	}
	return int_t(static_cast<std::int64_t>(position));
}

[[nodiscard]] inline result_or_false<string_t> fgets(const falseable_resource_handle_t &resource, const nullable<int_t> &length = null) {
	auto &file = require_file_resource(resource, "fgets");
	if (!file.readable) {
		throw std::runtime_error("fgets(): file resource is not readable");
	}
	if (length.has_value().native_value()) {
		const auto requested = length.value().native_value();
		if (requested <= 0) {
			throw std::runtime_error("fgets(): length must be greater than 0");
		}
		std::vector<char> buffer(static_cast<std::size_t>(requested));
		char *result = std::fgets(buffer.data(), static_cast<int>(buffer.size()), file.native_handle());
		if (result == nullptr) {
			return false_sentinel;
		}
		return string_t(std::string(result));
	}

	std::string out;
	std::array<char, 4096> buffer{};
	while (true) {
		char *result = std::fgets(buffer.data(), static_cast<int>(buffer.size()), file.native_handle());
		if (result == nullptr) {
			if (out.empty()) {
				return false_sentinel;
			}
			break;
		}
		out += result;
		if (!out.empty() && out.back() == '\n') {
			break;
		}
		if (std::feof(file.native_handle()) != 0) {
			break;
		}
	}
	return string_t(std::move(out));
}

[[nodiscard]] inline result_or_false<string_t> fread(const falseable_resource_handle_t &resource, const int_t &length) {
	auto &file = require_file_resource(resource, "fread");
	if (!file.readable) {
		throw std::runtime_error("fread(): file resource is not readable");
	}
	const auto requested = length.native_value();
	if (requested < 0) {
		throw std::runtime_error("fread(): length must be greater than or equal to 0");
	}
	if (requested == 0) {
		return string_t("");
	}
	std::string out(static_cast<std::size_t>(requested), '\0');
	const auto bytes_read = std::fread(out.data(), 1, out.size(), file.native_handle());
	if (bytes_read == 0 && std::ferror(file.native_handle()) != 0) {
		return false_sentinel;
	}
	out.resize(bytes_read);
	return string_t(std::move(out));
}

[[nodiscard]] inline result_or_false<int_t> fwrite(const falseable_resource_handle_t &resource, const string_t &data) {
	auto &file = require_file_resource(resource, "fwrite");
	if (!file.writable) {
		throw std::runtime_error("fwrite(): file resource is not writable");
	}
	const auto &native = data.native_value();
	const auto bytes_written = std::fwrite(native.data(), 1, native.size(), file.native_handle());
	if (bytes_written == 0 && !native.empty() && std::ferror(file.native_handle()) != 0) {
		return false_sentinel;
	}
	return int_t(static_cast<std::int64_t>(bytes_written));
}

[[nodiscard]] inline result_or_false<int_t> fputs(const falseable_resource_handle_t &resource, const string_t &data) {
	return fwrite(resource, data);
}

[[nodiscard]] inline bool_t rewind(const falseable_resource_handle_t &resource) {
	auto &file = require_file_resource(resource, "rewind");
	std::rewind(file.native_handle());
	std::clearerr(file.native_handle());
	return bool_t(true);
}

[[nodiscard]] inline bool_t fflush(const falseable_resource_handle_t &resource) {
	auto &file = require_file_resource(resource, "fflush");
	return bool_t(std::fflush(file.native_handle()) == 0);
}

[[nodiscard]] inline bool_t feof(const falseable_resource_handle_t &resource) {
	auto &file = require_file_resource(resource, "feof");
	return bool_t(std::feof(file.native_handle()) != 0);
}

[[nodiscard]] inline bool_t fclose(const falseable_resource_handle_t &resource) {
	auto &file = require_file_resource(resource, "fclose");
	const auto status = std::fclose(file.native_handle());
	file.mark_closed();
	return bool_t(status == 0);
}

} // namespace scpp::php
