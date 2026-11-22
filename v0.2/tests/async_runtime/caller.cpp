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
    trace void_success;
    notify(void_success, false);
    if (void_success.order != 1234 || void_success.parent_drops != 1 || void_success.child_drops != 1) return 6;
    trace void_failure;
    try {
        notify(void_failure, true);
        return 7;
    } catch (const child_error& error) {
        if (error.code != 4294967297) return 8;
    }
    if (void_failure.order != 123 || void_failure.parent_drops != 1 || void_failure.child_drops != 1) return 9;
    if (!verify_owned_text()) return 10;
}
