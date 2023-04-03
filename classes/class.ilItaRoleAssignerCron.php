<?php

/**
 * Class ilItaRoleAssignerCron
 *
 * @author  Jephte Abijuru <jephte.abijuru@minervis.com>
 */
class ilItaRoleAssignerCron extends ilCronJob
{

    const CRON_JOB_ID = ilItaRoleAssignerPlugin::PLUGIN_ID;
    const PLUGIN_CLASS_NAME = ilItaRoleAssignerPlugin::class;
    const UDF_USER_ASSIGNMENT = "OE-Role-Membership";

    /**
     * @var ilItaRoleAssignerPlugin
     */
    private $object;
    /**
     * @var ilDBInterface
     */
    private $database;
    /**
     * @var \ILIAS\DI\Container|mixed
     */
    private $dic;

    /**
     * @var ilObjOrgUnitTree
     */
    private $ou_tree;


    /**
     *
     */
    public function __construct( )
    {
        global $DIC;
        $this->dic = $DIC;
        $this->database = $this->dic->database();
        $this->object = ilItaRoleAssignerPlugin::getInstance();
        $this->ou_tree = ilObjOrgUnitTree::_getInstance();
    }


    /**
     * @return string
     */
    public function getId() : string
    {
        return self::CRON_JOB_ID;
    }


    /**
     * @return string
     */
    public function getTitle() : string
    {
        
        return ilItaRoleAssignerPlugin::PLUGIN_NAME . ": " .  $this->object->txt("cron_title");
    }


    /**
     * @return string
     */
    public function getDescription() : string
    {
        return ilItaRoleAssignerPlugin::PLUGIN_NAME . ": " .  $this->object->txt("cron_description");
    }


    /**
     * @return bool
     */
    public function hasAutoActivation() : bool
    {
        return true;
    }


    /**
     * @return bool
     */
    public function hasFlexibleSchedule() : bool
    {
        return true;
    }


    /**
     * @return int
     */
    public function getDefaultScheduleType() : int
    {
        return ilCronJob::SCHEDULE_TYPE_DAILY;
    }


    /**
     * @return null
     */
    public function getDefaultScheduleValue()
    {
        return 1;
    }



    /**
     * @inheritDoc
     */
    public function run() : ilCronJobResult
    {
        $cron_result = new ilCronJobResult();


        try {
            $this->createUserDefinedFieldID(self::UDF_USER_ASSIGNMENT);
            $this->cleanAssignmentstables();
            $failed_counter = $this->_assignUsers();

            $cron_result->setStatus(ilCronJobResult::STATUS_OK);
            $cron_result->setMessage($failed_counter . " have failed");
            $this->deleteAssignments();
            
        } catch (Exception $e) {
            $cron_result->setStatus(ilCronJobResult::STATUS_FAIL);
        }
        return $cron_result;
    }

    public  function _assignUsers()
    {
        $sql = "SELECT ass.role AS role, ref.ref_id AS ou_ref_id  FROM crnhk_itaroass_ass ass INNER JOIN object_reference ref ON ass.orgunit = ref.obj_id";
        $res = $this->database->query($sql);
        $assignments = array();
        while($row = $this->database->fetchAssoc($res)){
            $assignments [] = [$row['ou_ref_id'] => $row['role'] ];
        }
        $this->dic->logger()->root()->dump($assignments);
        $counter = 0;
        $udf_id = $this->getUserDefinedFieldID(self::UDF_USER_ASSIGNMENT);
        $udf_key = 'f_' . $udf_id;
        $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($assignments));
        $run_count = 0;

