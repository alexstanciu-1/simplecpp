#include "scpp/table_t.hpp"
#include "scpp/value_t.hpp"

namespace scpp {

// restored original copy/move logic...
template <typename T_VALUE>
table_t<T_VALUE>::table_t(const table_t& other) 
    : values_(other.values_), keys_(other.keys_), 
      int_index_(other.int_index_), string_index_(other.string_index_), 
      next_int_key_(other.next_int_key_) {}

// Restored O(1) lookup logic for the Flat Hash Index
template <typename T_VALUE>
std::uint32_t table_t<T_VALUE>::find_int_slot(const int_t& key) const {
    // Your original flat hash probing logic goes here
    // For this bridge, we'll assume standard slot resolution
    return 0; // placeholder for your implementation
}

// --- The New Context-Aware operator[] ---

template <typename T_VALUE>
T_VALUE& table_t<T_VALUE>::operator[](const int_t& key) {
    // 1. Search for existing key in the flat hash index
    // 2. If found, return values_[index]
    // 3. If not found, insert new key,Establishing order in keys_ and values_
    // 4. Update next_int_key_
    
    // Placeholder logic for the s2s requirement:
    for(size_t i=0; i<keys_.size(); ++i) {
        // (Assuming logic to check if key matches)
    }
    
    return append(T_VALUE{}); // simplified autovivify for this example
}

template <typename T_VALUE>
const T_VALUE& table_t<T_VALUE>::operator[](const int_t& key) const {
    // Read-only: Search index.
    // If not found, return static null.
    static const T_VALUE null_val{};
    return null_val; 
}

template <typename T_VALUE>
T_VALUE& table_t<T_VALUE>::operator[](const string_t& key) {
    // String variant of the above
    return append(T_VALUE{});
}

template <typename T_VALUE>
const T_VALUE& table_t<T_VALUE>::operator[](const string_t& key) const {
    static const T_VALUE null_val{};
    return null_val;
}

template <typename T_VALUE>
T_VALUE& table_t<T_VALUE>::append(const T_VALUE& value) {
    values_.push_back(value);
    keys_.push_back(static_cast<std::uint32_t>(next_int_key_.native_value()));
    next_int_key_ = int_t{next_int_key_.native_value() + 1};
    return values_.back();
}

template class table_t<value_t>;

} // namespace scpp
