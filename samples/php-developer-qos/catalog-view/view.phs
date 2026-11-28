// The output inserts labels into HTML text nodes.
function html_text(string $text): string {
    return htmlspecialchars($text, ENT_NOQUOTES | ENT_SUBSTITUTE);
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
