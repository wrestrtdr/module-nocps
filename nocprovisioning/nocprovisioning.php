<?php
/**
 * NOC-PS Blesta v3 module, version 0.4.
 *
 * Copyright (C) Maxnet 2013-2015
 * Author: Floris Bos
 *
 * May be distributed under the terms of the LGPL license.
 * In plain English: feel free to use and modify this file to fit your needs,
 * however do NOT ioncube encode it.
 * The source code must be available to anyone you distribute this module to.
 */
class Nocprovisioning extends Module
{
    /**
     * Global space seperated list of blacklisted or whitelisted profiles
     * Profiles can be specified by numeric ID, or tag (e.g. "windows").
     *
     * A different list is used for provisioning dedicated servers and VPSes
     *
     * Additional items can also be added in the package settings
     */
    protected $whitelist_dedicated = '';
    protected $blacklist_dedicated = '';
    protected $whitelist_vps = '';
    protected $blacklist_vps = 'xenserver esxi proxmox ovirt'; // Do not install hypervisors to vps

    protected $_version = '1.0.0';
    protected $_authors = [['name' => 'Maxnet', 'url' => 'http://www.noc-ps.com/']];
    protected $_api = false;
    protected $_viewspath;
    protected $_vpstypes = ['xenserver','vmware','proxmox', 'ovirt'];
    protected $_fields;
    protected $_vars;

    public function __construct()
    {
        Loader::loadComponents($this, ['Input', 'Json']);
        Language::loadLang('nocprovisioning', null, __DIR__ . DS . 'language' . DS);

        // FIXME: detect path automatically
        $this->_viewspath = 'components' . DS . 'modules' . DS . 'nocprovisioning' . DS;
    }

    private function l($msg)
    {
        return Language::_("Nocprovisioning.$msg", true);
    }

    protected function addError($field, $msg)
    {
        $errors = $this->Input->errors();
        if (!$errors) {
            $errors = [];
        }

        $errors[$field][] = $msg;
        $this->Input->setErrors($errors);
    }

    protected function logInfo($msg)
    {
        $this->log('NOC-PS', $msg, 'input', true);
    }

    protected function logError($msg)
    {
        $this->log('NOC-PS', $msg, 'input', false);
    }

    public function getName()
    {
        return $this->l('name');
    }

    public function getVersion()
    {
        return $this->_version;
    }

    public function getAuthors()
    {
        return $this->_authors;
    }

    public function moduleRowName()
    {
        return $this->l('module_row');
    }

    public function moduleRowNamePlural()
    {
        return $this->l('module_row_plural');
    }

    public function moduleGroupName()
    {
        return $this->l('module_group');
    }

    public function moduleRowMetaKey()
    {
        return 'server_name';
    }

    /* Convenience methods to add form fields to ModuleFields object */
    protected function addTextField($id, $default = '')
    {
        if ($this->_vars && isset($this->_vars->meta[$id])) {
            $value = $this->_vars->meta[$id];
        } else {
            $value = $default;
        }

        $label = $this->_fields->label($this->l('label_' . $id, $id));
        $label->attach($this->_fields->fieldText(
            'meta[' . $id . ']',
            $value,
            ['id' => $id]
        ));
        $this->_fields->setField($label);
    }

    protected function addCheckBox($id, $default = false)
    {
        if ($this->_vars && isset($this->_vars->meta[$id])) {
            $value = $this->_vars->meta[$id];
        } else {
            $value = $default;
        }

        $label = $this->_fields->label($this->l('label_' . $id, $id));
        $label->attach($this->_fields->fieldCheckBox(
            'meta[' . $id . ']',
            null,
            $value,
            ['id' => $id]
        ));
        $this->_fields->setField($label);
    }

    protected function addSelectField($id, $options, $default = null)
    {
        if ($this->_vars && isset($this->_vars->meta[$id])) {
            $value = $this->_vars->meta[$id];
        } else {
            $value = $default;
        }

        $label = $this->_fields->label($this->l('label_' . $id, $id));
        $label->attach($this->_fields->fieldSelect(
            'meta[' . $id . ']',
            $options,
            $value,
            ['id' => $id]
        ));
        $this->_fields->setField($label);
    }

