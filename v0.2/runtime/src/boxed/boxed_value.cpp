#include "boxed_value.hpp"
#include "packed_table.hpp"
#include <stdexcept>
#include <type_traits>
#include <utility>

namespace scpp::detail {
// The donor's mixed_t::detach_shared_table_for_write supplies the COW rule.
// Dynamic retains its shared table identity; mixed publishes a staged clone only
// after mutation succeeds. No traversal binding,
// weak/native adapter, cross-family carrier or type-blind operator is imported.
template<boxed_family Family>
boxed_value<Family>::boxed_value(boxed_value<Family>&& other) noexcept : value_(std::move(other.value_)) {
    other.clean();
}
template<boxed_family Family>
boxed_value<Family>& boxed_value<Family>::operator=(boxed_value<Family>&& other) noexcept {
    if (this != &other) { value_ = std::move(other.value_); other.clean(); }
    return *this;
}
template<boxed_family Family>
boxed_value<Family> boxed_value<Family>::boolean(bool v) noexcept { boxed_value<Family> r; r.value_ = v; return r; }
template<boxed_family Family>
boxed_value<Family> boxed_value<Family>::integer(std::int64_t v) noexcept { boxed_value<Family> r; r.value_ = v; return r; }
template<boxed_family Family>
boxed_value<Family> boxed_value<Family>::floating(double v) noexcept { boxed_value<Family> r; r.value_ = v; return r; }
template<boxed_family Family>
boxed_value<Family> boxed_value<Family>::string(std::string_view v) {
    boxed_value<Family> r; r.value_ = std::make_shared<const std::string>(v); return r;
}
template<boxed_family Family>
boxed_value<Family> boxed_value<Family>::container() {
    boxed_value<Family> r; r.value_ = std::make_shared<table>(); return r;
}
template<boxed_family Family>
typename boxed_value<Family>::kind boxed_value<Family>::type() const noexcept { return static_cast<kind>(value_.index()); }
template<boxed_family Family>
bool boxed_value<Family>::get_boolean() const {
    if (type() != kind::boolean) throw std::runtime_error("boxed_expected_boolean");
    return std::get<bool>(value_);
}
template<boxed_family Family>
std::int64_t boxed_value<Family>::get_integer() const {
    if (type() != kind::integer) throw std::runtime_error("boxed_expected_integer");
    return std::get<std::int64_t>(value_);
}
template<boxed_family Family>
double boxed_value<Family>::get_floating() const {
    if (type() != kind::floating) throw std::runtime_error("boxed_expected_floating");
    return std::get<double>(value_);
}
template<boxed_family Family>
std::string boxed_value<Family>::get_string() const {
    if (type() != kind::string) throw std::runtime_error("boxed_expected_string");
    return *std::get<std::shared_ptr<const std::string>>(value_);
}
template<boxed_family Family>
void boxed_value<Family>::clean() noexcept { value_ = std::monostate{}; }
template<boxed_family Family>
const typename boxed_value<Family>::table* boxed_value<Family>::read_table() const {
    if (type() == kind::null) return nullptr;
    if (type() != kind::container) throw std::runtime_error("boxed_expected_container");
    return std::get<std::shared_ptr<table>>(value_).get();
}
template<boxed_family Family>
template<class Operation> auto boxed_value<Family>::mutate(Operation operation) {
    using result = std::invoke_result_t<Operation, table&>;
    const auto* current = read_table();
    std::shared_ptr<table> prepared;
    if constexpr (Family == boxed_family::mixed) {
        if (!current) prepared = std::make_shared<table>();
        else if (std::get<std::shared_ptr<table>>(value_).use_count() > 1)
            prepared = std::make_shared<table>(*current);
    } else {
        if (!current) throw std::runtime_error("dynamic_requires_container");
        // Dynamic mutation retains the same table even when other bindings share it.
    }
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
template<boxed_family Family>
boxed_value<Family> boxed_value<Family>::read(std::int64_t key) const {
    (void)packed_key::integer(key);
    auto* t = read_table();
    auto* value = t ? t->find(key) : nullptr;
    return value ? *value : boxed_value<Family>{};
}
template<boxed_family Family>
boxed_value<Family> boxed_value<Family>::read(std::string_view key) const {
    auto* t = read_table();
    auto* value = t ? t->find(key) : nullptr;
    return value ? *value : boxed_value<Family>{};
}
template<boxed_family Family>
bool boxed_value<Family>::contains(std::int64_t key) const {
    (void)packed_key::integer(key);
    auto* t = read_table(); return t && t->find(key);
}
template<boxed_family Family>
bool boxed_value<Family>::contains(std::string_view key) const {
    auto* t = read_table(); return t && t->find(key);
}
template<boxed_family Family>
void boxed_value<Family>::set(std::int64_t key, boxed_value<Family> value) {
    (void)packed_key::integer(key);
    mutate([&](table& t) { t.set(key, std::move(value)); });
}
template<boxed_family Family>
void boxed_value<Family>::set(std::string_view key, boxed_value<Family> value) {
    mutate([&](table& t) { t.set(key, std::move(value)); });
}
template<boxed_family Family>
std::int64_t boxed_value<Family>::append(boxed_value<Family> value) {
    return mutate([&](table& t) { return t.append(std::move(value)); });
}
template<boxed_family Family>
bool boxed_value<Family>::remove(std::int64_t key) {
    if (!contains(key)) return false; // misses do not detach or bootstrap
    return mutate([&](table& t) { return t.remove(key); });
}
template<boxed_family Family>
bool boxed_value<Family>::remove(std::string_view key) {
    if (!contains(key)) return false;
    return mutate([&](table& t) { return t.remove(key); });
}
template<boxed_family Family>
std::size_t boxed_value<Family>::container_size() const {
    const auto* t = read_table();
    if (!t) throw std::runtime_error("boxed_expected_container");
    return t->size();
}
template<boxed_family Family>
bool boxed_value<Family>::is_packed_container() const {
    const auto* t = read_table();
    if (!t) throw std::runtime_error("boxed_expected_container");
    return t->is_packed();
}
template class boxed_value<boxed_family::mixed>;
template class boxed_value<boxed_family::dynamic>;
} // namespace scpp::detail
