#pragma once
#include "scpp/result.hpp"
#include <algorithm>
#include <filesystem>
#include <string>
#include <vector>

namespace scpp::filesystem::filesystem_detail
{
    // One scan algorithm constructs the selected stored representation directly.
    template<class Vector, class MakeString>
    result<Vector> scan_owned(const string_t &path, const MakeString &make_string)
    {
        std::error_code error;
        const auto native_path = std::filesystem::path(path.native_value());
        if (!std::filesystem::is_directory(native_path, error) || error) {
            return error_t(string_t("fs::scan(): not a directory"));
        }
        std::vector<std::string> names;
        std::filesystem::directory_iterator entries(native_path, error);
        if (error) { return error_t(string_t("fs::scan(): cannot open directory")); }
        const std::filesystem::directory_iterator end;
        while (entries != end) {
            names.push_back(entries->path().filename().string());
            entries.increment(error);
            if (error) { return error_t(string_t("fs::scan(): iteration failed")); }
        }
        std::sort(names.begin(), names.end());
        Vector output;
        for (auto &name : names) { output.append(make_string(std::move(name))); }
        return output;
    }
}
