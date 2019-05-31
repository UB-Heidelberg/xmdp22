<?php

/**
 * @file plugins/metadata/xmdp/form/XMDPSettingsForm.inc.php
 *
 * Copyright (c) 2015 Heidelberg University
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class XMDPSettingsForm
 * @ingroup plugins_metadata_xmdp22
 *
 * @brief Form for press managers to setup DOI plugin
 */

import('lib.pkp.classes.form.Form');

class Xmdp22SettingsForm extends Form {

	//
	// Private properties
	//
	/** @var integer */
	var $_pressId;

	/** @var array */
	var $_ddbKindOptions = array(
			"free" => "free",
			"domain" => "domain",
			"blocked" => "blocked",
			"unkown" => "unknown"
			);

	/** @var Xmdp22MetadataPlugin */
	var $_plugin;

	/**
	 * Get the plugin.
	 * @return Xmdp22MetadataPlugin
	 */
	function &_getPlugin() {
		return $this->_plugin;
	}


	//
	// Constructor
	//
	/**
	 * Constructor
	 * @param $plugin DOIPubIdPlugin
	 * @param $pressId integer
	 */
	function __construct($plugin, $pressId) {
		$this->_pressId = $pressId;
		$this->_plugin = $plugin;

		parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

		$this->addCheck(new FormValidator($this, 'cc_place' ,'required', 'plugins.metadata.xmdp22.manager.settings.cc.place.required'));
		$this->addCheck(new FormValidator($this, 'cc_address' ,'required', 'plugins.metadata.xmdp22.manager.settings.cc.address.required'));
		$this->addCheck(new FormValidatorRegExp($this, 'ddb_contactID', 'optional', 'plugins.metadata.xmdp22.manager.settings.ddb.contactID.pattern', '/^F[0-9][0-9][0-9][0-9]-[0-9][0-9][0-9][0-9]$/'));
		$this->addCheck(new FormValidator($this, 'ddb_kind' ,'required', 'plugins.metadata.xmdp22.manager.settings.ddb.kind.required'));
		// Add an additional check for the genre to the form.
		$this->addCheck(
				new FormValidatorCustom(
						$this, 'genre_id', 'optional',
						'plugins.metadata.xmdp22.manager.settings.transfer.genre.id',
						create_function(
								'$genreId, $genreDao, $contextId',
								'return is_a($genreDao->getById($genreId, $contextId), "Genre");'
						),
						array(DAORegistry::getDAO('GenreDAO'), $pressId)
				)
		);

		$this->addCheck(new FormValidatorPost($this));

		$this->setData('pluginName', $plugin->getName());
	}


	//
	// Implement template methods from Form
	//
	/**
	 * @see Form::initData()
	 */
	function initData() {
		$pressId = $this->_getPressId();
		$plugin =& $this->_getPlugin();

		$this->setData("ddbKindOptions", $this->_ddbKindOptions);
		$this->setData("genres", $this->_retrieveGenreList($pressId));

		foreach($this->_getFormFields() as $fieldName => $fieldType) {
			$this->setData($fieldName, $plugin->getSetting($pressId, $fieldName));
		}
	}

	/**
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array_keys($this->_getFormFields()));
	}

	/**
	 * @see Form::execute()
	 */
	function execute() {
		$plugin =& $this->_getPlugin();
		$pressId = $this->_getPressId();
		foreach($this->_getFormFields() as $fieldName => $fieldType) {
			$plugin->updateSetting($pressId, $fieldName, $this->getData($fieldName), $fieldType);
		}
	}

	/**
	 * @see Form::validate()
	 */
	function validate() {
		// FIXME: There is probably a better way to add these options to the template.
		$pressId = $this->_getPressId();

		$this->setData("ddbKindOptions", $this->_ddbKindOptions);
		$this->setData("genres", $this->_retrieveGenreList($pressId));

		return parent::validate();
	}

	//
	// Private helper methods
	//
	function _getFormFields() {
		return array(
			'cc_place' => 'string',
			'cc_address' => 'string',
			'ddb_contactID' => 'string',
			'ddb_kind' => 'string',
			'genre_id' => 'string',
		);
	}

	/**
	 * Get the press ID.
	 * @return integer
	 */
	function _getPressId() {
		return $this->_pressId;
	}


	function _retrieveGenreList($contextId) {
		$genreDao = DAORegistry::getDAO('GenreDAO'); /* @var $genreDao GenreDAO */
		$genres = $genreDao->getEnabledByContextId($contextId);

		// Transform the genres into an array
		$genreList = array();
		while ($genre = $genres->next()) {
			$genreList[$genre->getId()] = $genre->getLocalizedName();
		}
		return $genreList;
	}
}

?>
