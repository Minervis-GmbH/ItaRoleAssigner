<?php

/**

 * @version $Id$
 */
class ilItaRoleAssignerAssTableGUI extends ilTable2GUI {

    const TABLE_NAME_ASSIGNMENTS = "crnhk_itaroass_ass";

    private $plugin_object;

    /**
     * ilItaRoleAssignerAssTableGUI constructor.
     * @param $a_parent_obj
     * @param string $a_parent_cmd
     * @param string $a_template_context
     */
    function __construct($plugin, $a_parent_obj, $a_parent_cmd = '', $a_template_context = '')
    {
        // this uses the cached plugin object
        $this->plugin_object = $plugin;
        
        
        parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);
    }

    /**
     *
     *
     * @access public
     */
    public function init()
    {
        global $ilCtrl, $lng;
        $this->setTitle($this->plugin_object->txt('assignments'));

        $wS = '10%';
        $wM = '20%';
        $wL = '30%';
        $this->addColumn($lng->txt(''), 'counter', $wS);
        $this->addColumn($lng->txt('role'), 'role');
        $this->addColumn($lng->txt('objs_orgu'), 'orgunit');
        $this->addColumn($lng->txt("action"), "lang");
        
        $this->setEnableHeader(true);

        $this->setRowTemplate('tpl.assignment_table_row.html', $this->plugin_object->getDirectory());
        $this->getAssData();


    }

    public function createAssignmentsTable()
    {
        global $DIC, $ilDB;
        $data = [];
        $res = $ilDB->query(
            "SELECT o1.obj_id ou_id, o1.title orgunit, o2.obj_id role_id, o2.title role FROM object_data o1 INNER JOIN  " . self::TABLE_NAME_ASSIGNMENTS . 
            " ass ON o1.type='orgu' AND o1.obj_id=ass.orgunit INNER JOIN object_data o2 ON o2.type='role' AND ass.role = o2.obj_id"
        );
        $i=0;
        while($row = $ilDB->fetchAssoc($res)){
            $row['counter'] = $i+1;
            $data [] = $row;
            $i += 1;
        }

        return $data;
    }

    /**
     * Get data and put it into an array
     */
    function getAssData()
    {
        global $DIC;
        
        $data = $this->createAssignmentsTable();
        $this->setData($data);
    }

    /**
     * Fill a single data row.
     */
    protected function fillRow($a_set)
    {
        global $DIC;

        $this->tpl->setVariable('COUNTER', $a_set['counter']);
        $this->tpl->setVariable('ROLE', $a_set['role']);
        $this->tpl->setVariable('ORGUNIT', $a_set['orgunit']);
        $actions = $this->getActionMenuEntries($a_set);
        $this->tpl->setVariable("ACTIONS", $this->getActionMenu($actions, $a_set["orgunit"]));
    }
        /**
     * Get action menu for each row
     *
     * @param string[] $actions
     * @param int      $plugin_id
     *
     * @return ilAdvancedSelectionListGUI
     */
    protected function getActionMenu(array $actions, $plugin_id)
    {
        $alist = new ilAdvancedSelectionListGUI();
        $alist->setId($plugin_id);
        $alist->setListTitle($this->lng->txt("actions"));

        foreach ($actions as $caption => $cmd) {
            $alist->addItem($caption, "", $cmd);
        }

        return $alist->getHTML();
    }


    /**
     * Get entries for action menu
     *
     * @param string[] $a_set
     *
     * @return string[]
     */
    protected function getActionMenuEntries(array $a_set)
    {
        $this->setParameter($a_set);

        $actions = array();
        $this->ctrl->setParameter($this->parent_obj, "ouid", $a_set["ou_id"]);
        $this->ctrl->setParameter($this->parent_obj, "rid", $a_set["role_id"]);
        $this->addCommandToActions($actions, "edit", "editAssignment");
        $this->addCommandToActions($actions, "delete", "deleteAssignment");
        $this->clearParameter();

        return $actions;
    }

        /**
     * Set parameter for plugin
     *
     * @param string[] $a_set
     *
     * @return void
     */
    protected function setParameter(array $a_set)
    {

        $this->ctrl->setParameter($this->parent_obj, "ouid", $a_set["ou_id"]);
        $this->ctrl->setParameter($this->parent_obj, "rid", $a_set["role_id"]);
        $this->ctrl->setParameter($this->parent_obj, "ctype", IL_COMP_SERVICE);
        $this->ctrl->setParameter($this->parent_obj, "cname", "Cron");
        $this->ctrl->setParameter($this->parent_obj, "slot_id", "crnhk");
        $this->ctrl->setParameter($this->parent_obj, "pname", $this->plugin_object->getPluginName());
        
    }
    /**
     * Clear parameter
     *
     * @return void
     */
    protected function clearParameter()
    {
        $this->ctrl->setParameter($this->parent_obj, "ctype", null);
        $this->ctrl->setParameter($this->parent_obj, "cname", null);
        $this->ctrl->setParameter($this->parent_obj, "slot_id", null);
        $this->ctrl->setParameter($this->parent_obj, "pname", null);
    }

    /**
     * Add command to actions
     *
     * @param string[]    &$actions
     * @param string       $caption not translated lang var
     * @param string       $command
     *
     * @return void
     */
    protected function addCommandToActions(array &$actions, $caption, $command)
    {
        $actions[$this->lng->txt($caption)]
            = $this->ctrl->getLinkTarget($this->parent_obj, $command);
    }


}
