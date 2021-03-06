<?php
require_once "../../Graph.class.php";

$graph = new Graph(400, 30);
$graph->border->hide();

$drawer = $graph->getDrawer();

for($i = 7; $i < 400; $i += 15) {
	$drawer->line(
		new Color(0, 0, 0),
		new Line(
			new Point($i, 0),
			new Point($i, 30)
		)
	);
}

for($i = 7; $i < 30; $i += 15) {
	$drawer->line(
		new Color(0, 0, 0),
		new Line(
			new Point(0, $i),
			new Point(400, $i)
		)
	);
}

$drawer->filledRectangle(
	new Color(0, 100, 200, 50),
	new Line(
		new Point(0, 0),
		new Point(400, 30)
	)
);

$graph->draw();

?>
