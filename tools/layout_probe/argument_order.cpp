// Exercise the by-value helper calls used in emitted C++ before runtime publication.
#include "../../runtime/src/numeric/scalar_semantics.hpp"
#include <cstdio>
#include <cstring>

struct operands {
    char trace[3]{};
    unsigned count{};
    std::int64_t read(char marker, std::int64_t value) {
        if (count >= 2) throw std::logic_error("operand evaluated more than once");
        trace[count++] = marker;
        return value;
    }
};

int main() {
    for (bool divide : {true, false}) {
        for (int divisor : {3, 0}) {
            operands values;
            try {
                const auto result = divide
                    ? scpp::detail::integer_divide(values.read('L', 10), values.read('R', divisor))
                    : scpp::detail::integer_remainder(values.read('L', 10), values.read('R', divisor));
                if (divisor == 0 || result != (divide ? 3 : 1)) {
                    std::fprintf(stderr, "wrong result for %s with divisor %d\n", divide ? "division" : "remainder", divisor);
                    return 1;
                }
            } catch (const std::domain_error& error) {
                if (divisor != 0 || std::strcmp(error.what(), divide ? "division_by_zero" : "modulo_by_zero") != 0) {
                    std::fprintf(stderr, "unexpected arithmetic failure: %s\n", error.what());
                    return 1;
                }
            }
            std::printf("%s%s %s\n", divide ? "divide" : "remainder", divisor ? "" : "_zero", values.trace);
        }
    }
}
