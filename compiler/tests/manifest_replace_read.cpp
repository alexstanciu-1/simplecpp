// Linux test fixture. The caller sets cwd to its temporary project and selects the trigger by symlink.
#include <cstdio>
#include <sys/stat.h>
#include <sys/syscall.h>
#include <unistd.h>

extern "C" ssize_t read(int fd, void* buffer, size_t length) {
    static bool replaced = false;
    struct stat trigger{}, opened{};
    if (!replaced && ::stat("trigger", &trigger) == 0 && ::fstat(fd, &opened) == 0
        && trigger.st_dev == opened.st_dev && trigger.st_ino == opened.st_ino) {
        if (std::rename("next.json", "app/module.json") != 0) ::_exit(91);
        replaced = true;
    }
    return ::syscall(SYS_read, fd, buffer, length);
}
