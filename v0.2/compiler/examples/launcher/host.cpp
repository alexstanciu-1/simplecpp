// Proof-only process adapter. The test supplies the generated host declarations.
#include <scpp/entry.hpp>
#include <cstdio>
#include <stdexcept>

namespace {
int argument_count;
char** argument_values;
}

namespace App {
int scpp_entry_body();

scpp::vector_string host_arguments_() {
    scpp::vector_string arguments;
    arguments.reserve(static_cast<scpp::int64>(argument_count));
    for (int i = 0; i < argument_count; ++i) {
        arguments.append(scpp::string{argument_values[i]});
    }
    return arguments;
}

void host_write_error_(scpp::string message) {
    const auto size = static_cast<std::size_t>(message.size());
    if (std::fwrite(message.data(), 1, size, stderr) != size) {
        throw std::runtime_error("Cannot write launcher diagnostics");
    }
}
}

int main(int argc, char** argv) {
    argument_count = argc;
    argument_values = argv;
    return scpp::run_entry_with_exceptions(App::scpp_entry_body);
}
