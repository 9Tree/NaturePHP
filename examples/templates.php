<?php
//to-do...

$mytpl = new Template(array('file' => Path::to('tpls/index.html', __FILE__), 'mode'=>'auto', 'content'=>false));

ob_start();
?>
	<ul>
		<li>
	</ul>
<?php
$menu = ob_get_clean();

$mytpl->addContent('menu', $menu);
?>