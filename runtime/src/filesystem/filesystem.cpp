#include "filesystem_provider.hpp"

#include <algorithm>
#include <cerrno>
#include <cstdio>
#include <cstring>
#include <cstdlib>
#include <filesystem>
#include <memory>
#include <string>
#include <utility>
#include <vector>
#include <sys/stat.h>

#if defined(__unix__) || defined(__APPLE__)
#include <fcntl.h>
#include <unistd.h>
#elif defined(_WIN32)
#ifndef NOMINMAX
#define NOMINMAX
#endif
#include <windows.h>
#endif

namespace {
const char* c_path(const scpp::string& path) {
    const auto* bytes = path.data();
    return std::memchr(bytes, '\0', static_cast<std::size_t>(path.size())) == nullptr ? bytes : nullptr;
}
}

namespace scpp::fs {
result_string get(const string& path) {
    const auto* filename = c_path(path);
    if (filename == nullptr) {
        return result_string{error_t{string{"fs_get: path contains a NUL byte"}}};
    }

    const std::unique_ptr<std::FILE, decltype(&std::fclose)> file(
        std::fopen(filename, "rb"), &std::fclose);
    if (!file) {
        return result_string{error_t{string{"fs_get: cannot open file"}}};
    }

    std::string contents;
    char buffer[8192];
    std::size_t count;
    do {
        count = std::fread(buffer, 1, sizeof(buffer), file.get());
        contents.append(buffer, count);
    } while (count == sizeof(buffer));

    if (std::ferror(file.get()) != 0) {
        return result_string{error_t{string{"fs_get: cannot read file"}}};
    }
    if (!std::in_range<int64>(contents.size())) {
        return result_string{error_t{string{"fs_get: file is too large"}}};
    }
    return result_string{string{contents.data(), static_cast<int64>(contents.size())}};
}

result_int64 put(const string& path, const string& data) {
    const auto* filename = c_path(path);
    if (filename == nullptr) {
        return result_int64{error_t{string{"fs_put: path contains a NUL byte"}}};
    }

    const auto size = static_cast<std::size_t>(data.size());
    const auto* bytes = data.data();
    auto* file = std::fopen(filename, "wb");
    if (file == nullptr) {
        return result_int64{error_t{string{"fs_put: cannot open file"}}};
    }

    const auto written = std::fwrite(bytes, 1, size, file);
    const auto closed = std::fclose(file);
    if (written != size || closed != 0) {
        return result_int64{error_t{string{"fs_put: cannot write complete file"}}};
    }
    return result_int64{static_cast<int64>(written)};
}

bool_t rename(const string& source, const string& destination) {
    const auto* from = c_path(source);
    const auto* to = c_path(destination);
    if (from == nullptr || to == nullptr) return false;

    std::error_code error;
    std::filesystem::rename(from, to, error);
    return !error;
}

bool_t remove(const string& path) {
    const auto* filename = c_path(path);
    if (filename == nullptr) return false;

    std::error_code error;
    return std::filesystem::remove(filename, error) && !error;
}

result_string stage(const string& directory) {
#if defined(__unix__) || defined(__APPLE__)
    const auto* name = c_path(directory);
    if (name == nullptr || *name == '\0') {
        return result_string{error_t{string{"fs_stage: invalid directory"}}};
    }

    auto path = (std::filesystem::path(name) / ".scpp-stage-XXXXXX").string();
#if defined(__linux__)
    const int descriptor = ::mkostemp(path.data(), O_CLOEXEC);
#else
    const int descriptor = ::mkstemp(path.data());
#endif
    if (descriptor < 0) {
        return result_string{error_t{string{"fs_stage: cannot reserve file"}}};
    }

    const auto discard = [](const char* filename) { ::unlink(filename); };
    std::unique_ptr<char, decltype(discard)> reservation(path.data(), discard);
#if !defined(__linux__)
    const bool cloexec = ::fcntl(descriptor, F_SETFD, FD_CLOEXEC) == 0;
#else
    constexpr bool cloexec = true;
#endif
    if (::close(descriptor) != 0 || !cloexec) {
        return result_string{error_t{string{"fs_stage: cannot seal reservation"}}};
    }

    auto result = result_string{string{path.c_str()}};
    reservation.release();
    return result;
#else
    (void)directory;
    return result_string{error_t{string{"fs_stage: exclusive reservation is unavailable on this host"}}};
#endif
}

} // namespace scpp::fs

