#include "public_api_guard.hpp"
#include "scpp/cli/cli_argc.hpp"
#include "scpp/cli/cli_argv.hpp"
#include "scpp/cli/cli_args.hpp"
#include "scpp/observations/count_mixed.hpp"
#include <cstdio>
#include <cstdlib>
#include <new>

unsigned cli_allocations = 0;
void *operator new(std::size_t size)
{
    ++cli_allocations;
    if (auto memory = std::malloc(size ? size : 1)) return memory;
    throw std::bad_alloc();
}
void operator delete(void *memory) noexcept { std::free(memory); }
void operator delete(void *memory, std::size_t) noexcept { std::free(memory); }
void initialize_test_cli(int count, char **arguments);
unsigned native_cli_snapshot_allocations();
#define CHECK(value) do { if (!(value)) { std::fprintf(stderr, "CLI failure %d\n", __LINE__); std::abort(); } } while(false)

static scpp::mixed retained_snapshot()
{
    using namespace scpp;
    char program[] = "sample.phs";
    char empty_arg[] = "";
    char spaced[] = "two words";
    char long_arg[] = "argument_long_enough_to_exercise_owned_string_allocation_and_lifetime";
    char *arguments[] = {program, empty_arg, spaced, long_arg};
    initialize_test_cli(4, arguments);
    const auto before = cli_allocations;
    for (int i = 0; i < 100; ++i) CHECK(cli_argc().native_value() == 4);
    CHECK(cli_allocations == before);
    const auto native_count = native_cli_snapshot_allocations();
    const auto public_before = cli_allocations;
    auto first = cli_argv();
    const auto public_count = cli_allocations - public_before;
    CHECK(public_count == native_count);
    auto second = cli_args();
    CHECK(first.is_hash() && second.is_hash());
    CHECK(count(first).native_value() == 4 && count(second).native_value() == 4);
    CHECK(first.get(int64(0)).get_string().equals("sample.phs"));
    CHECK(first.get(int64(1)).get_string().equals(""));
    CHECK(first.get(int64(2)).get_string().equals("two words"));
    CHECK(first.get(int64(3)).get_string().equals(long_arg));
    second.set(int64(0), mixed(string("edited snapshot")));
    CHECK(first.get(int64(0)).get_string().equals("sample.phs"));
    CHECK(cli_argv().get(int64(0)).get_string().equals("sample.phs"));
    program[0] = 'X';
    CHECK(cli_args().get(int64(0)).get_string().equals("Xample.phs"));
    CHECK(first.get(int64(0)).get_string().equals("sample.phs"));
    initialize_test_cli(0, nullptr);
    std::printf("CLI snapshot allocations: native=%u public=%u\n", native_count, public_count);
    return first;
}

int main()
{
    using namespace scpp;
    CHECK(cli_argc().native_value() == 0);
    CHECK(cli_argv().is_hash() && count(cli_args()).native_value() == 0);
    auto retained = retained_snapshot();
    CHECK(cli_argc().native_value() == 0);
    CHECK(count(cli_argv()).native_value() == 0);
    CHECK(retained.get(int64(2)).get_string().equals("two words"));
    CHECK(retained.get(int64(3)).get_string().equals("argument_long_enough_to_exercise_owned_string_allocation_and_lifetime"));
    std::puts("PASS: cross-TU context, owned independent snapshots, byte preservation, allocation-neutral boundary");
}
