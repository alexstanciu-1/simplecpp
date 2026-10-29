#pragma once
#include <cstddef>
#include <cstdint>
#include <memory>
#include <string>
#include <string_view>
#include <variant>

namespace scpp::detail {
template<class Value> class packed_table;

// Private storage for distinct mixed/dynamic instantiations, not generated APIs.
// The compiler selects operations; this owner handles tags and family ownership.
enum class boxed_family { mixed, dynamic };

template<boxed_family Family> class boxed_value final {
public:
    enum class kind { null, boolean, integer, floating, string, container };
private:
    friend struct boxed_inspection;
    using table = packed_table<boxed_value>;
    using payload = std::variant<std::monostate, bool, std::int64_t, double,
        std::shared_ptr<const std::string>, std::shared_ptr<table>>;
    payload value_;
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
    kind type() const noexcept;
    bool get_boolean() const;
    std::int64_t get_integer() const;
    double get_floating() const;
    std::string get_string() const;
    void clean() noexcept;

    boxed_value read(std::int64_t key) const;
    boxed_value read(std::string_view key) const;
    bool contains(std::int64_t key) const;
    bool contains(std::string_view key) const;
    void set(std::int64_t key, boxed_value value);
    void set(std::string_view key, boxed_value value);
    std::int64_t append(boxed_value value);
    bool remove(std::int64_t key);
    bool remove(std::string_view key);
    std::size_t container_size() const;
    bool is_packed_container() const;
};
} // namespace scpp::detail
