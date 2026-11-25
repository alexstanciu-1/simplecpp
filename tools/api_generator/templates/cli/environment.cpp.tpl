    if (name.size() && std::memchr(name.data(), 0, name.size())) {
        throw ValueError("environment name must not contain NUL");
    }
    const std::string key(name.data(), name.size());
    const char* value = std::getenv(key.c_str());
    return value ? result_or_false_string{string{value}} : result_or_false_string{};
