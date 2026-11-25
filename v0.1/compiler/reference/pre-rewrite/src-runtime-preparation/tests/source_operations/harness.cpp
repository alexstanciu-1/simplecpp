#include "native.hpp"
#include <array>
#include <cstdio>
#include <cstdlib>
#include <unordered_set>

extern "C" void compiler_entry(std::int32_t revision);

namespace {
std::unordered_set<const void*> live_fields;
std::array<unsigned, 6> counts{};
bool active = false;
int current_operation = -1;
unsigned field_index = 0;
void *current_source = nullptr;

void require(bool condition) {
    if (!condition) {
        std::fputs("lifecycle invariant failed\n", stderr);
        std::exit(1);
    }
}
}

extern "C" void check_equal(std::int32_t actual, std::int32_t expected) noexcept {
    require(actual == expected);
}

// Every field transition must occur within exactly one compiler-owned operation.
// No duplicate construction, destruction, or native field composition is allowed.
extern "C" void observe_field(std::int32_t operation, void *self, const void *source) noexcept {
    using source_operations_probe::source_layout;
    require(active && current_operation == operation && field_index < 2);
    const std::size_t offsets[] = {offsetof(source_layout, first), offsetof(source_layout, second)};
    unsigned index = operation == 5 ? 1 - field_index : field_index;
    require(self == static_cast<std::byte*>(current_source) + offsets[index]);
    if (operation != 0 && operation != 5) require(live_fields.contains(source));
    if (operation < 3) require(live_fields.insert(self).second);
    else require(live_fields.contains(self));
    if (operation == 5) require(live_fields.erase(self) == 1);
    ++field_index;
}

extern "C" void observe_source(std::int32_t operation, std::int32_t phase, void *self) noexcept {
    require(operation >= 0 && operation < 6);
    if (phase == 0) {
        require(!active);
        require(reinterpret_cast<std::uintptr_t>(self) % alignof(source_operations_probe::source_layout) == 0);
        active = true;
        current_operation = operation;
        current_source = self;
        field_index = 0;
        ++counts[operation];
    } else {
        require(active && current_operation == operation && current_source == self && field_index == 2);
        active = false;
    }
}

int main(int argc, char **argv) {
    require(argc == 2);
    compiler_entry(std::atoi(argv[1]));
    require(!active && live_fields.empty());
    for (unsigned count : counts) require(count != 0);
    require(counts[0] + counts[1] + counts[2] == counts[5]);
    std::printf("{\"construct\":%u,\"copy\":%u,\"move\":%u,\"assign\":%u,\"move_assign\":%u,\"destroy\":%u}\n",
        counts[0], counts[1], counts[2], counts[3], counts[4], counts[5]);
}
