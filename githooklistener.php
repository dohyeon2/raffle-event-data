<?php
$ch = curl_init();
// set URL and other appropriate options
curl_setopt($ch, CURLOPT_URL, "https://admin.jejodo.life/wp-content/plugins/raffle-event-plugin/hookinit.php");
curl_setopt($ch, CURLOPT_HEADER, 0);
// grab URL and pass it to the browser
$ret = curl_exec($ch);
if ($ret != TRUE) {
    var_dump("curl exec failed: " .  curl_error($ch));
}
// close cURL resource, and free up system resources
var_dump("curl exec sucess");
curl_close($ch);
