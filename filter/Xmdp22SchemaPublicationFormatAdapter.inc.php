<?php

/**
 * @file plugins/metadata/xmdp22/filter/Xmdp22SchemaPublicationFormatAdapter.inc.php
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
import('plugins.metadata.xmdp22.schema.Pc14NameSchema');
import('plugins.metadata.xmdp22.schema.CC21InstitutionSchema');

class Xmdp22SchemaPublicationFormatAdapter extends MetadataDataObjectAdapter {
    /**
     * Constructor
     * @param $filterGroup FilterGroup
     * @return Xmdp22SchemaPublicationFormatAdapter
     */
	function __construct(&$filterGroup) {
		parent::__construct($filterGroup);
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
     * @return void
     */
	function &injectMetadataIntoDataObject(&$description, &$publicationFormat) {
		// Not implemented
		assert(false);
	}

	/**
	 * @see MetadataDataObjectAdapter::extractMetadataFromDataObject()
	 * @param $publicationFormat PublicationFormat
	 * @return MetadataDescription
	 */
	function extractMetadataFromDataObject(&$publicationFormat) {
		assert(is_a($publicationFormat, 'PublicationFormat'));

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON);

		// Retrieve data that belongs to the publication format.
		$oaiDao = DAORegistry::getDAO('OAIDAO');
		$publishedMonographDao = DAORegistry::getDAO('PublishedMonographDAO');
		$monographDao = DAORegistry::getDAO('MonographDAO');

		$monograph = $publishedMonographDao->getById($publicationFormat->getMonographId());
		$series = $oaiDao->getSeries($monograph->getSeriesId()); /* @var $series Series */
		$press = $oaiDao->getPress($monograph->getPressId());

		$chapterDao = DAORegistry::getDAO('ChapterDAO');
		$chapters = $chapterDao->getChapters($monograph->getId());

		$description = $this->instantiateMetadataDescription();

		// Title
		$this->_addLocalizedElements($description, 'dc:title[@xsi:type="ddb:titleISO639-2"]', $monograph->getTitle(null));

		// Creator
 		$authors = $monograph->getAuthors();
 		foreach($authors as $author) {
			$pc = new MetadataDescription('plugins.metadata.xmdp22.schema.Pc14NameSchema', ASSOC_TYPE_AUTHOR);

 			$this->_checkForContentAndAddElement($pc, 'pc:person/pc:name[@type="nameUsedByThePerson"]/pc:foreName', $author->getLocalizedGivenName());
 			$this->_checkForContentAndAddElement($pc, 'pc:person/pc:name[@type="nameUsedByThePerson"]/pc:surName', $author->getLocalizedFamilyName());

 			$this->_checkForContentAndAddElement($description, 'dc:creator[@xsi:type="pc:MetaPers"]', $pc);
 		}

		// Subject
		$subjects = array_merge_recursive(
				(array) $monograph->getDiscipline(null),
				(array) $monograph->getSubject(null));
		$this->_addLocalizedElements($description, 'dc:subject[@xsi:type="xMetaDiss:noScheme"]', $subjects);

		// Table of Contents

		// Abstract
		$this->_addLocalizedElements($description, 'dcterms:abstract[@xsi:type="ddb:contentISO639-2"]', $monograph->getAbstract(null));

		// Publisher
		$publisherInstitution = $press->getSetting('publisherInstitution');
		if (!empty($publisherInstitution)) {
			$publishers = array($press->getPrimaryLocale() => $publisherInstitution);
		} else {
			$publishers = $press->getName(null); // Default
		}

		// Corporate Core Institution Schema
		// Since composite elements cannot be localized, the content of this element is based on the primary press locale
		$cc = new MetadataDescription('plugins.metadata.xmdp22.schema.CC21InstitutionSchema', ASSOC_TYPE_PRESS);

		// Name
		$this->_checkForContentAndAddElement($cc, 'cc:universityOrInstitution/cc:name', $press->getName()[$press->getPrimaryLocale()]);

		// Address
		$metadataPlugins = PluginRegistry::loadCategory('metadata', true);
		$address = $press->getData("mailingAddress");
		if ( !$address) {
			$address = $metadataPlugins['Xmdp22MetadataPlugin']->getData("cc:address", $monograph->getPressId());
		}
		$cc->addStatement('cc:address', $address);

		// Place
		$place = $address;
		$place = $metadataPlugins['Xmdp22MetadataPlugin']->getData("cc:place", $monograph->getPressId());
		$cc->addStatement('cc:universityOrInstitution/cc:place', $place);

		$this->_checkForContentAndAddElement($description, 'dc:publisher[@xsi:type="cc:Publisher"]', $cc);

		// Contributor
		$contributors = $monograph->getSponsor(null);
		if (is_array($contributors)) {
			foreach ($contributors as $locale => $contributor) {
				$contributors[$locale] = array_map('trim', explode(';', $contributor));
			}
			$this->_addLocalizedElements($description, 'dc:contributor', $contributors);
		}

		// Date submitted
		//$description->addStatement('dcterms:dateSubmitted', date('Y', strtotime($monograph->getDateSubmitted())));

		// Issued
		$this->_checkForContentAndAddElement($description, 'dcterms:issued[@xsi:type="dcterms:W3CDTF"]', date('Y-m-d', strtotime($monograph->getDatePublished())));

		// Type
		$types = array_merge_recursive(
				array_map('lcfirst', array(AppLocale::getLocale() => __('rt.metadata.pkp.dctype'))),
				array_map('lcfirst', (array) $monograph->getType(null))
		);
		$this->_addLocalizedElements($description, 'dc:type[@xsi:type="dini:PublType"]', $types);

		// Format
		$onixCodelistItemDao = DAORegistry::getDAO('ONIXCodelistItemDAO');
		$entryKeys = $onixCodelistItemDao->getCodes('List7'); // List7 is for object formats
		if ($publicationFormat->getEntryKey()) {
			$formatName = $entryKeys[$publicationFormat->getEntryKey()];
			$this->_checkForContentAndAddElement($description, 'dc:format', $formatName);
		}

		// Identifier(s)
		// dc:identifier: xsi:type=urn:nbn|doi|hdl (1, mandatory)
		// ddb:identifier: ddb:type=URL|URN|DOI|handle|VG-Wort-Pixel|URL_Frontdoor|URL_Publikation|Erstkat-ID|ISSN|other (many, optional)
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds');
		if ( isset($pubIdPlugins) && array_key_exists('DOIPubIdPlugin', $pubIdPlugins) && $pubIdPlugins['DOIPubIdPlugin']->getEnabled() == true) {
			$doi = $pubIdPlugins['DOIPubIdPlugin']->getPubId($publicationFormat);
		}

		if ( isset($pubIdPlugins) && array_key_exists('URNDNBPubIdPlugin', $pubIdPlugins) && $pubIdPlugins['URNDNBPubIdPlugin']->getEnabled() == true) {
			$urn_dnb = $pubIdPlugins['URNDNBPubIdPlugin']->getPubId($monograph);
			$namespaces = explode(':', $urn_dnb);
			$numberOfNamespaces = min(sizeof($namespaces), 3);
			$scheme = implode(":", array_slice($namespaces, 0, $numberOfNamespaces));
		}

		if ( isset($urn_dnb) ) {
			$description->addStatement('dc:identifier', $urn_dnb . ' [@xsi:type="' . $scheme . '"]');
			if ( isset($doi) ) {
				$description->addStatement('ddb:identifier', $doi . ' [@ddb:type="DOI"]');
			}
		} else if ( isset($doi) ) {
			$description->addStatement('dc:identifier', $doi . ' [@xsi:type="doi"]');
		}
		$this->_checkForContentAndAddElement($description, 'ddb:identifier', Request::url($press->getPath(), 'catalog', 'book', array($monograph->getId())) . ' [@ddb:type="URL_Frontdoor"]');

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
		$language = $monograph->getLanguage();
		if (!$language) {
			$language = AppLocale::get3LetterFrom2LetterIsoLanguage(substr($press->getPrimaryLocale(), 0, 2));
		} else {
			$language = AppLocale::get3LetterFrom2LetterIsoLanguage($language);
		}
		$this->_checkForContentAndAddElement($description, 'dc:language[@xsi:type="dcterms:ISO639-2"]', $language);

		// Relation

		// Coverage
		$coverage = array_merge_recursive((array) $monograph->getCoverage(null)
				);
		$this->_addLocalizedElements($description, 'dc:coverage[@xsi:type="ddb:encoding" @ddb:Scheme="None"]', $coverage);

		// Rights
		$salesRightsFactory = $publicationFormat->getSalesRights();
		while ($salesRight = $salesRightsFactory->next()) {
			$this->_checkForContentAndAddElement($description, 'dc:rights', $salesRight->getNameForONIXCode());
		}

		// File transfer
		// Per default, only the full manuscript or a file of a custom genre
		// (set via settings form) is transferred. If several files of the same
		// genre are found for one publication format, the first is selected as
		// the transfer file per default.
		// Alternative configurations (e.g. container formats) are thinkable, but not implemented.
  		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');

 		$availableFiles = array_filter(
 				$submissionFileDao->getLatestRevisions($monograph->getId()),
 				create_function('$a', 'return $a->getViewable() && $a->getDirectSalesPrice() !== null && $a->getAssocType() == ASSOC_TYPE_PUBLICATION_FORMAT;')
 		);

 		$genreDao = DAORegistry::getDAO('GenreDAO');
 		$genreId = $metadataPlugins['Xmdp22MetadataPlugin']->getData("genre:id", $monograph->getPressId());
 		if ( !isset($genreId) ) {
 			// if genre is not set, try to make monograph default
 			// -- this fails if the press uses custom components and the default components
 			// have been deleted
 			if ( $genre = $genreDao->getByKey('MANUSCRIPT', $press->getId()) ) {
                $genreId = $genre->getId();
            }
            else {
 				fatalError("genre with key 'MANUSCRIPT' not found");
            }
 			if ( isset($genreId) ) {
 				$metadataPlugins['Xmdp22MetadataPlugin']->updateSetting($monograph->getPressId(), "genre_id", $genreId);
 			}
 		}

 		$transferableFiles = array();
 		foreach ($availableFiles as $availableFile) {
 			if ( ($availableFile->getAssocId() == $publicationFormat->getId()) && ($availableFile->getGenreId() == $genreId) ) {
 				// Collect all files that belong to this publication format and have the selected genre
 				$transferableFiles[] = $availableFile;
 			};
 		};

 		// first file that fits criteria is transfered per default
 		// -- another solution would be to place all files in a container here
 		if ( $transferableFiles ) {
 			$transferFile = $transferableFiles[0];
 			$transferableFiles = array( $transferFile );
 		}

 		// Number of files (will always be 1, unless a container solution is implemented)
 		$this->_checkForContentAndAddElement($description, 'ddb:fileNumber', sizeof($transferableFiles));

		// File Properties and Transfer link
		if ( isset($transferFile) ) {
			$description->addStatement('ddb:fileProperties', '[@ddb:fileName="' . $transferFile->getServerFileName() . '" @ddbfileSize="' . $transferFile->getFileSize().'"]');
			$description->addStatement('ddb:transfer[@ddb:type="dcterms:URI"]', Request::url($press->getPath(),
					'catalog', 'download', array($monograph->getId(), $publicationFormat->getId(), $transferFile->getFileIdAndRevision())));
		};

		// Contact ID
		$contactId = $metadataPlugins['Xmdp22MetadataPlugin']->getData("ddb:contactID", $monograph->getPressId());
		$this->_checkForContentAndAddElement($description, 'ddb:contact', '[@ddb:contactID="' . $contactId .'"]');

		// Rights
		$kind = $metadataPlugins['Xmdp22MetadataPlugin']->getData("ddb:kind", $monograph->getPressId());
		$description->addStatement('ddb:rights', '[@ddb:kind="' . $kind . '"]');

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
					if ($value) {
						$description->addStatement($propertyName, $value, $locale);
					}
				unset($value);
			}
		}
	}

	function _checkForContentAndAddElement(&$description, $propertyName, $value) {
		if ($value) {
			$description->addStatement($propertyName, $value);
		}
	}
}


if(false === function_exists('lcfirst'))
{
	/**
	 * Make a string's first character lowercase
	 *
	 * @param string $str
	 * @return string the resulting string.
	 */
	function lcfirst( $str ) {
		$str[0] = strtolower($str[0]);
		return (string)$str;
	}
}

?>
