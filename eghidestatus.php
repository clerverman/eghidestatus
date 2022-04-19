<?php
/**
* 2007-2022 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Eghidestatus extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'eghidestatus';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Slimani mouhcine';
        $this->need_instance = 0; 
        $this->bootstrap = true; 
        parent::__construct(); 
        $this->displayName = $this->l('Eghidestatus');
        $this->description = $this->l('eghidestatus ........'); 
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }
    
    public function install()
    {
        Configuration::updateValue('EGHIDESTATUS_LIVE_MODE', false);

        return parent::install() && 
            $this->registerHook('backOfficeHeader');
    }

    public function uninstall()
    {
        Configuration::deleteByName('EGHIDESTATUS_LIVE_MODE');

        return parent::uninstall();
    }
 
    public function getContent()
    {
        
        if (((bool)Tools::isSubmit('submitEghidestatusModule')) == true) {
            $this->postProcess(); 
        }

        $this->context->smarty->assign('module_dir', $this->_path); 

        return $this->renderForm();
    } 
    
    protected function renderForm()
    {
        $helper = new HelperForm(); 
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitEghidestatusModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );
        return $helper->generateForm(array($this->getConfigForm()));
    }

    protected function getConfigForm()
    {   
        $options = $this->getOptions() ;  
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Maquer les status'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array( 
                        'type' => 'select',
                        'label' => $this->trans('Status'),
                        'name' => 'ID_STATUS[]',
                        'id' => 'ID_STATUS',
                        'class' => 'chosen', 
                        'required' => true,
                        'multiple' => true , 
                        'options' => [
                            'query' => $options,
                            'id' => 'id_order_state',
                            'name' => 'name',
                        ],
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l(''),
                        'name' => 'EGHIDESTATUS_LIVE_MODE',
                        'is_bool' => true, 
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Activé')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Désactivé')
                            )
                        ),
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save'),  
                ),
            ),
        );
    }

    protected function getOptions()
    {
        $res =  Db::getInstance()->executeS('
        SELECT id_order_state , name 
        FROM `' . _DB_PREFIX_ . 'order_state_lang` os where id_lang = '.$this->context->language->id);
        return $res;
    } 

    protected function getConfigFormValues()
    {
        return array(
            'EGHIDESTATUS_LIVE_MODE' => Configuration::get('EGHIDESTATUS_LIVE_MODE', true),
            'ID_STATUS[]' => explode(',', Configuration::get('ID_STATUS', true)),
        );
    }
    
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            $active = Tools::getValue('EGHIDESTATUS_LIVE_MODE');
            $orderStatus = implode(',', Tools::getValue('ID_STATUS'));
            Configuration::updateValue('ID_STATUS', $orderStatus);
            Configuration::updateValue('EGHIDESTATUS_LIVE_MODE', $active);
        }
    }

    public function hookBackOfficeHeader()
    { 
       // dump($this->context->controller) ; 
        if( Tools::getValue('controller') == "AdminOrders" && Configuration::get('EGHIDESTATUS_LIVE_MODE') == 1 ) 
        {  
            $staus_ids = explode(',', Configuration::get('ID_STATUS', true));
            Media::addJsDef([
                'optionsToHide' => $staus_ids
            ]);
            
            $this->context->controller->addJS($this->_path . 'views/js/back.js', 'all');
        }
    }

}