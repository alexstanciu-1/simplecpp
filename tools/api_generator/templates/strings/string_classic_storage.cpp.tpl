    static @n@ ascii_case(@n@ result, bool upper, bool first_only) {
        auto* data = result.is_inline() ? result.rep_.small.bytes : result.rep_.large.ptr->bytes.data();
        const auto size = read(result).size();
        const auto limit = first_only && size > 1 ? 1 : size;
        for (std::size_t i = 0; i < limit; ++i) {
            auto& ch = data[i];
            if (upper && ch >= 'a' && ch <= 'z') ch -= 'a' - 'A';
            else if (!upper && ch >= 'A' && ch <= 'Z') ch += 'a' - 'A';
        }
        return result;
    }
