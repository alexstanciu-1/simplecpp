#include "filesystem_provider.hpp"

#include <cstdio>
#include <cstring>
#include <memory>
#include <string>
#include <utility>

scpp::result_string scpp::fs::get(const string& path) {
    if (std::memchr(path.data(), '\0', static_cast<std::size_t>(path.size())) != nullptr) {
        return result_string{error_t{string{"fs_get: path contains a NUL byte"}}};
    }

    const std::unique_ptr<std::FILE, decltype(&std::fclose)> file(
        std::fopen(path.data(), "rb"), &std::fclose);
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
