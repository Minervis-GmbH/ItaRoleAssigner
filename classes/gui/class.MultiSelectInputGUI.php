<?php


/**
 * Class MultiSelectInputGUI
 * 
 * 
 *@ilCtrl_Calls MultiSelectInputGUI: OrgUnitAjaxAutoComplete, ObjAjaxAutoComplete
 */
class MultiSelectInputGUI extends ilFormPropertyGUI implements ilTableFilterItem, ilToolbarItem
{

    const EMPTY_PLACEHOLDER = "__empty_placeholder__";
    const CMD_AJAX_AUTO_COMPLETE= "ajaxAutoComplete";
    /**
     * @var bool
     */
    protected static $init = false;
    /**
     * @var AbstractAjaxAutoComplete|null
     */
    protected $ajax_auto_complete_ctrl = null;
    /**
     * @var int|null
     */
    protected $limit_count = null;
    /**
     * @var int|null
     */
    protected $minimum_input_length = null;
    /**
     * @var array
     */
    protected $options = [];
    /**
     * @var array
     */
    protected $value = [];

    private $plugin = null;

    private $default_option = null;
    private $cmd = '';


    /**
     * MultiSelectSearchNewInputGUI constructor
     *
     * @param string $title
     * @param string $post_var
     */
    public function __construct(string $title = "", string $post_var = "", ilPlugin $plugin = null)
    {
        parent::__construct($title, $post_var);
        $this->plugin = $plugin;

        self::init($plugin); // TODO: Pass $plugin
    }


    /**
     * @param array $values
     *
     * @return array
     */
    public static function cleanValues(array $values) : array
    {
        return array_values(array_filter($values, function ($value) : bool {
            return ($value !== self::EMPTY_PLACEHOLDER);
        }));
    }


    /**
     */
    public static function init( ilPlugin $plugin) : void
    {
        global $DIC;
        if (self::$init === false) {
            self::$init = true;

            $dir = $plugin->getDirectory();

            $DIC->ui()->mainTemplate()->addCss($dir . "/node_modules/select2/dist/css/select2.min.css");

            $DIC->ui()->mainTemplate()->addCss($dir . "/css/multi_select_input.css");

            $DIC->ui()->mainTemplate()->addJavaScript($dir . "/node_modules/select2/dist/js/select2.full.min.js");

            $DIC->ui()->mainTemplate()->addJavaScript($dir . "/node_modules/select2/dist/js/i18n/" . $DIC->user()->getCurrentLanguage(). ".js");
        }
    }


    /**
     * @param string $key
     * @param mixed  $value
     */
    public function addOption(string $key, $value) : void
    {
        $this->options[$key] = $value;
    }


    /**
     * @inheritDoc
     */
    public function checkInput() : bool
    {
        global $DIC; 
        $values = $_POST[$this->getPostVar()];
        if (!is_array($values)) {
            $values = [];
        }

        $values = self::cleanValues($values);

        if ($this->getRequired() && empty($values)) {
            $this->setAlert($DIC->language()->txt("msg_input_is_required"));

            return false;
        }

        if ($this->getLimitCount() !== null && count($values) > $this->getLimitCount()) {
            $this->setAlert($DIC->language()->txt("form_input_not_valid"));

            return false;
        }

        if ($this->getAjaxAutoCompleteCtrl() !== null) {
            if (!$this->getAjaxAutoCompleteCtrl()->validateOptions($values)) {
                $this->setAlert($DIC->language()->txt("form_input_not_valid"));

                return false;
            }
        } else {
            foreach ($values as $key => $value) {
                if (!isset($this->getOptions()[$value])) {
                    $this->setAlert($DIC->language()->txt("form_input_not_valid"));

                    return false;
                }
            }
        }

        return true;
    }


    /**
     * @return AbstractAjaxAutoCompleteCtrl|null
     */
    public function getAjaxAutoCompleteCtrl(): ?AbstractAjaxAutoComplete
    {
        return $this->ajax_auto_complete_ctrl;
    }


    /**
     * @param AbstractAjaxAutoCompleteCtrl|null $ajax_auto_complete_ctrl
     */
    public function setAjaxAutoCompleteCtrl(/*?*/  AbstractAjaxAutoComplete $ajax_auto_complete_ctrl = null) : void
    {
        $this->ajax_auto_complete_ctrl = $ajax_auto_complete_ctrl;
    }


