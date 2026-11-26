// Linux host boundaries for the native toolchain probe.
#include "frontend_host.hpp"

#include <algorithm>
#include <cerrno>
#include <cstdio>
#include <cstdlib>
#include <cstring>
#include <filesystem>
#include <memory>
#include <stdexcept>
#include <string>
#include <system_error>
#include <vector>
#include <spawn.h>
#include <sys/stat.h>
#include <sys/wait.h>
#include <unistd.h>

extern char** environ;

namespace {
scpp::string bytes(const std::string& s) { return scpp::string(s.data(), s.size()); }
std::string native(const scpp::string& s) { return {s.data(), static_cast<std::size_t>(s.size())}; }
void check(int err, const char* op) { if (err) throw std::system_error(err, std::generic_category(), op); }
using File = std::unique_ptr<FILE, decltype(&std::fclose)>;
File capture() {
    File file(std::tmpfile(), std::fclose);
    if (!file) check(errno, "tmpfile");
    return file;
}
std::string read_capture(FILE* file) {
    if (std::fseek(file, 0, SEEK_SET)) check(errno, "rewind capture");
    std::string result;
    char data[8192];
    while (auto n = std::fread(data, 1, sizeof(data), file)) result.append(data, n);
    if (std::ferror(file)) check(errno, "read capture");
    return result;
}
struct Actions {
    posix_spawn_file_actions_t value;
    Actions() { check(posix_spawn_file_actions_init(&value), "spawn actions"); }
    ~Actions() { posix_spawn_file_actions_destroy(&value); }
};
}

namespace App {
scpp::string host_self_() { return bytes(std::filesystem::read_symlink("/proc/self/exe").string()); }
scpp::string host_abspath_(scpp::string path) {
    return bytes(std::filesystem::absolute(native(path)).lexically_normal().string());
}
scpp::string host_stat_tuple_(scpp::string path) {
    struct stat s{};
    if (::stat(path.data(), &s) < 0) {
        if (errno == ENOENT || errno == ENOTDIR) return scpp::string("null");
        check(errno, "stat qualification input");
    }
    auto ns = [](timespec t) { return std::to_string(t.tv_sec * 1000000000LL + t.tv_nsec); };
    return bytes("[" + std::to_string(s.st_dev) + "," + std::to_string(s.st_ino) + "," +
                 std::to_string(s.st_size) + "," + ns(s.st_mtim) + "," + ns(s.st_ctim) + "]");
}

// Track candidate names without duplicating the compiler's header search rules.
scpp::string host_include_tree_(scpp::string path) {
    namespace fs = std::filesystem;
    const fs::path root(path.size() ? native(path) : ".");
    std::error_code error;
    if (std::find(root.begin(), root.end(), "..") != root.end() || fs::is_symlink(root, error) || error) return {};
    std::vector<std::string> names;
    fs::recursive_directory_iterator entry(root, error), end;
    while (!error && entry != end) {
        const auto type = entry->symlink_status(error).type();
        if (error || names.size() >= 4096 || (type != fs::file_type::regular && type != fs::file_type::directory))
            return {};
        names.push_back((type == fs::file_type::directory ? "D" : "F") + entry->path().lexically_relative(root).native());
        entry.increment(error);
    }
    if (error) return {};
    std::sort(names.begin(), names.end());
    std::string inventory;
    for (const auto& name : names) { inventory += name; inventory += '\0'; }
    return host_sha256_bytes_(bytes(inventory));
}

// Keep argv literal. Separate anonymous files allow either child stream to grow without a pipe deadlock.
scpp::string host_probe_command_(scpp::string packed, bool want_stderr) {
    auto args = native(packed);
    std::vector<char*> argv;
    for (std::size_t i = 0; i < args.size();) {
        argv.push_back(args.data() + i);
        auto end = args.find('\0', i);
        if (end == std::string::npos) break;
        i = end + 1;
    }
    if (argv.empty()) throw std::runtime_error("Empty qualification command");
    argv.push_back(nullptr);
    auto out = capture(), err = capture();
    Actions actions;
    check(posix_spawn_file_actions_adddup2(&actions.value, fileno(out.get()), STDOUT_FILENO), "capture stdout");
    check(posix_spawn_file_actions_adddup2(&actions.value, fileno(err.get()), STDERR_FILENO), "capture stderr");
    check(posix_spawn_file_actions_addclose(&actions.value, fileno(out.get())), "close capture");
    check(posix_spawn_file_actions_addclose(&actions.value, fileno(err.get())), "close capture");
    pid_t pid;
    check(posix_spawnp(&pid, argv.front(), &actions.value, nullptr, argv.data(), environ), "launch qualification command");
    int status;
    while (::waitpid(pid, &status, 0) < 0) if (errno != EINTR) check(errno, "wait for qualification");
    if (!WIFEXITED(status) || WEXITSTATUS(status) != 0) {
        auto diagnostic = read_capture(err.get()) + read_capture(out.get());
        if (std::string(argv[0]) == "env" && argv.size() == 5 && std::string(argv[2]) == "ldd" &&
            diagnostic.find("not a dynamic executable") != std::string::npos) return bytes(diagnostic);
        throw std::runtime_error(std::string(argv[0]) + ": " + diagnostic);
    }
    return bytes(read_capture(want_stderr ? err.get() : out.get()));
}

// Split POSIX-quoted compiler -### and depfile text without interpreting it as a command.
scpp::string host_probe_words_(scpp::string text) {
    std::string result;
    char quote = 0;
    bool word = false;
    auto input = native(text);
    for (std::size_t i = 0; i < input.size(); ++i) {
        char c = input[i];
        if (!quote && std::strchr(" \t\r\n", c)) {
            if (word) result.push_back('\0');
            word = false;
            continue;
        }
        word = true;
        if (quote && c == quote) { quote = 0; continue; }
        if (!quote && (c == '\'' || c == '"')) { quote = c; continue; }
        if (c == '\\' && quote != '\'') {
            if (++i == input.size()) throw std::runtime_error("Invalid trailing escape in compiler output");
            if (quote && input[i] != '\\' && input[i] != quote) result.push_back(c);
            c = input[i];
        }
        result.push_back(c);
    }
    if (quote) throw std::runtime_error("Unclosed quote in compiler output");
    if (word) result.push_back('\0');
    return bytes(result);
}
}
