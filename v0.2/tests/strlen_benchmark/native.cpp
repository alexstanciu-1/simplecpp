#include <string>
#include <cstdint>
#if defined(SCPP_LENGTH) || defined(SCPP_SIZE)
#include <scpp/string.hpp>
#ifdef SCPP_LENGTH
#include "measure.inc"
#endif
using value_type = scpp::string;
#else
using value_type = std::string;
#endif

extern "C" void* make_value(std::size_t bytes) {
    std::string data(bytes, 'x');
#if defined(SCPP_LENGTH) || defined(SCPP_SIZE)
    return new value_type(data.data(), static_cast<scpp::int64>(bytes));
#else
    return new value_type(std::move(data));
#endif
}
extern "C" std::uint64_t length(void* handle) {
    auto& value = *static_cast<value_type*>(handle);
#ifdef SCPP_LENGTH
    return measure_source(value);
#else
    return value.size();
#endif
}
extern "C" void release_value(void* handle) { delete static_cast<value_type*>(handle); }
