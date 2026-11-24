static void check_each() {
    for (Int cap : {1, 2, 8}) {
        scpp::vector_int64 entered, output;
        for (int i = 0; i < 12; ++i) { entered.append(0); output.append(0); }
        assert(App::each_batch_(entered, output, 0, cap, 12).size() == 0);
        // First eleven output grants are disjoint. Only the final partition wraps to slot zero.
        try { App::each_batch_(entered, output, 12, cap, 11); assert(false); }
        catch (const std::invalid_argument& error) { assert(std::string_view(error.what()) == "Overlapping exclusive segments"); }
        for (int i = 0; i < 12; ++i) assert(entered.at(i) == 0 && output.at(i) == 0);
        auto results = App::each_batch_(entered, output, 12, cap, 12);
        assert(results.size() == 12);
        for (int i = 0; i < 12; ++i) assert(results.at(i) == i && entered.at(i) == 1 && output.at(i) == i + 10);
        try { App::each_batch_(entered, output, 0, 0, 12); assert(false); } catch (const std::invalid_argument&) {}
        try { App::each_batch_(entered, output, -1, cap, 12); assert(false); } catch (const std::invalid_argument&) {}
    }
}
