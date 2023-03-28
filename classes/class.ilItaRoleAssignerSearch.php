<?php


/**
 * 
 * Class ilItaRoleAssignerConfigGUI
 */
class ilItaRoleAssignerSearch {
    const TYPE_ROLE = 0;
    const TYPE_OE   = 1;
    const MAX_ENTRIES = 30;
    public function __construct()
    {
        
    }
    public function search(string $title, $type = self::TYPE_OE)
    {
        global $ilDB;
        $object_type = $type == self::TYPE_OE? "orgu" : "role";

        $query = "SELECT * FROM object_data WHERE title LIKE '%". $title . "%' AND type = '" . $object_type . "'" ;
        $res = $ilDB->query($query);
        $objects = array();
        while($row = $ilDB->fetchAssoc($res)){
            $r = array();
            $r['value'] = $row['title'];
            $r['id'] = $row['obj_id'];
            $r['label'] = $row['title'];
            $objects [] = $r;
        }
        return array( 'items' => $objects);

    }
}