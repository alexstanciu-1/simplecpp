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
boxed_value<Family> boxed_value<Family>::python_list() {
    boxed_value r;
    r.value_ = list_storage{std::make_shared<table>()};
    return r;
}
template<boxed_family Family>
boxed_value<Family> boxed_value<Family>::tuple(std::vector<boxed_value> slots) {
    boxed_value result;
    result.value_ = std::make_shared<const std::vector<boxed_value>>(std::move(slots));
    return result;
}
template<boxed_family Family>
std::shared_ptr<typename boxed_value<Family>::table>& boxed_value<Family>::writable_table_handle() {
    if (auto* list = std::get_if<list_storage>(&value_)) return list->entries;
    return std::get<std::shared_ptr<table>>(value_);
}
template<boxed_family Family>
typename boxed_value<Family>::kind boxed_value<Family>::type() const noexcept { return std::holds_alternative<list_storage>(value_) ? kind::container : static_cast<kind>(value_.index()); }
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
void boxed_value<Family>::clear() {
    if (type() == kind::tuple) throw std::runtime_error("TypeError");
    if (type() != kind::container) throw std::runtime_error("boxed_expected_container");
    auto& storage = writable_table_handle();
    if constexpr (Family == boxed_family::mixed) {
        // Discarding a shared value needs a new empty table, not a clone of every old element.
        if (storage.use_count() > 1) { storage = std::make_shared<table>(); return; }
    }
    storage->clear();
}
template<boxed_family Family>
const typename boxed_value<Family>::table* boxed_value<Family>::read_table() const {
    if (type() == kind::tuple) throw std::runtime_error("TypeError");
    if (type() == kind::null) return nullptr;
    if (type() != kind::container) throw std::runtime_error("boxed_expected_container");
    if (const auto* list = std::get_if<list_storage>(&value_)) return list->entries.get();
    return std::get<std::shared_ptr<table>>(value_).get();
}
template<boxed_family Family>
template<class Operation> auto boxed_value<Family>::mutate(Operation operation) {
    using result = std::invoke_result_t<Operation, table&>;
    const auto* current = read_table();
    std::shared_ptr<table> prepared;
    if constexpr (Family == boxed_family::mixed) {
        if (!current) prepared = std::make_shared<table>();
        else if (writable_table_handle().use_count() > 1)
            prepared = std::make_shared<table>(*current);
    } else {
        if (!current) throw std::runtime_error("dynamic_requires_container");
        // Dynamic mutation retains the same table even when other bindings share it.
    }
    table& target = prepared ? *prepared : *writable_table_handle();
    auto publish = [&] {
        if (prepared) {
            if (current) writable_table_handle() = std::move(prepared);
            else value_ = std::move(prepared);
        }
    };
    if constexpr (std::is_void_v<result>) {
        operation(target);
        publish();
    } else {
        auto value = operation(target);
        publish();
        return value;
    }
}
template<boxed_family Family>
boxed_value<Family> boxed_value<Family>::require(std::int64_t key) const {
    if (const auto* slots = std::get_if<tuple_storage>(&value_)) {
        return (**slots)[static_cast<std::size_t>(python_position(key))];
    }
    (void)packed_key::integer(key);
    auto* t = read_table();
    auto* value = t ? t->find(key) : nullptr;
    if (!value) throw std::out_of_range("boxed_missing_key");
    return *value;
}
template<boxed_family Family>
boxed_value<Family> boxed_value<Family>::require(std::string_view key) const {
    auto* t = read_table();
    auto* value = t ? t->find(key) : nullptr;
    if (!value) throw std::out_of_range("boxed_missing_key");
    return *value;
}
template<boxed_family Family>
boxed_value<Family> boxed_value<Family>::read(std::int64_t key) const {
    if (type() == kind::tuple) return require(key);
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
    if (const auto* slots = std::get_if<tuple_storage>(&value_)) return (*slots)->size();
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
template<boxed_family Family>
std::int64_t boxed_value<Family>::iteration_limit() const {
    if (const auto* slots = std::get_if<tuple_storage>(&value_)) return (*slots)->size();
    const auto* t = read_table();
    if (!t) throw std::runtime_error("boxed_expected_container");
    return static_cast<std::int64_t>(t->physical_size());
}
template<boxed_family Family>
bool boxed_value<Family>::iteration_next(std::int64_t& position, std::int64_t limit, boxed_value& value, std::uint32_t* key) const {
    if (const auto* slots = std::get_if<tuple_storage>(&value_)) {
        if (position < 0 || position >= limit || static_cast<std::size_t>(position) >= (*slots)->size()) return false;
        if (key) *key = packed_key::integer(position);
        value = (**slots)[static_cast<std::size_t>(position++)];
        return true;
    }
    const auto* t = read_table();
    if (!t) throw std::runtime_error("boxed_expected_container");
    return t->iteration_next(position, limit, value, key);
}
template<boxed_family Family>
std::int64_t boxed_value<Family>::python_position(std::int64_t index) const {
    const auto size = static_cast<std::int64_t>(container_size());
    if (index < 0) index += size;
    if (index < 0 || index >= size) throw std::out_of_range("IndexError");
    return index;
}
template<boxed_family Family>
boxed_value<Family> boxed_value<Family>::python_read(std::int64_t index) const {
    if (type() == kind::tuple) return require(index);
    if (std::holds_alternative<list_storage>(value_)) return require(python_position(index));
    if (type() != kind::container) throw std::runtime_error("TypeError");
    const auto* entry = index >= 0 && index <= packed_key::max_integer ? read_table()->find(index) : nullptr;
    if (!entry) throw std::out_of_range("KeyError");
    return *entry;
}
template<boxed_family Family>
boxed_value<Family> boxed_value<Family>::python_read(std::string_view key) const {
    if (!std::holds_alternative<std::shared_ptr<table>>(value_)) throw std::runtime_error("TypeError");
    const auto* entry = read_table()->find(key);
    if (!entry) throw std::out_of_range("KeyError");
    return *entry;
}
template<boxed_family Family>
void boxed_value<Family>::python_set(std::int64_t key, boxed_value value) {
    if (type() != kind::container) throw std::runtime_error("TypeError");
    if (std::holds_alternative<list_storage>(value_)) key = python_position(key);
    set(key, std::move(value));
}
template<boxed_family Family>
void boxed_value<Family>::python_set(std::string_view key, boxed_value value) {
    if (!std::holds_alternative<std::shared_ptr<table>>(value_)) throw std::runtime_error("TypeError");
    set(key, std::move(value));
}
template<boxed_family Family>
bool boxed_value<Family>::python_next(std::int64_t& position, std::int64_t limit, boxed_value& value) const {
    if (!std::holds_alternative<std::shared_ptr<table>>(value_)) return iteration_next(position, limit, value);
    std::uint32_t key;
    if (!iteration_next(position, limit, value, &key)) return false;
    value = packed_key::is_string(key) ? string(packed_string_pool::instance().resolve(key)) : integer(key);
    return true;
}
namespace {
bool python_integer_equal(std::int64_t integer, double number) {
    return number >= -0x1p63 && number < 0x1p63
        && static_cast<std::int64_t>(number) == integer && static_cast<double>(integer) == number;
}
}

template<boxed_family Family>
bool boxed_value<Family>::python_equal(const boxed_value& other, unsigned depth) const {
    const auto a = type(), b = other.type();
    if (a == kind::integer || a == kind::boolean) {
        const auto integer = a == kind::integer ? get_integer() : std::int64_t(get_boolean());
        if (b == kind::floating) return python_integer_equal(integer, other.get_floating());
        if (b == kind::boolean) return integer == other.get_boolean();
        return b == kind::integer && integer == other.get_integer();
    }
    if (a == kind::floating) {
        if (b == kind::integer || b == kind::boolean) return other.python_equal(*this, depth);
        return b == kind::floating && get_floating() == other.get_floating();
    }
    if (a != b) return false;
    if (a == kind::null) return true;
    if (a == kind::string) return *std::get<std::shared_ptr<const std::string>>(value_)
                               == *std::get<std::shared_ptr<const std::string>>(other.value_);
    if (a == kind::tuple) {
        const auto& lhs = std::get<tuple_storage>(value_);
        const auto& rhs = std::get<tuple_storage>(other.value_);
        if (lhs == rhs) return true;
        if (lhs->size() != rhs->size()) return false;
        if (depth >= 256) throw std::runtime_error("Python recursive container comparison");
        for (std::size_t i = 0; i < lhs->size(); ++i)
            if (!(*lhs)[i].python_equal((*rhs)[i], depth + 1)) return false;
        return true;
    }
    const auto* lhs = read_table();
    const auto* rhs = other.read_table();
    const bool list = std::holds_alternative<list_storage>(value_);
    if (list != std::holds_alternative<list_storage>(other.value_) || lhs->size() != rhs->size()) return false;
    if (lhs == rhs) return true;
    if (depth >= 256) throw std::runtime_error("Python recursive container comparison");
    std::int64_t pos = 0, peer_pos = 0;
    boxed_value item, peer;
    std::uint32_t key;
    while (iteration_next(pos, iteration_limit(), item, &key)) {
        if (list) {
            if (!other.iteration_next(peer_pos, other.iteration_limit(), peer) || !item.python_equal(peer, depth + 1)) return false;
        } else {
            const auto* value = packed_key::is_string(key) ? rhs->find(packed_string_pool::instance().resolve(key))
                                                            : rhs->find(static_cast<std::int64_t>(key));
            if (!value || !item.python_equal(*value, depth + 1)) return false;
        }
    }
    return true;
}

template<boxed_family Family>
bool boxed_value<Family>::python_contains(const boxed_value& needle) const {
    if (type() == kind::string && needle.type() == kind::string) {
        const auto& text = *std::get<std::shared_ptr<const std::string>>(value_);
        return text.find(*std::get<std::shared_ptr<const std::string>>(needle.value_)) != std::string::npos;
    }
    if (const auto* slots = std::get_if<tuple_storage>(&value_)) {
        for (const auto& slot : **slots) if (needle.python_equal(slot)) return true;
        return false;
    }
    if (type() != kind::container) throw std::runtime_error("TypeError");
    if (std::holds_alternative<list_storage>(value_)) {
        boxed_value item;
        std::int64_t position = 0;
        const auto limit = iteration_limit();
        while (iteration_next(position, limit, item)) if (needle.python_equal(item, 0)) return true;
        return false;
    }
    switch (needle.type()) {
        case kind::string: return contains(*std::get<std::shared_ptr<const std::string>>(needle.value_));
        case kind::boolean: return contains(std::int64_t(needle.get_boolean()));
        case kind::integer: {
            const auto key = needle.get_integer();
            return key >= 0 && key <= packed_key::max_integer && contains(key);
        }
        case kind::floating: {
            const auto number = needle.get_floating();
            if (!(number >= 0 && number <= packed_key::max_integer)) return false;
            const auto key = static_cast<std::int64_t>(number);
            return static_cast<double>(key) == number && contains(key);
        }
        case kind::null: return false;
        case kind::tuple:
        case kind::container: throw std::runtime_error("TypeError");
    }
    throw std::logic_error("invalid boxed kind");
}
template class boxed_value<boxed_family::mixed>;
template class boxed_value<boxed_family::dynamic>;
} // namespace scpp::detail
