
#include <string>
#include <vector>
#include <unordered_map>
#include <cstdint>
#include <stdexcept>

using string_t = std::string;

class global_string_pool {
public:
    // The MSB (Most Significant Bit) flags
    static constexpr uint32_t string_key_flag = 0x80000000;
    static constexpr uint32_t string_id_mask  = 0x7FFFFFFF;

    // Meyers Singleton: Thread-safe initialization in C++11 and later
    static global_string_pool& instance() {
        static global_string_pool pool;
        return pool;
    }

    // Delete copy and move semantics to enforce the singleton pattern
    global_string_pool(const global_string_pool&) = delete;
    global_string_pool& operator=(const global_string_pool&) = delete;

    // --- INTERNING (String -> Tagged 32-bit ID) ---
    uint32_t intern(const string_t& str) {
        auto it = lookup_.find(str);
        if (it != lookup_.end()) {
            // String exists. Return the ID with the 32nd bit flipped to 1.
            return it->second | string_key_flag;
        }

        // New string. Assign it the current size of the vector as its ID.
        uint32_t new_id = static_cast<uint32_t>(strings_.size());
        
        // Safety check: Ensure we haven't exceeded 2.14 billion unique strings
        if (new_id > string_id_mask) {
            throw std::overflow_error("global_string_pool capacity exceeded");
        }

        // Insert into the hash map. 
        // emplace returns a pair: [iterator to the new element, success bool]
        auto [inserted_it, success] = lookup_.emplace(str, new_id);
        
        // Store the memory address of the string key directly from the map node.
        strings_.push_back(&(inserted_it->first));

        // Return the new ID with the MSB flag set
        return new_id | string_key_flag;
    }

    // --- REVERSE LOOKUP (Tagged 32-bit ID -> String) ---
    const string_t& get_string(uint32_t tagged_id) const {
        // Strip the MSB flag to get the real array index
        uint32_t real_id = tagged_id & string_id_mask;
        return *(strings_[real_id]);
    }

    // --- HELPER METADATA ---
    static bool is_string_id(uint32_t tagged_id) {
        return (tagged_id & string_key_flag) != 0;
    }

private:
    global_string_pool() {
        // As requested: Spend global memory to buy extreme CPU speed.
        // Forcing a 0.4 max load factor ensures hash chains remain incredibly short,
        // practically eliminating hash collisions during interning.
        lookup_.max_load_factor(0.4f);
    }

    // The map owns the actual string data and maps it to a sequential integer
    std::unordered_map<string_t, uint32_t> lookup_;

    // The vector provides O(1) reverse lookup using stable pointers
    std::vector<const string_t*> strings_;
};
