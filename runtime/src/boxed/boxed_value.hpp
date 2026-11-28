#pragma once
#include <cstddef>
#include <cstdint>
#include <memory>
#include <string>
#include <string_view>
#include <variant>
#include <vector>

namespace scpp::detail {
template<class Value> class packed_table;

// Private storage for distinct mixed/dynamic instantiations, not generated APIs.
// The compiler selects operations; this owner handles tags and family ownership.
enum class boxed_family { mixed, dynamic };

template<boxed_family Family> class boxed_value final {
public:
    enum class kind {
#define SCPP_VALUE_KIND(name, category) name,
#include "value_kinds.def"
#undef SCPP_VALUE_KIND
    };
private:
    friend struct boxed_inspection;
    using table = packed_table<boxed_value>;
    struct list_storage { std::shared_ptr<table> entries; };
    using tuple_storage = std::shared_ptr<const std::vector<boxed_value>>;
    using payload = std::variant<std::monostate, bool, std::int64_t, double,
        std::shared_ptr<const std::string>, std::shared_ptr<table>, tuple_storage, list_storage>;
    payload value_;
    std::shared_ptr<table>& writable_table_handle();
    std::int64_t python_position(std::int64_t index) const;
    const table* read_table() const;
    template<class Operation> auto mutate(Operation operation);
public:
    boxed_value() noexcept = default;
    boxed_value(const boxed_value&) noexcept = default;
    boxed_value& operator=(const boxed_value&) noexcept = default;
    boxed_value(boxed_value&& other) noexcept;
    boxed_value& operator=(boxed_value&& other) noexcept;
    ~boxed_value() = default;

    static boxed_value boolean(bool value) noexcept;
    static boxed_value integer(std::int64_t value) noexcept;
    static boxed_value floating(double value) noexcept;
    static boxed_value string(std::string_view value);
    static boxed_value container();
    static boxed_value python_list();
    static boxed_value tuple(std::vector<boxed_value> slots);
    bool python_equal(const boxed_value& other, unsigned depth = 0) const;
    boxed_value python_read(std::int64_t index) const;
    boxed_value python_read(std::string_view key) const;
    void python_set(std::int64_t index, boxed_value value);
    void python_set(std::string_view key, boxed_value value);
    bool python_next(std::int64_t& position, std::int64_t limit, boxed_value& value) const;
    bool python_contains(const boxed_value& needle) const;
    kind type() const noexcept;
    bool get_boolean() const;
    std::int64_t get_integer() const;
    double get_floating() const;
    std::string get_string() const;
    void clean() noexcept;
    void clear();

    boxed_value require(std::int64_t key) const;
    boxed_value require(std::string_view key) const;
    boxed_value read(std::int64_t key) const;
    boxed_value read(std::string_view key) const;
    bool contains(std::int64_t key) const;
    bool contains(std::string_view key) const;
    void set(std::int64_t key, boxed_value value);
    void set(std::string_view key, boxed_value value);
    std::int64_t append(boxed_value value);
    bool remove(std::int64_t key);
    bool remove(std::string_view key);
    bool empty() const;
    std::size_t container_size() const;
    bool is_packed_container() const;
    std::int64_t iteration_limit() const;
    bool iteration_next(std::int64_t& position, std::int64_t limit, boxed_value& value, std::uint32_t* key = nullptr) const;
};
} // namespace scpp::detail
