<?php

require_once ("Customizing/global/plugins/Services/Cron/CronHook/ItaRoleAssigner/classes/gui/AbstractAjaxAutoComplete.php");

/**
 * Class OrgUnitAjaxAutoComplete
 *
 */
class OrgUnitAjaxAutoComplete extends AbstractAjaxAutoComplete
{

    /**
     * OrgUnitAjaxAutoComplete constructor
     *
     * @param array|null $skip_ids
     */
    public function __construct(/*?*/ array $skip_ids = null)
    {
        parent::__construct($skip_ids);
    }


    /**
     * @inheritDoc
     */
    public function fillOptions(array $ids) : array
    {
        if (!empty($ids)) {
            return $this->skipIds(ilOrgUnitPathStorage::where([
                "ref_id" => $ids
            ])->getArray("ref_id", "path"));
        } else {
            return [];
        }
    }


    /**
     * @inheritDoc
     */
    public function searchOptions(/*?*/ string $search = null) : array
    {
        if (!empty($search)) {
            $where = ilOrgUnitPathStorage::where([
                "path" => "%" . $search . "%"
            ], "LIKE");
        } else {
            $where = ilOrgUnitPathStorage::where([]);
        }

        return $this->skipIds($where->orderBy("path")->getArray("ref_id", "path"));
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
}
