
#include <scpp/entry.hpp>
#include <cstdio>
#include <exception>
@exception_header@
namespace scpp {
int run_entry_with_exceptions(int (*entry)()) {
    try {
        return entry();
@language_exception_handler@    } catch (const std::exception& failure) {
        std::fputs(failure.what(), stderr);
    }
    std::fputc('\n', stderr);
    return 1;
}
}
