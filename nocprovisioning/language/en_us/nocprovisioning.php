<?php
$lang['Nocprovisioning.name'] = "NOC Provisioning System (NOC-PS)";
$lang['Nocprovisioning.module_row'] = "NOC-PS management server";
$lang['Nocprovisioning.module_row_plural'] = "NOC-PS management servers";
$lang['Nocprovisioning.module_group'] = "Group";

$lang['Nocprovisioning.label_type'] = "Product type";
$lang['Nocprovisioning.dedicated_manual_assigned'] = "Dedicated server - manual assignment";
$lang['Nocprovisioning.dedicated_auto_assigned'] = "Dedicated server - automatic assignment";
$lang['Nocprovisioning.vps'] = "VPS";

$lang['Nocprovisioning.label_poolfrom'] = "Take server FROM pool (available servers pool)";
$lang['Nocprovisioning.label_poolto'] = "Move server TO pool after assignment";

$lang['Nocprovisioning.label_rebootmethod'] = "Reboot method";
$lang['Nocprovisioning.rebootmethod_auto'] = "Automatic (using stored passwords)";
$lang['Nocprovisioning.rebootmethod_ipmi'] = "IPMI (ask user for password)";
$lang['Nocprovisioning.rebootmethod_manual'] = "Manual (reboots done outside of panel)";

$lang['Nocprovisioning.label_node'] = "Hypervisor Node";
$lang['Nocprovisioning.label_subnet'] = "Subnet";
$lang['Nocprovisioning.label_datastore'] = "Datastore";
$lang['Nocprovisioning.label_network'] = "Network";
$lang['Nocprovisioning.label_vcpu'] = "vCPUs";
$lang['Nocprovisioning.label_memory'] = "Memory in MB";
$lang['Nocprovisioning.label_disk'] = "Disk space in MB";

$lang['Nocprovisioning.label_enable_provisioning'] = "Allow customer to (re)install the server's OS";
$lang['Nocprovisioning.label_enable_datatraffic'] = "Allow customer to view data traffic graphs";
$lang['Nocprovisioning.label_enable_power'] = "Allow customer to control power";
$lang['Nocprovisioning.label_enable_console'] = "Allow customer to access console";
$lang['Nocprovisioning.label_enable_sensors'] = "Allow customer to view IPMI sensor information";

$lang['Nocprovisioning.label_powerdown_on_suspend'] = "Power down server on suspend (warning: can corrupt file system)";
$lang['Nocprovisioning.label_powerdown_on_delete'] = "Power down server on service cancellation";
$lang['Nocprovisioning.label_deletevps_on_delete'] = "Delete VPS and all files on service cancellation";

$lang['Nocprovisioning.profilename'] = "Profile name";
$lang['Nocprovisioning.profiles'] = "Profiles";
$lang['Nocprovisioning.txt_lists'] = "<p>You can restrict the profiles your customer may install by entering the numeric profile ID number or one of the profile TAGS.<br>"
                                    ."E.g. to only allow installation of Linux profiles, enter tagname &quot;linux&quot; in the whitelist field.<br>"
                                    ."To enter multiple profiles/tags separate them by spaces, e.g. &quot;1 2 4 windows&quot;</p>";
$lang['Nocprovisioning.label_whitelist'] = "Profile whitelist (empty = allow all)";
$lang['Nocprovisioning.label_blacklist'] = "Profile blacklist";

$lang['Nocprovisioning.label_ip'] = "IP-address of the client's server";

$lang['Nocprovisioning.tab_provision'] = "Provision";
$lang['Nocprovisioning.tab_rescue'] = "Rescue system";
$lang['Nocprovisioning.tab_datatraffic'] = "Data traffic";
$lang['Nocprovisioning.tab_power'] = "Power control";
$lang['Nocprovisioning.tab_console'] = "Console";
$lang['Nocprovisioning.tab_sensors'] = "Sensors";

$lang['Nocprovisioning.ip'] = "IP-address";
$lang['Nocprovisioning.mac'] = "MAC-address";
$lang['Nocprovisioning.hostname'] = "Hostname";
$lang['Nocprovisioning.ipmipassword'] = "Your server's IPMI password";

