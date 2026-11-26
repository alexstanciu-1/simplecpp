// Round-trip probe for the compiler-private binary64 implementation.
#include "../../compiler/06_native/binary64_literal.hpp"
#include <bit>
#include <cfenv>
#include <cstdint>
#include <iostream>
#include <stdexcept>
#include <string>

// Exercise the conversion inside a caller with nondefault floating state.
bool preserves_environment() {
    for (int mode : {FE_DOWNWARD, FE_UPWARD, FE_TOWARDZERO, FE_TONEAREST}) {
        if (std::fesetround(mode) || std::feclearexcept(FE_ALL_EXCEPT) || std::feraiseexcept(FE_INVALID)) return false;
        const int flags = std::fetestexcept(FE_ALL_EXCEPT);
        if (scpp::compiler::parse_decimal_literal("1.00000000000000011102230246251565404236316680908203125") != 1.0) return false;
        if (std::fegetround() != mode || std::fetestexcept(FE_ALL_EXCEPT) != flags) return false;
        try {
            scpp::compiler::parse_decimal_literal("1e9999");
            return false;
        } catch (const std::out_of_range&) {
            if (std::fegetround() != mode || std::fetestexcept(FE_ALL_EXCEPT) != flags) return false;
        }
    }
    try {
        scpp::compiler::parse_decimal_literal(std::string{"1.0\0x", 5});
        return false;
    } catch (const std::invalid_argument&) {
        return true;
    }
}

int main(int argc, char** argv) {
    if (argc == 3 && std::string(argv[1]) == "decimal-literal") {
        try {
            std::cout << scpp::compiler::cpp_binary64_literal(scpp::compiler::parse_decimal_literal(argv[2])) << '\n';
            return 0;
        } catch (const std::exception& error) {
            std::cerr << error.what() << '\n';
            return 1;
        }
    }
    if (!preserves_environment()) return 1;
    std::uint64_t bits;
    while (std::cin >> std::hex >> bits) {
        try {
            std::cout << scpp::compiler::cpp_binary64_literal(std::bit_cast<double>(bits)) << '\n';
        } catch (const std::domain_error&) {
            std::cout << "nonfinite\n";
        }
    }
    return std::cin.eof() ? 0 : 1;
}
