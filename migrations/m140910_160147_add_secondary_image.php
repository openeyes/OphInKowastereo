<?php

class m140910_160147_add_secondary_image extends OEMigration
{
	// Use safeUp/safeDown to do migration with transaction
	public function safeUp()
	{
            $this->addColumn('ophinkowastereo_field_measurement',
                    "image_2ndary_id", "integer unsigned default null");
            $this->addForeignKey('ophinkowastereo_field_measurement_image_2ndary_fk',
                    'ophinkowastereo_field_measurement', "image_2ndary_id", 
                    "protected_file", "id");
	}

	public function safeDown()
	{
            $this->dropForeignKey('ophinkowastereo_field_measurement_image_2ndary_fk',
                    "ophinkowastereo_field_measurement");
            $this->dropColumn('ophinkowastereo_field_measurement', "image_2ndary_id");
	}
}