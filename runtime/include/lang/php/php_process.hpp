#pragma once

#include "scpp/detail/cli_arguments.hpp"
#include "scpp/detail/process_strings.hpp"

namespace scpp::php {

[[nodiscard]] inline result_or_false<string_t> getenv(const string_t &name) {
    return process_detail::getenv_owned<string_t>(name, [](std::string bytes) {
        return string_t(std::move(bytes));
    });
}

[[nodiscard]] inline result_or_false<string_t> shell_exec(const string_t &command) {
    return process_detail::shell_exec_owned<string_t>(command, [](std::string bytes) {
        return string_t(std::move(bytes));
    });
}

} // namespace scpp::php
