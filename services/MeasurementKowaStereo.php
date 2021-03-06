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

namespace OEModule\OphInKowastereo\services;

class MeasurementKowaStereo extends \services\Resource
{

  public $id;
  public $study_datetime;
  public $patient_id;
  public $eye_id;
  public $scanned_field_id;

  static public function fromFhir($fhirObject)
  {
	$report = parent::fromFhir($fhirObject);

	$patient = \Patient::model()->find("id=?", array($report->patient_id));
	$report->patient_id = $patient->id;
	$eye = 'Right';
	if ($report->eye == 'L') {
	  $eye = 'Left';
	} else if ($report->eye == 'B') {
            $eye = 'Both';
        }
	$report->eye_id = \Eye::model()->find("name=:name", array(":name" => $eye))->id;

	$title = $report->file_reference;
	$protected_file = \ProtectedFile::createForWriting($title);
	$protected_file->name = $title;
	file_put_contents($protected_file->getPath(), base64_decode($report->image_scan_data));
	$protected_file->mimetype = 'image/jpeg';
	$protected_file->save();
	$report->scanned_field_id = $protected_file->id;
        file_put_contents("/tmp/kowa.txt", "Report ID: " . $report->scanned_field_id);
        
        if (isset($report->image_scan_data_2ndary)) {
            
            $protected_file = \ProtectedFile::createForWriting($title);
            $protected_file->name = $title;
            file_put_contents($protected_file->getPath(), base64_decode($report->image_scan_data_2ndary));
            $protected_file->mimetype = 'image/jpeg';
            $protected_file->save();
            $report->scanned_field_2ndary_id = $protected_file->id;
            file_put_contents("/tmp/kowa.txt", "Report ID: " . $report->scanned_field_2ndary_id);
        }
	return $report;
  }
}
