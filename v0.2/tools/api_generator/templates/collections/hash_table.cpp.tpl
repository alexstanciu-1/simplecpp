struct @n@::storage {
    @type_contracts@
    struct entry { key_type key; value_type value; };
    unsigned char* data = nullptr;
    unsigned char* live = nullptr;
    std::size_t used = 0;
    std::size_t capacity = 0;
    std::size_t live_size = 0;
    @index@ index;

    static std::uint64_t hash_key(const key_type& key) noexcept { return @hash@; }
    static bool equal_key(const key_type& left, const key_type& right) noexcept { return @equal@; }
    static std::uint32_t checked_capacity(std::uint64_t count) {
        if (count > UINT32_MAX) throw std::length_error("hash_index_capacity_limit");
        return static_cast<std::uint32_t>(count);
    }
    static unsigned char* allocate(std::size_t count) {
        if (count > static_cast<std::size_t>(PTRDIFF_MAX) / sizeof(entry)) throw std::length_error("hash_physical_entry_limit");
        return static_cast<unsigned char*>(alignof(entry) > __STDCPP_DEFAULT_NEW_ALIGNMENT__
            ? ::operator new(count * sizeof(entry), static_cast<std::align_val_t>(alignof(entry))) : ::operator new(count * sizeof(entry)));
    }
    static void deallocate(unsigned char* pointer) noexcept {
        if (alignof(entry) > __STDCPP_DEFAULT_NEW_ALIGNMENT__) ::operator delete(pointer, static_cast<std::align_val_t>(alignof(entry)));
        else ::operator delete(pointer);
    }
    entry& at_slot(std::size_t position) noexcept { return *reinterpret_cast<entry*>(data + position * sizeof(entry)); }
    const entry& at_slot(std::size_t position) const noexcept { return *reinterpret_cast<const entry*>(data + position * sizeof(entry)); }
    storage() noexcept = default;
    storage(const storage& other) {
        try {
            reserve(other.used);
            used = other.used;
            for (std::size_t i = 0; i < used; ++i) {
                if (!other.live[i]) continue;
                ::new (data + i * sizeof(entry)) entry{other.at_slot(i).key, other.at_slot(i).value};
                live[i] = 1;
                ++live_size;
            }
            if (live_size) rehash(checked_capacity(static_cast<std::uint64_t>(used) * 2), used);
        } catch (...) { release(); throw; }
    }
    storage(storage&& other) noexcept { swap(other); }
    storage& operator=(const storage& other) { if (this != &other) { storage copy(other); swap(copy); } return *this; }
    storage& operator=(storage&& other) noexcept { if (this != &other) { storage discarded; swap(discarded); swap(other); } return *this; }
    ~storage() { release(); }
    void swap(storage& other) noexcept {
        auto* d = data; data = other.data; other.data = d;
        auto* l = live; live = other.live; other.live = l;
        auto u = used; used = other.used; other.used = u;
        auto c = capacity; capacity = other.capacity; other.capacity = c;
        auto s = live_size; live_size = other.live_size; other.live_size = s;
        index.swap(other.index);
    }
    void release() noexcept {
        for (std::size_t i = 0; i < used; ++i) if (live[i]) at_slot(i).~entry();
        deallocate(data); ::operator delete(live);
        data = nullptr; live = nullptr; used = capacity = live_size = 0;
    }
    void clear() noexcept { storage empty; swap(empty); }
    void reserve(std::size_t required) {
        if (required <= capacity) return;
        const auto limit = static_cast<std::size_t>(PTRDIFF_MAX) / sizeof(entry);
        if (required > limit) throw std::length_error("hash_physical_entry_limit");
        auto next_capacity = capacity == 0 ? (limit < 4 ? limit : 4) : (capacity > limit / 2 ? limit : capacity * 2);
        if (next_capacity < required) next_capacity = required;
        auto* next = allocate(next_capacity);
        unsigned char* next_live;
        try { next_live = static_cast<unsigned char*>(::operator new(next_capacity)); }
        catch (...) { deallocate(next); throw; }
        for (std::size_t i = 0; i < next_capacity; ++i) next_live[i] = 0;
        for (std::size_t i = 0; i < used; ++i) {
            if (!live[i]) continue;
            auto& old = at_slot(i);
            ::new (next + i * sizeof(entry)) entry{static_cast<key_type&&>(old.key), static_cast<value_type&&>(old.value)};
            next_live[i] = 1;
            old.~entry();
        }
        deallocate(data); ::operator delete(live);
        data = next; live = next_live; capacity = next_capacity;
    }
    void rehash(std::uint32_t buckets, std::size_t physical_count) {
        @index@ next(buckets, physical_count);
        for (std::size_t i = 0; i < used; ++i) if (live[i]) next.add(hash_key(at_slot(i).key), static_cast<std::uint32_t>(i));
        index.swap(next);
    }
    @candidate@ locate(const key_type& key) const noexcept {
        const auto hash = hash_key(key);
        std::uint32_t offset = 0;
        for (;;) {
            const auto found = index.next(hash, offset);
            if (found.position == UINT32_MAX || equal_key(at_slot(found.position).key, key)) return found;
        }
    }
    const value_type* find(const key_type& key) const noexcept {
        const auto found = locate(key);
        return found.position == UINT32_MAX ? nullptr : &at_slot(found.position).value;
    }
    void set(key_type key, value_type value) {
        const auto found = locate(key);
        if (found.position != UINT32_MAX) { at_slot(found.position).value = static_cast<value_type&&>(value); return; }
        if (used >= UINT32_MAX) throw std::length_error("hash_physical_entry_limit");
        reserve(used + 1);
        const auto buckets = index.capacity();
        if (!buckets || static_cast<std::uint64_t>(index.size()) + index.deleted() >= static_cast<std::uint64_t>(buckets) * 3 / 4 || !index.holds(used + 1)) {
            const auto next = !buckets ? 4 : (index.size() >= buckets / 2 ? checked_capacity(static_cast<std::uint64_t>(buckets) * 2) : buckets);
            rehash(next, used + 1);
        }
        ::new (data + used * sizeof(entry)) entry{static_cast<key_type&&>(key), static_cast<value_type&&>(value)};
        live[used] = 1;
        index.add(hash_key(at_slot(used).key), static_cast<std::uint32_t>(used));
        ++used; ++live_size;
    }
    bool remove(const key_type& key) noexcept {
        const auto found = locate(key);
        if (found.position == UINT32_MAX) return false;
        index.erase(found.bucket);
        at_slot(found.position).~entry();
        live[found.position] = 0;
        --live_size;
        return true;
    }
    std::size_t size() const noexcept { return live_size; }
    std::size_t physical_size() const noexcept { return used; }
    unsigned index_bytes() const noexcept { return index.index_bytes(); }
    const entry* iteration_select(std::int64_t& position, std::int64_t limit) const noexcept {
        auto next = static_cast<std::uint64_t>(position);
        const auto end = static_cast<std::uint64_t>(limit) < used ? static_cast<std::uint64_t>(limit) : used;
        while (next < end && !live[next]) ++next;
        position = static_cast<std::int64_t>(next);
        return next < end ? &at_slot(next) : nullptr;
    }
    bool iteration_next(std::int64_t& position, std::int64_t limit, value_type& value, key_type* key = nullptr) const {
        auto selected_position = position;
        const auto* item = iteration_select(selected_position, limit);
        if (!item) { position = selected_position; return false; }
        value_type selected = item->value;
        if (key) { key_type selected_key = item->key; *key = static_cast<key_type&&>(selected_key); }
        value = static_cast<value_type&&>(selected);
        position = selected_position + 1;
        return true;
    }
@append@
};
