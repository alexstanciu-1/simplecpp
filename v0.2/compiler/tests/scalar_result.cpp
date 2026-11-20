// Native bootstrap proof. The selected conversion supplies the payload type at both ends.
#include <bit>
#include <cstdint>
#include <limits>
#include <type_traits>

union scalar_payload {
    std::int64_t integer;
    double floating;
    bool boolean;
};

struct scalar_result {
    scalar_payload payload;
    bool known;
};

static_assert(std::is_trivially_copyable_v<scalar_result>);
static_assert(sizeof(scalar_result) <= 2 * sizeof(std::int64_t));

[[gnu::noinline]] static scalar_result identity(scalar_result source) {
    return source;
}

[[gnu::noinline]] static scalar_result as_boolean(scalar_result integer) {
    if (!integer.known) return {};
    return {{.boolean = integer.payload.integer != 0}, true};
}

int main() {
    const auto null_value = identity({{}, true}); // Known null has no payload to read.
    const auto unavailable = identity({});
    const auto maximum = identity({{.integer = std::numeric_limits<std::int64_t>::max()}, true});
    const auto negative_zero = identity({{.floating = -0.0}, true});
    const auto zero = identity({{.integer = 0}, true});
    const auto falsity = as_boolean(zero);
    const auto truth = as_boolean(maximum);
    return !(null_value.known && !unavailable.known && !as_boolean(unavailable).known
        && maximum.known && maximum.payload.integer == std::numeric_limits<std::int64_t>::max()
        && negative_zero.known && std::bit_cast<std::uint64_t>(negative_zero.payload.floating) == (UINT64_C(1) << 63)
        && zero.known && zero.payload.integer == 0 && falsity.known && !falsity.payload.boolean
        && truth.known && truth.payload.boolean);
}