namespace {
namespace files = std::filesystem;

scpp::string owned_string(const std::string& bytes) {
    return scpp::string{bytes.data(), static_cast<scpp::int64>(bytes.size())};
}

scpp::error_t failure(const char* message) {
    return scpp::error_t{scpp::string{message}};
}

files::file_status status_of(const scpp::string& path, bool follow) {
    const auto* name = c_path(path);
    if (!name) return {};
    std::error_code error;
    return follow ? files::status(name, error) : files::symlink_status(name, error);
}

#ifdef _WIN32
// Inspect and delete the same entry even if another process replaces its path.
bool remove_windows_entry(const char* name, bool directory_only) {
    if (!name) return false;
    HANDLE entry = ::CreateFileA(name, DELETE | FILE_READ_ATTRIBUTES,
        FILE_SHARE_READ | FILE_SHARE_WRITE | FILE_SHARE_DELETE, nullptr, OPEN_EXISTING,
        FILE_FLAG_OPEN_REPARSE_POINT | FILE_FLAG_BACKUP_SEMANTICS, nullptr);
    if (entry == INVALID_HANDLE_VALUE) return false;
    BY_HANDLE_FILE_INFORMATION info{};
    bool removed = false;
    if (::GetFileInformationByHandle(entry, &info)) {
        const bool directory = (info.dwFileAttributes & FILE_ATTRIBUTE_DIRECTORY) != 0
            && (info.dwFileAttributes & FILE_ATTRIBUTE_REPARSE_POINT) == 0;
        if (directory == directory_only) {
            FILE_DISPOSITION_INFO disposition{TRUE};
            removed = ::SetFileInformationByHandle(entry, FileDispositionInfo, &disposition, sizeof(disposition)) != 0;
        }
    }
    return ::CloseHandle(entry) && removed;
}
#endif

files::path lexical_path(const scpp::string& path) {
    return files::path(path.data(), path.data() + path.size());
}
}

