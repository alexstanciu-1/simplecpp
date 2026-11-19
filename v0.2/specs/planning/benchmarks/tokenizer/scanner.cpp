struct Token {
    int offset;
    int length;
    int kind;
};
struct Scan {
    string source;
    vector<Token> tokens;
    int error=-1;
    string reason;
};
Scan scan(string text, int previous_count) {
    Scan out;
    out->source=text;
    vector<Token> items = [];
    auto size=strlen(text);
    if (previous_count > size) {
        previous_count = size;
    }
    items->reserve(previous_count);
    auto i=0;
    while (i<size) {
        auto b=text[i];
        auto start=i;
        auto kind=1;
        i++;
        if (b==32 || b==9 || b==10 || b==13) {
            continue;
        }
        if (((b>=65 && b<=90) || (b>=97 && b<=122) || b==95)) {
            while (i<size) {
                b=text[i];
                if (!(((b>=65 && b<=90) || (b>=97 && b<=122) || b==95) || (b>=48 && b<=57))) {
                    break;
                }
                i++;
            }
            if ((i-start==2 && text[start+0]==105 && text[start+1]==102) || (i-start==5 && text[start+0]==119 && text[start+1]==104 && text[start+2]==105 && text[start+3]==108 && text[start+4]==101) || (i-start==4 && text[start+0]==97 && text[start+1]==117 && text[start+2]==116 && text[start+3]==111)) {
                kind=62;
            }
        }
        else if (b==61) {
            kind=61;
            if (i<size && text[i]==61) {
                i++;
                kind=15;
                if (i<size && text[i]==61) {
                    i++;
                    kind=11;
                }
            }
        }
        else {
            out->error=start;
            out->reason="Unsupported token";
            return out;
        }
        Token item;
        item->offset=start;
        item->length=i-start;
        item->kind=kind;
        items[]=item;
    }
    out->tokens=items;
    return out;
}
