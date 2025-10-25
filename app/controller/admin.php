<?php

/**
 * Class AdminController
 * 
 * Gateway to administrative operations
 */
class AdminController extends BaseController {
    /**
	 * Perform base initialization
	 * 
	 * @return void
	 */
    public function __construct()
    {
        parent::__construct();

        if (!UserModel::isCurrentlyAdmin()) {
            header('Location: /');
            exit();
        }
    }

    /**
	 * Handles URL: /admin
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\ViewHandler
	 */
	public function index($request)
	{
		$user = UserModel::getAuthUser();
		$locs = LocationsModel::getAll(false);
        $user_accounts = UserModel::getAll();
		$calendar_classes = CalendarClassModel::getAll();
		$mail_encryption_types = AppModel::getMailEncryptionTypes();
		$themes = ThemeModule::getList();
		$api_keys = ApiModel::getKeys();
		
		$new_version = null;
		$current_version = null;

		$check_version = $request->params()->query('cv', false);

		try {
			if ($check_version) {
				$new_version = VersionModule::getVersion();
				$current_version = config('version') ?? '1';
			}
		} catch (\Exception $e) {
			addLog(ASATRU_LOG_ERROR, $e->getMessage());
		}

		$timezone_identifiers = timezone_identifiers_list();
		$current_timezone = app('timezone', date_default_timezone_get());

		$global_attributes = CustAttrSchemaModel::getAll(false);
		$plant_attributes = PlantDefAttrModel::getAll();

		$bulk_cmds = CustBulkCmdModel::getCmdList();
		
		return parent::view(['content', 'admin'], [
			'user' => $user,
			'locations' => $locs,
			'user_accounts' => $user_accounts,
			'calendar_classes' => $calendar_classes,
			'mail_encryption_types' => $mail_encryption_types,
			'themes' => $themes,
			'api_keys' => $api_keys,
			'timezone_identifiers' => $timezone_identifiers,
			'current_timezone' => $current_timezone,
			'global_attributes' => $global_attributes,
			'plant_attributes' => $plant_attributes,
			'bulk_cmds' => $bulk_cmds,
			'new_version' => $new_version,
			'current_version' => $current_version
		]);
	}

