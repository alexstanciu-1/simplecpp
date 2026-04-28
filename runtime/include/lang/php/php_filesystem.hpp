#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/result_or_false.hpp"
#include "scpp/string_t.hpp"
#include "modules/filesystem/filesystem.hpp"

namespace scpp::php {

[[nodiscard]] inline bool_t is_file(const string_t &path) {
	return scpp::fs::is_file(path);
}

[[nodiscard]] inline bool_t is_dir(const string_t &path) {
	return scpp::fs::is_dir(path);
}

[[nodiscard]] inline bool_t is_link(const string_t &path) {
	return scpp::fs::is_link(path);
}

[[nodiscard]] inline bool_t file_exists(const string_t &path) {
	return scpp::fs::exists(path);
}

[[nodiscard]] inline result_or_false<string_t> file_get_contents(const string_t &path) {
	return scpp::filesystem::file_get_contents(path);
}

[[nodiscard]] inline result_or_false<int_t> file_put_contents(const string_t &path, const string_t &data) {
	return scpp::filesystem::file_put_contents(path, data);
}

[[nodiscard]] inline bool_t mkdir(const string_t &path) {
	return scpp::fs::mkdir(path);
}

[[nodiscard]] inline result_or_false<hash_t<mixed_t>> scandir(const string_t &path) {
	return scpp::filesystem::scandir(path);
}

[[nodiscard]] inline result_or_false<int_t> filesize(const string_t &path) {
	return scpp::filesystem::filesize(path);
}

[[nodiscard]] inline result_or_false<int_t> filemtime(const string_t &path) {
	return scpp::filesystem::filemtime(path);
}

[[nodiscard]] inline bool_t touch(const string_t &path) {
	return scpp::fs::touch(path);
}

[[nodiscard]] inline bool_t rmdir(const string_t &path) {
	return scpp::fs::rmdir(path);
}

[[nodiscard]] inline bool_t unlink(const string_t &path) {
	return scpp::fs::remove(path);
}

[[nodiscard]] inline bool_t copy(const string_t &source, const string_t &dest) {
	return scpp::fs::copy(source, dest);
}

[[nodiscard]] inline bool_t rename(const string_t &source, const string_t &dest) {
	return scpp::fs::rename(source, dest);
}

[[nodiscard]] inline result_or_false<string_t> realpath(const string_t &path) {
	return scpp::filesystem::realpath(path);
}

[[nodiscard]] inline string_t dirname(const string_t &path) {
	return scpp::fs::dirname(path);
}

[[nodiscard]] inline string_t basename(const string_t &path) {
	return scpp::fs::basename(path);
}

} // namespace scpp::php
