    string result, part;
    int64 position = 0;
    const auto limit = parts.iteration_limit();
    bool first = true;
    while (parts.iteration_next(position, limit, part)) {
        if (!first) result.append(separator);
        first = false;
        result.append(part);
    }
    return result;
