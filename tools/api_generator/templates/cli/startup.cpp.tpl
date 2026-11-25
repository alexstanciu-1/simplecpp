namespace scpp::detail {
cli_context invocation{};
}
namespace scpp {
int run_entry_with_exceptions(int (*entry)(), int argc, char** argv) {
    detail::invocation = {argc, argv};
    return run_entry_with_exceptions(entry);
}
}
