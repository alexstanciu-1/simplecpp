#include "lang/php/php_filesystem.hpp"

#include "modules/filesystem/filesystem.hpp"

namespace scpp::php {

bool_t is_file(const string_t &path) {
	return scpp::filesystem::is_file(path);
}

bool_t is_dir(const string_t &path) {
	return scpp::filesystem::is_dir(path);
}

bool_t is_link(const string_t &path) {
	return scpp::filesystem::is_link(path);
}

bool_t file_exists(const string_t &path) {
	return scpp::filesystem::file_exists(path);
}

result_or_false<string_t> file_get_contents(const string_t &path) {
	return scpp::filesystem::file_get_contents(path);
}

result_or_false<int_t> file_put_contents(const string_t &path, const string_t &data) {
	return scpp::filesystem::file_put_contents(path, data);
}

bool_t mkdir(const string_t &path) {
	return scpp::filesystem::mkdir(path);
}

result_or_false<hash_t<mixed_t>> scandir(const string_t &path) {
	return scpp::filesystem::scandir(path);
}

result_or_false<int_t> filesize(const string_t &path) {
	return scpp::filesystem::filesize(path);
}

result_or_false<int_t> filemtime(const string_t &path) {
	return scpp::filesystem::filemtime(path);
}

bool_t touch(const string_t &path) {
	return scpp::filesystem::touch(path);
}

bool_t rmdir(const string_t &path) {
	return scpp::filesystem::rmdir(path);
}

bool_t unlink(const string_t &path) {
	return scpp::filesystem::unlink(path);
}

bool_t copy(const string_t &source, const string_t &dest) {
	return scpp::filesystem::copy(source, dest);
}

bool_t rename(const string_t &source, const string_t &dest) {
	return scpp::filesystem::rename(source, dest);
}

result_or_false<string_t> realpath(const string_t &path) {
	return scpp::filesystem::realpath(path);
}

string_t dirname(const string_t &path) {
	return scpp::filesystem::dirname(path);
}

string_t basename(const string_t &path) {
	return scpp::filesystem::basename(path);
}

} // namespace scpp::php