$lang['Nocprovisioning.result_last_action'] = "Result of last action";
$lang['Nocprovisioning.error'] = "Error";
$lang['Nocprovisioning.no_server'] = "Your order has not been assigned to a server yet!";

$lang['Nocprovisioning.tab_power.title'] = "Power management";
$lang['Nocprovisioning.tab_power.status'] = "Power status";
$lang['Nocprovisioning.tab_power.action'] = "Power action";
$lang['Nocprovisioning.tab_power.on'] = "Power on";
$lang['Nocprovisioning.tab_power.off'] = "Power off";
$lang['Nocprovisioning.tab_power.reset'] = "Reset";
$lang['Nocprovisioning.tab_power.cycle'] = "Cycle power";
$lang['Nocprovisioning.tab_power.ctrlaltdel'] = "Send CTRL-ALT-DEL";
$lang['Nocprovisioning.tab_power.perform'] = "Perform action";

$lang['Nocprovisioning.tab_provision.title'] = "Provisioning";
$lang['Nocprovisioning.tab_provision.profile'] = "Installation profile";
$lang['Nocprovisioning.tab_provision.disk_layout'] = "Disk layout";
$lang['Nocprovisioning.tab_provision.package_selection'] = "Package selection";
$lang['Nocprovisioning.tab_provision.extras'] = "Extras";
$lang['Nocprovisioning.tab_provision.root_password'] = "Root user password";
$lang['Nocprovisioning.tab_provision.repeat_root_password'] = "Repeat root user password";
$lang['Nocprovisioning.tab_provision.admin_username'] = "Admin user (required for FreeBSD, Debian and Ubuntu)";
$lang['Nocprovisioning.tab_provision.admin_password'] = "User password";
$lang['Nocprovisioning.tab_provision.repeat_admin_password'] = "Repeat user password";
$lang['Nocprovisioning.tab_provision.provision_server'] = "Provision server (WARNING: overwrites data on disk)";
$lang['Nocprovisioning.tab_provision.provision_confirm'] = "This will delete all existing data on disk. Are you sure?";

$lang['Nocprovisioning.tab_provision.status.title'] = "Provisioning status";
$lang['Nocprovisioning.tab_provision.status.msg'] = "Your server is currently being provisioned... Be aware that this could take 10+ minutes to complete...";
$lang['Nocprovisioning.tab_provision.status.last_msg'] = "Last status message";
$lang['Nocprovisioning.tab_provision.status.finished'] = "Finished!";
$lang['Nocprovisioning.tab_provision.status.cancel'] = "Cancel provisioning";

$lang['Nocprovisioning.tab_rescue.title'] = "Start rescue system (accessible with SSH)";
$lang['Nocprovisioning.tab_rescue.profile'] = "Rescue system profile";
$lang['Nocprovisioning.tab_rescue.rescue_server'] = "Start rescue system";

$lang['Nocprovisioning.tab_datatraffic.title'] = "Data traffic Graphs";
$lang['Nocprovisioning.tab_datatraffic.no_info'] = "No data traffic information available for this server.";
$lang['Nocprovisioning.tab_datatraffic.current_month'] = "Current month";
$lang['Nocprovisioning.tab_datatraffic.previous_month'] = "Previous month";

$lang['Nocprovisioning.tab_console.title'] = "Console";
$lang['Nocprovisioning.tab_console.retrieving_info'] = "Retrieving console information...";
$lang['Nocprovisioning.tab_console.power_on_server'] = "Power on server";
$lang['Nocprovisioning.tab_console.activate'] = "Activate console";
$lang['Nocprovisioning.tab_console.info_html5'] = "Press the 'activate console' button to open the console in a new window.<br>"
	."The console feature requires a modern browser such as Firefox or Google Chrome supporting HTML 5 canvas and websockets.<br>"
	."In addition a SSL certificate must have been installed on the NOC-PS managament server by your provider.";
$lang['Nocprovisioning.tab_console.info_jnlp'] = "Press the 'activate console' button to open the console.<br>"
	."The console feature uses Java Webstart technology and requires that JAVA is installed on your computer.<br>"
        ."If you have problems accessing the console, try resetting the BMC.<br><br>";
