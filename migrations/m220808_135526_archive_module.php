<?php

class m220808_135526_archive_module extends OEMigration
{
	// Use safeUp/safeDown to do migration with transaction
	public function safeUp()
	{
		$archive_table_name = "archive_ophinkowastereo_event";

        // find event type id
        $kowa_stereo_event_type_id = $this->getDbConnection()
            ->createCommand('SELECT id FROM event_type WHERE name="Kowa Stereo"')
            ->queryScalar();

		// copy kowa events to new table
		$this->execute("CREATE TABLE $archive_table_name SELECT * FROM event WHERE event_type_id = $kowa_stereo_event_type_id");
		$this->execute("ALTER TABLE $archive_table_name ADD PRIMARY KEY (id)");

		// reassign event_id related foreign keys to archive table
		foreach([
			"et_ophinkowastereo_image" => "et_ophinkowastereo_image_event_id_fk",
			"et_ophinkowastereo_condition" => "et_ophinkowastereo_condition_event_id_fk",
			"et_ophinkowastereo_comments" => "et_ophinkowastereo_comments_event_id_fk",
			"et_ophinkowastereo_result" => "et_ophinkowastereo_result_event_id_fk"
			] as $table_name => $foreign_key_name) {
				$this->execute("ALTER TABLE $table_name DROP CONSTRAINT $foreign_key_name");	
				$this->execute("ALTER TABLE $table_name ADD CONSTRAINT $foreign_key_name FOREIGN KEY (event_id) REFERENCES $archive_table_name (id)");
		};

		// delete archived kowastereo events from event table
		$this->execute("DELETE FROM event WHERE event_type_id = $kowa_stereo_event_type_id");

		// rename tables with archive prefix
		foreach([
			"ophinkowastereo_result_assessment",
			"ophinkowastereo_condition_ability",
			"ophinkowastereo_field_measurement",
			"et_ophinkowastereo_image",
			"et_ophinkowastereo_condition",
			"et_ophinkowastereo_condition_ability_assignment",
			"et_ophinkowastereo_comments",
			"et_ophinkowastereo_result",
			"et_ophinkowastereo_result_assessment_assignment",
			"et_ophinkowastereo_hvf_post_ocr"
			] as $table_name) {
				$this->execute("RENAME TABLE $table_name TO archive_$table_name;");
		};
	}

	public function safeDown()
	{
        echo "m220808_135526_archive_module does not support migration down.\n";

		return false;
	}
}