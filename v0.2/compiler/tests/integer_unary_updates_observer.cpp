// Check the complete uint8 domain against the agreed representation-preserving operations.
#include <stdexcept>
#include <initializer_list>
int main() {
    for (unsigned int n = 0; n < 256; ++n) {
        const auto value = static_cast<__UINT8_TYPE__>(n);
        if (@positive@(value) != value || @negative@(value) != static_cast<__UINT8_TYPE__>(0u - n)
            || @nested@(value) != value || @truth@(value) != (n != 0) || @logical@(value) != (n == 0)) return 90;
        for (auto operation : {@pre_inc@, @post_inc@}) {
            auto local = value;
            operation(local);
            if (local != static_cast<__UINT8_TYPE__>(n + 1)) return 91;
        }
        for (auto operation : {@pre_dec@, @post_dec@}) {
            auto local = value;
            operation(local);
            if (local != static_cast<__UINT8_TYPE__>(n - 1)) return 92;
        }
        auto local = value;
        if (n == 255) {
            try { @compound@(local); return 93; }
            catch (const std::out_of_range&) { if (local != value) return 94; }
        } else {
            @compound@(local);
            if (local != n + 1) return 95;
        }
    }
    return 8;
}
