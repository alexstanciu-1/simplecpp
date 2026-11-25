function reconcile_batch(vector<Stock> &$stock, vector<int> &$adjustments,
                         int $split, int $second_offset, int $remaining): vector<int> {
    return mt_join(
        reconcile(segment($stock, 0, $split), segment($adjustments, 0, $split)),
        reconcile(segment($stock, $split, $remaining), segment($adjustments, $second_offset, $remaining)));
}
