#pragma once
#include <cstddef>
#include <cstdint>
#include <memory>
#include <string>
#include <string_view>
#include <variant>

namespace scpp::detail {
template<class Value> class packed_table;

// Private storage, not the generated mixed API. The compiler selects operations;
// this owner handles tagged values and COW, never coercion or overload admission.
class mixed_value final {
public:
    enum class kind { null, boolean, integer, floating, string, container };
private:
    using table = packed_table<mixed_value>;
    using payload = std::variant<std::monostate, bool, std::int64_t, double,
        std::shared_ptr<const std::string>, std::shared_ptr<table>>;
    payload value_;
    const table* read_table() const;
    template<class Operation> auto mutate(Operation operation);
public:
    mixed_value() noexcept = default;
    mixed_value(const mixed_value&) noexcept = default;
    mixed_value& operator=(const mixed_value&) noexcept = default;
    mixed_value(mixed_value&& other) noexcept;
    mixed_value& operator=(mixed_value&& other) noexcept;
    ~mixed_value() = default;

    static mixed_value boolean(bool value) noexcept;
    static mixed_value integer(std::int64_t value) noexcept;
    static mixed_value floating(double value) noexcept;
    static mixed_value string(std::string_view value);
    static mixed_value container();
    kind type() const noexcept;
    bool get_boolean() const;
    std::int64_t get_integer() const;
    double get_floating() const;
    std::string get_string() const;
    void clean() noexcept;

    mixed_value read(std::int64_t key) const;
    mixed_value read(std::string_view key) const;
    bool contains(std::int64_t key) const;
    bool contains(std::string_view key) const;
    void set(std::int64_t key, mixed_value value);
    void set(std::string_view key, mixed_value value);
    std::int64_t append(mixed_value value);
    bool remove(std::int64_t key);
    bool remove(std::string_view key);
    std::size_t container_size() const;
    bool is_packed_container() const;
};
} // namespace scpp::detail
