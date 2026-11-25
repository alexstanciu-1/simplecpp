#include "lang/php/support/php_take.hpp"
#include <cassert>
#include <cstdint>
#include <iostream>

enum class Unit { Zero, One };
enum class Signed : std::int64_t { Low = INT64_MIN, High = INT64_MAX };
enum class Unsigned : std::uint64_t { Zero = 0, High = UINT64_MAX };

template<class E> void prove(E first, E second) {
    E output = second;
    scpp::nullable<E> absent = scpp::null;
    assert(!scpp::php::take(output, absent).native_value() && output == second);
    scpp::nullable<E> present = first;
    auto saved = present;
    present = second;
    assert(scpp::php::take(output, saved).native_value() && output == first);
    bool threw = false;
    try { (void)absent.require_value("enum probe"); } catch (const std::exception &) { threw = true; }
    assert(threw);
    scpp::error_t error{scpp::string_t("old")};
    scpp::result<E> good = first;
    assert(scpp::php::take(output, error, good).native_value() && output == first);
    assert(!error.has_error().native_value());
    scpp::result<E> bad = scpp::error_t{scpp::string_t("failed")};
    assert(!scpp::php::take(output, error, bad).native_value() && output == first);
    assert(error.has_error().native_value());
    auto copied = good;
    good = second;
    assert(copied.value() == first);
    threw = false;
    try { (void)bad.require_value("enum probe"); } catch (const std::exception &) { threw = true; }
    assert(threw);
}
int main() {
    prove(Unit::Zero, Unit::One);
    prove(Signed::Low, Signed::High);
    prove(Unsigned::Zero, Unsigned::High);
    std::cout << "enum wrapper runtime passed\n";
}
