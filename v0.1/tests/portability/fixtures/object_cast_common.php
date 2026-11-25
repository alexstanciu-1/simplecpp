<?php
interface Cast_View { public function read(): int; }
interface Cast_Edit { public function change(int $value): void; }
class Cast_Base implements Cast_View {
    public int $value = 7;
    public function read(): int { return $this->value; }
}
final class Cast_Row extends Cast_Base implements Cast_Edit {
    public function change(int $value): void { $this->value = $value; }
}
final class Cast_Other extends Cast_Base {}
final class Cast_Source {
    public int $calls = 0;
    public function acquire(?Cast_View $value): ?Cast_View { $this->calls += 1; return $value; }
}
$row = new Cast_Row();
$source = new Cast_Source();
$view /** nullable<Cast_View> */ = $row;
$edit = object_cast($source->acquire($view), Cast_Edit::class);
$edit->change(9);
$back = object_cast($edit, Cast_Row::class);
$absent /** nullable<Cast_View> */ = null;
if ($absent instanceof Cast_Row) { echo 'unexpected'; }
echo $row->value, ':', $back->value, ':', $source->calls, ':', $view instanceof Cast_Row ? 1 : 0, ':', $view instanceof Cast_Other ? 1 : 0, "\n";
