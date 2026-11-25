// Native observers cover declared-width shifts and write-back independently of compiler facts.
#include <cstdint>
#include <limits>
#include <stdexcept>
#include <string_view>
#include <initializer_list>
int main() {
    for (unsigned n = 0; n < 256; ++n) {
        auto byte = static_cast<std::uint8_t>(n);
        if (@not_byte@(byte) != static_cast<std::uint8_t>(~n)) return 90;
        for (unsigned m = 0; m < 256; ++m) {
            auto rhs = static_cast<std::uint8_t>(m);
            if (@and_bb@(byte, rhs) != (n & m) || @or_bb@(byte, rhs) != (n | m) || @xor_bb@(byte, rhs) != (n ^ m)) return 91;
        }
        for (std::int64_t rhs : {std::int64_t{-1}, std::int64_t{0}, std::int64_t{255}, std::int64_t{256}, std::numeric_limits<std::int64_t>::min(), std::numeric_limits<std::int64_t>::max()}) {
            if (@and_bi@(byte, rhs) != (std::int64_t(n) & rhs) || @and_ib@(rhs, byte) != (rhs & std::int64_t(n))) return 92;
            if (@or_bi@(byte, rhs) != (std::int64_t(n) | rhs) || @or_ib@(rhs, byte) != (rhs | std::int64_t(n))) return 93;
            if (@xor_bi@(byte, rhs) != (std::int64_t(n) ^ rhs) || @xor_ib@(rhs, byte) != (rhs ^ std::int64_t(n))) return 94;
        }
        for (std::int64_t count = 0; count < 8; ++count) {
            auto bcount = static_cast<std::uint8_t>(count);
            auto expected = static_cast<std::uint8_t>(n << count);
            if (@left_bi@(byte, count) != expected || @left_bb@(byte, bcount) != expected) return 95;
            if (@right_bi@(byte, count) != (n >> count) || @right_bb@(byte, bcount) != (n >> count)) return 96;
            auto written = byte; @left_write_byte@(written, count); if (written != expected) return 97;
            written = byte; @right_write_byte@(written, count); if (written != (n >> count)) return 98;
        }
    }
    for (std::int64_t value : {std::int64_t{0}, std::int64_t{1}, std::int64_t{-3}, std::numeric_limits<std::int64_t>::min(), std::numeric_limits<std::int64_t>::max()}) {
        if (@not_int@(value) != ~value || @and_ii@(value, -1) != value || @or_ii@(value, 0) != value || @xor_ii@(value, value) != 0) return 99;
        auto written = value; @and_write_int@(written, -1); @or_write_int@(written, 0); @xor_write_int@(written, 0); if (written != value) return 100;
        for (std::int64_t count = 0; count < 64; ++count) {
            auto expected = static_cast<std::int64_t>(static_cast<std::uint64_t>(value) << count);
            if (@left_ii@(value, count) != expected || @left_ib@(value, static_cast<std::uint8_t>(count)) != expected) return 101;
            if (@right_ii@(value, count) != (value >> count) || @right_ib@(value, static_cast<std::uint8_t>(count)) != (value >> count)) return 102;
            written = value; @left_write_int@(written, count); if (written != expected) return 103;
            written = value; @right_write_int@(written, count); if (written != (value >> count)) return 104;
        }
    }
    for (auto count : {-1LL, 8LL, 64LL, 256LL, 9223372036854775807LL}) {
        for (auto op : {@left_bi@, @right_bi@}) {
            try { (void)op(1, count); return 105; }
            catch (const std::domain_error& e) { if (std::string_view(e.what()) != "shift_count_out_of_range") return 106; }
        }
        for (auto op : {@left_write_byte@, @right_write_byte@}) {
            std::uint8_t value = 9;
            try { op(value, count); return 107; }
            catch (const std::domain_error&) { if (value != 9) return 108; }
        }
        if (count == 8) continue;
        for (auto op : {@left_ii@, @right_ii@}) {
            try { (void)op(1, count); return 109; }
            catch (const std::domain_error&) {}
        }
    }
    std::uint8_t value = 5;
    @and_write_byte@(value, 3); @or_write_byte@(value, 2); @xor_write_byte@(value, 1);
    if (value != 2) return 110;
    try { @or_write_byte@(value, 256); return 111; }
    catch (const std::out_of_range&) { if (value != 2) return 112; }
    try { @failed_write@(value); return 113; }
    catch (const std::domain_error&) { if (value != 7) return 114; }
    return 8;
}
