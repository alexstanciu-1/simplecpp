#include "allocation_probe.hpp"

// App comparator definitions precede this native caller in the generated fixture.
int test_result_equality() {
    using scpp::int64;
    using scpp::result_int64;
    using scpp::string;
    auto failure = [](const char* message, const char* file, int64 line) {
        return result_int64{scpp::error_t{string{message, 3}, line, string{file, 3}}};
    };
    result_int64 zero{}, one{int64{1}};
    auto first = failure("m\0a", "f\0a", 7);
    auto same = failure("m\0a", "f\0a", 7);
    auto message = failure("m\0b", "f\0a", 7);
    auto file = failure("m\0a", "f\0b", 7);
    auto line = failure("m\0a", "f\0a", 8);
    result_int64 empty{scpp::error_t{string{}}};
    result_int64 independent_empty{scpp::error_t{string{}}};
    auto check = [](result_int64& a, result_int64& b, bool equal, bool identical) {
        return App::ordinary_(a, b) == equal && App::different_(a, b) == !equal
            && App::identical_(a, b) == identical && App::distinct_(a, b) == !identical;
    };
    const auto before = allocations.load();
    if (!check(zero, zero, true, true) || !check(zero, one, false, false)) return 81;
    if (!check(zero, empty, false, false) || !check(empty, zero, false, false)) return 82;
    if (!check(first, same, true, true) || !check(empty, independent_empty, true, true)) return 83;
    if (!check(first, message, true, false) || !check(first, file, true, false)
        || !check(first, line, true, false)) return 84;
    if (App::error_peers_(empty)) return 87;
    if (allocations.load() != before) return 86;
    if (App::before_replace_(first) || first.value() != 9) return 85;
    return App::scpp_entry_body();
}
