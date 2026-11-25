#include "scpp/runtime_error.hpp"
#include "scpp/exception_info.hpp"
#include <stdexcept>

static const void *original = nullptr;
static const void *foreign = nullptr;
[[noreturn]] void throw_error_fixture()
{
    try {
        throw scpp::runtime_error("A retained runtime exception with a message longer than short storage",
            "test_code", "test_component", "test_operation", {{"field", "catalog.name"}, {"row", "27"}});
    } catch (const scpp::runtime_error &error) {
        original = &error;
        throw;
    }
}
bool verify_error_fixture(const scpp::exception_info &info)
{
    try { info.rethrow(); }
    catch (const scpp::runtime_error &error) {
        if (&error != original || info.trace_count().native_value() != static_cast<long>(error.trace().size())) { return false; }
        for (std::size_t i = 0; i < error.trace().size(); ++i) {
            if (!info.trace_module(scpp::int64(i)).equals(error.trace()[i].module_path.c_str()) ||
                !info.trace_address(scpp::int64(i)).equals(error.trace()[i].relative_address.c_str())) { return false; }
        }
        return true;
    }
}
[[noreturn]] void throw_foreign_fixture()
{
    try { throw std::logic_error("foreign exception"); }
    catch (const std::logic_error &error) { foreign = &error; throw; }
}
bool active_foreign_fixture()
{
    try { throw; } catch (const std::logic_error &error) { return &error == foreign && std::string(error.what()) == "foreign exception"; }
    catch (...) { return false; }
}