    /**
     * @return int|null
     */
    public function getLimitCount() : ?int
    {
        return $this->limit_count;
    }


    /**
     * @param int|null $limit_count
     */
    public function setLimitCount(/*?*/ int $limit_count = null) : void
    {
        $this->limit_count = $limit_count;
    }


    /**
     * @return int
     */
    public function getMinimumInputLength() : int
    {
        if ($this->minimum_input_length !== null) {
            return $this->minimum_input_length;
        } else {
            return ($this->getAjaxAutoCompleteCtrl() !== null ? 3 : 0);
        }
    }


    /**
     * @param int|null $minimum_input_length
     */
    public function setMinimumInputLength(/*?*/ int $minimum_input_length = null) : void
    {
        $this->minimum_input_length = $minimum_input_length;
    }


    /**
     * @return array
     */
    public function getOptions() : array
    {
        return $this->options;
    }


    /**
     * @param array $options
     */
    public function setOptions(array $options) : void
    {
        $this->options = $options;
    }


    /**
     * @inheritDoc
     */
    public function getTableFilterHTML() : string
    {
        return $this->render();
    }


    /**
     * @inheritDoc
     */
    public function getToolbarHTML() : string
    {
        return $this->render();
    }


    /**
     * @return array
     */
    public function getValue() : array
    {
        return $this->value;
    }


    /**
     * @param array $value
     */
    public function setValue(/*array*/ $value) : void
    {
        if (is_array($value)) {
            $this->value = self::cleanValues($value);
        } else {
            $this->value = [];
        }
    }

    /**
     * @param ilTemplate $tpl
     */
    public function insert(ilTemplate $tpl) : void
    {
        $html = $this->render();

        $tpl->setCurrentBlock("prop_generic");
        $tpl->setVariable("PROP_GENERIC", $html);
        $tpl->parseCurrentBlock();
    }

    public function setCmd($cmd)
    {
        $this->cmd = $cmd;

    }
    public function setDefaultOption($option = "")
    {
        $this->default_option = $option ? $option  : null;        
    }


    /**
     * @return string
     */
    public function render() : string
    {
        global $DIC;
        $tpl = new ilTemplate($this->plugin->getDirectory(). "/templates/multi_select_input.html", true, true);

        $tpl->setVariable("ID", $this->getFieldId());

        $tpl->setVariable("POST_VAR", $this->getPostVar());

        $tpl->setVariable("EMPTY_PLACEHOLDER", self::EMPTY_PLACEHOLDER); // ILIAS 6 will not set `null` value to input on post

        $config = [
            "maximumSelectionLength" => $this->getLimitCount(),
            "minimumInputLength"     => $this->getMinimumInputLength()
        ];
        if ($this->getAjaxAutoCompleteCtrl() !== null) {
            $config["ajax"] = [
                "delay" => 500,
                "url"   => $DIC->ctrl()->getLinkTarget($this->getAjaxAutoCompleteCtrl(), AbstractAjaxAutoComplete::CMD_AJAX_AUTO_COMPLETE, "", true, false)
            ];

            $options = $this->getAjaxAutoCompleteCtrl()->fillOptions($this->getValue());
        } else {
            $options = $this->getOptions();
        }
        $tpl->setVariable("REQUIRED", $this->getRequired()? htmlspecialchars("required") : null);
        $tpl->setVariable("CONFIG", base64_encode(json_encode($config)));

        if (!empty($options)) {

            $tpl->setCurrentBlock("option");

            foreach ($options as $option_value => $option_text) {
                $selected = in_array($option_value, $this->getValue());
                if($this->default_option && $this->default_option == $option_value){
                    $tpl->setVariable("SELECTED", $this->default_option);
                }else  if ($selected) {
                    $tpl->setVariable("SELECTED", "selected");
                }

                $tpl->setVariable("VAL", $option_value);
                $tpl->setVariable("TEXT", $option_text);

                $tpl->parseCurrentBlock();
            }
        }

        return $tpl->get();
    }


    /**
     * @param array $values
     */
    public function setValueByArray(/*array*/ $values) : void
    {
        $this->setValue($values[$this->getPostVar()]);
    }
}
