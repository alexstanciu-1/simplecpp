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
$maybe ?int=$n; echo (string)$maybe;
$label=new Label();
echo "before={$label->text}/$label/", $label->text;
echo $p, render_implicit($p), (string)$p, (string)(int16)7;
$built="<$p>"; echo $built;
try { $bad=new Bad(); echo "kept", "hidden:$bad"; } catch(Exception $e) { echo "!"; }
$builder=make_builder(); $parts=make_parts();
echo $small->to_string(),$p->to_string(),render($p),via_js($p),via_python($p),$n->to_string(),$flag->to_string(),$f->to_string(),
     $b->to_string(),$s->to_string(),"[",$builder->to_string(),$parts->to_string(),"]";
echo $builder, "$parts", (string)$builder, (string)$small, "$small";
return 8;
'''
if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-stringable-') as tmp:
        work = Path(tmp)
        subprocess.run(['php', Path(__file__).with_name('stringable.php')], check=True)
        output = b'42before=old/X/newPPP7<P>kept!-123PPPP42false1.25255a\0b[AB]ABA-123-123'
        edits = [
            (None, None, output),
            ('page', PAGE.replace('string {return "P"', 'int {return 1'), 'signature must match'),
            ('page', PAGE.replace(' implements Stringable', '').replace('override ', ''), 'printable scalar or Stringable'),
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
        revisions(work, 'php', dict(main=MAIN, page=PAGE, api=API + 'function render_implicit(Stringable $x):string {return (string)$x;}',
                       labels='final class Label implements Stringable {public string $text="old";'
                              'public override function to_string():string {$this->text="new";return "X";}}'
                              'final class Bad implements Stringable {public override function to_string():string {throw new Exception("stop");}}'),
                  edits if '--edits' in sys.argv else edits[:1], support_sources=support)
        lowered = '\n'.join(p.read_text() for p in (work/'php-out').rglob('*.cpp'))
        assert 'scpp_virtual_to_string()' in lowered and '::scpp::to_string(' in lowered
        assert '.to_string()' in lowered
        assert ')->scpp_virtual_to_string()' in lowered and 'const auto scpp_text_' in lowered
