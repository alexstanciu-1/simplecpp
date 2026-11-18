    key_type append(value_type value) {
        key_type maximum = -1;
        for (std::size_t i = 0; i < used; ++i) if (live[i] && at_slot(i).key > maximum) maximum = at_slot(i).key;
        if (maximum == INT64_MAX) throw std::overflow_error("hash_append_key_overflow");
        const auto key = maximum + 1;
        set(key, static_cast<value_type&&>(value));
        return key;
    }
