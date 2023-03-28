<?php

require_once "Customizing/global/plugins/Services/Cron/CronHook/ItaRoleAssigner/classes/class.ilItaRoleAssignerCron.php";
require_once "Customizing/global/plugins/Services/Cron/CronHook/ItaRoleAssigner/classes/class.ilItaRoleAssignerPlugin.php";
/**
 * Class ilItaRoleAssignerPlugin
 *
 * @author Jephte Abijuru <jephte.abijuru@minervis.com>
 */
class ilItaRoleAssignerPlugin extends ilCronHookPlugin
{


    const PLUGIN_CLASS_NAME = ilItaRoleAssignerPlugin::class;
    const PLUGIN_ID = "itaroass";
    const PLUGIN_NAME = "ItaRoleAssigner";
    /**
     * @var self|null
     */
    protected static $instance = null;


    /**
     * ilItaRoleAssignerPlugin constructor
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * @return self
     */
    public static function getInstance() : self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    /**
     * @inheritDoc
     */
    public function getPluginName() : string
    {
        return self::PLUGIN_NAME;
    }


    public function getCronJobInstance(/*string*/ $a_job_id): ?ilItaRoleAssignerCron/*: ?ilCronJob*/
    {

        switch ($a_job_id) {
            case ilItaRoleAssignerCron::CRON_JOB_ID:
                return new ilItaRoleAssignerCron();

            default:
                return null;
        }

    }


    public function getCronJobInstances() : array
    {
        return [
            new ilItaRoleAssignerCron()
        ];
    }

}
