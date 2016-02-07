<?
 include 'includes/application_top.php';
 if (empty($_GET['box'])) {
	die("Error. No box gived.");
 };
 $box = $_GET['box'];
 if (file_exists('includes/modules/dash_box/' . $box . '.php')) {
	include 'includes/modules/dash_box/' . $box . '.php';
 } else {
	die("Error. Box does not exists.");
 };
 $box_class = new $box();
 $box_class->render_ajax();
?>
