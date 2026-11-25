// The linker's read wrapper replaces the path after open and before the final checks.
#include "scpp/filesystem/fs_read_snapshot.hpp"
#include <cerrno>
#include <cstdio>
#include <exception>
#include <fcntl.h>
#include <unistd.h>

namespace {
int observed_fd = -1;
bool replace_path = false;

bool observed_closed() {
    return observed_fd >= 0 && ::fcntl(observed_fd, F_GETFD) == -1 && errno == EBADF;
}
}

extern "C" ssize_t __real_read(int, void*, size_t);
extern "C" ssize_t __wrap_read(int fd, void* buffer, size_t size) {
    observed_fd = fd;
    if (replace_path) {
        replace_path = false;
        if (std::rename("replacement", "snapshot") != 0) std::terminate();
    }
    return __real_read(fd, buffer, size);
}

int main() {
    auto first = scpp::fs::read_snapshot(scpp::string{"snapshot"}, 1000000000, 4);
    if (!first.has_value() || first.value() != scpp::string{"data"} || !observed_closed()) return 1;
    observed_fd = -1;
    replace_path = true;
    auto changed = scpp::fs::read_snapshot(scpp::string{"snapshot"}, 1000000000, 4);
    return changed.has_error() && !replace_path && observed_closed() ? 0 : 2;
}
