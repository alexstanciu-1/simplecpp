#include "modules/filesystem/filesystem.hpp"

#include <algorithm>
#include <chrono>
#include <filesystem>
#include <fstream>
#include <string>
#include <system_error>
#include <utility>
#include <vector>

namespace scpp::filesystem {
namespace filesystem_detail {

[[nodiscard]] std::filesystem::path to_path(const string_t &value) {
	return std::filesystem::path(value.native_value());
}

[[nodiscard]] hash_t<mixed_t> names_to_php_array(std::vector<std::string> names) {
	hash_t<mixed_t> out;
	for (auto &name : names) {
		static_cast<void>(out.append(mixed_t(string_t(std::move(name)))));
	}
	return out;
}

[[nodiscard]] vector_t<string_t> names_to_vector(std::vector<std::string> names) {
	vector_t<string_t> out;
	for (auto &name : names) {
		out.append(string_t(std::move(name)));
	}
	return out;
}

[[nodiscard]] std::int64_t file_time_to_unix_seconds(const std::filesystem::file_time_type &time) {
	const auto adjusted = std::chrono::time_point_cast<std::chrono::system_clock::duration>(
		time - std::filesystem::file_time_type::clock::now() + std::chrono::system_clock::now());
	return static_cast<std::int64_t>(std::chrono::duration_cast<std::chrono::seconds>(adjusted.time_since_epoch()).count());
}

} // namespace filesystem_detail

} // namespace scpp::filesystem

namespace scpp::fs {

bool_t is_file(const string_t &path) {
	std::error_code error;
	return bool_t(std::filesystem::is_regular_file(scpp::filesystem::filesystem_detail::to_path(path), error));
}

bool_t is_dir(const string_t &path) {
	std::error_code error;
	return bool_t(std::filesystem::is_directory(scpp::filesystem::filesystem_detail::to_path(path), error));
}

bool_t is_link(const string_t &path) {
	std::error_code error;
	return bool_t(std::filesystem::is_symlink(std::filesystem::symlink_status(scpp::filesystem::filesystem_detail::to_path(path), error)) && !error);
}

bool_t exists(const string_t &path) {
	std::error_code error;
	return bool_t(std::filesystem::exists(scpp::filesystem::filesystem_detail::to_path(path), error));
}

result<string_t> get(const string_t &path) {
	std::ifstream input(scpp::filesystem::filesystem_detail::to_path(path), std::ios::binary);
	if (!input.is_open()) {
		return error_t(string_t("fs::get(): open failed"));
	}
	std::string contents((std::istreambuf_iterator<char>(input)), std::istreambuf_iterator<char>());
	if (input.bad()) {
		return error_t(string_t("fs::get(): read failed"));
	}
	return string_t(std::move(contents));
}

result<int_t> put(const string_t &path, const string_t &data) {
	std::ofstream output(scpp::filesystem::filesystem_detail::to_path(path), std::ios::binary | std::ios::trunc);
	if (!output.is_open()) {
		return error_t(string_t("fs::put(): open failed"));
	}
	const auto &native = data.native_value();
	output.write(native.data(), static_cast<std::streamsize>(native.size()));
	if (!output.good()) {
		return error_t(string_t("fs::put(): write failed"));
	}
	return int_t(static_cast<std::int64_t>(native.size()));
}

bool_t mkdir(const string_t &path) {
	std::error_code error;
	const auto created = std::filesystem::create_directory(scpp::filesystem::filesystem_detail::to_path(path), error);
	return bool_t(!error && created);
}

result<vector_t<string_t>> scan(const string_t &path) {
	std::error_code error;
	const auto native_path = scpp::filesystem::filesystem_detail::to_path(path);
	if (!std::filesystem::is_directory(native_path, error) || error) {
		return error_t(string_t("fs::scan(): not a directory"));
	}
	std::vector<std::string> names;
	for (const auto &entry : std::filesystem::directory_iterator(native_path, error)) {
		if (error) {
			return error_t(string_t("fs::scan(): iteration failed"));
		}
		names.push_back(entry.path().filename().string());
	}
	std::sort(names.begin(), names.end());
	return scpp::filesystem::filesystem_detail::names_to_vector(std::move(names));
}

result<int_t> size(const string_t &path) {
	std::error_code error;
	const auto size_value = std::filesystem::file_size(scpp::filesystem::filesystem_detail::to_path(path), error);
	if (error) {
		return error_t(string_t("fs::size(): stat failed"));
	}
	return int_t(static_cast<std::int64_t>(size_value));
}

result<int_t> mtime(const string_t &path) {
	std::error_code error;
	const auto native_path = scpp::filesystem::filesystem_detail::to_path(path);
	if (!std::filesystem::exists(native_path, error) || error) {
		return error_t(string_t("fs::mtime(): path missing"));
	}
	const auto time = std::filesystem::last_write_time(native_path, error);
	if (error) {
		return error_t(string_t("fs::mtime(): stat failed"));
	}
	return int_t(scpp::filesystem::filesystem_detail::file_time_to_unix_seconds(time));
}

bool_t touch(const string_t &path) {
	std::error_code error;
	const auto native_path = scpp::filesystem::filesystem_detail::to_path(path);
	if (std::filesystem::exists(native_path, error) && !error) {
		std::filesystem::last_write_time(native_path, std::filesystem::file_time_type::clock::now(), error);
		return bool_t(!error);
	}
	std::ofstream output(native_path, std::ios::binary | std::ios::app);
	if (!output.is_open()) {
		return bool_t(false);
	}
	output.close();
	return bool_t(static_cast<bool>(output));
}

bool_t rmdir(const string_t &path) {
	std::error_code error;
	const auto removed = std::filesystem::remove(scpp::filesystem::filesystem_detail::to_path(path), error);
	return bool_t(!error && removed);
}

bool_t remove(const string_t &path) {
	std::error_code error;
	const auto removed = std::filesystem::remove(scpp::filesystem::filesystem_detail::to_path(path), error);
	return bool_t(!error && removed);
}

bool_t copy(const string_t &source, const string_t &dest) {
	std::error_code error;
	const auto copied = std::filesystem::copy_file(
		scpp::filesystem::filesystem_detail::to_path(source),
		scpp::filesystem::filesystem_detail::to_path(dest),
		std::filesystem::copy_options::overwrite_existing,
		error);
	return bool_t(!error && copied);
}

bool_t rename(const string_t &source, const string_t &dest) {
	std::error_code error;
	std::filesystem::rename(scpp::filesystem::filesystem_detail::to_path(source), scpp::filesystem::filesystem_detail::to_path(dest), error);
	return bool_t(!error);
}

result<string_t> realpath(const string_t &path) {
	std::error_code error;
	const auto canonical = std::filesystem::canonical(scpp::filesystem::filesystem_detail::to_path(path), error);
	if (error) {
		return error_t(string_t("fs::realpath(): canonicalize failed"));
	}
	return string_t(canonical.string());
}

string_t dirname(const string_t &path) {
	const auto parent = scpp::filesystem::filesystem_detail::to_path(path).parent_path();
	return string_t(parent.string());
}

string_t basename(const string_t &path) {
	return string_t(scpp::filesystem::filesystem_detail::to_path(path).filename().string());
}

} // namespace scpp::fs

