<?php
/**
 * OpenEyes
 *
 * (C) Moorfields Eye Hospital NHS Foundation Trust, 2008-2011
 * (C) OpenEyes Foundation, 2011-2012
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenEyes
 * @link http://www.openeyes.org.uk
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (c) 2008-2011, Moorfields Eye Hospital NHS Foundation Trust
 * @copyright Copyright (c) 2011-2012, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/gpl-3.0.html The GNU General Public License V3.0
 */
?>
<div class="element-fields">
	<?= $form->multiSelectList($element, 'MultiSelect_assessment', 'assessment', 'ophinkowastereo_result_assessment_id', CHtml::listData(OphInKowastereo_Result_Assessment::model()->findAll(array('order'=>'display_order asc')),'id','name'), $element->ophinkowastereo_result_assessment_defaults, array('empty' => '- Please select -', 'label' => 'Result Assessment', 'class' => 'linked-fields','data-linked-fields' => 'other', 'data-linked-values' => 'Other'))?>
	<?= $form->textArea($element, 'other', array('rows' => 4), !$element->hasMultiSelectValue('assessment','Other')) ?>
</div>
