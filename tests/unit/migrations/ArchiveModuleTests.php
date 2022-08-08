<?php
/**
 * (C) Copyright Apperta Foundation 2021
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU Affero General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @link http://www.openeyes.org.uk
 *
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (C) 2021, Apperta Foundation
 * @license http://www.gnu.org/licenses/agpl-3.0.html The GNU Affero General Public License V3.0
 */

namespace OEModule\OphInKowastereo\tests\unit\migrations;

/**
 * Class ArchiveModuleTest
 *
 * @package OEModule\OphInKowastereo\tests\unit\migrations
 * @covers \OEModule\OphInKowastereo\migrations\m220808_135526_archive_module
 * @group sample-data
 * @group migration
 * @group ophinkowastereo
 */

class ArchiveModuleTest extends \OEDbTestCase
{
    protected $db_connection;

    public function setUp()
    {
        parent::setUp();
        $this->db_connection = \Yii::app()->db;
    }

    /** @test */
    public function archive_migration_correctly_moves_existing_kowastereo_event_data_to_archive_table_and_renames_tables () {
        $sql = <<<EOSQL
            SELECT id FROM event_type WHERE name="Kowa Stereo" INTO @event_type_id;     
            SELECT id FROM institution ORDER BY RAND() LIMIT 1 INTO @institution_id;

            INSERT INTO event (event_date, event_type_id, institution_id) values (now(), @event_type_id, @institution_id);
EOSQL;

        $this->executeSQL($sql);

        //$result = $this->db_connection->createCommand('Select * from event where event_type_id=50')->queryColumn();

        fwrite(STDERR, print_r([$this->db_connection->createCommand('Select * from event where event_type_id=50')->execute()], true));

        $this->assertEquals(true, false);
    }
 
    protected function doUpMigration(string $migration_name)
    {
        $migration = $this->instantiateMigration($migration_name);
        ob_start();
        $this->assertNotFalse($migration->safeUp());
        ob_get_clean();
    }

    protected function instantiateMigration(string $migration_name)
    {
        $path = \Yii::getPathOfAlias('application.modules.OphCoCorrespondence.migrations') . DIRECTORY_SEPARATOR . $migration_name . '.php';
        require_once($path);
        $instance = new $migration_name();
        $instance->setDbConnection($this->db_connection);
        return $instance;
    }

    private function executeSQL($sql) {
        return $this->db_connection->createCommand($sql)->execute();
    }
}