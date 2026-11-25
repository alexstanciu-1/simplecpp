// Native benchmark only. Match the inventory row's stride and first field.
struct Record {
    Word sku;
    std::array<Word, 15> padding;
};
static_assert(sizeof(Record) == 128);
constexpr unsigned record_count = 65'536;

template<bool from_memory, bool write_back = false, bool weighted = false>
[[gnu::noinline]] void advance_records(Record* rows, unsigned first_chain, unsigned end_chain,
                                       unsigned passes, std::array<Word, 24>& state, unsigned steps = 1) {
    for (unsigned pass = 0; pass < passes; ++pass) {
        for (unsigned chain = first_chain; chain < end_chain; ++chain) {
            Word x = state[chain];
            const unsigned end = (chain + 1) * record_count / 24;
            for (unsigned i = chain * record_count / 24; i < end; ++i) {
                const Word sku = from_memory ? rows[i].sku : i;
                if constexpr (write_back) rows[i].sku = sku + 1;
                x ^= sku;
                for (unsigned step = 0; step < (weighted ? steps : 1); ++step) {
                    x ^= x >> 27;
                    x = std::rotl(x * 0x2545f4914f6cdd1dULL, 17);
                }
            }
            state[chain] = x;
        }
    }
}

template<bool from_memory, bool write_back = false>
[[gnu::noinline]] Word record_passes(Record* rows, unsigned first_chain,
                                    unsigned end_chain, unsigned passes) {
    std::array<Word, 24> state{};
    for (unsigned chain = first_chain; chain < end_chain; ++chain) state[chain] = chain + 1;
    advance_records<from_memory, write_back>(rows, first_chain, end_chain, passes, state);
    Word sum = 0;
    for (unsigned chain = first_chain; chain < end_chain; ++chain) sum += state[chain];
    return sum;
}
