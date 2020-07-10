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
class Gif2JpegCommand extends CConsoleCommand {


    /**
     * Take a list of real patient identifiers that appear in a collection
     * of FMES files, and remove the 'real' PID in the FMES file in favour
     * of 
     * 
     * @param type $realPidFile
     * @param type $anonPidFile 
     */
    public function actionTransform($archiveDir) {
	print $archiveDir . PHP_EOL;
	$files = glob($archiveDir . "/*.jpg");
	echo count($files) . PHP_EOL;
	foreach ($files as $file) {
		echo basename($file) . PHP_EOL;
		$pfs = ProtectedFile::model()->findAll("name=\"" . basename($file) . "\"");
//		echo count($pfs) . PHP_EOL;
		foreach($pfs as $pf) {
			echo $pf->getPath() . PHP_EOL;
			$dims = getimagesize($pf->getPath());
			echo $dims[3] . PHP_EOL;
		}
	}
	return;

        $realPids = file_get_contents($realPidFile);
        $anonPids = file_get_contents($anonPidFile);
        $rPids = explode(PHP_EOL, $realPids);
        $aPids = explode(PHP_EOL, $anonPids);
        // make sure PID count is equal:
        if (count($rPids) != count($aPids)) {
            echo 'Error: PID counts do not match; file contents must match 1-1' . PHP_EOL;
            exit(1);
        }
        // check all real patients exist:
        foreach ($aPids as $pid) {
            if ($pid) {
                if (count(Patient::model()->find("hos_num='" . $pid . "'")) < 1) {
                    echo 'Failed to find anonymous patient ' . $pid . PHP_EOL;
                    exit(1);
                }
            }
        }
        // now check that all 'real' patients are listed in the files:
        $entries = array();
        // build up an array of matches we've encountered so far, and if it's
        // been matched before, ignore it.

        $smgr = Yii::app()->service;
        $fhirMarshal = Yii::app()->fhirMarshal;
        if ($entry = glob($fmesDir . '/*.fmes')) {
            foreach ($entry as $file) {
                $field = file_get_contents($file);
                $fieldObject = $fhirMarshal->parseXml($field);
                $match = $this->getHosNum($file, $field);
                if (!in_array($match, $entries)) {
                    // only add it if it's in the list of real patient IDs:
                    if (in_array($match, $rPids)) {
                        array_push($entries, $match);
                    }
                }
            }
        }
        // now create new FMES files
        // need to go through each one, pairing anonymised IDs with real ones,
        // replacing the real ID with the anonymised ID; note that we also
        // need to swap out the image and do some redaction: 

        if ($entry = glob($fmesDir . '/*.fmes')) {
            foreach ($entry as $file) {
                $field = file_get_contents($file);
                $fieldObject = $fhirMarshal->parseXml($field);
                // swap out hos nums:
                $match = $this->getHosNum($file, $field);
                if (in_array($match, $rPids)) {
                    $index = array_search($match, $rPids);
                    $anonPid = $aPids[$index];
                    unset($fieldObject->patient_id);
                    $fieldObject->patient_id = "__OE_PATIENT_ID_" . $anonPid . "__";
                    echo 'replacing ' . $match . ' with ' . $anonPid . PHP_EOL;
                } else {
                    // not interested, move on:
                    continue;
                }
                // now swap out the actual image. This is slightly involved -
                // we need to write the image to temporary file, perform
                // image operations on it to anonymise PID, DoB etc., 
                // step 1: extract image:
                $image = base64_decode($fieldObject->image_scan_data);
                unset($fieldObject->image_scan_data);
                // now redact it - we need to perform imagemagick operations:
                $img = 'img.gif';
                file_put_contents($img, $image);
                $image = new Imagick($img);
                $this->fillImage($image);
                $image->writeImage($img);
                $contents = file_get_contents($img);
                $fieldObject->image_scan_data = base64_encode($contents);
                $doc = new DOMDocument;
                file_put_contents($outputDir . '/' . basename($file), $fhirMarshal->renderXml($fieldObject));
                echo "Successfully written " . $file . PHP_EOL;
            }
        }
    }
}