namespace scpp::fs {
bool_t exists(const string& path) { return files::exists(status_of(path, true)); }
bool_t is_file(const string& path) { return files::is_regular_file(status_of(path, true)); }
bool_t is_dir(const string& path) { return files::is_directory(status_of(path, true)); }
bool_t is_link(const string& path) { return files::is_symlink(status_of(path, false)); }
bool_t is_windows() { return files::path::preferred_separator == '\\'; }

string basename(const string& path) { return owned_string(lexical_path(path).filename().string()); }
string dirname(const string& path) { return owned_string(lexical_path(path).parent_path().string()); }

result_string realpath(const string& path) {
    const auto* name = c_path(path);
    std::error_code error;
    if (name) {
        auto canonical = files::canonical(name, error);
        if (!error) return result_string{owned_string(canonical.string())};
    }
    return result_string{failure("fs_realpath: cannot resolve path")};
}

bool_t mkdir(const string& path) {
    const auto* name = c_path(path);
    std::error_code error;
    return name && files::create_directory(name, error);
}

bool_t rmdir(const string& path) {
    const auto* name = c_path(path);
#ifdef _WIN32
    return remove_windows_entry(name, true);
#else
    return name && ::rmdir(name) == 0;
#endif
}

bool_t copy(const string& source, const string& destination) {
    const auto* from = c_path(source);
    const auto* to = c_path(destination);
    std::error_code error;
    return from && to && files::copy_file(from, to, files::copy_options::overwrite_existing, error);
}

bool_t touch(const string& path) {
    const auto* name = c_path(path);
    if (!name) return false;
    std::error_code error;
    files::last_write_time(name, files::file_time_type::clock::now(), error);
    if (!error) return true;
    if (error != std::errc::no_such_file_or_directory) return false;
    auto* file = std::fopen(name, "ab");
    return file && std::fclose(file) == 0;
}

result_int64 size(const string& path) {
    const auto* name = c_path(path);
    if (name) {
        std::error_code error;
        const auto bytes = files::file_size(name, error);
        if (!error && std::in_range<int64>(bytes)) return result_int64{static_cast<int64>(bytes)};
    }
    return result_int64{failure("fs_size: cannot determine file size")};
}

result_int64 mtime(const string& path) {
    const auto* name = c_path(path);
#ifdef _WIN32
    struct _stat64 info{};
    const bool ok = name && ::_stat64(name, &info) == 0;
#else
    struct stat info{};
    const bool ok = name && ::stat(name, &info) == 0;
#endif
    if (!ok) return result_int64{failure("fs_mtime: cannot stat path")};
    return result_int64{static_cast<int64>(info.st_mtime)};
}

result_vector_string scan(const string& path) {
    const auto* name = c_path(path);
    if (!name) return result_vector_string{failure("fs_scan: invalid path")};
    std::error_code error;
    files::directory_iterator entry(name, error), end;
    std::vector<std::string> names;
    while (!error && entry != end) {
        names.push_back(entry->path().filename().string());
        entry.increment(error);
    }
    if (error) return result_vector_string{failure("fs_scan: cannot enumerate directory")};
    std::sort(names.begin(), names.end());
    vector_string values;
    for (const auto& item : names) values.append(owned_string(item));
    return result_vector_string{std::move(values)};
}

result_string read_snapshot(const string& path, int64 expected_mtime, int64 expected_size) {
#ifdef __linux__
    const auto* name = c_path(path);
    std::string bytes;
    if (!name || !*name || expected_size < 0 || !std::in_range<std::size_t>(expected_size)
        || static_cast<std::size_t>(expected_size) >= bytes.max_size()) {
        return result_string{failure("fs_read_snapshot: invalid path or size")};
    }
    const auto matches = [&](const struct stat& info) {
        return S_ISREG(info.st_mode) && info.st_size == expected_size && info.st_mtime == expected_mtime;
    };
    struct stat original{}, observed{};
    if (::lstat(name, &original) != 0 || !matches(original)) {
        return result_string{failure("fs_read_snapshot: initial metadata mismatch")};
    }
    const auto same = [&] {
        return matches(observed) && observed.st_dev == original.st_dev && observed.st_ino == original.st_ino;
    };
    struct descriptor {
        int fd;
        ~descriptor() { if (fd >= 0) ::close(fd); }
    } file{::open(name, O_RDONLY | O_CLOEXEC | O_NOFOLLOW | O_NONBLOCK)};
    if (file.fd < 0 || ::fstat(file.fd, &observed) != 0 || !same()) {
        return result_string{failure("fs_read_snapshot: opened file changed")};
    }
    const auto limit = static_cast<std::size_t>(expected_size);
    char chunk[8192];
    while (bytes.size() <= limit) {
        const auto count = ::read(file.fd, chunk, std::min(sizeof(chunk), limit + 1 - bytes.size()));
        if (count < 0 && errno == EINTR) continue;
        if (count < 0) return result_string{failure("fs_read_snapshot: read failed")};
        if (!count) break;
        bytes.append(chunk, static_cast<std::size_t>(count));
    }
    if (bytes.size() != limit || ::fstat(file.fd, &observed) != 0 || !same()
        || ::lstat(name, &observed) != 0 || !same()) {
        return result_string{failure("fs_read_snapshot: file changed during read")};
    }
    if (::close(std::exchange(file.fd, -1)) != 0) return result_string{failure("fs_read_snapshot: close failed")};
    return result_string{owned_string(bytes)};
#else
    (void)path; (void)expected_mtime; (void)expected_size;
    return result_string{failure("fs_read_snapshot: unsupported platform")};
#endif
}
}

namespace scpp::php {
bool_t unlink(const string& path) {
    const auto* name = c_path(path);
#ifdef _WIN32
    return remove_windows_entry(name, false);
#else
    return name && ::unlink(name) == 0;
#endif
}
}
