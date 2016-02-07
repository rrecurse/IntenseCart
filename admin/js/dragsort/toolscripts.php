<link rel="stylesheet" type="text/css" href="js/dragsort/common.css"/>
<style type="text/css">
/*	div {
		margin: 0px;
		padding: 0px;
	}*/
	.verticalgridline {
		padding-top: 27px;
	}
	.box, .handle {
		font-size: 14px;
		font-family: Arial, sans-serif;
		border: 1px solid #aaa;
	}
	.box {
		float: left;
		padding: 0px;
		width: 123px;
		height: 123px;
		margin: 5px;
		background-color: #eee;
		z-index: 1;
	}
	/*.handle {
		cursor: move;
		height: 14px;
		border-width: 0px 0px 1px 0px;
		background: #666;
		color: #eee;
		padding: 2px 6px;
		margin: 0px;
	}*/
	.box p {
		font-size: 12px;
		margin: 5px 5px 10px 5px;
		text-align: left;
		white-space: normal;
	}
	#boxDrag, #boxVerticalOnly, #boxHorizontalOnly, #boxRegionConstraint, 
	#boxThreshold, #boxAbsolute {
		cursor: move;
	}
	#boxAbsolute {
		position: absolute;
		bottom: 0px;
		right: 0px;
	}
</style>

<script language="JavaScript" type="text/javascript" src="js/dragsort/core.js"></script>
<script language="JavaScript" type="text/javascript" src="js/dragsort/events.js"></script>
<script language="JavaScript" type="text/javascript" src="js/dragsort/css.js"></script>
<script language="JavaScript" type="text/javascript" src="js/dragsort/coordinates.js"></script>
<script language="JavaScript" type="text/javascript" src="js/dragsort/drag.js"></script>

<script language="JavaScript"><!--
window.onload = function() {
	var group
	var coordinates = ToolMan.coordinates()
	var drag = ToolMan.drag()

	var boxDrag = document.getElementById("boxDrag")
	drag.createSimpleGroup(boxDrag)



	var boxRegionConstraint = document.getElementById("boxRegionConstraint")
	group = drag.createSimpleGroup(boxRegionConstraint)
	var origin = coordinates.create(0, 0)
	group.addTransform(function(coordinate, dragEvent) {
		var originalTopLeftOffset = 
				dragEvent.topLeftOffset.minus(dragEvent.topLeftPosition)
		return coordinate.constrainTo(origin, originalTopLeftOffset)
	})

    var boxAbsolute = document.getElementById("boxAbsolute")
	group = drag.createSimpleGroup(boxAbsolute)
	group.verticalOnly()
	group.addTransform(function(coordinate, dragEvent) {
		var scrollOffset = coordinates.scrollOffset()
		if (coordinate.y < scrollOffset.y)
			return coordinates.create(coordinate.x, scrollOffset.y)

		var clientHeight = coordinates.clientSize().y
		var boxHeight = coordinates._size(boxAbsolute).y
		if ((coordinate.y + boxHeight) > (scrollOffset.y + clientHeight))
			return coordinates.create(coordinate.x, 
					(scrollOffset.y + clientHeight) - boxHeight)

		return coordinate
	})
}

//-->
</script>
