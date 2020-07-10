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
class LegacyFieldsCommand extends CConsoleCommand {

    public $importDir;
    public $archiveDir;
    public $errorDir;
    public $dupDir;
    public $interval;

    public function getHelp() {
        return "Usage: legacyfields import --interval=<time> --importDir=<dir> --archiveDir=<dir> --errorDir=<dir> --dupDir=<dir>\n\n"
                . "Import Humphrey visual fields into OpenEyes from the given import directory.\n"
                . "Successfully imported files are moved to the given archive directory;\n"
                . "likewise, errored files and duplicate files (already within OE) are moved to\n"
                . "the respective directory. --interval is used to check for tests within\n"
                . "the specified time limit, so PT10M looks for files within 10 minutes of the other to\n"
                . "bind to an existing field.\n\n"
                . "The import expects to find .XML files in the given directory, two\n"
                . "for each field test, and each file is expected to be in a format\n"
                . "acceptable by OpenEyes (specifically they must conform to the API).\n"
                . "For each pair of files, the first is a patient measurement, the\n"
                . "second a humphrey field test reading.\n"
                . "\n";
    }

    public function actionImport($importDir, $archiveDir, $errorDir, $dupDir, $interval='PT45M') {
        $this->importDir = $this->checkSeparator($importDir);
        $this->archiveDir = $this->checkSeparator($archiveDir);
        $this->errorDir = $this->checkSeparator($errorDir);
        $this->dupDir = $this->checkSeparator($dupDir);
        $this->interval = $interval;
        $smgr = Yii::app()->service;
        $fhirMarshal = Yii::app()->fhirMarshal;
        $directory = $this->importDir;
        if ($entry = glob($directory . '/*.fmes')) {
            foreach ($entry as $file) {
                echo "Importing " . $file . PHP_EOL;

                // first check the file has not already been imported:
                $field = file_get_contents($file);
                $resource_type = 'MeasurementVisualFieldHumphrey';
                $fieldObject = $fhirMarshal->parseXml($field);

                if (count(ProtectedFile::model()->find("name=:name", array(":name" => $fieldObject->file_reference))) > 0) {
                    echo "Moving " . basename($file) . " to duplicates directory; "
                    . $fieldObject->file_reference . " already exists within OE" . PHP_EOL;
                    $this->move($this->dupDir, $file);
                    continue;
                }

                $matches = array();
                preg_match("/__OE_PATIENT_ID_([0-9]*)__/", $field, $matches);
                if (count($matches) < 2) {
                    echo "Failed to extract patient ID in " . basename($file) . "; moving to " . $this->errorDir . PHP_EOL;
                    $this->move($this->errorDir, $file);
                    continue;
                }
                $match = str_pad($matches[1], 7, '0', STR_PAD_LEFT);

                $patient = Patient::model()->find("hos_num=:hos_num", array(":hos_num" => $match));
                if (!$patient) {
                    echo "Failed to find patient in " . basename($file) . "; moving to " . $this->errorDir . PHP_EOL;
                    $this->move($this->errorDir, $file);
                    continue;
                }
                $pid = $patient->id;
                $field = preg_replace("/__OE_PATIENT_ID_([0-9]*)__/", $pid, $field);

                // first check the file has not already been imported:

                $resource_type = 'MeasurementVisualFieldHumphrey';
                $service = Yii::app()->service->getService($resource_type);
                $fieldObject = $fhirMarshal->parseXml($field);
                $tx = Yii::app()->db->beginTransaction();
                $ref = $service->fhirCreate($fieldObject);
                $tx->commit();
                $refId = $ref->getId();
				if (!$refId) {
					// move to error dir
					// this means there's been a problem constructing
					// the object - possibly a bad strategy ID or pattern ID?
                    echo "Moving " . basename($file) . " to error directory; no object could be created from "
                    . $fieldObject->file_reference . PHP_EOL;
                    $this->move($this->errorDir, $file);
					continue;
				}
                $measurement = OphInVisualfields_Field_Measurement::model()->findByPk($refId);
	//echo 'null? ' . var_dump($measurement) . PHP_EOL;
                $study_datetime = $measurement->study_datetime;

                // does the user have any legacy field events associated with them?
                $eventType = EventType::model()->find("class_name=:class_name", array(":class_name" => "OphInVisualfields"));
                if (!isset($eventType)) {
                    echo "Correct event type, OphInVisualfields, is not present; quitting...\n";
                    echo "OphInVisualfields is required in order to import legacy field images.\n";
                    exit(1);
                }
                $legacyEpisode = Episode::model()->find("legacy=1 AND patient_id=:patient_id", array(":patient_id" => $pid));
                if (count($legacyEpisode) == 0) {
                    $episode = new Episode;
                    $episode->legacy = 1;
                    $episode->start_date = date("y-mm-dd H:i:s");
                    $episode->patient_id = $pid;
                    $episode->save();
                    echo "Successfully created new legacy episode for patient in " . basename($file) . "\n";
                    $this->newEvent($episode, $eventType, $measurement);
                } else {
                    echo "Legacy episode already present for patient in " . basename($file) . "\n";
                    // so if we've got a legacy episode that means there's possibly an event with the 
                    // image bound to it - let's look for it:
                    $eye = $fieldObject->eye;
                    if ($eye == 'L') {
                        // we're looking for the other eye:
                        $eye = Eye::RIGHT;
                    } else {
                        $eye = Eye::LEFT;
                    }
                    // base time on interval defined by user, a anrrow time slot that the test falls within:
                    $startCreatedTime = new DateTime($study_datetime);
                    $endCreatedTime = new DateTime($study_datetime);
                    $startCreatedTime->sub(new DateInterval($this->interval));
                    $endCreatedTime->add(new DateInterval($this->interval));

                    $criteria = new CdbCriteria;
                    if ($interval) {
                        // we're looking for all events that are bound to a legacy episode,
                        // for the given patient, looking for the last created test -
                        // this accounts for multiple tests per eye - the implication
                        // being that the newest test overrides the last test for the same eye
                        // (e.g. when a mistake is made and the test is re-ran):

                        $criteria->condition = 't.event_date >= STR_TO_DATE("' . $startCreatedTime->format('Y-m-d H:i:s')
                                . '", "%Y-%m-%d %H:%i:%s") and t.event_date <= STR_TO_DATE("' . $endCreatedTime->format('Y-m-d H:i:s')
                                . '", "%Y-%m-%d %H:%i:%s") and event_type_id=' . $eventType->id
                                . ' and t.deleted = 0 and ep.deleted = 0 and ep.legacy = 1 and ep.patient_id = :patient_id';
                        $criteria->join = 'join episode ep on ep.id = t.episode_id';
                        $criteria->order = 't.event_date desc';
                        $criteria->params = array(':patient_id' => $pid);
//			$criteria->distinct = true;
		}
                    // Of events, there can only be one or none:
                    $events = Event::model()->findAll($criteria);
					
                    if (count($events) > 0) {
                        // test already picked up - bind it to the event
						
                        $image = Element_OphInVisualfields_Image::model()->find("event_id=:event_id", array(":event_id" => $events[0]->id));
			
                        try {
				if ($measurement->eye->name == 'Left') {
					$image->left_field_id = $measurement->id;
                        	} else {
                        		$image->right_field_id = $measurement->id;
                        	}
	                        $image->save();
        	                $this->move($this->archiveDir, $file);
                	        echo "Successfully bound " . basename($file) . " to existing event.\n";
			} catch (Exception $ex) {
				echo $ex . PHP_EOL;
				$this->move($this->errorDir, $file);
			}
                    } else if (count($events) == 0) {
                        // existing legacy episode needs new event - first time a test taken for this episode::
                        $this->newEvent($legacyEpisode, $eventType, $measurement);
                        $this->move($this->archiveDir, $file);
                    } else { // this block should never get caught now, since we're only picking up one event...
                        echo 'events=' . count($events);
                        foreach ($events as $event) {
                            echo "Bad event: " . $event->id . PHP_EOL;
                        }
                    }
                }
            }
        }
    }

