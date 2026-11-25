#include <scpp/filesystem/lock_empty.hpp>
#include <scpp/filesystem/lock_release.hpp>
#include <scpp/filesystem/lock_transfer.hpp>
#include <scpp/filesystem/lock_try.hpp>
#include <cassert>
#include <cerrno>
#include <fcntl.h>
#include <filesystem>
#include <sys/file.h>
#include <sys/wait.h>
#include <unistd.h>

using namespace scpp::fs;

// Faults affect one call in this test executable; other calls reach libc.
static bool fail_unlock;
static bool fail_close;
extern "C" int __real_flock(int, int);
extern "C" int __real_close(int);
extern "C" int __wrap_flock(int fd, int op) {
    if (op == LOCK_UN && fail_unlock) {
        fail_unlock = false;
        errno = EIO;
        return -1;
    }
    return __real_flock(fd, op);
}
extern "C" int __wrap_close(int fd) {
    const int rc = __real_close(fd);
    if (fail_close && rc == 0) {
        fail_close = false;
        errno = EIO;
        return -1;
    }
    return rc;
}

static void wait_ok(pid_t child) {
    int status{};
    assert(child > 0 && waitpid(child, &status, 0) == child && WIFEXITED(status) && WEXITSTATUS(status) == 0);
}

static auto open_files() {
    return std::distance(std::filesystem::directory_iterator("/proc/self/fd"),
                         std::filesystem::directory_iterator());
}

int main(int argc, char** argv) {
    const std::filesystem::path path = std::filesystem::absolute("lifetime.lock");
    if (argc > 1) {
        for (const auto& fd : std::filesystem::directory_iterator("/proc/self/fd"))
            assert(std::filesystem::read_symlink(fd.path()) != path);
        return 0;
    }
    const scpp::string name{path.c_str()};
    const auto baseline = open_files();
    for (int i = 0; i < 64; ++i) {
        auto token = lock_empty();
        {
            auto owner = lock_empty();
            assert(lock_try(owner, name).value());
            token = owner;
        }
        auto other = lock_empty();
        assert(!lock_try(other, name).value());
        assert(lock_try(other, scpp::string{"."}).has_error());
    }
    assert(open_files() == baseline);

    auto owner = lock_empty();
    assert(lock_try(owner, name).value());
    auto alias = owner;
    auto other = lock_empty();
    fail_unlock = true;
    assert(lock_release(alias).has_error() && !lock_try(other, name).value());
    fail_close = true;
    assert(lock_release(owner).has_error() && lock_release(alias).value());
    assert(lock_try(other, name).value());
    assert(lock_release(owner).value() && !lock_try(alias, name).value());
    assert(lock_release(other).value());
    assert(lock_try(owner, name).value());
    alias = owner;
    pid_t child = fork();
    if (child == 0) {
        assert(lock_release(owner).has_error() && lock_transfer(owner).has_error());
        owner = lock_empty();
        alias = lock_empty(); // Child drops its final inherited reference.
        auto probe = lock_empty();
        _exit(lock_try(probe, name).value() ? 1 : 0);
    }
    wait_ok(child);
    child = fork();
    if (child == 0) {
        execl(argv[0], argv[0], "exec-check", static_cast<char*>(nullptr));
        _exit(2);
    }
    wait_ok(child);
    int gate[2];
    assert(pipe(gate) == 0);
    child = fork();
    if (child == 0) {
        close(gate[1]);
        char byte;
        assert(read(gate[0], &byte, 1) == 1);
        auto probe = lock_empty();
        _exit(lock_try(probe, name).value() ? 0 : 1);
    }
    close(gate[0]);
    assert(lock_release(alias).value()); // Child still holds an inherited descriptor.
    assert(write(gate[1], "x", 1) == 1);
    close(gate[1]);
    wait_ok(child);
    assert(open_files() == baseline);
}
