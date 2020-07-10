<?php
/**
 * (C) OpenEyes Foundation, 2014
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenEyes
 * @link http://www.openeyes.org.uk
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (C) 2014, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/gpl-3.0.html The GNU General Public License V3.0
 */

$fields = OphInKowastereo_Field_Measurement::model()->getUnattachedForPatient(
	$this->patient, $side == 'left' ? Eye::LEFT : Eye::RIGHT, $this->event
);
if (!$element->{"{$side}_field_id"} && $fields) $element->{"{$side}_field_id"} = end($fields)->id;

$field_data = array();
foreach ($fields as $field) {
	$field_data[$field->id] = array(
		'id' => $field->id,
		'url' => Yii::app()->baseUrl . "/file/view/{$field->image_id}/img.gif",
		'date' => date(Helper::NHS_DATE_FORMAT . ' H:i:s', strtotime($field->study_datetime)),
	);
}
$current_field = $element->{"{$side}_field_id"} ? $field_data[$element->{"{$side}_field_id"}] : null;

Yii::app()->clientScript->registerScript(
	"OphInKowastereo_available_fields_{$side}",
	"var OphInKowastereo_available_fields_{$side} = " . CJSON::encode($field_data),
	CClientScript::POS_END
);

?>
<div class="element-eye <?= $side ?>-eye column">
	<?php if ($current_field): ?>
		<div class="field-row row">
			<div class="large-5 column">
				<?= $form->dropDownList($element, "{$side}_field_id", CHtml::listData($field_data, 'id', 'date'), array('nowrapper' => true)) ?>
			</div>
			<div class="large-7 column">
				<img id="Element_OphInKowastereo_Image_image_<?= $side ?>" src="<?= CHtml::encode($current_field['url']) ?>">
			</div>
		</div>
	<?php else: ?>
		<p>There are no fields to view for the <?= $side ?> eye.</p>
	<?php endif; ?>
</div>
