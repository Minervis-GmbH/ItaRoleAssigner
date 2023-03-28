<?php

require_once ("Customizing/global/plugins/Services/Cron/CronHook/ItaRoleAssigner/classes/class.ilItaRoleAssignerSearch.php");
require_once ("Customizing/global/plugins/Services/Cron/CronHook/ItaRoleAssigner/classes/gui/class.MultiSelectInputGUI.php");
require_once ("Customizing/global/plugins/Services/Cron/CronHook/ItaRoleAssigner/classes/gui/class.OrgUnitAjaxAutoComplete.php");
require_once ("Customizing/global/plugins/Services/Cron/CronHook/ItaRoleAssigner/classes/gui/class.ObjAjaxAutoComplete.php");
require_once ("Customizing/global/plugins/Services/Cron/CronHook/ItaRoleAssigner/classes/class.ilItaRoleAssignerAssTableGUI.php");

/**
 * 
 * Class ilItaRoleAssignerConfigGUI
 * @ilCtrl_Calls ilItaRoleAssignerConfigGUI: OrgUnitAjaxAutoComplete, ObjAjaxAutoComplete
 * @ilCtrl_isCalledBy OrgUnitAjaxAutoComplete: ilAdministrationGUI, ilUIPluginRouter, ilItaRoleAssignerConfigGUI
 */
class ilItaRoleAssignerConfigGUI extends ilPluginConfigGUI
{

    const PLUGIN_CLASS_NAME = ilItaRoleAssignerConfigGUI::class;
    const CMD_SAVE = "save";
    const TABLE_NAME_CONFIG = "crnhk_itaroass_conf";
    const TABLE_NAME_ASSIGNMENTS = "crnhk_itaroass_ass";
    const GET_ROLE_ID = "rid";
    const GET_ORGUNIT_ID = "ouid";
    const CMD_EDIT_ASSIGNMENT = "editAssignment";
    const CMD_DELETE_ASSIGNMENT = "deleteAssignment";
    const CMD_SAVE_EDIT = "saveEdit";

    private $db;
    private $dic;
    private $auto; 

    public function __construct()
    {
        global $DIC, $ilDB;
        $this->db = $ilDB;
        $this->dic = $DIC;
        $this->auto = new ilItaRoleAssignerSearch();
    }

    
	function performCommand($cmd)
	{

		switch ($cmd)
		{
			case "configure":
			case "save":
            case "doAutoComplete":
            case "editAssignment":
            case "deleteAssignment":
            case "saveEdit":
				$this->$cmd();
				break;
            default: 
                $cmd = "configure";
                $this->$cmd();

		}
	}

    /**
     * Configure screen
     */
    public function configure()
    {
        global $tpl;

        $form = $this->initConfigurationForm();
        $tpl->setContent($form->getHTML());
    }


    /**
     * Init configuration form.
     *
     * @return object form object
     */
    public function initConfigurationForm()
    {
        $this->getConfigFormFields();        
        $table = new ilItaRoleAssignerAssTableGUI($this->getPluginObject(), $this);
        $table->init();   
        return $table;

    }

    /**
     * Init configuration form.
     *
     * @return object form object
     */
    public function getConfigFormFields($default_ou = null, $default_role = null, $edit_assignments = false)
    {
        $toolbar = $this->dic->toolbar();

        $pl_obj = $this->getPluginObject();
        $rauto_ajax = new ObjAjaxAutoComplete('orgu');

        if($edit_assignments){
            $orgunit = new ilTextInputGUI($pl_obj->txt('orgunit'), 'orgunit');
            $orgunit->setRequired(true);
            $orgunit->setValue($default_ou);
            $orgunit->setDisabled(true);

        }else{
           
            $orgunit = new MultiSelectInputGUI($pl_obj->txt('orgunit'), 'orgunit', $pl_obj);
            $orgunit->setRequired(true);
            $orgunit->setOptions($rauto_ajax->getAllOrgUnits());
            $orgunit->setDefaultOption($default_ou);
        }
        
        $toolbar->addInputItem($orgunit, true);

        $role = new MultiSelectInputGUI($pl_obj->txt('role'), 'role', $pl_obj);
        $role->setOptions($rauto_ajax->getAllRoles());
        $role->setDefaultOption($default_role);
        $toolbar->addInputItem($role, true);   
        
        $cmd = !$edit_assignments ? self::CMD_SAVE : self::CMD_SAVE_EDIT;

        $mapper = ilSubmitButton::getInstance();
        $mapper->setCaption($pl_obj->txt('save'), false);
        $mapper->setCommand($cmd);
        $toolbar->addButtonInstance($mapper);

        if($edit_assignments){
            $this->dic->ctrl()->setParameter($this, self::GET_ORGUNIT_ID, $default_ou);
            $this->dic->ctrl()->setParameter($this, self::GET_ROLE_ID, $default_role);
        } 
        $toolbar->setFormAction($this->dic->ctrl()->getFormAction($this, $cmd));
        

    }
    /**
     * Save form input
     *
     */
    public function save()
    {
        $pl_obj = $this->getPluginObject();
        $role = $_POST['role'];
        $orgunit = $_POST['orgunit'];
        
        
        if(!$role || !$orgunit){
            ilUtil::sendFailure("Input role and/or organational unit should not be empty!", true);
        }

        //check if data exists to update or insert

        $rows = $this->db->query(
            "SELECT * FROM " . self::TABLE_NAME_ASSIGNMENTS . 
            " WHERE orgunit = " . $this->db->quote($orgunit, "integer") . 
            " AND role = " . $this->db->quote($role, "integer")
        );
        $num = $this->db->numRows($rows);
        if( $num == 0){
            //INSERT
            $this->db->manipulate( "INSERT INTO " .self::TABLE_NAME_ASSIGNMENTS .
                "(role, orgunit) VALUES (" .
                $this->db->quote($role, "integer") . ", " . $this->db->quote($orgunit, "integer") . ")"
            );
        }else{
            while($row = $this->db->fetchAssoc($rows)){
                if($row['orgunit'] !== $orgunit && $row['role'] !== $role){
                    $this->db->manipulate( "UPDATE " . self::TABLE_NAME_ASSIGNMENTS .
                    " SET role = " . $this->db->quote($role, "integer") . 
                    ", orgunit = " . $this->db->quote($orgunit, "integer")
                );
                }
            }

        }
        ilUtil::sendSuccess($pl_obj->txt("saving_invoked"), true);
        $this->dic->ctrl()->redirect($this);        
    }

