#pragma once

#include <algorithm>
#include <cstdint>
#include <string_view>

namespace scpp::detail::utf8 {

// Return the encoded width, or zero for a truncated, non-minimal or non-scalar encoding.
inline unsigned decode(std::string_view bytes, std::uint32_t& scalar) noexcept {
    if (bytes.empty()) return 0;
    const unsigned first = static_cast<unsigned char>(bytes.front());
    if (first < 0x80) {
        scalar = first;
        return 1;
    }
    if (first < 0xc2 || first > 0xf4) return 0;
    const unsigned width = first < 0xe0 ? 2 : first < 0xf0 ? 3 : 4;
    if (bytes.size() < width) return 0;
    scalar = first & (0x7f >> width);
    for (unsigned i = 1; i < width; ++i) {
        const unsigned next = static_cast<unsigned char>(bytes[i]);
        if ((next & 0xc0) != 0x80) return 0;
        scalar = (scalar << 6) | (next & 0x3f);
    }
    if ((width == 3 && scalar < 0x800) || (width == 4 && scalar < 0x10000)
        || (scalar >= 0xd800 && scalar <= 0xdfff) || scalar > 0x10ffff) return 0;
    return width;
}

// Return the width of a valid encoding without materializing the scalar.
inline unsigned validated_width(std::string_view bytes) noexcept {
    if (bytes.empty()) return 0;
    const auto lead = static_cast<unsigned char>(bytes[0]);
    if (lead < 0x80) return 1;
    if (lead < 0xc2 || lead > 0xf4 || bytes.size() < 2) return 0;
    const auto second = static_cast<unsigned char>(bytes[1]);
    if ((second & 0xc0) != 0x80) return 0;
    if (lead < 0xe0) return 2;
    if (bytes.size() < 3 || (static_cast<unsigned char>(bytes[2]) & 0xc0) != 0x80) return 0;
    if (lead < 0xf0) {
        if ((lead == 0xe0 && second < 0xa0) || (lead == 0xed && second >= 0xa0)) return 0;
        return 3;
    }
    if (bytes.size() < 4 || (static_cast<unsigned char>(bytes[3]) & 0xc0) != 0x80) return 0;
    if ((lead == 0xf0 && second < 0x90) || (lead == 0xf4 && second >= 0x90)) return 0;
    return 4;
}

struct sequence {
    std::string_view bytes;
    std::size_t count = 0;
    bool valid = true;

    explicit sequence(std::string_view input) noexcept : bytes(input) {
        while (!input.empty()) {
            std::uint32_t scalar;
            const auto width = decode(input, scalar);
            if (width == 0) {
                valid = false;
                count = bytes.size();
                return;
            }
            input.remove_prefix(width);
            ++count;
        }
    }

    // Positions are bounded by count before advancing. Invalid text uses unsigned byte units.
    void advance(std::string_view& view, std::size_t units) const noexcept {
        if (!valid) {
            view.remove_prefix(units);
            return;
        }
        while (units-- != 0) {
            std::uint32_t scalar;
            view.remove_prefix(decode(view, scalar));
        }
    }

    std::int64_t at(std::int64_t index) const noexcept {
        if (index < 0 || static_cast<std::uint64_t>(index) >= count) return -1;
        auto rest = bytes;
        advance(rest, static_cast<std::size_t>(index));
        if (!valid) return static_cast<unsigned char>(rest.front());
        std::uint32_t scalar = 0;
        decode(rest, scalar);
        return scalar;
    }

    std::string_view slice(std::int64_t start, std::int64_t length) const noexcept {
        if (start < 0 || length <= 0 || static_cast<std::uint64_t>(start) >= count) return bytes.substr(0, 0);
        auto rest = bytes;
        advance(rest, static_cast<std::size_t>(start));
        const auto begin = rest;
        advance(rest, std::min<std::uint64_t>(length, count - static_cast<std::size_t>(start)));
        return begin.substr(0, begin.size() - rest.size());
    }
};

} // namespace scpp::detail::utf8
