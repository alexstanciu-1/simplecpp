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
@conversion@private:
    friend struct detail::ownership_access;
    alignas(@alignment@) unsigned char storage_[@size@];
};
