#pragma once
@includes@
#include <cstdint>
#include <span>
#include <string_view>

namespace scpp {
class Decoder {
public:
    virtual void begin_object() = 0;
    virtual bool next_field() = 0;
    virtual std::string_view field_name() = 0;
    using field_lookup = int (*)(std::string_view);
    static constexpr int end_fields = -2, unknown_field = -1;
    // Providers already checking duplicates can keep their ordinary field traversal.
    virtual int next_record_field(field_lookup lookup, std::span<std::uint64_t>) {
        return next_field() ? lookup(field_name()) : end_fields;
    }
    virtual void begin_array() = 0;
    virtual bool next_element() = 0;
@declarations@    [[noreturn]] virtual void reject(const char* message) = 0;
protected:
    ~Decoder() = default;
};
}
