$app ui_app=ui_app_create();
$window ui_window=ui_window_create($app,"UI only",200,120);
$shown bool=ui_window_show($window);
$closed bool=ui_window_close($window);
$event ui_event=ui_app_next_event($app);
if(!$shown || !$closed || ui_event_type($event)!=="window_close" || !isset(ui_event_window($event))){return 1;}
if(isset(ui_app_next_event($app))){return 2;}
ui_app_exit($app);
return 0;
