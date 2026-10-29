#include "mixed_value.hpp"
#include "packed_table.hpp"
#include <stdexcept>
#include <type_traits>
#include <utility>

namespace scpp::detail {
// The donor's mixed_t::detach_shared_table_for_write supplies the COW rule.
// A staged clone is published only after mutation succeeds. No traversal binding,
// weak/native adapter, cross-family carrier or type-blind operator is imported.
mixed_value::mixed_value(mixed_value&& other) noexcept : value_(std::move(other.value_)) {
    other.clean();
}
mixed_value& mixed_value::operator=(mixed_value&& other) noexcept {
    if (this != &other) { value_ = std::move(other.value_); other.clean(); }
    return *this;
}
mixed_value mixed_value::boolean(bool v) noexcept { mixed_value r; r.value_ = v; return r; }
mixed_value mixed_value::integer(std::int64_t v) noexcept { mixed_value r; r.value_ = v; return r; }
mixed_value mixed_value::floating(double v) noexcept { mixed_value r; r.value_ = v; return r; }
mixed_value mixed_value::string(std::string_view v) {
    mixed_value r; r.value_ = std::make_shared<const std::string>(v); return r;
}
mixed_value mixed_value::container() {
    mixed_value r; r.value_ = std::make_shared<table>(); return r;
}
mixed_value::kind mixed_value::type() const noexcept { return static_cast<kind>(value_.index()); }
bool mixed_value::get_boolean() const {
    if (type() != kind::boolean) throw std::runtime_error("mixed_expected_boolean");
    return std::get<bool>(value_);
}
std::int64_t mixed_value::get_integer() const {
    if (type() != kind::integer) throw std::runtime_error("mixed_expected_integer");
    return std::get<std::int64_t>(value_);
}
double mixed_value::get_floating() const {
    if (type() != kind::floating) throw std::runtime_error("mixed_expected_floating");
    return std::get<double>(value_);
}
std::string mixed_value::get_string() const {
    if (type() != kind::string) throw std::runtime_error("mixed_expected_string");
    return *std::get<std::shared_ptr<const std::string>>(value_);
}
void mixed_value::clean() noexcept { value_ = std::monostate{}; }
const mixed_value::table* mixed_value::read_table() const {
    if (type() == kind::null) return nullptr;
    if (type() != kind::container) throw std::runtime_error("mixed_expected_container");
    return std::get<std::shared_ptr<table>>(value_).get();
}
template<class Operation> auto mixed_value::mutate(Operation operation) {
    using result = std::invoke_result_t<Operation, table&>;
    const auto* current = read_table();
    std::shared_ptr<table> prepared;
    if (!current) prepared = std::make_shared<table>();
    else if (std::get<std::shared_ptr<table>>(value_).use_count() > 1) prepared = std::make_shared<table>(*current);
    table& target = prepared ? *prepared : *std::get<std::shared_ptr<table>>(value_);
    if constexpr (std::is_void_v<result>) {
        operation(target);
        if (prepared) value_ = std::move(prepared);
    } else {
        auto value = operation(target);
        if (prepared) value_ = std::move(prepared);
        return value;
    }
}
mixed_value mixed_value::read(std::int64_t key) const {
    (void)packed_key::integer(key);
    auto* t = read_table();
    auto* value = t ? t->find(key) : nullptr;
    return value ? *value : mixed_value{};
}
mixed_value mixed_value::read(std::string_view key) const {
    auto* t = read_table();
    auto* value = t ? t->find(key) : nullptr;
    return value ? *value : mixed_value{};
}
bool mixed_value::contains(std::int64_t key) const {
    (void)packed_key::integer(key);
    auto* t = read_table(); return t && t->find(key);
}
bool mixed_value::contains(std::string_view key) const {
    auto* t = read_table(); return t && t->find(key);
}
void mixed_value::set(std::int64_t key, mixed_value value) {
    (void)packed_key::integer(key);
    mutate([&](table& t) { t.set(key, std::move(value)); });
}
void mixed_value::set(std::string_view key, mixed_value value) {
    mutate([&](table& t) { t.set(key, std::move(value)); });
}
std::int64_t mixed_value::append(mixed_value value) {
    return mutate([&](table& t) { return t.append(std::move(value)); });
}
bool mixed_value::remove(std::int64_t key) {
    if (!contains(key)) return false; // misses do not detach or bootstrap
    return mutate([&](table& t) { return t.remove(key); });
}
bool mixed_value::remove(std::string_view key) {
    if (!contains(key)) return false;
    return mutate([&](table& t) { return t.remove(key); });
}
std::size_t mixed_value::container_size() const {
    const auto* t = read_table();
    if (!t) throw std::runtime_error("mixed_expected_container");
    return t->size();
}
bool mixed_value::is_packed_container() const {
    const auto* t = read_table();
    if (!t) throw std::runtime_error("mixed_expected_container");
    return t->is_packed();
}
} // namespace scpp::detail
