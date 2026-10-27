#include "lang/php/php_process.hpp"
#include <cstdio>
#include <cstdlib>

extern unsigned process_allocations;
unsigned native_getenv_allocations(const char *name)
{
    const scpp::string_t input(name);
    const auto before = process_allocations;
    const auto value = scpp::php::getenv(input);
    return process_allocations - before;
}

static int closed = 0;
static int close_fixture(FILE *file)
{
    ++closed;
    return std::fclose(file);
}

struct immovable_factory {
    immovable_factory() = default;
    immovable_factory(const immovable_factory &) = delete;
    immovable_factory(immovable_factory &&) = delete;
    scpp::string_t operator()(std::string bytes) const { return scpp::string_t(std::move(bytes)); }
};

bool verify_pipe_failure_and_cleanup()
{
    using namespace scpp::php::process_detail;
    int constructed = 0;
    const auto absent = capture_pipe_owned<scpp::string_t>(nullptr, close_fixture,
        [&](std::string bytes) { ++constructed; return scpp::string_t(std::move(bytes)); });
    if (!absent.is_false() || constructed || closed) return false;
    FILE *file = std::tmpfile();
    if (!file) return false;
    std::fputs("captured", file);
    std::rewind(file);
    bool threw = false;
    try {
        (void)capture_pipe_owned<scpp::string_t>(file, close_fixture,
            [](std::string) -> scpp::string_t { throw 7; });
    } catch (int value) { threw = value == 7; }
    if (!threw || closed != 1) return false;
    FILE *second = std::tmpfile();
    if (!second) return false;
    immovable_factory factory;
    const auto empty = capture_pipe_owned<scpp::string_t>(second, close_fixture, factory);
    return empty.has_value() && empty.require_value("fixture").native_value().empty() && closed == 2;
}
