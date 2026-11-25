// Raw runtime proof only: ValueError is not a portable-PHP catch binding.
#include "lang/php/support/php_string.hpp"
#include <string>

int main() {
    for (const char* input : {"", "abc"}) {
        bool failed = false;
        try {
            scpp::php::explode(scpp::string_t(""), scpp::string_t(input));
        } catch (const scpp::ValueError& error) {
            if (std::string(error.what()) != "explode(): Argument #1 ($separator) must not be empty") return 2;
            failed = true;
        }
        if (!failed) return 1;
    }
    return 0;
}