$lang['Nocprovisioning.tab_console.resetbmc'] = "Reset BMC";
$lang['Nocprovisioning.tab_console.bmcreset_performed'] = "Performed BMC reset. It can take a few minutes before it is available again.";    
$lang['Nocprovisioning.tab_console.no_console_support'] = "This server does not have KVM-over-IP console functionality, or it uses technology that is not supported.";

$lang['Nocprovisioning.tab_sensors.title'] = "Sensors";
$lang['Nocprovisioning.tab_sensors.no_sensor_info'] = "No sensor information available";
$lang['Nocprovisioning.tab_sensors.warning_threshold'] = "Warning! Sensor value exceed thresholds! ";

// Module management
$lang['Nocprovisioning.add_module_row'] = "Add Server";
$lang['Nocprovisioning.manage.module_rows_title'] = "Servers";
$lang['Nocprovisioning.manage.module_rows_heading.name'] = "Server Label";
$lang['Nocprovisioning.manage.module_rows_heading.hostname'] = "Hostname";
$lang['Nocprovisioning.manage.module_rows_heading.accounts'] = "Accounts";
$lang['Nocprovisioning.manage.module_rows_heading.options'] = "Options";
$lang['Nocprovisioning.manage.module_rows.edit'] = "Edit";
$lang['Nocprovisioning.manage.module_rows.delete'] = "Delete";
$lang['Nocprovisioning.manage.module_rows.confirm_delete'] = "Are you sure you want to delete this server?";
$lang['Nocprovisioning.manage.module_rows_no_results'] = "There are no servers.";

// Add row
$lang['Nocprovisioning.add_row.box_title'] = "Add NOC-PS management server";
$lang['Nocprovisioning.add_row.basic_title'] = "Basic Settings";
$lang['Nocprovisioning.add_row.notes_title'] = "Notes";
$lang['Nocprovisioning.add_row.name_server_host_col'] = "Hostname";
$lang['Nocprovisioning.add_row.add_btn'] = "Add Server";

$lang['Nocprovisioning.edit_row.box_title'] = "Edit NOC-PS management server";
$lang['Nocprovisioning.edit_row.add_btn'] = "Edit Server";

$lang['Nocprovisioning.row_meta.server_name'] = "Server Label";
$lang['Nocprovisioning.row_meta.host_name'] = "Hostname of NOC-PS server";
$lang['Nocprovisioning.row_meta.user_name'] = "User Name (NOC-PS admin user)";
$lang['Nocprovisioning.row_meta.password'] = "Password";
$lang['Nocprovisioning.row_meta.create_new_account'] = "Create a new NOC-PS user for use by Blesta restricted to the webserver IP ";
$lang['Nocprovisioning.row_meta.ssl_verify'] = "Verify SSL certificate. Note: the NOC-PS server MUST have a proper SSL certificate if using the HTML5 noVNC console";

// Errors
$lang['Nocprovisioning.!error.server_name_valid'] = "You must enter a Server Label.";
$lang['Nocprovisioning.!error.host_name_valid'] = "The Hostname appears to be invalid.";
$lang['Nocprovisioning.!error.user_name_valid'] = "The User Name appears to be invalid.";
$lang['Nocprovisioning.!error.remote_password_valid'] = "The Password appears to be invalid.";
$lang['Nocprovisioning.!error.remote_password_valid_connection'] = "A connection to the server could not be established. Please check to ensure that the Hostname, User Name, and Remote Key are correct.";
$lang['Nocprovisioning.!error.hypervisor_valid'] = "You must specify a hypervisor";
$lang['Nocprovisioning.error.ip_not_in_db'] = "There is no server with this IP-address known in the NOC-PS database";
$lang['Nocprovisioning.error.no_server_assigned'] = "No server has been assigned yet";
$lang['Nocprovisioning.error.pool_is_same'] = "You must specify different pools to take a server from, and move it to";
$lang['Nocprovisioning.error.no_module_row'] = "Package not associated with NOC-PS server";
