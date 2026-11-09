    static @n@ own(std::string bytes) {
        @n@ result;
        if (bytes.size() <= @n@::inline_capacity) init(result, bytes);
        else result.rep_.large = {255, new @n@::storage{std::move(bytes)}};
        return result;
    }
    static @n@ ascii_case(@n@ result, bool upper) {
        auto* data = result.is_inline() ? result.rep_.small.bytes : result.rep_.large.ptr->bytes.data();
        for (std::size_t i = 0, n = read(result).size(); i < n; ++i) {
            auto& ch = data[i];
            if (upper && ch >= 'a' && ch <= 'z') ch -= 'a' - 'A';
            else if (!upper && ch >= 'A' && ch <= 'Z') ch += 'a' - 'A';
        }
        return result;
    }
