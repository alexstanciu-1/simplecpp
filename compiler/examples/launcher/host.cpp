// Proof-only stderr adapter. The test supplies the generated host declarations.
#include <cstdio>
#include <stdexcept>

namespace App {
void host_write_error_(scpp::string message) {
    const auto size = static_cast<std::size_t>(message.size());
    if (std::fwrite(message.data(), 1, size, stderr) != size) {
        throw std::runtime_error("Cannot write launcher diagnostics");
    }
}
}