    private function newEvent($episode, $eventType, $measurement) {
        // now bind a new event to the new legacy episode:
        $event = new Event;
        $event->episode_id = $episode->id;
        $event->event_type_id = $eventType->id;
        $event->created_user_id = 1;
        $event->event_date = $measurement->study_datetime;
        $event->save(true, null, true);
        $event->event_date = $measurement->study_datetime;
        $event->save(true, null, true);

        $image = new Element_OphInVisualfields_Image;
        $image->event_id = $event->id;
        if ($measurement->eye->name == 'Left') {
            $image->left_field_id = $measurement->id;
        } else {
            $image->right_field_id = $measurement->id;
        }
        $image->save();
        echo "Successfully added " . basename($measurement->cropped_image->name) . " to new event id " . $event->id. "\n";
    }

    /**
     * Moves both the .pmes and .fmes file.
     *
     * @param type $toDir
     * @param type $file
     */
    private function move($toDir, $file) {
        $file = basename($file);
        rename($this->importDir . $file, $toDir . $file);
    }

    /**
     * @param string $file
     * @return string
     */
    private function checkSeparator($file) {
        if (substr($file, -1) != DIRECTORY_SEPARATOR) {
            $file = $file . DIRECTORY_SEPARATOR;
        }
        return $file;
    }

}
