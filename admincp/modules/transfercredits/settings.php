<?php
/**
 * Transfer Credits
 * https://webenginecms.org/
 * 
 * @version 1.3.0
 * @author Lautaro Angelico <http://lautaroangelico.com/>
 * @copyright (c) 2013-2021 Lautaro Angelico, All Rights Reserved
 * @build w3c8c718b75a0f1fa1a557f7f9d70877
 */

function saveChanges() {
    global $_POST;
    foreach($_POST as $setting) {
        if(!check_value($setting)) {
            message('error', 'Missing data (complete all fields).');
            return;
        }
    }
    $xmlPath = __PATH_TRANSFERCREDITS_ROOT__.'config.xml';
    $xml = simplexml_load_file($xmlPath);

	$xml->enable_transfer_tax = $_POST['setting_1'];
	
	if(!Validator::UnsignedNumber($_POST['setting_2'])) throw new Exception('The transfer tax percentage must be a numeric value.');
	$xml->transfer_tax = $_POST['setting_2'];
	
	$xml->credit_configs = $_POST['setting_3'];
	
	if(!Validator::UnsignedNumber($_POST['setting_4'])) throw new Exception('The transfer minimum limit must be a numeric value.');
	$xml->transfer_minimum_limit = $_POST['setting_4'];
	
	if(!Validator::UnsignedNumber($_POST['setting_5'])) throw new Exception('The transfer maximum limit must be a numeric value.');
	$xml->transfer_maximum_limit = $_POST['setting_5'];
	
	$xml->enable_email_verification = $_POST['setting_6'];
	$xml->ignore_receiver_online_status = $_POST['setting_7'];
	
	
    $save = @$xml->asXML($xmlPath);
	if(!$save) throw new Exception('There has been an error while saving changes.');
}

if(check_value($_POST['submit_changes'])) {
	try {
		
		saveChanges();
		message('success', 'Settings successfully saved.');
		
	} catch (Exception $ex) {
		message('error', $ex->getMessage());
	}
}

if(check_value($_GET['checkusercplinks'])) {
	try {
		$TransferCredits = new \Plugin\TransferCredits\TransferCredits();
		$TransferCredits->checkPluginUsercpLinks();
		message('success', 'UserCP Links Successfully Added!');
	} catch (Exception $ex) {
		message('error', $ex->getMessage());
	}
}

// load configs
$pluginConfig = simplexml_load_file(__PATH_TRANSFERCREDITS_ROOT__.'config.xml');
if(!$pluginConfig) throw new Exception('Error loading config file.');
$pluginConfig = convertXML($pluginConfig->children());
if(!is_array($pluginConfig)) throw new Exception('Error loading config file.');

?>
<h2>Transfer Credits Settings</h2>
<form action="" method="post">

	<table class="table table-striped table-bordered table-hover module_config_tables">
		<tr>
            <th>Transfer Tax<br/><span>Enable/disable the transfer tax.</span></th>
            <td>
                <?php enabledisableCheckboxes('setting_1', $pluginConfig['enable_transfer_tax'], 'Enabled', 'Disabled'); ?>
            </td>
        </tr>
        <tr>
            <th>Transfer Tax Percent<br/><span></span></th>
            <td>
				<div class="input-group">
					<input class="form-control" type="text" name="setting_2" value="<?php echo $pluginConfig['transfer_tax']; ?>"/>
					<div class="input-group-addon">%</div>
				</div>
            </td>
        </tr>
		<tr>
            <th>Allowed Credit Configurations<br/><span>List of credit configuration id's that are allowed to be transferred between players. Separate each one by a comma (without spaces).</span></th>
            <td>
                <input class="form-control" type="text" name="setting_3" value="<?php echo $pluginConfig['credit_configs']; ?>"/>
            </td>
        </tr>
		<tr>
            <th>Transfer Minimum Limit<br/><span>Minimum amount of credits to transfer.</span></th>
            <td>
				<input class="form-control" type="text" name="setting_4" value="<?php echo $pluginConfig['transfer_minimum_limit']; ?>"/>
            </td>
        </tr>
		<tr>
            <th>Transfer Maximum Limit<br/><span>Maximum amount of credits to transfer.</span></th>
            <td>
				<input class="form-control" type="text" name="setting_5" value="<?php echo $pluginConfig['transfer_maximum_limit']; ?>"/>
            </td>
        </tr>
		<tr>
            <th>Email Verification<br/><span>If enabled, the account receiving the credits will have to verify the transfer with a special link sent to their email address before they receive the credits.</span></th>
            <td>
                <?php enabledisableCheckboxes('setting_6', $pluginConfig['enable_email_verification'], 'Enabled', 'Disabled'); ?>
            </td>
        </tr>
		<tr>
            <th>Ignore Receiver Online Status<br/><span>If email verification is disabled, you can choose to enable/disable checking the online status of the receiver account. This depends if your gameserver credit system allows the modification of credits while the account is online.</span></th>
            <td>
                <?php enabledisableCheckboxes('setting_7', $pluginConfig['ignore_receiver_online_status'], 'Enabled', 'Disabled'); ?>
            </td>
        </tr>
		<tr>
            <td colspan="2"><input type="submit" name="submit_changes" value="Save Changes" class="btn btn-success"/></td>
        </tr>
    </table>
</form>

<h2>UserCP Links</h2>
<p>Click the button below to automatically add the plugin's links to the user control panel menu.</p>
<a href="<?php echo admincp_base('transfercredits&page=settings&checkusercplinks=1'); ?>" class="btn btn-primary">Add UserCP Links</a>