// Native measurement only. Private boxed layout is measured directly; no generated API dependency.
#include <climits>
#include <cstdint>
#include <iostream>
#include <limits>
#include <memory>
#include "../../runtime/src/boxed/boxed_value.hpp"
#ifndef SCPP_PROBE_CONTEXT_ID
#define SCPP_PROBE_CONTEXT_ID "unbound"
#endif

template<class T> void integer(const char* name, bool comma) {
    if (comma) std::cout << ',';
    std::cout << '"' << name << "\":{\"signed\":" << (std::numeric_limits<T>::is_signed ? "true" : "false")
              << ",\"digits\":" << std::numeric_limits<T>::digits
              << ",\"size\":" << sizeof(T) << ",\"alignment\":" << alignof(T) << '}';
}
template<class T> void layout(const char* name, bool comma) {
    if (comma) std::cout << ',';
    std::cout << '"' << name << "\":{\"size\":" << sizeof(T) << ",\"alignment\":" << alignof(T) << '}';
}
int main() {
    std::cout << "{\"schema\":1,\"context_id\":\"" << SCPP_PROBE_CONTEXT_ID
              << "\",\"char_bits\":" << CHAR_BIT << ",\"pointer_bytes\":" << sizeof(void*)
              << ",\"standard_library\":\"";
#ifdef _LIBCPP_VERSION
    std::cout << "libc++:" << _LIBCPP_VERSION;
#elif defined(__GLIBCXX__)
    std::cout << "libstdc++:" << __GLIBCXX__;
#else
    std::cout << "unknown";
#endif
    std::cout << "\",\"native_integers\":{";
    integer<int>("int", false); integer<unsigned int>("unsigned int", true);
    integer<long>("long", true); integer<unsigned long>("unsigned long", true);
    integer<long long>("long long", true); integer<unsigned long long>("unsigned long long", true);
    std::cout << "},\"fixed_integers\":{";
    integer<__INT8_TYPE__>("int8", false); integer<__UINT8_TYPE__>("uint8", true);
    integer<__INT16_TYPE__>("int16", true); integer<__UINT16_TYPE__>("uint16", true);
    integer<__INT32_TYPE__>("int32", true); integer<__UINT32_TYPE__>("uint32", true);
    integer<__INT64_TYPE__>("int64", true); integer<__UINT64_TYPE__>("uint64", true);
    std::cout << "},\"double\":{\"iec559\":" << (std::numeric_limits<double>::is_iec559 ? "true" : "false")
              << ",\"radix\":" << std::numeric_limits<double>::radix
              << ",\"digits\":" << std::numeric_limits<double>::digits
              << ",\"max_exponent\":" << std::numeric_limits<double>::max_exponent
              << ",\"size\":" << sizeof(double) << ",\"alignment\":" << alignof(double)
              << "},\"has_int128\":";
#ifdef __SIZEOF_INT128__
    std::cout << "true";
#else
    std::cout << "false";
#endif
    std::cout << ",\"ownership\":{";
    layout<std::shared_ptr<void>>("shared", false);
    layout<std::weak_ptr<void>>("weak", true);
    layout<std::unique_ptr<unsigned char>>("unique", true);
    std::cout << "},\"boxed\":{";
    layout<scpp::detail::boxed_value<scpp::detail::boxed_family::mixed>>("mixed", false);
    layout<scpp::detail::boxed_value<scpp::detail::boxed_family::dynamic>>("dynamic", true);
    std::cout << "}}\n";
    return std::cout ? 0 : 1;
}
