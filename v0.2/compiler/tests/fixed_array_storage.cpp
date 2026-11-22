// Backend representation proof for positive extents, independent of compiler emission.
#include <scpp/core/scalars.hpp>
#include <scpp/fixed_array.hpp>
#include <cstddef>
#include <limits>
#include <stdexcept>

using Row = scpp::uint8[5];
using Matrix = Row[3];
struct Packet { Matrix grid; scpp::uint8 tail; };
static_assert(sizeof(Matrix) == 15);
static_assert(alignof(Matrix) == alignof(scpp::uint8));
static_assert(offsetof(Packet, tail) == 15);

int main() {
    Packet packet{};
    packet.grid[scpp::fixed_array_index(2, 3)][scpp::fixed_array_index(4, 5)] = 72;
    Row copy{};
    for (unsigned i = 0; i < 5; ++i) copy[i] = packet.grid[2][i];
    copy[4] = 88;
    Packet next = packet;
    next.grid[2][4] = 12;
    if (copy[4] != 88 || next.grid[2][4] != 12 || packet.grid[2][4] != 72) return 1;
    constexpr scpp::int64 extremes[] = {-1, 1LL << 32,
        std::numeric_limits<scpp::int64>::min(), std::numeric_limits<scpp::int64>::max()};
    for (auto pos : extremes) {
        try { packet.grid[scpp::fixed_array_index(pos, 3)][0] = 0; return 2; } catch (const std::out_of_range&) {}
        try { copy[0] = packet.grid[0][scpp::fixed_array_index(pos, 5)]; return 3; } catch (const std::out_of_range&) {}
    }
    try { (void)scpp::fixed_array_index(3, 3); return 8; } catch (const std::out_of_range&) {}
    try { (void)scpp::fixed_array_index(5, 5); return 9; } catch (const std::out_of_range&) {}
    try { (void)scpp::fixed_array_index(0, 0); return 4; } catch (const std::out_of_range&) {}
    return packet.grid[2][4] == 72 && copy[4] == 88 ? 0 : 5;
}
