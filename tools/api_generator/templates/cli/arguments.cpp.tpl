    vector_string values;
@reserve@    for (int i = 0; i < detail::invocation.count; ++i) {
        values.append(string{detail::invocation.values[i]});
    }
    return values;
