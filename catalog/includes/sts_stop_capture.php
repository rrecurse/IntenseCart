<?php


if (!$no_sts) {
  // Store captured output to $sts_capture
  $sts_block[$sts_block_name] = ob_get_contents();
  ob_end_clean(); // Clear out the capture buffer
}

?>
