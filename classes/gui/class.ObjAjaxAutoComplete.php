<?php

require_once ("Customizing/global/plugins/Services/Cron/CronHook/ItaRoleAssigner/classes/gui/AbstractAjaxAutoComplete.php");
/**
 * Class ObjectsAjaxAutoCompleteCtrl
 *
 */
class ObjAjaxAutoComplete extends AbstractAjaxAutoComplete
{
    const TYPE_ROLE = 0;
    const TYPE_OE   = 1;

    /**
     * @var bool
     */
    protected $ref_id;
    /**
     * @var string
     */
    protected $type;


    /**
     * ObjectsAjaxAutoComplete constructor
     *
     * @param string     $type
     * @param bool       $ref_id
     *
     * @param array|null $skip_ids
     */
    public function __construct(string $type, bool $ref_id = false,/*?*/ array $skip_ids = null)
    {
        parent::__construct($skip_ids);

        $this->type = $type;
        $this->ref_id = $ref_id;
    }


    /**
     * @inheritDoc
     */
    public function fillOptions(array $ids) : array
    {
        $result = self::dic()->database()->queryF('
            SELECT ' . ($this->ref_id ? 'object_reference.ref_id' : 'object_data.obj_id') . ', title
            FROM object_data
            INNER JOIN object_reference ON object_data.obj_id=object_reference.obj_id
            WHERE type=%s
            AND object_reference.deleted IS NULL
            AND ' . self::dic()
                            ->database()
                            ->in(($this->ref_id ? 'object_reference.ref_id' : 'object_data.obj_id'), $ids, false, ilDBConstants::T_INTEGER) . ' ORDER BY title ASC', [ilDBConstants::T_TEXT], [$this->type]);

        return $this->formatObjects(self::dic()->database()->fetchAll($result));
    }


    /**
     * @inheritDoc
     */
    public function searchOptions(/*?*/ string $search = null) : array
    {
        $result = self::dic()->database()->queryF('
            SELECT ' . ($this->ref_id ? 'object_reference.ref_id' : 'object_data.obj_id') . ', title
            FROM object_data
            INNER JOIN object_reference ON object_data.obj_id=object_reference.obj_id
            WHERE type=%s
            AND object_reference.deleted IS NULL
            ' . (!empty($search) ? ' AND ' . self::dic()
                    ->database()
                    ->like("title", ilDBConstants::T_TEXT, '%%' . $search . '%%') : '') . ' ORDER BY title ASC', [ilDBConstants::T_TEXT], [$this->type]);

        return $this->formatObjects(self::dic()->database()->fetchAll($result));
    }


    /**
     * @param array $objects
     *
     * @return array
     */
    protected function formatObjects(array $objects) : array
    {
        $formatted_objects = [];

        foreach ($objects as $object) {
            $formatted_objects[$object[($this->ref_id ? 'ref_id' : 'obj_id')]] = $object["title"];
        }

        return $this->skipIds($formatted_objects);
    }

    /**
     * @return array
     */
    public function getAllRoles() : array
    {
        /**
         * @var array $global_roles
         * @var array $roles
         */

        $global_roles = self::dic()->rbac()->review()->getRolesForIDs(self::dic()->rbac()->review()->getGlobalRoles(), false);

        $roles = [];
        foreach ($global_roles as $global_role) {
            $roles[$global_role["rol_id"]] = $global_role["title"];
        }

        return $roles;
    }

    /**
     * @return array
     */
    public function getAllOrgUnits() : array
    {
        
        $result = self::dic()->database()->queryF('
            SELECT ' . ($this->ref_id ? 'object_reference.ref_id' : 'object_data.obj_id') . ', title
            FROM object_data
            INNER JOIN object_reference ON object_data.obj_id=object_reference.obj_id
            WHERE type=%s
            AND object_reference.deleted IS NULL
            ' . (!empty($search) ? ' AND ' . self::dic()
                    ->database()
                    ->like("title", ilDBConstants::T_TEXT, '%%' . $search . '%%') : '') . ' ORDER BY title ASC', [ilDBConstants::T_TEXT], [$this->type]);

        return $this->formatObjects(self::dic()->database()->fetchAll($result));
    }


}
