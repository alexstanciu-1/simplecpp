// The output inserts labels into HTML text nodes.
function html_text(string $text): string {
    $safe = str_replace("&", "&amp;", $text);
    $safe = str_replace("<", "&lt;", $safe);
    return str_replace(">", "&gt;", $safe);
}

class Product {
    public string $label;
    public int $units;

    public function __construct(string $label, int $units) {
        $this->label = $label;
        $this->units = $units;
    }

    public function render(): string {
        $label = html_text($this->label);
        $units = $this->units;
        return "<li>$label ($units)</li>\n";
    }
}
