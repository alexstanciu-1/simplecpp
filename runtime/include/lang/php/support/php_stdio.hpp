#pragma once

#include "core/stdio.hpp"
#include "lang/php/support/php_resource.hpp"

namespace scpp::php {

using ::scpp::file_open_mode_info;
using ::scpp::parse_file_open_mode;

[[nodiscard]] inline falseable_resource_handle_t fopen(const string_t &path, const string_t &mode) {
	return ::scpp::open_file_resource(path, mode);
}

[[nodiscard]] inline nullable<int_t> fseek(const falseable_resource_handle_t &resource, const int_t &offset, const int_t &whence = int_t(SEEK_SET)) {
	return ::scpp::seek_file_resource(resource, offset, whence);
}

[[nodiscard]] inline result_or_false<int_t> ftell(const falseable_resource_handle_t &resource) {
	return ::scpp::tell_file_resource(resource);
}

[[nodiscard]] inline result_or_false<string_t> fgets(const falseable_resource_handle_t &resource, const nullable<int_t> &length = null) {
	return ::scpp::read_file_line(resource, length);
}

[[nodiscard]] inline result_or_false<string_t> fread(const falseable_resource_handle_t &resource, const int_t &length) {
	return ::scpp::read_file_bytes(resource, length);
}

[[nodiscard]] inline result_or_false<int_t> fwrite(const falseable_resource_handle_t &resource, const string_t &data) {
	return ::scpp::write_file_bytes(resource, data);
}

[[nodiscard]] inline result_or_false<int_t> fputs(const falseable_resource_handle_t &resource, const string_t &data) {
	return ::scpp::write_file_bytes(resource, data);
}

[[nodiscard]] inline bool_t rewind(const falseable_resource_handle_t &resource) {
	return ::scpp::rewind_file_resource(resource);
}

[[nodiscard]] inline bool_t fflush(const falseable_resource_handle_t &resource) {
	return ::scpp::flush_file_resource(resource);
}

[[nodiscard]] inline bool_t feof(const falseable_resource_handle_t &resource) {
	return ::scpp::eof_file_resource(resource);
}

[[nodiscard]] inline bool_t fclose(const falseable_resource_handle_t &resource) {
	return ::scpp::close_file_resource(resource);
}

} // namespace scpp::php
