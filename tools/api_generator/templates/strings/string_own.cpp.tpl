    static @n@ own(std::string bytes) {
        @n@ result;
        if (bytes.size() <= @n@::inline_capacity) init(result, bytes);
        else result.rep_.large = {255, new @n@::storage{std::move(bytes)}};
        return result;
    }
