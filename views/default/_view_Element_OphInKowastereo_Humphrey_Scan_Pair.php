<?php

$patient = $this->patient;

$name = $this->episode->firm->serviceSubspecialtyAssignment->subspecialty->name;

$api = new OphInVisualfields_API;
$event_id = null;
//if (isset($this->event)) {
//	$event_id = $this->event->id;
//}
$right_fields = $api->getVisualfields($this->patient, Eye::RIGHT, null, True, 1);
$left_fields = $api->getVisualfields($this->patient, Eye::LEFT, null, True, 1);
?>