	/**
	 * Handles URL: /admin/environment/save
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function save_environment($request)
	{
		try {
			$workspace = $request->params()->query('workspace', app('workspace'));
			$lang = $request->params()->query('lang', app('language'));
			$timezone = $request->params()->query('timezone', app('timezone'));
			$scroller = (bool)$request->params()->query('scroller', 0);
			$quick_add = (bool)$request->params()->query('quick_add', 0);
			$enabletasks = (bool)$request->params()->query('enabletasks', 0);
			$enableinventory = (bool)$request->params()->query('enableinventory', 0);
			$enablecalendar = (bool)$request->params()->query('enablecalendar', 0);
			$enablechat = (bool)$request->params()->query('enablechat', 0);
			$enablesysmsgs = (bool)$request->params()->query('enablesysmsgs', 0);
			$system_message_plant_log = (bool)$request->params()->query('system_message_plant_log', 0);
			$onlinetimelimit = (int)$request->params()->query('onlinetimelimit', app('chat_timelimit'));
			$chatonlineusers = (bool)$request->params()->query('chatonlineusers', 0);
			$chattypingindicator = (bool)$request->params()->query('chattypingindicator', 0);
			$enablehistory = (bool)$request->params()->query('enablehistory', 0);
			$history_name = $request->params()->query('history_name', app('history_name'));
			$enablephotoshare = (bool)$request->params()->query('enablephotoshare', 0);
			$custom_media_share_host = $request->params()->query('custom_media_share_host', share_api_host());
			$cronpw = $request->params()->query('cronpw', app('cronjob_pw'));
			$custom_head_code = $request->params()->query('custom_head_code', app('custom_head_code'));
			$enablepwa = (bool)$request->params()->query('enablepwa', 0);
			$plantrec_enable = (bool)$request->params()->query('plantrec_enable', 0);
			$plantrec_apikey = $request->params()->query('plantrec_apikey', app('plantrec_apikey'));
			$plantrec_quickscan = (bool)$request->params()->query('plantrec_quickscan', 0);

			$set = [
				'workspace' => $workspace,
				'language' => $lang,
				'timezone' => $timezone,
				'scroller' => $scroller,
				'quick_add' => $quick_add,
				'tasks_enable' => $enabletasks,
				'inventory_enable' => $enableinventory,
				'calendar_enable' => $enablecalendar,
				'chat_enable' => $enablechat,
				'chat_system' => $enablesysmsgs,
				'system_message_plant_log' => $system_message_plant_log,
				'chat_timelimit' => $onlinetimelimit,
				'chat_showusers' => $chatonlineusers,
				'chat_indicator' => $chattypingindicator,
				'history_enable' => $enablehistory,
				'history_name' => $history_name,
				'enable_media_share' => $enablephotoshare,
				'custom_media_share_host' => rtrim($custom_media_share_host, '/'),
				'cronjob_pw' => $cronpw,
				'custom_head_code' => $custom_head_code,
				'pwa_enable' => $enablepwa,
				'plantrec_enable' => $plantrec_enable,
				'plantrec_apikey' => $plantrec_apikey,
				'plantrec_quickscan' => $plantrec_quickscan
			];

			AppModel::updateSet($set);

			if ($enablepwa) {
				AppModel::writeManifest($workspace);
			}
			
			FlashMessage::setMsg('success', __('app.environment_settings_saved'));

			return redirect('/admin?tab=environment');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return back();
		}
	}

	/**
	 * Handles URL: /admin/environment/boolean/toggle
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function toggle_boolean_value($request)
	{
		try {
			$name = $request->params()->query('name');

			$value = (bool)app($name, 0);
			$value = !$value;

			AppModel::updateSingle($name, $value);

			return json([
				'code' => 200,
				'value' => $value
			]);
		} catch (\Exception $e) {
			return json([
				'code' => 500,
				'msg' => $e->getMessage()
			]);
		}
	}

	/**
	 * Handles URL: /admin/user/create
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function create_user($request)
	{
		try {
			$name = $request->params()->query('name', null);
			$email = $request->params()->query('email', null);
			$sendmail = (int)$request->params()->query('sendmail', 0);
			
			$password = UserModel::createUser($name, $email, $sendmail);

			FlashMessage::setMsg('success', __('app.user_created_successfully'));

			return redirect('/admin?tab=users' . (($password) ? '&user_password=' . $password : ''));
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return redirect('/admin?tab=users');
		}
	}

	/**
	 * Handles URL: /admin/user/update
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function update_user($request)
	{
		try {
			$id = $request->params()->query('id');
			$name = $request->params()->query('name', null);
			$email = $request->params()->query('email', null);
			$admin = $request->params()->query('admin', 0);
			
			UserModel::updateUser($id, $name, $email, (int)$admin);

			FlashMessage::setMsg('success', __('app.user_updated_successfully'));

			return redirect('/admin?tab=users');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return back();
		}
	}

	/**
	 * Handles URL: /admin/user/remove
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function remove_user($request)
	{
		try {
			$id = $request->params()->query('id');
			
			UserModel::removeUser($id);

			FlashMessage::setMsg('success', __('app.user_removed_successfully'));

			return redirect('/admin?tab=users');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return back();
		}
	}

	/**
	 * Handles URL: /admin/location/add
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function add_location($request)
	{
		try {
			$name = $request->params()->query('name', null);
			
			LocationsModel::addLocation($name);

			FlashMessage::setMsg('success', __('app.location_added_successfully'));

			return redirect('/admin?tab=locations');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return back();
		}
	}

	/**
	 * Handles URL: /admin/location/update
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function update_location($request)
	{
		try {
			$id = $request->params()->query('id');
			$name = $request->params()->query('name', null);
			$active = $request->params()->query('active', 0);
			
			LocationsModel::editLocation($id, $name, (int)$active);

			FlashMessage::setMsg('success', __('app.location_updated_successfully'));

			return redirect('/admin?tab=locations');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return back();
		}
	}
	
	/**
	 * Handles URL: /admin/location/photo
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function set_location_photo($request)
	{
		try {
			$ident = $request->params()->query('ident');
			
			LocationsModel::setPhoto($ident);

			FlashMessage::setMsg('success', __('app.location_updated_successfully'));

			return redirect('/admin?tab=locations');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return back();
		}
	}

	/**
	 * Handles URL: /admin/location/remove
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function remove_location($request)
	{
		try {
			$id = $request->params()->query('id');
			$target = $request->params()->query('target');
			
			LocationsModel::removeLocation($id, $target);

			FlashMessage::setMsg('success', __('app.location_removed_successfully'));

			return redirect('/admin?tab=locations');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return redirect('/admin?tab=locations');
		}
	}

	/**
	 * Handles URL: /admin/auth/proxy/save
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function save_proxy_auth_settings($request)
	{
		try {
			$auth_proxy_enable = (bool)$request->params()->query('auth_proxy_enable', 0);
			$auth_proxy_header_email = $request->params()->query('auth_proxy_header_email', '');
			$auth_proxy_header_username = $request->params()->query('auth_proxy_header_username', '');
			$auth_proxy_sign_up = (bool)$request->params()->query('auth_proxy_sign_up', 0);
			$auth_proxy_whitelist = $request->params()->query('auth_proxy_whitelist', '');
			$auth_proxy_hide_logout = (bool)$request->params()->query('auth_proxy_hide_logout', 0);

			$set = [
				'auth_proxy_enable' => $auth_proxy_enable,
				'auth_proxy_header_email' => $auth_proxy_header_email,
				'auth_proxy_header_username' => $auth_proxy_header_username,
				'auth_proxy_sign_up' => $auth_proxy_sign_up,
				'auth_proxy_whitelist' => $auth_proxy_whitelist,
				'auth_proxy_hide_logout' => $auth_proxy_hide_logout
			];

			AppModel::updateSet($set);

			FlashMessage::setMsg('success', __('app.proxy_auth_settings_saved_successfully'));

			return redirect('/admin?tab=auth');
        } catch (\Exception $e) {
            FlashMessage::setMsg('error', $e->getMessage());
			return redirect('/admin?tab=auth');
        }
	}

	/**
	 * Handles URL: /admin/attribute/schema/add
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function add_attribute_schema($request)
	{
		try {
			$label = $request->params()->query('label');
			$datatype = $request->params()->query('datatype');
			
			CustAttrSchemaModel::addSchema($label, $datatype);

			FlashMessage::setMsg('success', __('app.attribute_schema_added_successfully'));

			return redirect('/admin?tab=attributes');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return redirect('/admin?tab=attributes');
		}
	}

	/**
	 * Handles URL: /admin/attribute/schema/edit
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function edit_attribute_schema($request)
	{
		try {
			$id = $request->params()->query('id');
			$label = $request->params()->query('label');
			$datatype = $request->params()->query('datatype');
			$active = (bool)$request->params()->query('active', 0);
			
			CustAttrSchemaModel::editSchema($id, $label, $datatype, $active);

			FlashMessage::setMsg('success', __('app.attribute_schema_edited_successfully'));

			return redirect('/admin?tab=attributes');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return redirect('/admin?tab=attributes');
		}
	}

	/**
	 * Handles URL: /admin/attribute/schema/remove
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function remove_attribute_schema($request)
	{
		try {
			$id = $request->params()->query('id');
			
			CustAttrSchemaModel::removeSchema($id);

			FlashMessage::setMsg('success', __('app.attribute_schema_removed_successfully'));

			return redirect('/admin?tab=attributes');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return redirect('/admin?tab=attributes');
		}
	}

	/**
	 * Handles URL: /admin/attribute/update
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function update_attribute($request)
	{
		try {
			$name = $request->params()->query('name');

			$newValue = PlantDefAttrModel::toggle($name);

			return json([
				'code' => 200,
				'active' => $newValue
			]);
		} catch (\Exception $e) {
			return json([
				'code' => 500,
				'msg' => $e->getMessage()
			]);
		}
	}

	/**
	 * Handles URL: /admin/attributes/bulkcmd/add
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function add_bulk_cmd($request)
	{
		try {
			$label = $request->params()->query('label');
			$attribute = $request->params()->query('attribute');
			$datatype = $request->params()->query('datatype');
			$styles = $request->params()->query('styles');
			
			CustBulkCmdModel::addCmd($label, $attribute, $datatype, $styles);

			FlashMessage::setMsg('success', __('app.bulk_cmd_added_successfully'));

			return redirect('/admin?tab=attributes');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return redirect('/admin?tab=attributes');
		}
	}

	/**
	 * Handles URL: /admin/attributes/bulkcmd/edit
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function edit_bulk_cmd($request)
	{
		try {
			$id = $request->params()->query('id');
			$label = $request->params()->query('label');
			$attribute = $request->params()->query('attribute');
			$datatype = $request->params()->query('datatype');
			$styles = $request->params()->query('styles');
			
			CustBulkCmdModel::editCmd($id, $label, $attribute, $datatype, $styles);

			FlashMessage::setMsg('success', __('app.bulk_cmd_updated_successfully'));

			return redirect('/admin?tab=attributes');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return redirect('/admin?tab=attributes');
		}
	}

	/**
	 * Handles URL: /admin/attributes/bulkcmd/remove
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function remove_bulk_cmd($request)
	{
		try {
			$id = $request->params()->query('id');
			
			CustBulkCmdModel::removeCmd($id);

			FlashMessage::setMsg('success', __('app.bulk_cmd_removed_successfully'));

			return redirect('/admin?tab=attributes');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return redirect('/admin?tab=attributes');
		}
	}

	/**
	 * Handles URL: /admin/calendar/class/add
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function add_calendar_class($request)
	{
		try {
			$ident = $request->params()->query('ident', null);
			$name = $request->params()->query('name', null);
			$color_background = $request->params()->query('color_background', null);
			$color_border = $request->params()->query('color_border', null);
			
			CalendarClassModel::addClass($ident, $name, $color_background, $color_border);

			FlashMessage::setMsg('success', __('app.calendar_class_added_successfully'));

			return redirect('/admin?tab=calendar');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return back();
		}
	}

	/**
	 * Handles URL: /admin/calendar/class/edit
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function edit_calendar_class($request)
	{
		try {
			$id = $request->params()->query('id', null);
			$ident = $request->params()->query('ident', null);
			$name = $request->params()->query('name', null);
			$color_background = $request->params()->query('color_background', null);
			$color_border = $request->params()->query('color_border', null);
			
			CalendarClassModel::editClass($id, $ident, $name, $color_background, $color_border);

			FlashMessage::setMsg('success', __('app.calendar_class_edited_successfully'));

			return redirect('/admin?tab=calendar');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return back();
		}
	}

	/**
	 * Handles URL: /admin/calendar/class/remove
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function remove_calendar_class($request)
	{
		try {
			$id = $request->params()->query('id', null);
			
			CalendarClassModel::removeClass($id);

			return json([
				'code' => 200
			]);
		} catch (\Exception $e) {
			return json([
				'code' => 500,
				'msg' => $e->getMessage()
			]);
		}
	}

	/**
	 * Handles URL: /admin/media/logo
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function upload_media_logo($request)
	{
		try {
			if ((!isset($_FILES['asset'])) || ($_FILES['asset']['error'] !== UPLOAD_ERR_OK) || ($_FILES['asset']['type'] !== 'image/png')) {
				throw new \Exception('Failed to upload file or invalid file uploaded');
			}

			move_uploaded_file($_FILES['asset']['tmp_name'], public_path() . '/logo.png');

			FlashMessage::setMsg('success', __('app.media_saved'));

			return redirect('/admin?tab=media');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return back();
		}
	}

	/**
	 * Handles URL: /admin/media/banner
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function upload_media_banner($request)
	{
		try {
			if ((!isset($_FILES['asset'])) || ($_FILES['asset']['error'] !== UPLOAD_ERR_OK) || ($_FILES['asset']['type'] !== 'image/jpeg')) {
				throw new \Exception('Failed to upload file or invalid file uploaded');
			}

			move_uploaded_file($_FILES['asset']['tmp_name'], public_path() . '/img/banner.jpg');

			FlashMessage::setMsg('success', __('app.media_saved'));

			return redirect('/admin?tab=media');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return back();
		}
	}

	/**
	 * Handles URL: /admin/media/background
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function upload_media_background($request)
	{
		try {
			if ((!isset($_FILES['asset'])) || ($_FILES['asset']['error'] !== UPLOAD_ERR_OK) || ($_FILES['asset']['type'] !== 'image/jpeg')) {
				throw new \Exception('Failed to upload file or invalid file uploaded');
			}

			move_uploaded_file($_FILES['asset']['tmp_name'], public_path() . '/img/background.jpg');

			FlashMessage::setMsg('success', __('app.media_saved'));

			return redirect('/admin?tab=media');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return back();
		}
	}

	/**
	 * Handles URL: /admin/media/overlay/alpha
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function save_overlay_alpha($request)
	{
		try {
			$overlayalpha = $request->params()->query('overlayalpha', app('overlay_alpha'));
			
			AppModel::updateSingle('overlay_alpha', $overlayalpha);

			FlashMessage::setMsg('success', __('app.environment_settings_saved'));

			return redirect('/admin?tab=media');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return back();
		}
	}

	/**
	 * Handles URL: /admin/media/sound/message
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function upload_media_sound_message($request)
	{
		try {
			if ((!isset($_FILES['asset'])) || ($_FILES['asset']['error'] !== UPLOAD_ERR_OK) || ($_FILES['asset']['type'] !== 'audio/wav')) {
				throw new \Exception('Failed to upload file or invalid file uploaded');
			}

			move_uploaded_file($_FILES['asset']['tmp_name'], public_path() . '/snd/new_message.wav');

			FlashMessage::setMsg('success', __('app.media_saved'));

			return redirect('/admin?tab=media');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return back();
		}
	}

	/**
	 * Handles URL: /admin/mail/save
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function save_mail_settings($request)
	{
		try {
			$smtp_fromname = $request->params()->query('smtp_fromname', app('smtp_fromname'));
			$smtp_fromaddress = $request->params()->query('smtp_fromaddress', app('smtp_fromaddress'));
			$smtp_host = $request->params()->query('smtp_host', app('smtp_host'));
			$smtp_port = $request->params()->query('smtp_port', app('smtp_port'));
			$smtp_username = $request->params()->query('smtp_username', app('smtp_username'));
			$smtp_password = $request->params()->query('smtp_password', app('smtp_password'));
			$smtp_encryption = $request->params()->query('smtp_encryption', app('smtp_encryption'));
			$mail_rp_address = $request->params()->query('mail_rp_address', app('mail_rp_address'));

			if (substr($mail_rp_address, strlen($mail_rp_address) - 1) === '/') {
				$mail_rp_address = substr($mail_rp_address, 0, strlen($$mail_rp_address) - 1);
			}
			
			$set = [
				'smtp_fromname' => $smtp_fromname,
				'smtp_fromaddress' => $smtp_fromaddress,
				'smtp_host' => $smtp_host,
				'smtp_port' => $smtp_port,
				'smtp_username' => $smtp_username,
				'smtp_password' => $smtp_password,
				'smtp_encryption' => $smtp_encryption,
				'mail_rp_address' => $mail_rp_address
			];

			AppModel::updateSet($set);

			FlashMessage::setMsg('success', __('app.environment_settings_saved'));

			return redirect('/admin?tab=mail');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return back();
		}
	}

	/**
	 * Handles URL: /admin/mail/test
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function send_test_email($request)
	{
		try {
			$user = UserModel::getAuthUser();

			$mailobj = new Asatru\SMTPMailer\SMTPMailer();
			$mailobj->setRecipient($user->get('email'));
			$mailobj->setSubject('[' . env('APP_NAME') . '] Test E-Mail');
			$mailobj->setView('mail/mail_layout', [['mail_content', 'mail/mail_admintest']], ['user' => $user]);
			$mailobj->setProperties(mail_properties());
			$mailobj->send();

			return json([
				'code' => 200
			]);
		} catch (\Exception $e) {
			return json([
				'code' => 500,
				'msg' => $e->getMessage()
			]);
		}
	}

	/**
	 * Handles URL: /admin/cronjob/token
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function generate_cronjob_token($request)
	{
		try {
			$token = AppModel::generateCronjobToken();

			return json([
				'code' => 200,
				'token' => $token
			]);
		} catch (\Exception $e) {
			return json([
				'code' => 500,
				'msg' => $e->getMessage()
			]);
		}
	}

	/**
	 * Handles URL: /admin/themes/import
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
    public function import_theme($request)
    {
        try {
            $themes = ThemeModule::startImport();

            return json([
                'code' => 200,
				'themes' => $themes
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => $e->getMessage()
            ]);
        }
    }

	/**
	 * Handles URL: /admin/themes/remove
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function remove_theme($request)
	{
		try {
			$theme = $request->params()->query('theme', null);

            ThemeModule::removeTheme($theme);

            return json([
                'code' => 200
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => $e->getMessage()
            ]);
        }
	}

	/**
	 * Handles URL: /admin/weather/save
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function save_weather_data($request)
	{
		try {
			$enable = (bool)$request->params()->query('owm_enable', 0);
			$apikey = $request->params()->query('owm_apikey', app('owm_api_key'));
			$latitude = $request->params()->query('owm_latitude', app('owm_latitude'));
			$longitude = $request->params()->query('owm_longitude', app('owm_longitude'));
			$unittype = $request->params()->query('owm_unittype', app('owm_unittype'));
			$cache = $request->params()->query('owm_cache', app('owm_cache'));
			
			$set = [
				'owm_enable' => $enable,
				'owm_api_key' => $apikey,
				'owm_latitude' => $latitude,
				'owm_longitude' => $longitude,
				'owm_unittype' => $unittype,
				'owm_cache' => $cache
			];

			AppModel::updateSet($set);

			WeatherModule::clearCache();

			FlashMessage::setMsg('success', __('app.environment_settings_saved'));

			return redirect('/admin?tab=weather');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return back();
		}
	}

	/**
	 * Handles URL: /admin/api/add
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function add_api_key($request)
	{
		try {
			ApiModel::addKey();

			FlashMessage::setMsg('success', __('app.api_key_added'));

			return redirect('/admin?tab=api');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return back();
		}
	}

	/**
	 * Handles URL: /admin/api/{token}/remove
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function remove_api_key($request)
	{
		try {
			$token = $request->arg('token');

			ApiModel::removeKey($token);

			FlashMessage::setMsg('success', __('app.api_key_removed'));

			return redirect('/admin?tab=api');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return back();
		}
	}

	/**
	 * Handles URL: /admin/api/{id}/toggle
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function toggle_api_key($request)
	{
		try {
			$id = $request->arg('id');

            $active = ApiModel::toggleApiKey((int)$id);

            return json([
                'code' => 200,
				'active' => $active
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => $e->getMessage()
            ]);
        }
	}

	/**
	 * Handles URL: /admin/backup/cronjob/save
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function save_backup_cronjob_settings($request)
	{
		try {
			$auto_backup = (bool)$request->params()->query('auto_backup', 0);
			$backup_path = $request->params()->query('backup_path', null);

			$set = [
				'auto_backup' => $auto_backup,
				'backup_path' => $backup_path
			];

			AppModel::updateSet($set);

			FlashMessage::setMsg('success', __('app.backup_settings_stored'));

			return redirect('/admin?tab=backup');
        } catch (\Exception $e) {
            FlashMessage::setMsg('error', $e->getMessage());
			return back();
        }
	}

	/**
	 * Handles URL: /admin/cache/clear
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function clear_cache($request)
	{
		try {
			CacheModel::clear();

			$cache_dir = base_path() . '/cache';
			UtilsModule::clearFolder($cache_dir . '/HTML');
			UtilsModule::clearFolder($cache_dir . '/URI');

			return json(['code' => 200]);
		} catch (\Exception $e) {
			return json([
				'code' => 500,
				'msg' => $e->getMessage()
			]);
		}
	}
}
