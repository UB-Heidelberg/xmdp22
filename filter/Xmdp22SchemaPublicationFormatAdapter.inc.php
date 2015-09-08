<?php

/**
 * @file plugins/metadata/dc11/filter/Xmdp22SchemaPublicationFormatAdapter.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Xmdp22SchemaPublicationFormatAdapter
 * @ingroup plugins_metadata_xmdp22_filter
 * @see PublicationFormat
 *
 * @brief Adapter that injects/extracts XMetaDissPlus schema compliant meta-data
 * into/from a PublicationFormat object.
 */


import('lib.pkp.classes.metadata.MetadataDataObjectAdapter');

class Xmdp22SchemaPublicationFormatAdapter extends MetadataDataObjectAdapter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function Xmdp22SchemaPublicationFormatAdapter(&$filterGroup) {
		parent::MetadataDataObjectAdapter($filterGroup);
	}


	//
	// Implement template methods from Filter
	//
	/**
	 * @see Filter::getClassName()
	 */
	function getClassName() {
		return 'plugins.metadata.xmdp22.filter.Xmdp22SchemaPublicationFormatAdapter';
	}


	//
	// Implement template methods from MetadataDataObjectAdapter
	//
	/**
	 * @see MetadataDataObjectAdapter::injectMetadataIntoDataObject()
	 * @param $description MetadataDescription
	 * @param $publicationFormat PublicationFormat
	 * @param $authorClassName string the application specific author class name
	 */
	function &injectMetadataIntoDataObject(&$description, &$publicationFormat, $authorClassName) {
		// Not implemented
		assert(false);
	}

	/**
	 * @see MetadataDataObjectAdapter::extractMetadataFromDataObject()
	 * @param $publicationFormat PublicationFormat
	 * @return MetadataDescription
	 */
	function extractMetadataFromDataObject($publicationFormat) {
		assert(is_a($publicationFormat, 'PublicationFormat'));

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON);

		// Retrieve data that belongs to the publication format.
		$oaiDao = DAORegistry::getDAO('OAIDAO'); /* @var $oaiDao OAIDAO */
		$publishedMonographDao = DAORegistry::getDAO('PublishedMonographDAO');
		$monograph = $publishedMonographDao->getById($publicationFormat->getMonographId());
		$series = $oaiDao->getSeries($monograph->getSeriesId()); /* @var $series Series */
		$press = $oaiDao->getPress($monograph->getPressId());
		$description = $this->instantiateMetadataDescription();

		// Title
		$this->_addLocalizedElements($description, 'dc:title', $monograph->getTitle(null));
		
		// Creator
		$authors = $monograph->getAuthors();
		foreach($authors as $author) {
			$authorName = $author->getFullName(true);
			$affiliation = $author->getLocalizedAffiliation();
			if (!empty($affiliation)) {
				$authorName .= '; ' . $affiliation;
			}
			$description->addStatement('dc:creator', $authorName);
			unset($authorName);
		}
		
		// Subject
		$subjects = array_merge_recursive(
				(array) $monograph->getDiscipline(null),
				(array) $monograph->getSubject(null),
				(array) $monograph->getSubjectClass(null));
		$this->_addLocalizedElements($description, 'dc:subject', $subjects);
		
		// Publisher
		$publisherInstitution = $press->getSetting('publisherInstitution');
		if (!empty($publisherInstitution)) {
			$publishers = array($press->getPrimaryLocale() => $publisherInstitution);
		} else {
			$publishers = $press->getName(null); // Default
		}
		$this->_addLocalizedElements($description, 'dc:publisher', $publishers);
		
		// Contributor
		$contributors = $monograph->getSponsor(null);
		if (is_array($contributors)) {
			foreach ($contributors as $locale => $contributor) {
				$contributors[$locale] = array_map('trim', explode(';', $contributor));
			}
			$this->_addLocalizedElements($description, 'dc:contributor', $contributors);
		}
		
		// Type
		$types = array_merge_recursive(
				array(AppLocale::getLocale() => __('rt.metadata.pkp.dctype')),
				(array) $monograph->getType(null)
		);
		$this->_addLocalizedElements($description, 'dc:type', $types);
		
		// Format
		$onixCodelistItemDao = DAORegistry::getDAO('ONIXCodelistItemDAO');
		$entryKeys = $onixCodelistItemDao->getCodes('List7'); // List7 is for object formats
		if ($publicationFormat->getEntryKey()) {
			$formatName = $entryKeys[$publicationFormat->getEntryKey()];
			$description->addStatement('dc:format', $formatName);
		}
		
		// Identifier: URL
		if (is_a($monograph, 'PublishedMonograph')) {
			$description->addStatement('dc:identifier', Request::url($press->getPath(), 'catalog', 'book', array($monograph->getId())));
		}
		
		// Identifier: others
		$identificationCodeFactory = $publicationFormat->getIdentificationCodes();
		while ($identificationCode = $identificationCodeFactory->next()) {
			$description->addStatement('dc:identifier', $identificationCode->getNameForONIXCode());
		}
		
		// Source (press title and pages)
		$sources = $press->getName(null);
		$pages = $monograph->getPages();
		if (!empty($pages)) $pages = '; ' . $pages;
		foreach ($sources as $locale => $source) {
			$sources[$locale] .= '; ';
			$sources[$locale] .=  $pages;
		}
		$this->_addLocalizedElements($description, 'dc:source', $sources);
		
		// Language
		
		// Relation
		
		// Coverage
		$coverage = array_merge_recursive(
				(array) $monograph->getCoverageGeo(null),
				(array) $monograph->getCoverageChron(null),
				(array) $monograph->getCoverageSample(null));
		$this->_addLocalizedElements($description, 'dc:coverage', $coverage);
		
		// Rights
		$salesRightsFactory = $publicationFormat->getSalesRights();
		while ($salesRight = $salesRightsFactory->next()) {
			$description->addStatement('dc:rights', $salesRight->getNameForONIXCode());
		}
		

		Hookregistry::call('Xmdp22SchemaPublicationFormatAdapter::extractMetadataFromDataObject', array(&$this, $monograph, $press, &$description));

		return $description;
	}

	/**
	 * @see MetadataDataObjectAdapter::getDataObjectMetadataFieldNames()
	 * @param $translated boolean
	 */
	function getDataObjectMetadataFieldNames($translated = true) {
		return array();
	}


	//
	// Private helper methods
	//
	/**
	 * Add an array of localized values to the given description.
	 * @param $description MetadataDescription
	 * @param $propertyName string
	 * @param $localizedValues array
	 */
	function _addLocalizedElements(&$description, $propertyName, $localizedValues) {
		foreach(stripAssocArray((array) $localizedValues) as $locale => $values) {
			if (is_scalar($values)) $values = array($values);
			foreach($values as $value) {
				$description->addStatement($propertyName, $value, $locale);
				unset($value);
			}
		}
	}
}
?>
