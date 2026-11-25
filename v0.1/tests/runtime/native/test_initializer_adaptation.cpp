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

    // Keyed adapters transport complete immutable records without a staging hash.
    using public_entry = initializer_entry<scpp::mixed, scpp::mixed>;
    initializer_buffer<public_entry> keyed(2);
    keyed.append({scpp::mixed(int64(4)), scpp::mixed(int64(12))});
    keyed.append({scpp::mixed(int64(8)), scpp::mixed(int64(24))});
    auto mixed_entry = [](const public_entry &entry) {
        return initializer_entry<mixed_t, mixed_t>{
            detail::concrete_access::get<mixed_t>(entry.key),
            detail::concrete_access::get<mixed_t>(entry.value)};
    };
    auto keyed_m = data_initializer<mixed_t>::from_key_value_adapted(keyed.view(), mixed_entry);
    CHECK(keyed_m.table_if()->_find_val(int_t<>(8)).get_int().native_value() == 24);
    initializer_buffer<initializer_entry<scpp::dynamic, scpp::mixed>> dynamic_keyed(1);
    dynamic_keyed.append({scpp::mixed(int64(4)), scpp::dynamic(int64(12))});
    auto dynamic_entry = [](const initializer_entry<scpp::dynamic, scpp::mixed> &entry) {
        return initializer_entry<dynamic, mixed_t>{detail::concrete_access::get<mixed_t>(entry.key),
            detail::concrete_access::get<dynamic>(entry.value)};
    };
    auto keyed_d = data_initializer<dynamic>::from_key_value_adapted(dynamic_keyed.view(), dynamic_entry);
    CHECK(keyed_d.get_table().at(int_t<>(4)).get_int().native_value() == 12);
    initializer_buffer<initializer_entry<int64, int64>> typed_keyed(2);
    typed_keyed.append({int64(4), int64(12)}); typed_keyed.append({int64(8), int64(24)});
    auto typed_entry = [](const initializer_entry<int64, int64> &entry) -> const auto & { return entry; };
    auto keyed_h = data_initializer<hash>::from_key_value_adapted(typed_keyed.view(), typed_entry);
    CHECK(keyed_h.at(int64(8)) == int64(24));
    CHECK(data_initializer<hash>::from_key_value(typed_keyed.view()).at(int64(4)) == int64(12));
    // Duplicate detection observes presence even when the first value is null.
    initializer_buffer<initializer_entry<mixed_t, mixed_t>> duplicate(2);
    duplicate.append({mixed_t(int_t<>(4)), mixed_t{}});
    duplicate.append({mixed_t(int_t<>(4)), mixed_t(int_t<>(9))});
    failed = false;
    try { (void)data_initializer<mixed_t>::from_key_value(duplicate.view()); }
    catch (const std::runtime_error &) { failed = true; }
    CHECK(failed);
    calls = 0; failed = false;
    try {
        (void)data_initializer<hash_t<tracked, int64>>::from_key_value_adapted(typed_keyed.view(),
            [&](const initializer_entry<int64, int64> &entry) {
                if (++calls == 2) throw 1;
                return initializer_entry<tracked, int64>{entry.key, tracked(int(entry.value.native_value()))};
            });
    } catch (int) { failed = true; }
    CHECK(failed && calls == 2 && tracked::live == 0);
    std::puts("PASS: keyed adaptation, duplicate presence and partial-result cleanup");
    std::puts("PASS: common positional adaptation for four factories, ordered ownership and failure cleanup");
}
