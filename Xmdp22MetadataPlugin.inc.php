<?php

/**
 * @file plugins/metadata/xmdp22/Xmdp22MetadataPlugin.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Xmdp22MetadataPlugin
 * @ingroup plugins_metadata_xmdp22
 *
 * @brief XMetaDissPlus 2.2 metadata plugin
 */

import('lib.pkp.classes.plugins.MetadataPlugin');

class Xmdp22MetadataPlugin extends MetadataPlugin {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	//
	// Override protected template methods from Plugin
	//
	/**
	* @copydoc Plugin::getName()
	*/
	function getName() {
		return 'Xmdp22MetadataPlugin';
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.metadata.xmdp22.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.metadata.xmdp22.description');
	}

	/**
	 * @see PKPPlugin::getManagementVerbs()
	 */
	function getManagementVerbs() {
		return array(array('settings', __('manager.plugins.settings')));
	}

	function getActions($request, $actionArgs) {
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		return array_merge(
				$this->getEnabled()?array(
						new LinkAction(
								'settings',
								new AjaxModal(
										$router->url($request, null, null, 'manage', null, $actionArgs),
										$this->getDisplayName()
								),
								__('manager.plugins.settings'),
								null
						),
				):array(),
				parent::getActions($request, $actionArgs)
		);
	}

	/**
	 * @copydoc PKPPlugin::manage()
	 */
	function manage($args, $request) {
		$notificationManager = new NotificationManager();
		$user = $request->getUser();
		$press = $request->getPress();

		$settingsFormName = $this->getSettingsFormName();
		$settingsFormNameParts = explode('.', $settingsFormName);
		$settingsFormClassName = array_pop($settingsFormNameParts);
		$this->import($settingsFormName);
		$form = new $settingsFormClassName($this, $press->getId());
		if ($request->getUserVar('save')) {
			$form->readInputData();
			if ($form->validate()) {
				$form->execute();
				$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_SUCCESS);
				return new JSONMessage(true);
			} else {
				return new JSONMessage(true, $form->fetch($request));
			}
		} else {
			$form->initData();
			return new JSONMessage(true, $form->fetch($request));
		}
	}

	/**
	 * @see PubIdPlugin::getSettingsFormName()
	 */
	function getSettingsFormName() {
		return 'form.Xmdp22SettingsForm';
	}

	/**
	 * Access settings.
	 * @param string $fieldName
	 * @param int $pressId
	 */
	function getData($fieldName, $pressId) {
		$fieldName = str_replace(":", "_", $fieldName);
		return $this->getSetting($pressId, $fieldName);
	}

	/**
	 * @copydoc MetadataPlugin::supportsFormat()
	 */
	public function supportsFormat($format) {
		return $format === 'xmdp22';
	}

	/**
	 * @copydoc MetadataPlugin::getSchemaObject()
	 */
	public function getSchemaObject($format) {
		assert($this->supportsFormat($format));
		import('plugins.metadata.xmdp22.schema.Xmdp22Schema');
		return new Xmdp22Schema();
	}
}

?>
