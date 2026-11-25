class @n@ final {
public:
    @n@() noexcept;
    ~@n@() noexcept;
    @n@(const @n@&) noexcept;
    @n@(@n@&&) noexcept;
    @n@& operator=(const @n@&) noexcept;
    @n@& operator=(@n@&&) noexcept;
    static @n@ from_boolean(bool_t value) noexcept;
    static @n@ from_integer(int64 value) noexcept;
    static @n@ from_floating(float64 value) noexcept;
    static @n@ from_string(const string& value);
    static @n@ container();
    static @n@ python_list();
    static @n@ from_tuple(@n@* values, int64 count);
    @n@ python_read(int64 key) const;
    @n@ python_read(const string& key) const;
    void python_set(int64 key, @n@ value);
    void python_set(const string& key, @n@ value);
    bool_t python_equal(const @n@& other) const;
    bool_t python_next(int64& position, int64 limit, @n@& value) const;
    bool_t python_contains(const @n@& needle) const;
    boxed_kind kind() const noexcept;
    bool_t get_boolean() const;
    int64 get_integer() const;
    float64 get_floating() const;
    string get_string() const;
    @n@ read(const int64& key) const;
    @n@ read(const string& key) const;
    bool_t has(const int64& key) const;
    bool_t has(const string& key) const;
    void set(int64 key, @n@ value);
    void set(const string& key, @n@ value);
    bool_t remove(const int64& key);
    bool_t remove(const string& key);
    int64 append(@n@ value);
    int64 container_size() const;
    bool_t is_packed_container() const;
    void clean() noexcept;
    void clear();
    // Compiler traversal API; key_index == -1 selects key_text. No table owner is retained.
    int64 @limit_method@() const;
    bool_t @next_method@(int64& position, int64 limit, @n@& value) const;
    bool_t @next_method@(int64& position, int64 limit, int64& key_index, string& key_text, @n@& value) const;
@conversion@@fences@private:
    friend struct detail::ownership_access;
    alignas(@alignment@) unsigned char storage_[@size@];
};
