#include "scpp/data_initializer_typed.hpp"
#include "scpp/data_initializer_mixed.hpp"
#include "scpp/data_initializer_dynamic.hpp"
#include "scpp/integers.hpp"
#include "scpp/detail/concrete_keys.hpp"
#include "scpp/detail/string_access.hpp"
#include "scpp/detail/concrete_access.hpp"
#include "scpp/mixed.hpp"
#include "scpp/dynamic.hpp"
#include <cstdio>
#include <cstdlib>
#include <new>

static bool counting = false;
static unsigned allocations = 0;
void *operator new(std::size_t size)
{
    if (counting) ++allocations;
    if (void *memory = std::malloc(size ? size : 1)) return memory;
    throw std::bad_alloc();
}
void operator delete(void *memory) noexcept { std::free(memory); }
void operator delete(void *memory, std::size_t) noexcept { std::free(memory); }
#define CHECK(x) do { if (!(x)) { std::fprintf(stderr, "initializer adaptation failure %d\n", __LINE__); std::abort(); } } while (false)

struct tracked final {
    static inline int live = 0;
    int value = 0;
    explicit tracked(int v = 0) : value(v) { ++live; }
    tracked(const tracked &v) : value(v.value) { ++live; }
    tracked &operator=(const tracked &) = default;
    ~tracked() { --live; }
};

int main()
{
    using namespace scpp;
    using vector = vector_t<int64>;
    using hash = hash_t<int64, int64>;
    using dynamic = detail::dynamic_value;
    initializer_buffer<int64> input(3);
    input.append(int64(3)); input.append(int64(7)); input.append(int64(11));
    int calls = 0;
    auto identity = [&](const int64 &value) -> const int64 & { ++calls; return value; };
    auto v = data_initializer<vector>::from_list_adapted(input.view(), identity);
    CHECK(calls == 3 && v.at(std::size_t(0)) == int64(3) && v.at(std::size_t(2)) == int64(11));
    calls = 0;
    auto h = data_initializer<hash>::from_list_adapted(input.view(), identity);
    CHECK(calls == 3 && h.at(int64(1)) == int64(7));
    calls = 0;
    auto m = data_initializer<mixed_t>::from_list_adapted(input.view(), [&](const int64 &value) {
        ++calls; return mixed_t(int_t<>(value.native_value()));
    });
    CHECK(calls == 3 && m.table_if()->_find_val(int_t<>(2)).get_int().native_value() == 11);
    calls = 0;
    auto d = data_initializer<dynamic>::from_list_adapted(input.view(), [&](const int64 &value) {
        ++calls; return dynamic(int_t<>(value.native_value()));
    });
    CHECK(calls == 3 && d.get_table().at(int_t<>(1)).get_int().native_value() == 7);
    auto old = data_initializer<vector>::from_list(input.view());
    CHECK(old.at(std::size_t(1)) == v.at(std::size_t(1)));

    initializer_buffer<int64> empty(0);
    CHECK(data_initializer<vector>::from_list_adapted(empty.view(), identity).empty().native_value());
    CHECK(data_initializer<hash>::from_list_adapted(empty.view(), identity).empty().native_value());
    auto empty_m = data_initializer<mixed_t>::from_list_adapted(empty.view(), [](const int64 &value) { return mixed_t(int_t<>(value.native_value())); });
    auto empty_d = data_initializer<dynamic>::from_list_adapted(empty.view(), [](const int64 &value) { return dynamic(int_t<>(value.native_value())); });
    CHECK(empty_m.is_hash().native_value() && empty_m.table_if()->empty().native_value());
    CHECK(empty_d.table_if() && empty_d.get_table().empty().native_value());

    // Adapter references become owned values before append; no extra public copy.
    const string text("A sufficiently long public input string to detect duplicate boundary copies");
    initializer_buffer<string> public_input(1); public_input.append(text);
    initializer_buffer<string_t> native_input(1); native_input.append(detail::string_access::get(text));
    counting = true; allocations = 0;
    auto native_result = data_initializer<vector_t<string_t>>::from_list(native_input.view());
    counting = false; const unsigned native_allocations = allocations;
    counting = true; allocations = 0;
    auto adapted_result = data_initializer<vector_t<string_t>>::from_list_adapted(public_input.view(),
        [](const string &value) -> const string_t & { return detail::string_access::get(value); });
    counting = false;
    CHECK(allocations == native_allocations);
    CHECK(adapted_result.at(int_t<>(0)).native_value() == native_result.at(int_t<>(0)).native_value());
    std::printf("initializer string allocations native=%u adapted=%u\n", native_allocations, allocations);

    // Real boxed boundary adaptation only unwraps representation; no source cast.
    initializer_buffer<scpp::mixed> public_mixed(1);
    initializer_buffer<scpp::dynamic> public_dynamic(1);
    public_mixed.append(scpp::mixed(text)); public_dynamic.append(scpp::dynamic(text));
    auto boxed_m = data_initializer<mixed_t>::from_list_adapted(public_mixed.view(),
        [](const scpp::mixed &value) -> const mixed_t & { return detail::concrete_access::get<mixed_t>(value); });
    auto boxed_d = data_initializer<dynamic>::from_list_adapted(public_dynamic.view(),
        [](const scpp::dynamic &value) -> const dynamic & { return detail::concrete_access::get<dynamic>(value); });
    CHECK(boxed_m.table_if()->_find_val(int_t<>(0)).get_string().native_value() == detail::string_access::get(text).native_value());
    CHECK(boxed_d.get_table().at(int_t<>(0)).get_string().native_value() == detail::string_access::get(text).native_value());

    bool failed = false;
    calls = 0;
    try {
        (void)data_initializer<vector_t<tracked>>::from_list_adapted(input.view(), [&](const int64 &value) {
            ++calls; if (value == int64(7)) throw 1; return tracked(int(value.native_value()));
        });
    } catch (int) { failed = true; }
    CHECK(failed && calls == 2 && tracked::live == 0);
    failed = false;
    try {
        (void)data_initializer<hash_t<tracked, int64>>::from_list_adapted(input.view(), [](const int64 &value) {
            if (value == int64(7)) throw 1; return tracked(int(value.native_value()));
        });
    } catch (int) { failed = true; }
    CHECK(failed && tracked::live == 0);
    failed = false;
    try {
        (void)data_initializer<mixed_t>::from_list_adapted(input.view(), [](const int64 &value) {
            if (value == int64(7)) throw 1; return mixed_t(int_t<>(value.native_value()));
        });
    } catch (int) { failed = true; }
    CHECK(failed);
    failed = false;
    try {
        (void)data_initializer<dynamic>::from_list_adapted(input.view(), [](const int64 &value) {
            if (value == int64(7)) throw 1; return dynamic(int_t<>(value.native_value()));
        });
    } catch (int) { failed = true; }
    CHECK(failed && input.view().at(int_t<>(1)) == int64(7));
    std::puts("PASS: common positional adaptation for four factories, ordered ownership and failure cleanup");
}
