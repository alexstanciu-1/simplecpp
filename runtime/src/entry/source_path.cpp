// This object is linked only by programs that read PHP source locations.
#include <scpp/string.hpp>
#include <filesystem>
#include <system_error>

namespace scpp::php {
namespace {
struct executable_location {
    std::error_code error;
    std::filesystem::path directory = std::filesystem::read_symlink("/proc/self/exe", error).parent_path();
};

// The startup snapshot follows the running Linux executable, including through symlink launches.
const executable_location location;
}

string source_path(const char* suffix)
{
    if (location.error) throw std::system_error(location.error, "Cannot locate running executable");
    auto path = (location.directory / suffix).lexically_normal();
    if (path.has_relative_path() && !path.has_filename()) path = path.parent_path();
    const auto& bytes = path.native();
    return string{bytes.data(), static_cast<int64>(bytes.size())};
}
}
