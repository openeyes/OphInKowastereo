<?php

class m140731_130020_assessment_result_values extends CDbMigration
{
	public function up()
	{
		$this->insert('ophinkowastereo_result_assessment',array('id'=>1,'active'=>1,'name'=>'Nasal step'));
		$this->insert('ophinkowastereo_result_assessment',array('id'=>2,'active'=>1,'name'=>'Arcuate defect'));
		$this->insert('ophinkowastereo_result_assessment',array('id'=>3,'active'=>1,'name'=>'Paracentral defect'));
		$this->insert('ophinkowastereo_result_assessment',array('id'=>4,'active'=>1,'name'=>'Hemianopic defect'));
		$this->insert('ophinkowastereo_result_assessment',array('id'=>5,'active'=>1,'name'=>'Bitemporal defect'));
		$this->insert('ophinkowastereo_result_assessment',array('id'=>6,'active'=>1,'name'=>'Homonymous hemianopia'));
		$this->insert('ophinkowastereo_result_assessment',array('id'=>7,'active'=>1,'name'=>'Other'));
	}

	public function down()
	{
		$this->delete('ophinkowastereo_result_assessment');
	}
}
