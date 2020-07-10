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
class Element_OphInKowastereo_Image extends BaseEventTypeElement {

    public function tableName() {
        return 'et_ophinkowastereo_image';
    }

    public function rules() {
        return array(
            array('left_field_id, right_field_id', 'safe'),
        );
    }

    public function relations() {
        return array(
            'event' => array(self::BELONGS_TO, 'Event', 'event_id'),
            'left_field' => array(self::BELONGS_TO, 'OphInKowastereo_Field_Measurement', 'left_field_id'),
            'right_field' => array(self::BELONGS_TO, 'OphInKowastereo_Field_Measurement', 'right_field_id'),
        );
    }

    public function afterSave() {
        parent::afterSave();
        if ($this->left_field)
            $this->updateMeasurementReference($this->left_field, Eye::LEFT);
        if ($this->right_field)
            $this->updateMeasurementReference($this->right_field, Eye::RIGHT);
    }

    private function updateMeasurementReference(OphInKowastereo_Field_Measurement $measurement, $eye_id) {
        $patient_measurement_id = $measurement->getPatientMeasurement()->id;

        $existing = $this->dbConnection->createCommand()
                ->select(array('pm.id pm_id', 'mr.id mr_id'))
                ->from('ophinkowastereo_field_measurement fm')
                ->join('patient_measurement pm', 'pm.id = fm.patient_measurement_id')
                ->join('measurement_reference mr', 'mr.patient_measurement_id = pm.id and mr.event_id = :event_id')
                ->join('event ev', 'ev.id = mr.event_id')->where('ev.deleted=0 and fm.eye_id = :eye_id and mr.event_id = :event_id',
                        array(':eye_id' => $eye_id, ':event_id' => $this->event_id))
                ->queryRow();

        if ($existing) {
            if ($existing['pm_id'] != $patient_measurement_id) {
                MeasurementReference::model()->deleteByPk($existing['mr_id']);
            } else {
                // Nothing to do
                return;
            }
        }

        $measurement->attach($this->event);
    }

}

