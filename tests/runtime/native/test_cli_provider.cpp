#include "scpp/detail/cli_arguments.hpp"

extern unsigned cli_allocations;

// Test-only startup boundary; no setter enters the generated public API.
void initialize_test_cli(int count, char **arguments)
{
    scpp::php::set_cli_args(count, arguments);
}

unsigned native_cli_snapshot_allocations()
{
    const auto before = cli_allocations;
    const auto result = scpp::php::cli_argv();
    return cli_allocations - before;
}
