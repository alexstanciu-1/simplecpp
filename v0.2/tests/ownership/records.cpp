#include "scpp/ownership_records.hpp"
#include <atomic>

namespace ownership_test {
std::atomic<int> live{0}, destroyed{0}, copied{0};
}
namespace scpp {
record_probe::record_probe(int64 initial) : value(initial) {
    if (initial.native_value() < 0) throw record_construction_failure{};
    ++ownership_test::live;
}
record_probe::record_probe(const record_probe &other) : value(other.value) {
    ++ownership_test::copied; ++ownership_test::live;
}
record_probe::~record_probe() noexcept { --ownership_test::live; ++ownership_test::destroyed; }
record_noncopyable::record_noncopyable(int64 initial) : value(initial) {
    if (initial.native_value() < 0) throw record_construction_failure{};
    ++ownership_test::live;
}
record_noncopyable::~record_noncopyable() noexcept { --ownership_test::live; ++ownership_test::destroyed; }
record_throwing_move::record_throwing_move(record_throwing_move &&other) : value(other.value) {
    if (value.native_value() < 0) throw record_move_failure{};
    other.value = int64{0};
}
record_throwing_move &record_throwing_move::operator=(record_throwing_move &&other) {
    if (other.value.native_value() < 0) throw record_move_failure{};
    if (this != &other) { value = other.value; other.value = int64{0}; }
    return *this;
}
}
