#include "api.hpp"

int main() {
    trace failure;
    try {
        report(failure, true);
        return 1;
    } catch (const child_error& error) {
        if (error.code != 4294967297) return 2;
    }
    if (failure.order != 123 || failure.parent_drops != 1 || failure.child_drops != 1) return 3;
    trace success;
    if (report(success, false) != 4294967298) return 4;
    if (success.order != 1234 || success.parent_drops != 1 || success.child_drops != 1) return 5;
}
