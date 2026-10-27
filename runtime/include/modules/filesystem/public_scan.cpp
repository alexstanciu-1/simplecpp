#include "../../../public/modules/filesystem/scan.hpp"
#include "scpp/detail/concrete_access.hpp"
#include "scpp/detail/string_access.hpp"
#include "detail/scan.hpp"

namespace scpp::fs
{
    result_vector_string scan(const string &path)
    {
        return detail::concrete_access::make<result_vector_string, result<vector_string>>([&] {
            return filesystem::filesystem_detail::scan_owned<vector_string>(detail::string_access::get(path),
                [](std::string name) {
                    return detail::string_access::make([&] { return string_t(std::move(name)); });
                });
        });
    }
}
