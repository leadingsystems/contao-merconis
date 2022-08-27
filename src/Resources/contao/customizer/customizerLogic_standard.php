<?php

namespace Merconis\Core;

class customizerLogic_standard extends customizer {
    protected $bln_showFieldsetsInSummary = true;

    /*
     * $arr_language will hold a reference to the language array.
     * This allows customizer logic classes that extend customizerLogic_standard to use the original method "renderForSummary"
     * and provide their own language array by just pointing the reference to the respective data source.
     */
    protected $arr_language = null;

    public function initialize()
    {
        \System::loadLanguageFile('customizerLogic_standard');
        $this->arr_language = &$GLOBALS['TL_LANG']['MSC']['merconis']['customizerLogic_standard'];
    }

    public function manipulateProductData()
    {
    }

    public function receiveUserInput($var_userInput)
    {
        $var_userInput = $this->validateUserInput($var_userInput);
        $this->obj_storage->writeCustomizationData($var_userInput);
        return $this->getStoredCustomizationData();
    }

    public function validateUserInput($var_userInput)
    {
        if ($var_userInput['step05']['elements']['greeting-card-text']['value'] == 'evil') {
            $var_userInput['step05']['elements']['greeting-card-text']['value'] = 'e***';
        }
        return $var_userInput;
    }

    public function getStoredCustomizationData()
    {
        return $this->obj_storage->getCustomizationData();
    }

    public function getStoredMiscData()
    {
        return $this->obj_storage->getMiscData();
    }

    public function getUserInterface()
    {
        ob_start();
        ?>
        <div data-merconis-component="customizerInterfaceStandard" data-merconis-productVariantId="<?php echo $this->obj_productOrVariant->_productVariantID; ?>" data-merconis-targetUrl="<?php echo \Environment::get('request'); ?>"></div>
        <?php
        return ob_get_clean();
    }

    public function getSummary()
    {
        return $this->renderForSummary($this->obj_storage->getCustomizationData());
    }

    public function getSummaryForCart()
    {
        return $this->getSummary();
    }

    public function getSummaryForMerchant()
    {
        return $this->getSummaryForCart();
    }

    public function hasCustomization()
    {
        return !empty($this->getStoredCustomizationData());
    }

    protected function renderForSummary($arr_element) {
        switch ($arr_element['type']) {
            case null:
                ob_start();
                ?>
                <div class="customizer-summary">
                    <?php
                    foreach ($arr_element as $arr_subElement) {
                        echo $this->renderForSummary($arr_subElement);
                    }
                    ?>
                </div>
                <?php
                return ob_get_clean();
                break;

            case 'fieldset':
                ob_start();

                if ($this->bln_showFieldsetsInSummary) {
                    ?>
                    <fieldset>
                        <legend><?php echo isset($this->arr_language['fieldsets'][$arr_element['name']]['name']) ? $this->arr_language['fieldsets'][$arr_element['name']]['name'] : $arr_element['name']; ?></legend>
                        <div class="elements">
                    <?php
                }

                        foreach ($arr_element['elements'] as $arr_subElement) {
                            echo $this->renderForSummary($arr_subElement);
                        }

                if ($this->bln_showFieldsetsInSummary) {
                    ?>
                        </div>
                    </fieldset>
                    <?php
                }
                return ob_get_clean();
                break;

            case 'text':
            case 'textarea':
                ob_start();
                ?>
                <div class="<?php echo $arr_element['type']; ?>">
                    <span class="label">
                        <?php
                        if (isset($this->arr_language['fields'][$arr_element['name']]['name'])) {
                            if ($this->arr_language['fields'][$arr_element['name']]['name'] !== '') {
                                echo $this->arr_language['fields'][$arr_element['name']]['name'] . ':';
                            }
                        } else {
                            echo $arr_element['name'] . ':';
                        }
                        ?>
                    </span>
                    <span class="value"><?php echo $arr_element['value']; ?></span>
                </div>
                <?php
                return ob_get_clean();
                break;

            default:
                ob_start();
                ?>
                <div class="<?php echo $arr_element['type']; ?>">
                    <span class="label">
                        <?php
                        if (isset($this->arr_language['fields'][$arr_element['name']]['name'])) {
                            if ($this->arr_language['fields'][$arr_element['name']]['name'] !== '') {
                                echo $this->arr_language['fields'][$arr_element['name']]['name'] . ':';
                            }
                        } else {
                            echo $arr_element['name'] . ':';
                        }
                        ?>
                    </span>
                    <span class="value">
                        <?php
                        if (is_array($arr_element['value'])) {
                            if (!empty($arr_element['value'])) {
                                echo implode(', ', array_map(function($str_value) use ($arr_element) { return $this->arr_language['fields'][$arr_element['name']]['values'][$str_value] ?: $str_value; }, $arr_element['value']));
                            }
                        } else {
                            if ($arr_element['value'] !== '') {
                                echo isset($this->arr_language['fields'][$arr_element['name']]['values'][$arr_element['value']]) ? $this->arr_language['fields'][$arr_element['name']]['values'][$arr_element['value']] : $arr_element['value'];
                            }
                        }
                        ?>
                    </span>
                </div>
                <?php
                return ob_get_clean();
                break;
        }
    }
}