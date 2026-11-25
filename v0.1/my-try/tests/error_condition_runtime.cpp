#include "lang/php/operators/conditional/condition_truthiness.hpp"
#include <cassert>
#include <iostream>
int main() {
 scpp::error_t none;
 assert(!scpp::php::condition_truthy(none).native_value());
 assert(!scpp::php::condition_truthy(scpp::error_t::no_error).native_value());
 scpp::error_t failure(scpp::string_t(""));
 assert(scpp::php::condition_truthy(failure).native_value());
 auto saved = failure;
 failure = scpp::error_t::no_error;
 assert(scpp::php::condition_truthy(saved).native_value());
 assert(!scpp::php::condition_truthy(failure).native_value());
 int count = 0;
 auto once = [&]() { ++count; return saved; };
 assert(scpp::php::condition_truthy(once()).native_value());
 assert(count == 1);
 std::cout << "runtime-ok";
}