namespace scpp::filesystem {

bool_t is_file(const string_t &path) {
	return scpp::fs::is_file(path);
}

bool_t is_dir(const string_t &path) {
	return scpp::fs::is_dir(path);
}

bool_t is_link(const string_t &path) {
	return scpp::fs::is_link(path);
}

bool_t file_exists(const string_t &path) {
	return scpp::fs::exists(path);
}

result_or_false<string_t> file_get_contents(const string_t &path) {
	const auto value = scpp::fs::get(path);
	if (!value.has_value().native_value()) {
		return false_sentinel;
	}
	return value.value();
}

result_or_false<int_t> file_put_contents(const string_t &path, const string_t &data) {
	const auto value = scpp::fs::put(path, data);
	if (!value.has_value().native_value()) {
		return false_sentinel;
	}
	return value.value();
}

bool_t mkdir(const string_t &path) {
	return scpp::fs::mkdir(path);
}

result_or_false<hash_t<mixed_t>> scandir(const string_t &path) {
	const auto listing = scpp::fs::scan(path);
	if (!listing.has_value().native_value()) {
		return false_sentinel;
	}
	std::vector<std::string> names;
	const auto count = listing.value().size();
	names.reserve(count);
	for (std::size_t index = 0; index < count; ++index) {
		names.push_back(listing.value().native_value()[index].native_value());
	}
	return filesystem_detail::names_to_php_array(std::move(names));
}

result_or_false<int_t> filesize(const string_t &path) {
	const auto value = scpp::fs::size(path);
	if (!value.has_value().native_value()) {
		return false_sentinel;
	}
	return value.value();
}

result_or_false<int_t> filemtime(const string_t &path) {
	const auto value = scpp::fs::mtime(path);
	if (!value.has_value().native_value()) {
		return false_sentinel;
	}
	return value.value();
}

bool_t touch(const string_t &path) {
	return scpp::fs::touch(path);
}

bool_t rmdir(const string_t &path) {
	return scpp::fs::rmdir(path);
}

bool_t unlink(const string_t &path) {
	return scpp::fs::remove(path);
}

bool_t copy(const string_t &source, const string_t &dest) {
	return scpp::fs::copy(source, dest);
}

bool_t rename(const string_t &source, const string_t &dest) {
	return scpp::fs::rename(source, dest);
}

result_or_false<string_t> realpath(const string_t &path) {
	const auto value = scpp::fs::realpath(path);
	if (!value.has_value().native_value()) {
		return false_sentinel;
	}
	return value.value();
}

string_t dirname(const string_t &path) {
	return scpp::fs::dirname(path);
}

string_t basename(const string_t &path) {
	return scpp::fs::basename(path);
}

} // namespace scpp::filesystem
