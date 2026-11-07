#include <cstdint>
#include <charconv>
#include <string>
#ifdef SCPP_TEXT
#include "measure.inc"
#endif

extern "C" std::uint64_t consume(const char*, std::size_t);

#ifdef SCPP_TEXT
static std::uint64_t observe(scpp::string value) {
    return consume(value.data(), value.size());
}
#else
template<class T> static std::uint64_t number(T value) {
    char buf[128];
    const auto end = std::to_chars(buf, buf + sizeof(buf), value).ptr;
    std::string text(buf, end);
    return consume(text.data(), text.size());
}
#endif

extern "C" std::uint64_t format_sample(unsigned kind, unsigned index) {
    switch (kind) {
    case 0: {
        bool value = index % 2 != 0;
#ifdef SCPP_TEXT
        return observe(render_bool(value));
#else
        std::string text = value ? "true" : "false";
        return consume(text.data(), text.size());
#endif
    }
    case 1: {
        std::uint8_t value = static_cast<std::uint8_t>(index);
#ifdef SCPP_TEXT
        return observe(render_byte(value));
#else
        return number(value);
#endif
    }
    case 2: {
#ifdef SCPP_TEXT
        scpp::int64 value = static_cast<scpp::int64>(index) * 97003 - 50000000;
        return observe(render_int(value));
#else
        return number(static_cast<std::int64_t>(index) * 97003 - 50000000);
#endif
    }
    default: {
        double value = (index % 10000) / 16.0 + 0.03125;
#ifdef SCPP_TEXT
        return observe(render_float(value));
#else
        return number(value);
#endif
    }
    }
}
