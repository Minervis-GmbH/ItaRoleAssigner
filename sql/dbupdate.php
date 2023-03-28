<#1>
<?php
if(!$ilDB->tableExists('crnhk_itaroass_conf')){
    $fields = array(
        'id' => array(
            'type' => 'integer',
            'length' => 2,
            'notnull' => true,
            'default' => 1
        ),
        'delete_roles' => array(
            'type' => 'integer',
            'length' => 1,
            'default' => 0
        ),
        'object_links' => array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => false,
            'default' => 1
        ),
   

    );
    $ilDB->createTable('crnhk_itaroass_conf', $fields);
    $ilDB->addPrimaryKey('crnhk_itaroass_conf', array('id'));
}

if(!$ilDB->tableExists('crnhk_itaroass_ass')){
    $fields = array(
        'orgunit' => array(
            'type' => 'integer',
            'length' => 8,
            'notnull' => true

        ),
        'role' => array(
            'type' => 'integer',
            'length' => 8,
            'notnull' => true
        )
        
    );
    $ilDB->createTable('crnhk_itaroass_ass', $fields);
    $ilDB->addPrimaryKey('crnhk_itaroass_ass', array('orgunit', 'role'));
}

?>
