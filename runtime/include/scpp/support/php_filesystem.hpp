#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/string_t.hpp"

#include <algorithm>
#include <chrono>
#include <filesystem>
#include <fstream>
#include <string>
#include <system_error>
#include <utility>
#include <vector>

namespace scpp::php {

namespace filesystem_detail {

[[nodiscard]] inline std::filesystem::path to_path(const string_t &value) {
	return std::filesystem::path(value.native_value());
}

[[nodiscard]] inline hash_t<mixed_t> names_to_php_array(std::vector<std::string> names) {
	hash_t<mixed_t> out;
	for (auto &name : names) {
		static_cast<void>(out.append(mixed_t(string_t(std::move(name)))));
	}
	return out;
}

[[nodiscard]] inline std::int64_t file_time_to_unix_seconds(const std::filesystem::file_time_type &time) {
	const auto adjusted = std::chrono::time_point_cast<std::chrono::system_clock::duration>(
		time - std::filesystem::file_time_type::clock::now() + std::chrono::system_clock::now());
	return static_cast<std::int64_t>(std::chrono::duration_cast<std::chrono::seconds>(adjusted.time_since_epoch()).count());
}

} // namespace filesystem_detail

[[nodiscard]] inline bool_t is_file(const string_t &path) {
	std::error_code error;
	return bool_t(std::filesystem::is_regular_file(filesystem_detail::to_path(path), error));
}

[[nodiscard]] inline bool_t is_dir(const string_t &path) {
	std::error_code error;
	return bool_t(std::filesystem::is_directory(filesystem_detail::to_path(path), error));
}

[[nodiscard]] inline bool_t is_link(const string_t &path) {
	std::error_code error;
	return bool_t(std::filesystem::is_symlink(std::filesystem::symlink_status(filesystem_detail::to_path(path), error)) && !error);
}

[[nodiscard]] inline bool_t file_exists(const string_t &path) {
	std::error_code error;
	return bool_t(std::filesystem::exists(filesystem_detail::to_path(path), error));
}

[[nodiscard]] inline nullable<string_t> file_get_contents(const string_t &path) {
	std::ifstream input(filesystem_detail::to_path(path), std::ios::binary);
	if (!input.is_open()) {
		return null;
	}
	std::string contents((std::istreambuf_iterator<char>(input)), std::istreambuf_iterator<char>());
	if (input.bad()) {
		return null;
	}
	return string_t(std::move(contents));
}

[[nodiscard]] inline nullable<int_t> file_put_contents(const string_t &path, const string_t &data) {
	std::ofstream output(filesystem_detail::to_path(path), std::ios::binary | std::ios::trunc);
	if (!output.is_open()) {
		return null;
	}
	const auto &native = data.native_value();
	output.write(native.data(), static_cast<std::streamsize>(native.size()));
	if (!output.good()) {
		return null;
	}
	return int_t(static_cast<std::int64_t>(native.size()));
}

[[nodiscard]] inline nullable<bool_t> mkdir(const string_t &path) {
	std::error_code error;
	const auto created = std::filesystem::create_directory(filesystem_detail::to_path(path), error);
	if (error || !created) {
		return null;
	}
	return bool_t(true);
}

[[nodiscard]] inline nullable<hash_t<mixed_t>> scandir(const string_t &path) {
	std::error_code error;
	const auto native_path = filesystem_detail::to_path(path);
	if (!std::filesystem::is_directory(native_path, error) || error) {
		return null;
	}
	std::vector<std::string> names;
	for (const auto &entry : std::filesystem::directory_iterator(native_path, error)) {
		if (error) {
			return null;
		}
		names.push_back(entry.path().filename().string());
	}
	std::sort(names.begin(), names.end());
	return filesystem_detail::names_to_php_array(std::move(names));
}

[[nodiscard]] inline nullable<int_t> filesize(const string_t &path) {
	std::error_code error;
	const auto size = std::filesystem::file_size(filesystem_detail::to_path(path), error);
	if (error) {
		return null;
	}
	return int_t(static_cast<std::int64_t>(size));
}

[[nodiscard]] inline nullable<int_t> filemtime(const string_t &path) {
	std::error_code error;
	const auto native_path = filesystem_detail::to_path(path);
	if (!std::filesystem::exists(native_path, error) || error) {
		return null;
	}
	const auto time = std::filesystem::last_write_time(native_path, error);
	if (error) {
		return null;
	}
	return int_t(filesystem_detail::file_time_to_unix_seconds(time));
}

[[nodiscard]] inline nullable<bool_t> touch(const string_t &path) {
	std::error_code error;
	const auto native_path = filesystem_detail::to_path(path);
	if (std::filesystem::exists(native_path, error) && !error) {
		std::filesystem::last_write_time(native_path, std::filesystem::file_time_type::clock::now(), error);
		if (error) {
			return null;
		}
		return bool_t(true);
	}
	std::ofstream output(native_path, std::ios::binary | std::ios::app);
	if (!output.is_open()) {
		return null;
	}
	output.close();
	if (!output) {
		return null;
	}
	return bool_t(true);
}

[[nodiscard]] inline nullable<bool_t> rmdir(const string_t &path) {
	std::error_code error;
	const auto removed = std::filesystem::remove(filesystem_detail::to_path(path), error);
	if (error || !removed) {
		return null;
	}
	return bool_t(true);
}

[[nodiscard]] inline nullable<bool_t> unlink(const string_t &path) {
	std::error_code error;
	const auto removed = std::filesystem::remove(filesystem_detail::to_path(path), error);
	if (error || !removed) {
		return null;
	}
	return bool_t(true);
}

[[nodiscard]] inline nullable<bool_t> copy(const string_t &source, const string_t &dest) {
	std::error_code error;
	const auto copied = std::filesystem::copy_file(filesystem_detail::to_path(source), filesystem_detail::to_path(dest), std::filesystem::copy_options::overwrite_existing, error);
	if (error || !copied) {
		return null;
	}
	return bool_t(true);
}

[[nodiscard]] inline nullable<bool_t> rename(const string_t &source, const string_t &dest) {
	std::error_code error;
	std::filesystem::rename(filesystem_detail::to_path(source), filesystem_detail::to_path(dest), error);
	if (error) {
		return null;
	}
	return bool_t(true);
}

[[nodiscard]] inline nullable<string_t> realpath(const string_t &path) {
	std::error_code error;
	const auto canonical = std::filesystem::canonical(filesystem_detail::to_path(path), error);
	if (error) {
		return null;
	}
	return string_t(canonical.string());
}

[[nodiscard]] inline string_t dirname(const string_t &path) {
	const auto parent = filesystem_detail::to_path(path).parent_path();
	return string_t(parent.string());
}

[[nodiscard]] inline string_t basename(const string_t &path) {
	return string_t(filesystem_detail::to_path(path).filename().string());
}

} // namespace scpp::php
