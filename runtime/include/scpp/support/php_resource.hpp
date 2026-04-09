#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/nullable.hpp"
#include "scpp/string_t.hpp"

#include <cstdio>
#include <stdexcept>
#include <string>
#include <utility>

namespace scpp::php {

enum class resource_kind : unsigned char {
	file_stream = 1,
};

class php_resource_t {
public:
	resource_kind kind;
	bool closed = false;

	explicit php_resource_t(resource_kind resource_kind_value)
		: kind(resource_kind_value) {
	}

	virtual ~php_resource_t() = default;
};

class file_resource_t final : public php_resource_t {
private:
	FILE *fp_ = nullptr;

public:
	string_t path;
	string_t mode;
	bool readable = false;
	bool writable = false;
	bool append = false;

	file_resource_t(FILE *fp, string_t path_value, string_t mode_value, bool readable_value, bool writable_value, bool append_value)
		: php_resource_t(resource_kind::file_stream),
		  fp_(fp),
		  path(std::move(path_value)),
		  mode(std::move(mode_value)),
		  readable(readable_value),
		  writable(writable_value),
		  append(append_value) {
	}

	~file_resource_t() override {
		close_if_open();
	}

	[[nodiscard]] FILE *native_handle() const noexcept {
		return fp_;
	}

	void mark_closed() noexcept {
		fp_ = nullptr;
		closed = true;
	}

	[[nodiscard]] bool is_open() const noexcept {
		return fp_ != nullptr && !closed;
	}

	void close_if_open() noexcept {
		if (fp_ != nullptr) {
			std::fclose(fp_);
			fp_ = nullptr;
		}
		closed = true;
	}
};

using resource_handle_t = shared_p<php_resource_t>;
using nullable_resource_handle_t = nullable<resource_handle_t>;

[[nodiscard]] inline file_resource_t &require_file_resource(const nullable_resource_handle_t &resource, const char *function_name) {
	if (!resource.has_value().native_value()) {
		throw std::runtime_error(std::string(function_name) + "(): file resource is null");
	}
	const auto &handle = resource.value();
	if (!handle.has_value().native_value()) {
		throw std::runtime_error(std::string(function_name) + "(): file resource handle is null");
	}
	auto *base = handle.get();
	if (base == nullptr) {
		throw std::runtime_error(std::string(function_name) + "(): file resource handle is null");
	}
	if (base->kind != resource_kind::file_stream) {
		throw std::runtime_error(std::string(function_name) + "(): resource kind is not a file stream");
	}
	auto *file = dynamic_cast<file_resource_t *>(base);
	if (file == nullptr) {
		throw std::runtime_error(std::string(function_name) + "(): invalid file resource payload");
	}
	if (file->closed || file->native_handle() == nullptr) {
		throw std::runtime_error(std::string(function_name) + "(): file resource is closed");
	}
	return *file;
}

} // namespace scpp::php
