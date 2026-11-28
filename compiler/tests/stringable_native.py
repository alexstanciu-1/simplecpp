"""The core formatting contract uses ordinary class dispatch and concrete runtime providers."""
import subprocess
import sys
import tempfile
from pathlib import Path

from collection_goal_native import revisions

PAGE = 'final class Page implements Stringable { public override function to_string():string {return "P";} }'
API = 'function render(Stringable $page):string {return $page->to_string();}'
MAIN = '''
$p=new Page(); $small int16=-123; $n=42; $flag=false; $f=1.25; $b byte=255; $s="a\\0b";
$builder=make_builder(); $parts=make_parts();
echo $small->to_string(),$p->to_string(),render($p),via_js($p),via_python($p),$n->to_string(),$flag->to_string(),$f->to_string(),
     $b->to_string(),$s->to_string(),"[",$builder->to_string(),$parts->to_string(),"]";
return 8;
'''
if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-stringable-') as tmp:
        work = Path(tmp)
        subprocess.run(['php', Path(__file__).with_name('stringable.php')], check=True)
        output = b'-123PPPP42false1.25255a\0b[AB]'
        edits = [
            (None, None, output),
            ('page', PAGE.replace('string {return "P"', 'int {return 1'), 'signature must match'),
            ('page', PAGE.replace(' implements Stringable', '').replace('override ', ''), 'conversion'),
            ('page', None, 'Page'),
            ('page', PAGE, output),
        ]
        support = {
            'module.json': '{"dependencies":{"runtime":{"modules":["numeric"]}}}',
            'via.js': 'function via_js(page: Stringable):string { return page.to_string(); }',
            'via.py': 'def via_python(page: Stringable) -> string:\n    return page.to_string()\n',
            'builders.cpp': """
text_builder make_builder() { auto b=text_builder_create(); text_builder_append_string(b,"A"); return b; }
string_parts_builder make_parts() { auto b=string_parts_builder_create(); string_parts_builder_append_string(b,"B"); return b; }
""",
        }
        revisions(work, 'php', dict(main=MAIN, page=PAGE, api=API),
                  edits if '--edits' in sys.argv else edits[:1], support_sources=support)
        lowered = '\n'.join(p.read_text() for p in (work/'php-out').rglob('*.cpp'))
        assert 'scpp_virtual_to_string()' in lowered and '::scpp::to_string(' in lowered
        assert '.to_string()' in lowered
