struct Token {
    public int $offset;
    public int $length;
    public int $kind;
}
struct Scan {
    public string $source;
    public vector<Token> $tokens;
    public int $error=-1;
    public string $reason;
}
function scan(string $text, int $previous_count):Scan {
    $out Scan;
    $out->source=$text;
    $items vector<Token> = [];
    $size=strlen($text);
    if ($previous_count > $size) {
        $previous_count = $size;
    }
    $items->reserve($previous_count);
    $i=0;
    while ($i<$size) {
        $b=$text[$i];
        $start=$i;
        $kind=1;
        $i++;
        if ($b==32 || $b==9 || $b==10 || $b==13) {
            continue;
        }
        if ((($b>=65 && $b<=90) || ($b>=97 && $b<=122) || $b==95)) {
            while ($i<$size) {
                $b=$text[$i];
                if (!((($b>=65 && $b<=90) || ($b>=97 && $b<=122) || $b==95) || ($b>=48 && $b<=57))) {
                    break;
                }
                $i++;
            }
            if (($i-$start==2 && $text[$start+0]==105 && $text[$start+1]==102) || ($i-$start==5 && $text[$start+0]==119 && $text[$start+1]==104 && $text[$start+2]==105 && $text[$start+3]==108 && $text[$start+4]==101)) {
                $kind=62;
            }
        }
        else if ($b==61) {
            $kind=61;
            if ($i<$size && $text[$i]==61) {
                $i++;
                $kind=15;
                if ($i<$size && $text[$i]==61) {
                    $i++;
                    $kind=11;
                }
            }
        }
        else {
            $out->error=$start;
            $out->reason="Unsupported token";
            return $out;
        }
        $item Token;
        $item->offset=$start;
        $item->length=$i-$start;
        $item->kind=$kind;
        $items[]=$item;
    }
    $out->tokens=$items;
    return $out;
}
