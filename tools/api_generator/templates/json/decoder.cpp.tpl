#pragma once
@includes@
#include <string_view>

namespace scpp {
class Decoder {
public:
    virtual void begin_object() = 0;
    virtual bool next_field() = 0;
    virtual std::string_view field_name() = 0;
    virtual void begin_array() = 0;
    virtual bool next_element() = 0;
@declarations@    [[noreturn]] virtual void reject(const char* message) = 0;
protected:
    ~Decoder() = default;
};
}
