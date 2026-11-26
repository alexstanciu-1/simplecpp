class json_pull_reader final {
    struct state;
    state* data_ = nullptr;
public:
    json_pull_reader();
    ~json_pull_reader();
    json_pull_reader(const json_pull_reader&) noexcept;
    json_pull_reader(json_pull_reader&&) noexcept;
    json_pull_reader& operator=(const json_pull_reader&) noexcept;
    json_pull_reader& operator=(json_pull_reader&&) noexcept;
@methods@};
