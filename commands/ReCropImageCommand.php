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
class ReCropImageCommand extends CConsoleCommand
{

	public function getHelp()
	{
		return "Usage: recropimage gif --dir=[dir] --dims=[x,y,w,h]"
				. "\n\nTrawl the database and remove all cropped images; replace the cropped image with the image specified by the given dimensions."
				. "Only images of the correct size (2400x3180) are transformed.\n";
	}

	/**
	 * Take a list of real patient identifiers that appear in a collection
	 * of FMES files, and remove the 'real' PID in the FMES file in favour
	 * of 
	 * 
	 * @param type $realPidFile
	 * @param type $anonPidFile 
	 */
	public function actionCrop($dir='/var/www/protected/files', $dims='1328x560,776x864')
	{
		$dimensions = explode(',', $dims);
		$xy = explode('x', $dimensions[0]);
		$wh = explode('x', $dimensions[1]);
		$src_x = $xy[0];
		$src_y = $xy[1];
		$dest_w = $wh[0];
		$dest_h = $wh[1];
		// find all cropped images in ophinvisualfields_field_measurement->cropped_image_id:
		$fields = OphInVisualfields_Field_Measurement::model()->findAll();
		foreach ($fields as $field) {
			$cropped_image_id = $field->cropped_image_id;
			$original = ProtectedFile::model()->findByPk($field->image_id);
			$file = ProtectedFile::model()->findByPk($field->cropped_image_id);
			// if the value isnt set, move on
			if (!$file || !$original) {
				continue;
			}
			// test if the given file actually exists - if it does, delete it (from DB and FS):
			if (file_exists($file->getPath())) {
				echo "Removing " . $file->getPath() . PHP_EOL;
				unlink($file->getPath());
			}
			// next step, take image_id and open image:
			if (file_exists($original->getPath())) {
				// create new cropped image from it:
				$image = new Imagick($original->getPath());
				$geo = $image->getImageGeometry();
				// only modify the main image, not the thumbnails:
				if ($geo['width'] == 2400
						&& $geo['height'] == 3180) {
					$cropped_file = \ProtectedFile::createForWriting($file->name);
					$cropped_file->mimetype = 'image/gif';
					$cropped_file->name = $file->name;
					$src = imagecreatefromgif($original->getPath());
					$dest = imagecreatetruecolor($dest_w, $dest_h);
					imagecopy($dest, $src, 0, 0, $src_x, $src_y, $dest_w, $dest_h);
					imagegif($dest, $cropped_file->getPath());
					$cropped_file->save();
					
					echo 'Created ' . $cropped_file->getPath() . PHP_EOL;
					// relink:
					$cropped_id = $field->cropped_image_id;
					$field->cropped_image_id = $cropped_file->id;
					$field->save();
					echo 'Successfully unlinked file ' . $cropped_id . ' with new cropped image ID ' . $field->cropped_image_id . PHP_EOL;
				}
			}
		}
	}

}
