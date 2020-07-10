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

class MeasurementKowaStereoService extends \services\ModelService {

  static protected $operations = array(self::OP_CREATE);

  static protected $primary_model = 'OphInKowastereo_Field_Measurement';

  public function resourceToModel($res, $measurement)
  {
	  try {
		  $measurement->eye_id = $res->eye_id;
		  $measurement->study_datetime = $res->study_datetime;
		  $measurement->image_id = $res->scanned_field_id;
                  if (isset($res->scanned_field_2ndary_id)) {
                    $measurement->image_2ndary_id = $res->scanned_field_2ndary_id;
                  }
		  $measurement->patient_id = $res->patient_id;
		  $this->saveModel($measurement);
	  } catch(Exception $ex) {
		  echo $ex->getMessage() . PHP_EOL;
	  }
  }
}