    public function getPackageFields($vars=null)
    {
        if ($vars && isset($vars->meta['producttype'])) {
            $checkboxes = ['enable_provisioning', 'enable_power', 'enable_datatraffic', 'enable_console', 'enable_sensors',
                                'powerdown_on_suspend', 'powerdown_on_delete', 'deletevps_on_delete'];

            foreach ($checkboxes as $c) {
                if (!isset($vars->meta[$c])) {
                    $vars->meta[$c] = false;
                }
            }
        }

        $this->_fields = new ModuleFields();
        $this->_fields->setHtml("
			<script type='text/javascript'>
			$(document).ready(function() {
				toggleNPSFields();
			});

			$('#producttype').change(function() {
				fetchModuleOptions();
				toggleNPSFields();
			});

			$('#rebootmethod').change(function() {
				toggleNPSFields();
			});
			
			$('#module_row').change(function() {
				if ($('#producttype').val() == 'vps')
				{
					$('#producttype').val('dedicated-manual');
				}
				fetchModuleOptions();
			});

			$('#node').change(function() {
				fetchModuleOptions();
			});
			
			function toggleNPSFields() {
				var ptype = $('#producttype').val();
			
				if (ptype == 'dedicated-auto')
				{
					$('#poolfrom').parent('li').show();
					$('#poolto').parent('li').show();
				}
				else
				{
					$('#poolfrom').parent('li').hide();
					$('#poolto').parent('li').hide();
				}
				if (ptype == 'vps')
				{
					$('#rebootmethod').parent('li').hide();
					$('#node').parent('li').show();
					$('#subnet').parent('li').show();
					$('#datastore').parent('li').show();
					$('#network').parent('li').show();
					$('#vcpu').parent('li').show();
					$('#memory').parent('li').show();
					$('#disk').parent('li').show();
					$('#deletevps_on_delete').parent('li').show();
				}
				else
				{
					$('#rebootmethod').parent('li').show();
					$('#node').parent('li').hide();
					$('#subnet').parent('li').hide();
					$('#datastore').parent('li').hide();
					$('#network').parent('li').hide();
					$('#vcpu').parent('li').hide();
					$('#memory').parent('li').hide();
					$('#disk').parent('li').hide();
					$('#deletevps_on_delete').parent('li').hide();
				}
				if ($('#rebootmethod').val() == 'auto' || ptype == 'vps')
				{
					$('#powerdown_on_suspend').parent('li').show();
					$('#powerdown_on_delete').parent('li').show();
				}
				else
				{
					$('#powerdown_on_suspend').parent('li').hide();
					$('#powerdown_on_delete').parent('li').hide();
				}
				if ($('#rebootmethod').val() == 'manual' && ptype != 'vps')
				{
					$('#enable_power').parent('li').hide();
				}
				else
				{
					$('#enable_power').parent('li').show();
				}
			}
			</script>
		");

        if (isset($vars->module_row) && $vars->module_row > 0) {
            $module_row = $this->getModuleRow($vars->module_row);
        } else {
            $rows = $this->getModuleRows();
            if (isset($rows[0])) {
                $module_row = $rows[0];
            }
        }

        $pools = [];
        $nodes = [];
        $subnets = [];
        $networks = [];
        $datastores = [];
        $profiles = [];

        if ($module_row) {
            $this->connect($module_row);

            if (isset($vars->meta['producttype']) && $vars->meta['producttype'] == 'dedicated-auto') {
                $poolinfo = $this->_api->getPools();

                foreach ($poolinfo['data'] as $pool) {
                    $pools[$pool['id']] = $pool['name'];
                }
            }

            if (isset($vars->meta['producttype']) && $vars->meta['producttype'] == 'vps') {
                $modules = $this->_api->getDevices(0, 99999);
                foreach ($modules['data'] as $moduleinfo) {
                    if (in_array($moduleinfo['type'], $this->_vpstypes)) {
                        $nodes[$moduleinfo['id']] = $moduleinfo['name'];
                    }
                }

                if (empty($vars->meta['node']) && count($nodes)) {
                    reset($nodes);
                    $vars->meta['node'] = key($nodes);
                }

                if (!empty($vars->meta['node'])) {
                    $subnetinfo = $this->_api->getSubnets(0, 99999);
                    foreach ($subnetinfo['data'] as $subnet) {
                        $subnettxt = $subnet['subnet'];
                        $subnets[$subnettxt] = $subnettxt;
                    }

                    $dsinfo = $this->_api->getDatastores((int) ($vars->meta['node']));
                    foreach ($dsinfo['data'] as $ds) {
                        $datastores[$ds['id']] = $ds['name'];
                    }

                    $netinfo = $this->_api->getNetworks((int) ($vars->meta['node']));
                    foreach ($netinfo['data'] as $net) {
                        $networks[$net['id']] = $net['name'];
                    }
                }
            }

            $profiles = $this->_api->getProfileNames(0, 99999);
        }

        $this->_vars = $vars;
        $this->addSelectField('producttype',
            [
                'dedicated-manual' => $this->l('dedicated_manual_assigned'),
                'dedicated-auto'   => $this->l('dedicated_auto_assigned'),
                'vps' => $this->l('vps')
            ],
            'dedicated-manual'
        );
        $this->addSelectField('poolfrom', $pools, '1');
        $this->addSelectField('poolto', $pools, '0');
        $this->addSelectField('rebootmethod',
            [
                'auto'   => $this->l('rebootmethod_auto'),
                'ipmi'   => $this->l('rebootmethod_ipmi'),
                'manual' => $this->l('rebootmethod_manual')
            ],
            'auto'
        );
        $this->addSelectField('node', $nodes);
        $this->addSelectField('subnet', $subnets);
        $this->addSelectField('datastore', $datastores);
        $this->addSelectField('network', $networks);
        $this->addSelectField('vcpu', ['1' => '1','2' => '2', '3' => '3', '4' => '4']);
        $this->addTextField('memory', '1024');
        $this->addTextField('disk', '10000');

        $this->addCheckBox('enable_provisioning', true);
        $this->addCheckBox('enable_datatraffic', true);
        $this->addCheckBox('enable_power', true);
        $this->addCheckBox('enable_console', true);
        $this->addCheckBox('enable_sensors', true);
        $this->addCheckBox('powerdown_on_suspend', false);
        $this->addCheckBox('powerdown_on_delete', false);
        $this->addCheckBox('deletevps_on_delete', false);

        /* Profile black and white listing */
        $h  = '<p><b>' . $this->l('profiles') . '</b></p>';
        $h .= '<table border>';
        $h .= '<tr><th>ID</th><th>' . $this->l('profilename') . '</th><th>Tags</th>';
        foreach ($profiles['data'] as $profile) {
            $h .= '<tr><td>' . $profile['id'] . '</td><td>' . htmlentities($profile['name']) . '</td><td>' . htmlentities($profile['tags']) . '</td></tr>';
        }
        $h .= '</table>';
        $h .= '<p>' . $this->l('txt_lists') . '</p>';

        $this->_fields->setHtml($this->_fields->getHtml() . $h);
        $this->addTextField('whitelist', '');
        $this->addTextField('blacklist', '');

        return $this->_fields;
    }

    public function addPackage(array $vars=null)
    {
        $checkboxes = ['enable_provisioning', 'enable_power', 'enable_datatraffic', 'enable_console', 'enable_sensors',
                            'powerdown_on_suspend', 'powerdown_on_delete', 'deletevps_on_delete'];

        foreach ($checkboxes as $c) {
            if (!isset($vars['meta'][$c])) {
                $vars['meta'][$c] = false;
            }
        }

        $store_vars = ['producttype', 'rebootmethod', 'enable_provisioning', 'enable_power', 'enable_datatraffic', 'enable_console',
                    'enable_sensors', 'powerdown_on_suspend', 'powerdown_on_delete', 'whitelist', 'blacklist'];

        if ($vars['meta']['producttype'] == 'dedicated-manual') {
        } elseif ($vars['meta']['producttype'] == 'dedicated-auto') {
            if ($vars['meta']['poolfrom'] == $vars['meta']['poolto']) {
                $this->addError('poolto', $this->l('error.pool_is_same'));
            }

            $store_vars = array_merge($store_vars, ['poolfrom', 'poolto']);
        } elseif ($vars['meta']['producttype'] == 'vps') {
            if (empty($vars['meta']['node'])) {
                $this->addError('node', $this->l('!error.hypervisor_valid'));

                return;
            }

            $store_vars = array_merge($store_vars, ['node', 'subnet', 'datastore', 'network', 'vcpu', 'disk', 'memory', 'deletevps_on_delete']);
            $vars['meta']['rebootmethod'] = 'auto';
        }
        if ($vars['meta']['rebootmethod'] != 'auto') {
            $vars['meta']['powerdown_on_suspend'] = $vars['meta']['powerdown_on_delete'] = false;
        }
        if ($vars['meta']['rebootmethod'] == 'manual') {
            $vars['meta']['enable_power'] = false;
        }

        $meta = [];
        foreach ($store_vars as $v) {
            $meta[] = [
                'key' => $v,
                'value' => $vars['meta'][$v],
                'encrypted' => 0
            ];
        }

        return $meta;
    }

    public function editPackage($package, array $vars=null)
    {
        return $this->addPackage($vars);
    }

    public function getServiceName($service)
    {
        foreach ($service->fields as $field) {
            if ($field->key == 'ip') {
                return $field->value;
            }
        }

        return null;
    }

    public function getAdminEditFields($package, $vars=null)
    {
        $fields = new ModuleFields();

        $iplabel = $fields->label($this->l('label_ip'), 'ip');
        $iplabel->attach($fields->fieldText(
            'ip',
            isset($vars->ip) ? $vars->ip : ''
        ));
        $fields->setField($iplabel);

        return $fields;
    }

    public function getAdminAddFields($package, $vars=null)
    {
        if ($package->meta->producttype == 'dedicated-manual') {
            return $this->getAdminEditFields($package, $vars);
        } else {
            return new ModuleFields();
        }
    }

    public function addService($package, array $vars=null, $parent_package=null, $parent_service=null, $status='pending')
    {
        //die( print_r($vars, true) );
        if ($vars['use_module'] == 'true') {
            $ptype = $package->meta->producttype;

            if ($ptype == 'dedicated-manual') {
                if (!empty($vars['ip'])) {
                    $ip = $vars['ip'];
                    $this->connect();
                    if (!$this->_api->getServerByIP($vars['ip'])) {
                        $this->addError('ip', $this->l('error.ip_not_in_db'));

                        return;
                    }
                    $this->logInfo("Created new service. IP-address of server added: $ip");
                } else {
                    return;
                }
            } elseif ($ptype == 'dedicated-auto') {
                $this->connect();
                $mac = $this->_api->popFromPool((int) ($package->meta->poolfrom), (int) ($package->meta->poolto));
                if (!$mac) {
                    $err = 'Automatic provisioning failed: No available servers in pool';
                    $this->addError('pool', $err);
                    $this->logError($err);

                    return;
                }
                $hostinfo = $this->_api->getHost($mac);
                $ip = $hostinfo['ip'];

                $this->logInfo("Created new service. Server $ip moved from pool id: " . $package->meta->poolfrom . ' to pool id: ' . $package->meta->poolto);
            } elseif ($ptype == 'vps') {
                $this->connect();
                $ip = $this->_api->getFirstAvailableIP($package->meta->subnet);
                if (!$ip) {
                    $err = 'Automatic VPS provisioning failed: No IP-addressses available in subnet';
                    $this->addError('ip', $err);
                    $this->logError($err);

                    return;
                }

                if ($this->_api->ping($ip)) {
                    $err = "Automatic VPS provisioing failed safety check: IP $ip is free according to the database, but responds to ping";
                    $this->addError('ip', $err);
                    $this->logError($err);

                    return;
                }

                try {
                    $result = $this->_api->addVM([
                        'subnet'      => $package->meta->subnet,
                        'ip'          => $ip,
                        'hostname'    => 'h' . str_replace('.', '-', $ip),
                        'description' => 'Created by Blesta',
                        'module'      => (int) ($package->meta->node),
                        'numips'      => 1,
                        'datastore'   => $package->meta->datastore,
                        'network'     => $package->meta->network,
                        'vcpu'          => $package->meta->vcpu,
                        'memory'      => $package->meta->memory,
                        'disk'        => $package->meta->disk
                    ]);

                    if ($result['success']) {
                        $this->logInfo("Created new VPS with IP-address $ip");
                    } else {
                        $err = 'Automatic VPS provisioning failed: ' . print_r($result['errors'], true);
                        $this->addError('api', $err);
                        $this->logError($err);

                        return;
                    }
                } catch (Exception $e) {
                    $err = 'Automatic VPS provisioning failed: ' . $e->getMessage();
                    $this->addError('api', $err);
                    $this->logError($err);

                    return;
                }
            }

            return [
                [
                    'key' => 'ip',
                    'value' => $ip,
                    'encrypted' => 0
                ]
            ];
        }
    }

    public function editService($package, $service, array $vars=null, $parent_package=null, $parent_service=null)
    {
        if ($vars['use_module'] == 'true') {
            $this->connect();
            if (!$this->_api->getServerByIP($vars['ip'])) {
                $this->addError('ip', $this->l('error.ip_not_in_db'));
            } else {
                $this->logInfo('IP-address of server with service ID ' . $service->id . ' changed to ' . $vars['ip']);
            }
        } else {
            $this->logInfo('FORCED: IP-address of server with service ID ' . $service->id . ' changed to ' . $vars['ip'] . ' (use_module unchecked, verification overriden)');
        }

        return [
            [
                'key' => 'ip',
                'value' => $vars['ip'],
                'encrypted' => 0
            ]
        ];
    }

    public function suspendService($package, $service, $parent_package=null, $parent_service=null)
    {
        if ($package->meta->powerdown_on_suspend) {
            try {
                $mac = $this->_getMACfromService($service);

                if ($mac) {
                    $result = $this->_api->powercontrol($mac, 'off', '', 'auto');
                    $this->logInfo('Powered down server on suspend');
                }
            } catch (Exception $e) {
                $this->logError('Error powering down on suspend: ' . $e->getMessage());
            }
        }
    }

    public function unsuspendService($package, $service, $parent_package=null, $parent_service=null)
    {
        if ($package->meta->powerdown_on_suspend) {
            try {
                $mac = $this->_getMACfromService($service);

                if ($mac) {
                    $result = $this->_api->powercontrol($mac, 'on', '', 'auto');
                    $this->logInfo('Powered up server after unsuspend');
                }
            } catch (Exception $e) {
                $this->logError('Error powering up after unsuspend: ' . $e->getMessage());
            }
        }
    }

    public function cancelService($package, $service, $parent_package=null, $parent_service=null)
    {
        if ($package->meta->powerdown_on_delete) {
            try {
                $mac = $this->_getMACfromService($service);

                if ($mac) {
                    $result = $this->_api->powercontrol($mac, 'off', '', 'auto');
                    $this->logInfo('Powered down server on cancellation');
                }
            } catch (Exception $e) {
                $this->logError('Error powering down server on cancellation: ' . $e->getMessage());
            }
        }

        if ($package->meta->producttype == 'vps' && $package->meta->deletevps_on_delete) {
            try {
                $mac = $this->_getMACfromService($service);

                if ($mac) {
                    $result = $this->_api->deleteVM($mac);
                    $this->logInfo('Deleted VPS on cancellation');
                }
            } catch (Exception $e) {
                $this->logError('Error deleting VPS on cancellation: ' . $e->getMessage());
            }
        }
    }

    public function getAdminTabs($package)
    {
        $tabs = [
            'tabPower' => $this->l('tab_power'),
            'tabProvision' => $this->l('tab_provision'),
            'tabRescue' => $this->l('tab_rescue'),
            'tabDatatraffic' => $this->l('tab_datatraffic')
        ];
        if ($package->meta->producttype != 'vps') {
            $tabs['tabSensors'] = $this->l('tab_sensors');
        }
        if ($package->meta->enable_console) {
            $tabs['tabConsole'] = $this->l('tab_console');
        }

        return $tabs;
    }

    public function getClientTabs($package)
    {
        $tabs = [];

        if ($package->meta->enable_power) {
            $tabs['tabPower'] = $this->l('tab_power');
        }
        if ($package->meta->enable_provisioning) {
            $tabs['tabProvision'] = $this->l('tab_provision');
            $tabs['tabRescue'] = $this->l('tab_rescue');
        }
        if ($package->meta->enable_datatraffic) {
            $tabs['tabDatatraffic'] = $this->l('tab_datatraffic');
        }
        if ($package->meta->enable_sensors && $package->meta->producttype != 'vps') {
            $tabs['tabSensors'] = $this->l('tab_sensors');
        }
        if ($package->meta->enable_console) {
            $tabs['tabConsole'] = $this->l('tab_console');
        }

        return $tabs;
    }

    /**
     * Set Nonce against Cross-site request forgery attacks.
     */
    protected function _setNonce()
    {
        if (empty($_SESSION['nps_nonce'])) {
            $_SESSION['nps_nonce'] = uniqid() . mt_rand();
        }
    }

    /**
     * @return string Nonce value set earlier
     */
    protected function _nonce()
    {
        return $_SESSION['nps_nonce'];
    }

    /**
     * @return bool true if nonce value is correct
     */
    protected function _verifyNonce()
    {
        return $_SESSION['nps_nonce'] == $_POST['nps_nonce'];
    }

    protected function _getIPfromService($service)
    {
        $fields = $this->serviceFieldsToObject($service->fields);

        return $fields->ip;
    }

    protected function _getMACfromService($service)
    {
        $ip = $this->_getIPfromService($service);
        if (!$ip) {
            throw new Exception($this->l('error.no_server_assigned'));
        }
        $this->connect();
        $mac = $this->_api->getServerByIP($ip);
        if (!$mac) {
            throw new Exception($this->l('error.ip_not_in_db'));
        }
        return $mac;
    }

    protected function _applyTemplate($pdt, $vars)
    {
        $this->view = new View($pdt, 'default');
        Loader::loadHelpers($this, ['Form', 'Html']);
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView($this->_viewspath);

        foreach ($vars as $k => $v) {
            $this->view->set($k, $v);
        }

        return $this->view->fetch();
    }

    public function tabConsole($package, $service, array $get=null, array $post=null, array $files=null)
    {
        $this->connect();
        $this->_setNonce();
        $mac = $this->_getMACfromService($service);
        $rebootmethod = $package->meta->rebootmethod;

        $info = $this->_api->getHost($mac);
        if ($package->meta->producttype != 'vps' && (empty($info['ipmi_ip']) || $info['ipmi_type'] == 'v2nokvm' || $info['ipmi_type'] == 'v1')) {
            return $this->_applyTemplate('console', ['errormsg' => $this->l('tab_console.no_console_support')]);
        }

        if ($rebootmethod == 'auto') {
            $powerstate = $this->_api->powercontrol($mac, 'status', '', 'auto');
        } else {
            $powerstate = 'unknown';
        }

        if ($post && $this->_verifyNonce()) {
            try {
                $password = !empty($post['ipmipassword']) ? $post['ipmipassword'] : '';

                if (!empty($post['powerup'])) {
                    $powerstate = $this->_api->powercontrol($mac, 'on', $password, $rebootmethod);
                }
                if (!empty($post['resetbmc'])) {
                    $response = $this->_api->submitIPMI([
                        'ip' => $info['ipmi_ip'],
                        'username' => $info['ipmi_user'],
                        'password' => $password,
                        'ipmi_type' => 'v2',
                        'cmd' => 'bmc reset cold'
                    ]);

                    return $this->_applyTemplate('console', [
                        'statusmsg' => $this->l('tab_console.bmcreset_performed')
                    ]);
                } else {
                    $url = $this->_api->getConsoleURL($mac, $password, $_SERVER['REMOTE_ADDR']);

                    $this->logInfo('Activated console. Service ID: ' . $service->id);

                    if (!empty($post['getconsoleurl'])) {
                        echo $url;
                    } else {
                        header("Location: $url");
                    }

                    exit();
                }
            } catch (Exception $e) {
                return $this->_applyTemplate('console', [
                    'errormsg' => $e->getMessage()
                ]);
            }
        } else {
            return $this->_applyTemplate('console', [
                'nonce' => $this->_nonce(),
                'mac' => $mac,
                'consoletype' => ($package->meta->producttype == 'vps' || $info['ipmi_type'] == 'AMT' ? 'html5' : 'jnlp'),
                'powered_off' => ($powerstate == 'Halted' || $powerstate == 'stopped' || $powerstate == 'off'),
                'ask_ipmi_password' => ($rebootmethod == 'ipmi'),
            ]);
        }
    }

    public function tabPower($package, $service, array $get=null, array $post=null, array $files=null)
    {
        $this->connect();
        $this->_setNonce();
        $ip     = $this->_getIPfromService($service);
        $mac    = $this->_getMACfromService($service);
        $rebootmethod = $package->meta->rebootmethod;
        $password = isset($_POST['ipmipassword']) ? $_POST['ipmipassword'] : '';
        $result = '';

        if ($rebootmethod == 'manual') {
            return $this->_applyTemplate('power', ['errormsg' => 'Power management not enabled (reboot method set to manual)']);
        }

        if (!empty($_POST['poweraction']) && $this->_verifyNonce()) {
            try {
                if ($rebootmethod == 'ipmi' && !$password) {
                    return $this->_applyTemplate('power', ['errormsg' => "Enter your server's IPMI password"]);
                }

                $result = $this->_api->powercontrol($mac, $_POST['poweraction'], $password, $rebootmethod);
                $this->logInfo("Power management action '" . $_POST['poweraction'] . "' - $result - MAC $mac");
            } catch (Exception $e) {
                $result = 'ERROR: ' . $e->getMessage();
            }
        }

        $actionsString = '';
        if ($mac) {
            try {
                if ($rebootmethod == 'ipmi' && !$password) {
                    $status        = 'unknown';
                    $actionsString = 'on off reset soft cycle';
                } else {
                    $status           = $this->_api->powercontrol($mac, 'status', $password, $rebootmethod);
                    $actionsString    = $this->_api->powercontrol($mac, 'supportedactions', $password, $rebootmethod);
                    if (!$actionsString) {
                        return $this->_applyTemplate('power', ['errormsg' => 'This server does not support automatic reboots (no IPMI, AMT or reboot device associated with this server)']);
                    }
                }
            } catch (Exception $e) {
                $status = 'ERROR: ' . $e->getMessage();
            }
        } else {
            $status           = $this->lang['english']['ERR_UNKNOWN_SERVER'];
        }
        $supportedActions = explode(' ', $actionsString);

        return $this->_applyTemplate('power', [
            'ip'            => $ip,
            'nonce'        => $this->_nonce(),
            'result'        => $result,
            'status'        => $status,
            'supportsOn'    => in_array('on', $supportedActions),
            'supportsOff'   => in_array('off', $supportedActions),
            'supportsSoft'  => in_array('soft', $supportedActions),
            'supportsReset' => in_array('reset', $supportedActions),
            'supportsCycle' => in_array('cycle', $supportedActions),
            'supportsCtrlAltDel' => in_array('ctrlaltdel', $supportedActions),
            'ask_ipmi_password' => ($rebootmethod == 'ipmi')
        ]);
    }

    public function tabProvision($package, $service, array $get=null, array $post=null, array $files=null)
    {
        try {
            $this->_setNonce();
            $this->connect();
            $ip    = $this->_getIPfromService($service);
            $mac    = $this->_getMACfromService($service);

            if (isset($_POST['oldeventid'])) {
                /* AJAX status callback */
                $this->ajaxStatusPoll($mac, (int) ($_POST['oldeventid']));

                return;
            }

            if ($package->meta->producttype == 'vps') {
                $bl = $this->blacklist_vps;
                $wl = $this->whitelist_vps;
            } else {
                $bl = $this->blacklist_dedicated;
                $wl = $this->whitelist_dedicated;
            }
            if (!empty($package->meta->blacklist)) {
                $bl .= ' ' . $package->meta->blacklist;
            }
            if (!empty($package->meta->whitelist)) {
                $wl .= ' ' . $package->meta->whitelist;
            }

            $error = '';
            $rebootmethod = $package->meta->rebootmethod;
            if ($rebootmethod == 'manual') {
                $rebootmethod = '';
            }
            $ipmipassword = isset($_POST['ipmipassword']) ? $_POST['ipmipassword'] : '';

            if (!empty($_POST['profile']) && $this->_verifyNonce()) {
                /* Never trust user input. Double check if profile is not blacklisted */
                $whitelist = array_filter(explode(' ', $wl));
                $blacklist = array_filter(explode(' ', $bl));
                $profileid = (int) ($_POST['profile']);
                $profile   = $this->_api->getProfile($profileid);
                $tags      = explode(' ', $profile['data']['tags']);

                if (count($whitelist) && !in_array($profileid, $whitelist) && count(array_intersect($tags, $whitelist)) == 0) {
                    throw new Exception('Profile is not on whitelist');
                } elseif (count($blacklist) && (in_array($profileid, $blacklist) || count(array_intersect($tags, $blacklist)))) {
                    throw new Exception('Profile is on blacklist');
                }
                /* --- */
                if ($rebootmethod == 'ipmi' && !$ipmipassword) {
                    throw new Exception("Enter your server's IPMI password");
                }
                /* Provision server */
                $result = $this->_api->provisionHost([
                    'mac'           => $mac,
                    'hostname'    => $_POST['hostname'],
                    'profile'       => $profileid,
                    'rootpassword'  => $_POST['rootpassword'],
                    'rootpassword2' => $_POST['rootpassword2'],
                    'adminuser'    => $_POST['adminuser'],
                    'userpassword'  => $_POST['userpassword'],
                    'userpassword2' => $_POST['userpassword2'],
                    'disk_addon'    => $_POST['disklayout'],
                    'packages_addon'=> $_POST['packageselection'],
                    'extra_addon1'    => $_POST['extra1'],
                    'extra_addon2'  => $_POST['extra2'],
                    'rebootmethod'  => $rebootmethod,
                    'ipmipassword'  => $ipmipassword
                ]);

                if ($result['success']) {
                    $n = $profile['data']['name'];
                    if ($_POST['disklayout']) {
                        $n .= '+' . $_POST['disklayout'];
                    }
                    if ($_POST['packageselection']) {
                        $n .= '+' . $_POST['packageselection'];
                    }
                    if ($_POST['extra1']) {
                        $n .= '+' . $_POST['extra1'];
                    }
                    if ($_POST['extra2']) {
                        $n .= '+' . $_POST['extra2'];
                    }

                    $this->logInfo("Provisioning server - Profile '$n' - MAC $mac");
                } else {
                    /* input validation error */

                    foreach ($result['errors'] as $field => $msg) {
                        $error .= $field . ': ' . htmlentities($msg) . '<br>';
                    }

                    $this->logError('Error trying to provision - ' . str_replace('<br>', ' - ', $error));
                }
            } elseif (!empty($_POST['cancelprovisioning'])) {
                /* Cancel provisioning */
                $this->_api->cancelProvisioning($mac);
                $this->log("Cancelled provisioning - MAC $mac");
            }

            $status = $this->_api->getProvisioningStatusByServer($mac);

            if ($status) {
                /* Host is already being provisioned */

                return $this->_applyTemplate('provision-status', [
                    'ip'        => $ip,
                    'mac'        => $mac,
                    'nonce'     => $this->_nonce(),
                    'status'    => $status
                ]);
            } else {
                $profiles = $this->_api->getProfileNames(0, 1000);
                $addons   = $this->_api->getProfileAddonNames(0, 1000);

                /* Check profile against white- and blacklist */
                $whitelist = array_filter(explode(' ', $wl));
                $blacklist = array_filter(explode(' ', $bl));

                foreach ($profiles['data'] as $k => $profile) {
                    $tags = explode(' ', $profile['tags']);

                    /* Check wheter the profile ID or any of its tags are on the whitelist */
                    if (count($whitelist) && !in_array($profile['id'], $whitelist) && count(array_intersect($tags, $whitelist)) == 0) {
                        /* not on whitelist, remove */
                        unset($profiles['data'][$k]);
                    } elseif (count($blacklist) && (in_array($profile['id'], $blacklist) || count(array_intersect($tags, $blacklist)))) {
                        /* on blacklist, remove */
                        unset($profiles['data'][$k]);
                    }
                }

                /* --- */
                return $this->_applyTemplate('provision', [
                    'ip'        => $ip,
                    'mac'       => $mac,
                    'nonce'     => $this->_nonce(),
                    'profiles'  => $profiles['data'],
                    'addons_json'   => $this->Json->encode($addons['data']),
                    'profiles_json' => $this->Json->encode(array_values($profiles['data'])),
                    'errormsg'      => $error,
                    'ask_ipmi_password' => ($rebootmethod == 'ipmi')
                ]);
            }
        } catch (Exception $e) {
            return $this->_applyTemplate('provision', [
                'errormsg' => $e->getMessage()
            ]);
        }
    }

    public function tabRescue($package, $service, array $get=null, array $post=null, array $files=null)
    {
        try {
            $this->_setNonce();
            $this->connect();
            $ip    = $this->_getIPfromService($service);
            $mac    = $this->_getMACfromService($service);

            if (isset($_POST['oldeventid'])) {
                /* AJAX status callback */
                $this->ajaxStatusPoll($mac, (int) ($_POST['oldeventid']));

                return;
            }

            $wl = 'rescue';
            $bl = '';
            $error = '';
            $rebootmethod = $package->meta->rebootmethod;
            if ($rebootmethod == 'manual') {
                $rebootmethod = '';
            }
            $ipmipassword = isset($_POST['ipmipassword']) ? $_POST['ipmipassword'] : '';

            if (!empty($_POST['profile']) && $this->_verifyNonce()) {
                /* Never trust user input. Double check if profile is not blacklisted */
                $whitelist = array_filter(explode(' ', $wl));
                $blacklist = array_filter(explode(' ', $bl));
                $profileid = (int) ($_POST['profile']);
                $profile   = $this->_api->getProfile($profileid);
                $tags      = explode(' ', $profile['data']['tags']);

                if (count($whitelist) && !in_array($profileid, $whitelist) && count(array_intersect($tags, $whitelist)) == 0) {
                    throw new Exception('Profile is not on whitelist');
                } elseif (count($blacklist) && (in_array($profileid, $blacklist) || count(array_intersect($tags, $blacklist)))) {
                    throw new Exception('Profile is on blacklist');
                }
                /* --- */
                if ($rebootmethod == 'ipmi' && !$ipmipassword) {
                    throw new Exception("Enter your server's IPMI password");
                }
                $curinfo = $this->_api->getHost($mac);

                /* Provision server */
                $result = $this->_api->provisionHost([
                    'mac'           => $mac,
                    'hostname'    => $curinfo['hostname'],
                    'profile'       => $profileid,
                    'rootpassword'  => $_POST['rootpassword'],
                    'rootpassword2' => $_POST['rootpassword2'],
                    'rebootmethod'  => $rebootmethod,
                    'ipmipassword'  => $ipmipassword
                ]);

                if ($result['success']) {
                    $n = $profile['data']['name'];
                    $this->logInfo("Started rescue system - Profile '$n' - MAC $mac");
                } else {
                    /* input validation error */

                    foreach ($result['errors'] as $field => $msg) {
                        $error .= $field . ': ' . htmlentities($msg) . '<br>';
                    }

                    $this->logError('Error trying to start rescue system - ' . str_replace('<br>', ' - ', $error));
                }
            } elseif (!empty($_POST['cancelprovisioning'])) {
                /* Cancel provisioning */
                $this->_api->cancelProvisioning($mac);
                $this->log("Cancelled provisioning - MAC $mac");
            }

            $status = $this->_api->getProvisioningStatusByServer($mac);

            if ($status) {
                /* Host is already being provisioned */

                return $this->_applyTemplate('provision-status', [
                    'ip'        => $ip,
                    'mac'        => $mac,
                    'nonce'     => $this->_nonce(),
                    'status'    => $status
                ]);
            } else {
                $profiles = $this->_api->getProfileNames(0, 1000);

                /* Check profile against white- and blacklist */
                $whitelist = array_filter(explode(' ', $wl));
                $blacklist = array_filter(explode(' ', $bl));

                foreach ($profiles['data'] as $k => $profile) {
                    $tags = explode(' ', $profile['tags']);

                    /* Check wheter the profile ID or any of its tags are on the whitelist */
                    if (count($whitelist) && !in_array($profile['id'], $whitelist) && count(array_intersect($tags, $whitelist)) == 0) {
                        /* not on whitelist, remove */
                        unset($profiles['data'][$k]);
                    } elseif (count($blacklist) && (in_array($profile['id'], $blacklist) || count(array_intersect($tags, $blacklist)))) {
                        /* on blacklist, remove */
                        unset($profiles['data'][$k]);
                    }
                }

                /* --- */
                return $this->_applyTemplate('rescue', [
                    'ip'        => $ip,
                    'mac'       => $mac,
                    'nonce'     => $this->_nonce(),
                    'profiles'  => $profiles['data'],
                    'errormsg'      => $error,
                    'ask_ipmi_password' => ($rebootmethod == 'ipmi')
                ]);
            }
        } catch (Exception $e) {
            return $this->_applyTemplate('rescue', [
                'errormsg' => $e->getMessage()
            ]);
        }
    }

    /**
     * AJAX status poll.
     * @param mixed $mac
     * @param mixed $lastevent
     */
    public function ajaxStatusPoll($mac, $lastevent)
    {
        // wait up to 28 seconds for new event
        $eventnr = $this->_api->longPoll($lastevent, 28);
        $status  = $this->_api->getProvisioningStatusByServer($mac);
        if ($status) {
            $status = $status['statusmsg'];
        }

        echo $this->Json->encode(['eventnr' => $eventnr, 'statusmsg' => $status]);
        exit(0);
    }

    public function tabDatatraffic($package, $service, array $get=null, array $post=null, array $files=null)
    {
        $mac = $this->_getMACfromService($service);

        /* get the number of network connections associated with the server,
           and the time the data was first and last updated */
        $info = $this->_api->getAvailableBandwidthData($mac);

        /* check when the customer purchased the server, to hide traffic from previous customers */
        $regdate = strtotime($service->date_added);

        /* show graphs by calendar month */
        $day     = 0;

        /* this month's graph */
        $startgraph1 = mktime(0, 0, 0, date('n'), $day, date('Y'));
        $endgraph1   = mktime(0, 0, 0, date('n') + 1, $day, date('Y'));
        $startgraph1 = max($startgraph1, $info['start'], $regdate);
        $endgraph1     = $info['last'];

        /* last month's graph */
        $startgraph2 = mktime(0, 0, 0, date('n') - 1, $day, date('Y'));
        $endgraph2   = mktime(0, 0, 0, date('n'), $day, date('Y'));

        if ($endgraph2 < $info['start'] || $endgraph2 < $regdate) {
            /* we don't have data from last month */
            $startgraph2 = $endgraph2 = 0;
        } else {
            $startgraph2 = max($startgraph2, $info['start'], $regdate);
        }

        $currentGraphs   = [];
        $lastMonthGraphs = [];

        if ($endgraph1 > $startgraph1) {
            for ($port = 0; $port < $info['ports']; $port++) {
                $currentGraphs[] = 'data:image/png;base64,' . $this->_api->generateBandwidthGraph(['host' => $mac, 'port' => $port, 'start' => $startgraph1, 'end' => $endgraph1]);

                if ($startgraph2) {
                    $lastMonthGraphs[] = 'data:image/png;base64,' . $this->_api->generateBandwidthGraph(['host' => $mac, 'port' => $port, 'start' => $startgraph2, 'end' => $endgraph2]);
                }
            }
        }

        return $this->_applyTemplate('datatraffic', [
            'ports'              => $info['ports'],
            'currentGraphs'   => $currentGraphs,
            'lastMonthGraphs' => $lastMonthGraphs
        ]);
    }

    public function tabSensors($package, $service, array $get=null, array $post=null, array $files=null)
    {
        $mac  = $this->_getMACfromService($service);
        $info = $this->_api->getHost($mac);
        if (empty($info['ipmi_ip']) || $info['ipmi_type'] == 'AMT') {
            return $this->_applyTemplate('sensors', []);
        }

        $response = $this->_api->submitIPMI([
            'ip' => $info['ipmi_ip'],
            'username' => $info['ipmi_user'],
            'password' => '',
            'ipmi_type' => 'v2',
            'cmd' => 'sensor'
        ]);
        $lines   = explode("\n", $response['result']);
        $sensors = [];

        foreach ($lines as $line) {
            $d = explode('|', $line);
            if (count($d) == 10) {
                $d = array_map('trim', $d);
                $unit = $d[2];
                /* We are interested in temperature, fan and power usage sensors */
                if ($unit == 'degrees C' || $unit == 'RPM' || $unit == 'Watts') {
                    $sensor = [
                        'name'        => $d[0],
                        'value'       => $d[1],
                        'unit'        => $d[2],
                        'status'      => $d[3],
                        'min'         => $d[4],
                        'low_crit'    => $d[5],
                        'low_noncrit' => $d[6],
                        'up_noncrit'  => $d[7],
                        'up_crit'     => $d[8],
                        'max'         => $d[9]
                    ];

                    if ($sensor['min'] == 'na') {
                        $sensor['min'] = min(0, $sensor['value']);
                    }
                    if ($sensor['max'] == 'na') {
                        switch ($unit) {
                            case 'degrees C':
                                $sensor['max'] = 80;
                                break;
                            case 'RPM':
                                $sensor['max'] = 10000;
                                break;
                            case 'Watts':
                                $sensor['max'] = 1000;
                                break;
                        }
                        $sensor['max'] = max($sensor['max'], $sensor['value']);
                        if ($sensor['up_crit'] != 'na') {
                            $sensor['max'] = max($sensor['max'], $sensor['up_crit'] + 5);
                        }
                    }

                    if ($sensor['value'] != 'na') {
                        $sensors[] = $sensor;
                    }
                }
            }
        }

        return $this->_applyTemplate('sensors', [
            'sensors' => $sensors
        ]);
    }

    protected function connect($module_row = false)
    {
        if ($this->_api) {
            return;
        } /* Already connected */
        if (!$module_row) {
            $module_row = $this->getModuleRow();
        }

        if (!$module_row) {
            throw new Exception($this->l('error.no_module_row'));
        }
        require_once __DIR__ . DS . 'apis' . DS . 'nocps_api.php';

        /* Include Blesta userid with requests for logging purposes */
        $loguser = '';
        if (!empty($_SESSION['blesta_id'])) {
            $loguser .= 'blesta_id ' . $_SESSION['blesta_id'];
        }
        if (!empty($_SESSION['blesta_staff_id'])) {
            $loguser .= 'blesta_staff_id ' . $_SESSION['blesta_staff_id'];
        }

        $this->_api = new nocps_api($module_row->meta->host_name, $module_row->meta->user_name, $module_row->meta->password,
                        isset($module_row->meta->ssl_verify) && $module_row->meta->ssl_verify != 'false', $loguser);
    }

    public function validateConnection($password, $hostname, $username, $ssl_verify)
    {
        try {
            require_once __DIR__ . DS . 'apis' . DS . 'nocps_api.php';

            $this->_api = new nocps_api($hostname, $username, $password, $ssl_verify != 'false');
            $this->_api->getPools();

            return true;
        } catch (Exception $e) {
        }

        return false;
    }

    /* Module row management functions. */

    /**
     * Returns the rendered view of the manage module page.
     *
     * @param mixed $module A stdClass object representing the module and its rows
     * @param array $vars An array of post data submitted to or on the manager module page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the manager module page
     */
    public function manageModule($module, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('manage', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView($this->_viewspath);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        $this->view->set('module', $module);

        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the add module row page.
     *
     * @param array $vars An array of post data submitted to or on the add module row page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the add module row page
     */
    public function manageAddRow(array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('add_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView($this->_viewspath);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        // Set unspecified checkboxes
        if (!empty($vars)) {
            if (empty($vars['create_new_account'])) {
                $vars['create_new_account'] = 'false';
            }
            if (empty($vars['ssl_verify'])) {
                $vars['ssl_verify'] = 'false';
            }
        }

        $this->view->set('vars', (object) $vars);

        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the edit module row page.
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     * @param array $vars An array of post data submitted to or on the edit module row page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the edit module row page
     */
    public function manageEditRow($module_row, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('edit_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView($this->_viewspath);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        if (empty($vars)) {
            $vars = $module_row->meta;
        } else {
            // Set unspecified checkboxes
            if (empty($vars['create_new_account'])) {
                $vars['create_new_account'] = 'false';
            }
            if (empty($vars['ssl_verify'])) {
                $vars['ssl_verify'] = 'false';
            }
        }

        $this->view->set('vars', (object) $vars);

        return $this->view->fetch();
    }

    /**
     * Adds the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being added. Returns a set of data, which may be
     * a subset of $vars, that is stored for this module row.
     *
     * @param array $vars An array of module info to add
     * @return array A numerically indexed array of meta fields for the module row containing:
     * 	- key The key for this meta field
     * 	- value The value for this key
     * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function addModuleRow(array &$vars)
    {
        $meta_fields = ['server_name', 'host_name', 'user_name', 'password',
            'create_new_account', 'ssl_verify', 'notes'];
        $encrypted_fields = ['user_name', 'password'];

        // Set unspecified checkboxes
        if (empty($vars['create_new_account'])) {
            $vars['create_new_account'] = 'false';
        }
        if (empty($vars['ssl_verify'])) {
            $vars['ssl_verify'] = 'false';
        }

        $this->Input->setRules($this->getRowRules($vars));

        // Validate module row
        if ($this->Input->validates($vars)) {
            if ($vars['create_new_account'] != 'false') {
                /* Create new NOC-PS account, restricted to Blesta's IP */
                $randompass = base64_encode(openssl_random_pseudo_bytes(32));
                //$ip = $_SERVER['SERVER_ADDR'];
                $ip = $this->_api->getRemoteIP();
                $vars['user_name'] = 'blesta_' . $ip;
                $vars['password'] = $randompass;
                $p = ['username' => $vars['user_name'], 'restrict_ip' => $ip, 'password' => $randompass, 'password2' => $randompass];

                $r = $this->_api->submitUser($p);

                if (!$r['success']) {
                    /* User may already exist. Try to update */
                    $p['current_username'] = $p['username'];
                    $r = $this->_api->submitUser($p);
                    if (!$r['success']) {
                        $this->addError('api', print_r($r['errors'], true));

                        return;
                    }
                }
            }

            // Build the meta data for this row
            $meta = [];
            foreach ($vars as $key => $value) {
                if (in_array($key, $meta_fields)) {
                    $meta[] = [
                        'key'=>$key,
                        'value'=>$value,
                        'encrypted'=>in_array($key, $encrypted_fields) ? 1 : 0
                    ];
                }
            }

            return $meta;
        }
    }

    /**
     * Edits the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being updated. Returns a set of data, which may be
     * a subset of $vars, that is stored for this module row.
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     * @param array $vars An array of module info to update
     * @return array A numerically indexed array of meta fields for the module row containing:
     * 	- key The key for this meta field
     * 	- value The value for this key
     * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function editModuleRow($module_row, array &$vars)
    {
        if (empty($vars['password'])) {
            $vars['password'] = $module_row->meta->password;
        }

        return $this->addModuleRow($vars);
    }

    /**
     * Deletes the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being deleted.
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     */
    public function deleteModuleRow($module_row)
    {
        if ($module_row->meta->user_name == 'blesta_' . $_SERVER['SERVER_ADDR']) {
            /* Delete the NOC-PS user we created for Blesta */
            try {
                $this->connect($module_row);
                $this->_api->deleteUser($module_row->meta->user_name);
            } catch (Exception $e) {
            }
        }
    }

    protected function getRowRules(&$vars)
    {
        $rules = [
            'server_name'=>[
                'valid'=>[
                    'rule'=>'isEmpty',
                    'negate'=>true,
                    'message'=>Language::_('Nocprovisioning.!error.server_name_valid', true)
                ]
            ],
            'host_name'=>[
                'valid'=>[
                    'rule'=>[[$this, 'validateHostName']],
                    'message'=>Language::_('Nocprovisioning.!error.host_name_valid', true)
                ]
            ],
            'user_name'=>[
                'valid'=>[
                    'rule'=>'isEmpty',
                    'negate'=>true,
                    'message'=>Language::_('Nocprovisioning.!error.user_name_valid', true)
                ]
            ],
            'password'=>[
                'valid'=>[
                    'last'=>true,
                    'rule'=>'isEmpty',
                    'negate'=>true,
                    'message'=>Language::_('Nocprovisioning.!error.remote_password_valid', true)
                ],
                'valid_connection'=>[
                    'rule'=>[[$this, 'validateConnection'], $vars['host_name'], $vars['user_name'], $vars['ssl_verify']],
                    'message'=>Language::_('Nocprovisioning.!error.remote_password_valid_connection', true)
                ]
            ]
        ];

        return $rules;
    }

    /**
     * Validates that the given hostname is valid.
     *
     * @param string $host_name The host name to validate
     * @return bool True if the hostname is valid, false otherwise
     */
    public function validateHostName($host_name)
    {
        if (strlen($host_name) > 255) {
            return false;
        }

        return $this->Input->matches($host_name, "/^([a-z0-9]|[a-z0-9][a-z0-9\-]{0,61}[a-z0-9])(\.([a-z0-9]|[a-z0-9][a-z0-9\-]{0,61}[a-z0-9]))+$/");
    }
}
