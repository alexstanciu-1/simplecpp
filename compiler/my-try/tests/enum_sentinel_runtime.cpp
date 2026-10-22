#include "lang/php/support/php_take.hpp"
#include "lang/php/operators/isset/isset.hpp"
#include <cassert>
#include <cstdint>
#include <iostream>

enum class Unit { Zero, One };
enum class Signed : std::int64_t { Low = INT64_MIN, High = INT64_MAX };
enum class Unsigned : std::uint64_t { Zero = 0, High = UINT64_MAX };

template<class Wrapper> void requires_payload(const Wrapper& source) {
    bool threw = false;
    try { (void)source.require_value("enum sentinel probe"); }
    catch (const std::exception&) { threw = true; }
    assert(threw);
}
template<class E> void prove(E first, E second) {
    E output = second;
    scpp::result_or_false<E> f = first;
    auto copied = f;
    f = scpp::false_sentinel;
    assert(!scpp::php::isset(f).native_value());
    assert(!scpp::php::take(output, f).native_value() && output == second);
    requires_payload(f);
    assert(scpp::php::isset(copied).native_value());
    assert(scpp::php::take(output, copied).native_value() && output == first);
    assert(copied.has_value().native_value());
    scpp::result_or_bool<E> b = first;
    scpp::bool_t flag{false};
    assert(scpp::php::take(output, flag, b).native_value() && output == first && !flag.native_value());
    auto saved = b;
    b = scpp::true_sentinel;
    assert(scpp::php::isset(b).native_value() && !b.has_value().native_value());
    output = second;
    assert(scpp::php::take(output, flag, b).native_value() && output == second && flag.native_value());
    requires_payload(b);
    auto saved_true = b;
    b = scpp::false_sentinel;
    assert(!scpp::php::isset(b).native_value());
    assert(!scpp::php::take(output, flag, b).native_value() && output == second && !flag.native_value());
    requires_payload(b);
    assert(saved_true.is_true().native_value() && saved.has_value().native_value());
    flag = scpp::bool_t{true};
    assert(scpp::php::take(output, flag, saved).native_value() && output == first && flag.native_value());
    b = first;
    assert(scpp::php::isset(b).native_value() && b.value() == first);
}
int main() {
    prove(Unit::Zero, Unit::One);
    prove(Signed::Low, Signed::High);
    prove(Unsigned::Zero, Unsigned::High);
    std::cout << "enum sentinel runtime passed\n";
}
