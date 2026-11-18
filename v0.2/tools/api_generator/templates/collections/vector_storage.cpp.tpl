struct @n@::storage {
    @type_contracts@
    unsigned char* data = nullptr;
    std::size_t count = 0;
    std::size_t capacity = 0;

    storage() noexcept = default;
    storage(const storage& other) {
        data = @buffer@::vector_allocate(other.count, sizeof(element_type), alignof(element_type));
        capacity = other.count;
        try {
            while (count < other.count) {
                ::new (data + count * sizeof(element_type)) element_type(other[count]);
                ++count;
            }
        } catch (...) {
            clear();
            @buffer@::vector_deallocate(data, alignof(element_type));
            throw;
        }
    }
    storage& operator=(const storage&) = delete;
    ~storage() { clear(); @buffer@::vector_deallocate(data, alignof(element_type)); }
    std::size_t size() const noexcept { return count; }
    bool empty() const noexcept { return count == 0; }
    const element_type* iteration_select(@count@ position, @count@ limit) const noexcept {
        return position < limit && static_cast<unsigned long long>(position) < count
            ? &(*this)[static_cast<std::size_t>(position)] : nullptr;
    }
    element_type& operator[](std::size_t index) noexcept { return *reinterpret_cast<element_type*>(data + index * sizeof(element_type)); }
    const element_type& operator[](std::size_t index) const noexcept { return *reinterpret_cast<const element_type*>(data + index * sizeof(element_type)); }
    const element_type& front() const noexcept { return (*this)[0]; }
    void clear() noexcept { while (count != 0) (*this)[--count].~element_type(); }
    void reserve(std::size_t required) {
        if (required <= capacity) return;
        const auto next_capacity = @buffer@::vector_growth(capacity, required, sizeof(element_type));
        auto* next = @buffer@::vector_allocate(next_capacity, sizeof(element_type), alignof(element_type));
        for (std::size_t i = 0; i < count; ++i) {
            ::new (next + i * sizeof(element_type)) element_type(static_cast<element_type&&>((*this)[i]));
            (*this)[i].~element_type();
        }
        @buffer@::vector_deallocate(data, alignof(element_type));
        data = next;
        capacity = next_capacity;
    }
    void append(element_type&& value) {
        reserve(count + 1);
        ::new (data + count * sizeof(element_type)) element_type(static_cast<element_type&&>(value));
        ++count;
    }
    void remove(std::size_t offset) noexcept {
        for (std::size_t i = offset; i + 1 < count; ++i) (*this)[i] = static_cast<element_type&&>((*this)[i + 1]);
        (*this)[--count].~element_type();
    }
};
