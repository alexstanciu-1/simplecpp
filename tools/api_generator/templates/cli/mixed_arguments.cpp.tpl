    mixed values = mixed::container();
    for (int i = 0; i < detail::invocation.count; ++i) {
        values.append(mixed::from_string(string{detail::invocation.values[i]}));
    }
    return values;