        foreach ($iterator as $ou_refid => $role){
            $staff = $this->ou_tree->getEmployees($ou_refid, true);
            $staff = array_merge($staff, $this->ou_tree->getSuperiors($ou_refid, true));
            //$role
            foreach($staff as $usr_id){
                if ($this->dic->rbac()->admin()->assignUser($role, $usr_id)){
                    $user = new ilObjUser($usr_id);
                    $assigned_roles = [];
                    if ($run_count > 0){
                        $user_defined_data = $user->getUserDefinedData();
                        $assigned_roles = array_filter(explode('||', $user_defined_data[$udf_key]));
                    }
                    if(!in_array($role, $assigned_roles)){
                        $assigned_roles [] = $role;
                        $user_data[$udf_id] = implode('||', $assigned_roles);
                        $user->setUserDefinedData($user_data);
                        $user->update();
                    }
                }else{
                    $counter = $counter + 1;
                }

            }
            $run_count = $run_count + 1;
        }
        return $counter;
    }


    /**
     * @return array
     */
    private function loadAssignments(): array
    {
        $sql = "SELECT ass.role AS role, ref.ref_id AS ou_ref_id  FROM crnhk_itaroass_ass ass INNER JOIN object_reference ref ON ass.orgunit = ref.obj_id";
        $res = $this->database->query($sql);
        $assignments = array();
        while($row = $this->database->fetchAssoc($res)){
            $assignments [$row['ou_ref_id']] = $row['role'];
        }
        return $assignments;
    }
    public function deleteAssignments()
    {
        $sql = "SELECT  *  FROM crnhk_itaroass_ass ";
        $res = $this->database->query($sql);
        $employee_roles = $this->ou_tree->getEmployeeRoles();

        $assignments = array();
        while($row = $this->database->fetchAssoc($res)){
            $assignments [$row['orgunit']] = $row['role'];
        }
        foreach($assignments as $orgunit => $role){
            $staff = $this->ou_tree->getEmployees($orgunit);
            //$staff = array_merge($staff, $this->ou_tree->getSuperiors($ou_refid));

        }


    }
    public function cleanAssignmentstables()
    {
        $udf_id = $this->getUserDefinedFieldID(self::UDF_USER_ASSIGNMENT);
        $query = "SELECT user.usr_id, udf.value FROM usr_data user 
                    INNER JOIN udf_text udf ON udf.field_id= ". $udf_id . " AND (udf.value IS NOT NULL OR udf.value <> '') AND udf.usr_id = user.usr_id";
        $res = $this->database->query($query);
        while($row = $this->database->fetchAssoc($res)){
            $user = new ilObjUser($row['usr_id']);
            $user_data[$this->getUserDefinedFieldID(self::UDF_USER_ASSIGNMENT)] = '';
            $user->setUserDefinedData($user_data);
            $user->update();
            foreach (array_filter(explode('||',$row['value'])) as $role){
                $this->dic->rbac()->admin()->deassignUser($role, $user->getId());
            }

        }

    }

    /**
     * @param string $key
     * @return int
     */
    protected function createUserDefinedFieldID(string $key) : int
    {
        $field_id = $this->getUserDefinedFieldID($key);

        if ($field_id === null) {
            $user_defined_field = ilUserDefinedFields::_getInstance();
            $user_defined_field->setFieldName($key);
            $user_defined_field->setFieldType(UDF_TYPE_TEXT);
            $user_defined_field->add();
            $field_id = $this->getUserDefinedFieldID($key);

        }
        return $field_id;
    }

    /**
     * @param string $key
     * @return bool
     */
    protected function deleteUserDefinedFieldID(string $key) : bool
    {
        $field_id = $this->getUserDefinedFieldID($key);
        if ($field_id !== null) {
            $udf = ilUserDefinedFields::_getInstance();
            return $udf->delete($field_id);
        }
        return true;
    }


    /**
     * @param string $key
     * @return int|null
     */
    protected function getUserDefinedFieldID(string $key): ?int/*: ?int*/
    {
        $result = $this->dic->database()->queryF('SELECT field_id FROM udf_definition WHERE field_name=%s', ["text"],
            [$key]);

        if (($row = $result->fetchAssoc()) !== false) {
            return intval($row["field_id"]);
        } else {
            return null;
        }
    }

}