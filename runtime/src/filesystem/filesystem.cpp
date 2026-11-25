#include "filesystem_provider.hpp"

#include <cstdio>
#include <cstring>
#include <cstdlib>
#include <filesystem>
#include <memory>
#include <string>
#include <utility>

#if defined(__unix__) || defined(__APPLE__)
#include <fcntl.h>
#include <unistd.h>
#endif

namespace {
const char* c_path(const scpp::string& path) {
    const auto* bytes = path.data();
    return std::memchr(bytes, '\0', static_cast<std::size_t>(path.size())) == nullptr ? bytes : nullptr;
}
}

scpp::result_string scpp::fs::get(const string& path) {
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

scpp::result_int64 scpp::fs::put(const string& path, const string& data) {
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

scpp::bool_t scpp::fs::rename(const string& source, const string& destination) {
    const auto* from = c_path(source);
    const auto* to = c_path(destination);
    if (from == nullptr || to == nullptr) return false;

    std::error_code error;
    std::filesystem::rename(from, to, error);
    return !error;
}

scpp::bool_t scpp::fs::remove(const string& path) {
    const auto* filename = c_path(path);
    if (filename == nullptr) return false;

    std::error_code error;
    return std::filesystem::remove(filename, error) && !error;
}

scpp::result_string scpp::fs::stage(const string& directory) {
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
