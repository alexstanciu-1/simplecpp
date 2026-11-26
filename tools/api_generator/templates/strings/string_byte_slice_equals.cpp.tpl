    return arg1 >= 0 && arg1 <= arg0.size() && arg2 == arg3.size() && arg2 <= arg0.size() - arg1
        && std::memcmp(arg0.data() + arg1, arg3.data(), static_cast<std::size_t>(arg2)) == 0;
