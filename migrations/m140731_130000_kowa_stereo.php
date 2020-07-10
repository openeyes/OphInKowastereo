<?php

class m140731_130000_kowa_stereo extends OEMigration
{
	public function safeUp()
	{

		$this->createOETable('ophinkowastereo_result_assessment', array(
			'id' => 'pk',
			'name' => 'varchar(128) NOT NULL',
			'display_order' => 'int(10) unsigned NOT NULL DEFAULT 1',
			'default' => 'tinyint(1) unsigned NOT NULL DEFAULT 0',
			'deleted' => 'tinyint(1) unsigned not null',
			'active' => 'int(11) unsigned not null',
		),
			true);


		$this->createOETable('ophinkowastereo_condition_ability', array(
			'id' => 'pk',
			'name' => 'varchar(128) NOT NULL',
			'display_order' => 'int(10) unsigned NOT NULL DEFAULT 1',
			'default' => 'tinyint(1) unsigned NOT NULL DEFAULT 0',
			'deleted' => 'tinyint(1) unsigned not null',
		),true);


		$this->createOETable(
			'ophinkowastereo_field_measurement',
			array(
				'id' => 'pk',
				'patient_measurement_id' => 'integer not null',
				'eye_id' => 'integer unsigned not null',
				'image_id' => 'integer unsigned not null',
				'study_datetime' => 'datetime not null',
				'source' => 'text',
				'constraint ophinkowastereo_field_measurement_pm_id_fk foreign key (patient_measurement_id) references patient_measurement (id)',
				'constraint ophinkowastereo_field_measurement_im_id_fk foreign key (image_id) references protected_file (id)',
			),
			true
		);

		$this->createOETable(
			'et_ophinkowastereo_image',
			array(
				'id' => 'pk',
				'event_id' => 'integer unsigned not null',
				'eye_id' => 'integer unsigned not null',
				'left_field_id' => 'integer',
				'right_field_id' => 'integer',
				'constraint et_ophinkowastereo_image_event_id_fk foreign key (event_id) references event (id)',
				'constraint et_ophinkowastereo_image_lf_id_fk foreign key (left_field_id) references ophinkowastereo_field_measurement (id)',
				'constraint et_ophinkowastereo_image_rf_id_fk foreign key (right_field_id) references ophinkowastereo_field_measurement (id)',
			),
			true
		);

		$this->createOETable(
			'et_ophinkowastereo_condition',
			array(
				'id' => 'pk',
				'event_id' => 'integer unsigned not null',
				'other' => 'text',
				'glasses' => 'boolean not null',
				'constraint et_ophinkowastereo_condition_event_id_fk foreign key (event_id) references event (id)',
			),
			true
		);

		$this->createOETable('et_ophinkowastereo_condition_ability_assignment', array(
			'id' => 'pk',
			'element_id' => 'int(11) NOT NULL',
			'ophinkowastereo_condition_ability_id' => 'int(11) NOT NULL',
			'deleted' => 'tinyint(1) unsigned not null',
			'KEY `et_ophinkowastereo_condition_ability_assignment_lmui_fk` (`last_modified_user_id`)',
			'KEY `et_ophinkowastereo_condition_ability_assignment_cui_fk` (`created_user_id`)',
			'KEY `et_ophinkowastereo_condition_ability_assignment_ele_fk` (`element_id`)',
			'KEY `et_ophinkowastereo_condition_ability_assignment_lku_fk` (`ophinkowastereo_condition_ability_id`)',
			'CONSTRAINT `et_ophinkowastereo_condition_ability_assignment_ele_fk` FOREIGN KEY (`element_id`) REFERENCES `et_ophinkowastereo_condition` (`id`)',
			'CONSTRAINT `et_ophinkowastereo_condition_ability_assignment_lku_fk` FOREIGN KEY (`ophinkowastereo_condition_ability_id`) REFERENCES `ophinkowastereo_condition_ability` (`id`)',
		),
			true
		);

		$this->createOETable(
			'et_ophinkowastereo_comments',
			array(
				'id' => 'pk',
				'event_id' => 'integer unsigned not null',
				'comments' => 'text not null',
				'constraint et_ophinkowastereo_comments_event_id_fk foreign key (event_id) references event (id)',
			),
			true
		);

		$this->createOETable(
			'et_ophinkowastereo_result',
			array(
				'id' => 'pk',
				'event_id' => 'integer unsigned not null',
				'other' => 'text',
				'constraint et_ophinkowastereo_result_event_id_fk foreign key (event_id) references event (id)',
			),
			true
		);

		$this->createOETable('et_ophinkowastereo_result_assessment_assignment', array(
				'id' => 'pk',
				'element_id' => 'int(11) NOT NULL',
				'ophinkowastereo_result_assessment_id' => 'int(11) NOT NULL',
				'deleted' => 'tinyint(1) unsigned not null',
				'KEY `et_ophinkowastereo_result_assessment_assignment_ele_fk` (`element_id`)',
				'KEY `et_ophinkowastereo_result_assessment_assignment_lku_fk` (`ophinkowastereo_result_assessment_id`)',
				'CONSTRAINT `et_ophinkowastereo_result_ass_ele_fk` FOREIGN KEY (`element_id`) REFERENCES `et_ophinkowastereo_result` (`id`)',
				'CONSTRAINT `et_ophinkowastereo_result_ass_lku_fk` FOREIGN KEY (`ophinkowastereo_result_assessment_id`) REFERENCES `ophinkowastereo_result_assessment` (`id`)',
			),
			true);

		$event_type_id = $this->insertOEEventType('Kowa Stereo', 'OphInKowastereo', 'In');
		$this->insertOEElementType(
			array(
				'Element_OphInKowastereo_Image' => array('name' => 'Image', 'required' => true),
				'Element_OphInKowastereo_Condition' => array('name' => 'Condition', 'required' => true),
				'Element_OphInKowastereo_Comments' => array('name' => 'Comments', 'required' => true),
				'Element_OphInKowastereo_Result' => array('name' => 'Result', 'required' => true),
			),
			$event_type_id
		);

		$this->insert('episode_summary_item', array('event_type_id' => $event_type_id, 'name' => 'Kowa Stereo History'));

		$this->initialiseData(__DIR__);
	}

	public function safeDown()
	{
		$event_type_id = $this->dbConnection->createCommand()->select('id')->from('event_type')->where('class_name = ?', array('OphInKowastereo'))->queryScalar();

		$this->delete('episode_summary_item', 'event_type_id = ? and name = ?', array($event_type_id, 'Kowa Stereo History'));

		$this->delete('element_type', 'event_type_id = ?', array($event_type_id));
		$this->delete('event_type', 'id = ?', array($event_type_id));

		$this->dropTable('et_ophinkowastereo_image');
		$this->dropTable('et_ophinkowastereo_image_version');
		$this->dropTable('et_ophinkowastereo_condition');
		$this->dropTable('et_ophinkowastereo_condition_version');
		$this->dropTable('et_ophinkowastereo_comments');
		$this->dropTable('et_ophinkowastereo_comments_version');
		$this->dropTable('et_ophinkowastereo_result');
		$this->dropTable('et_ophinkowastereo_result_version');

		$this->dropTable('ophinkowastereo_field_measurement');
		$this->dropTable('ophinkowastereo_field_measurement_version');

		$this->dropTable('ophinkowastereo_ability');
		$this->dropTable('ophinkowastereo_ability_version');
		$this->dropTable('ophinkowastereo_assessment');
		$this->dropTable('ophinkowastereo_assessment_version');
	}
}
