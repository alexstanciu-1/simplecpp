#pragma once

#include <scpp/string_t.hpp>
#include <cstdio>
#include <stdexcept>
#include <string>
#include <utility>

namespace scpp_provider {

// Read one byte-preserving console line. Remove LF and its optional preceding
// CR; a lone CR is data. A final nonempty unterminated line succeeds. Empty EOF
// and read errors throw into the generated bridge's terminate boundary.
inline scpp::string_t input_line() {
    std::string bytes;
    while (true) {
        const int next = std::fgetc(stdin);
        if (next == EOF) {
            if (std::ferror(stdin)) {
                throw std::runtime_error("console input read error");
            }
            if (bytes.empty()) {
                throw std::runtime_error("console input reached end of input");
            }
            break;
        }
        if (next == '\n') {
            if (!bytes.empty() && bytes.back() == '\r') {
                bytes.pop_back();
            }
            break;
        }
        bytes.push_back(static_cast<char>(static_cast<unsigned char>(next)));
    }
    return scpp::string_t(std::move(bytes));
}

} // namespace scpp_provider
