// Linux probe: end every input immediately before a protected page.
#include "reader.hpp"
#include <cstring>
#include <sys/mman.h>
#include <unistd.h>

int main() {
    const auto page = sysconf(_SC_PAGESIZE);
    if (page < 128) return 1;
    void* mapping = mmap(nullptr, 2 * page, PROT_READ | PROT_WRITE, MAP_PRIVATE | MAP_ANONYMOUS, -1, 0);
    if (mapping == MAP_FAILED) return 2;
    auto* end = static_cast<char*>(mapping) + page;
    if (mprotect(end, page, PROT_NONE)) return 3;
    for (int length = 0; length <= 96; ++length) {
        auto* data = end - length;
        std::memset(data, 'a', length);
        std::string_view input(data, length);
        auto matches = [&](std::size_t expected) {
            using namespace scpp::detail::json::reader_detail;
            return plain_string_prefix(input) == expected && portable_string_prefix(input) == expected;
        };
        if (!matches(input.size())) return 4;
        for (int i = 0; i < length; ++i) {
            for (unsigned byte = 0; byte <= 255; ++byte) {
                data[i] = static_cast<char>(byte);
                const auto expected = byte < 32 || byte == '"' || byte == '\\' ? i : length;
                if (!matches(static_cast<std::size_t>(expected))) return 5;
            }
            data[i] = 'a';
        }
    }
    return munmap(mapping, 2 * page) != 0;
}