    public function saveEdit()
    {
        $pl_obj = $this->getPluginObject();
        $role = $_POST['role'];
        
        $orgunit = intval(filter_input(INPUT_GET, self::GET_ORGUNIT_ID));
        $old_role = intval(filter_input(INPUT_GET, self::GET_ROLE_ID));

        
        if(!$role || !$orgunit){
            ilUtil::sendFailure("Input role and/or organational unit should not be empty!", true);
            $this->dic->ctrl()->redirect($this);
            return;
        }

        //check if data exists to update or insert

        $rows = $this->db->query(
            "SELECT * FROM " . self::TABLE_NAME_ASSIGNMENTS . 
            " WHERE orgunit = " . $this->db->quote($orgunit, "integer") . 
            " AND role = " . $this->db->quote($role, "integer")
        );
        $num = $this->db->numRows($rows);
        if( $num == 0){
            $this->db->manipulate( "UPDATE " . self::TABLE_NAME_ASSIGNMENTS .
                " SET role = " . $this->db->quote($role, "integer") . 
                " WHERE orgunit = " . $this->db->quote($orgunit, "integer") . 
                " AND role = " . $this->db->quote($old_role, "integer")
            );            
        }else{
            $this->db->manipulate( "DELETE FROM " . self::TABLE_NAME_ASSIGNMENTS .
                " WHERE orgunit = " . $this->db->quote($orgunit, "integer") . 
                " AND role = " . $this->db->quote($old_role, "integer")
            );                      

        }
        ilUtil::sendSuccess($pl_obj->txt("saving_invoked"), true);
        $this->dic->ctrl()->redirect($this);
    }


    public function createAssignmentsTable()
    {
        $data = [];
        $res = $this->db->query(
            "SELECT o1.obj_id ou_id, o1.title ou_title, o2.obj_id role_id, o2.title role_title FROM object_data o1 INNER JOIN  " . self::TABLE_NAME_ASSIGNMENTS . 
            " ass ON o1.type='orgu' AND o1.obj_id=ass.orgunit INNER JOIN object_data o2 ON o2.type='role' AND ass.role = o2.obj_id"
        );
        while($row = $this->db->fetchAssoc($res)){
            $data [] = $row;
        }

        return $data;
    }
    public function deleteAssignment()
    {
        $role_id=  intval(filter_input(INPUT_GET, self::GET_ROLE_ID));
        $ou_id=  intval(filter_input(INPUT_GET, self::GET_ORGUNIT_ID));

        $this->db->manipulateF(
            "DELETE FROM " . self::TABLE_NAME_ASSIGNMENTS. " WHERE role = %s AND orgunit = %s",
            array("integer", "integer"),
            array($role_id, $ou_id)
        );
        $this->dic->ctrl()->redirect($this);  

    }
    public function editAssignment()
    {
        $role_id=  intval(filter_input(INPUT_GET, self::GET_ROLE_ID));
        $ou_id=  intval(filter_input(INPUT_GET, self::GET_ORGUNIT_ID));
        $this->getConfigFormFields($ou_id, $role_id, true);
        
        //$this->dic->ctrl()->redirect($this);  

    }
    

    public function doAutoComplete()
    {
        $res = $this->auto->search($_REQUEST['term']);
        echo json_encode($res);
        exit;

    }
}
