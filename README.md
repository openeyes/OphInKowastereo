# OphInKowaStereo

This module has now been archived.

## Archive notes

### External file behaviour

This module leveraged the `PatientMeasurement` pattern to attach external measurement files to its events. This uses the `measurement_reference` table to link a patient measurement to an event, and the files themselves were tracked by the `ProtectedFile` model.

For expediency, it was determined that it was not necessary to maintain this data in the archive - the files themselves remain in the Kowa system, and the study_datetime are tracked in the module records. Reconciliation at any point could therefore be facilitated through this information lookup.

As a result, the following data loss arises:

1. All `patient_measurement` entries for Kowa are removed from the db.
1. In turn the `measurement_reference` rows are removed.

In an ideal world, we would delete the relevant `ProtectedFile` instances that have been defined for the files being tracked. But time is limited and deleting data is not not something that should be undertaken lightly. Therefore, at the time of writing, this exercise has not been undertaken. If it proves necessary, two approaches may be taken:

1. write a yiic command in this module that uses the protected file references to delete the database entres and files from the system.
1. write an "orphaned protected file" clean up script in core that could do a similar thing, but would of course work for all possible causes of orphaned files. This would need to work on files over a certain age, and would assume that foreign key constraints would be defined for all references (i.e. leveraging the `RESTRICT` instruction of the `ON DELETE` clause of the FK definition)

User foreign key constraints remain in archive tables, this may cause issues deleting legacy users.

### SQL to test migration

Build at least one Kowa Stereo event

`
SELECT id FROM event_type WHERE name="Kowa Stereo" INTO @event_type_id;     
SELECT id FROM institution ORDER BY RAND() LIMIT 1 INTO @institution_id;

INSERT INTO event (event_date, event_type_id, institution_id) values (now(), @event_type_id, @institution_id);
SELECT last_insert_id() INTO @event_id;

INSERT INTO et_ophinkowastereo_comments (event_id, comments) values (@event_id, "Foo");

SELECT id FROM ophinkowastereo_condition_ability ORDER BY RAND() LIMIT 1 INTO @condition_ability_id;
INSERT INTO et_ophinkowastereo_condition (event_id, other, glasses) values (@event_id, "Bar", 0);
SELECT last_insert_id() INTO @condition_element_id;
INSERT INTO et_ophinkowastereo_condition_ability_assignment (element_id, ophinkowastereo_condition_ability_id, deleted) values (@condition_element_id, @condition_ability_id, 0);

SELECT id FROM ophinkowastereo_result_assessment ORDER BY RAND() LIMIT 1 INTO @result_assessment_id;

INSERT INTO et_ophinkowastereo_result (event_id, other) values (@event_id, "Foo Bar");
SELECT last_insert_id() INTO @result_element_id;
INSERT INTO et_ophinkowastereo_result_assessment_assignment (element_id, ophinkowastereo_result_assessment_id, deleted) values (@result_element_id, @result_assessment_id, 0);

SELECT id FROM protected_file ORDER BY RAND() LIMIT 1 INTO @protected_file_id;
SELECT id FROM patient ORDER BY RAND() LIMIT 1 INTO @patient_id;
SELECT id FROM measurement_type WHERE class_name = "OphInKowastereo_Field_Measurement" INTO @measurement_type_id;
INSERT INTO patient_measurement (patient_id, measurement_type_id) values (@patient_id, @measurement_type_id);
SELECT last_insert_id() INTO @patient_measurement_id;
INSERT INTO ophinkowastereo_field_measurement (patient_measurement_id, eye_id, image_id, study_datetime) values (@patient_measurement_id, 3, @protected_file_id, now());
`

Make a note of the number of kowa events in databse.
`SELECT COUNT(*) FROM event WHERE event_type_id=@event_type_id;`

Run the migration

Should match previous count
`SELECT COUNT(*) FROM archive_ophinkowastereo_event;`

Should now be zero
`SELECT COUNT(*) FROM event WHERE event_type_id=@event_type_id;`