<?php
$appId = OCA\PdfSignature\AppInfo\Application::APP_ID;
\OCP\Util::addScript($appId, $appId . '-adminSettings');
?>
<div id="signature_prefs"></div>