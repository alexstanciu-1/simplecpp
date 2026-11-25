#include "api.hpp"

#include <cassert>
#include <stdexcept>
#include <string_view>

int main() {
    AsyncTrace success;
    assert(report(success, false) == 43);
    assert(success.events == 1234 && success.child_drops == 1 && success.parent_drops == 1);

    AsyncTrace failure;
    bool caught = false;
    try {
        report(failure, true);
    } catch (const std::runtime_error& error) {
        caught = std::string_view{error.what()} == "child failed";
    }
    assert(caught);
    assert(failure.events == 123 && failure.child_drops == 1 && failure.parent_drops == 1);
}
