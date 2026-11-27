#include "../../runtime/src/json/string_capacity.hpp"
#include <string>
#include <string_view>

int main() {
    using namespace scpp::detail::json::reader_detail;
    std::string source(last_qualified_prefix, 'x');
    for (std::size_t size = first_qualified_prefix; size <= last_qualified_prefix; ++size) {
        std::string_view prefix(source.data(), size);
        std::string ordinary(prefix);
        if ((ordinary.capacity() == size) != predicted_full_prefix(size)) return 1;
        if (predicted_full_prefix(size)) {
            std::string reserved;
            reserved.reserve(size + 1);
            reserved.append(prefix);
            const auto capacity = reserved.capacity();
            reserved.push_back('x');
            if (reserved.capacity() != capacity) return 1;
        }
    }
}
