<?php

/**
 * @file plugins/metadata/xmdp22/schema/Xmdp22Schema.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Xmdp22Schema
 * @ingroup plugins_metadata_xmdp22_schema
 *
 * @brief OMP-specific implementation of the Xmdp22Schema.
 */

import('lib.pkp.classes.metadata.MetadataSchema');

class Xmdp22Schema extends MetadataSchema {
	/**
	 * Constructor
	 * @param $appSpecificAssocType integer
	 */
	function __construct($appSpecificAssocType = ASSOC_TYPE_PUBLICATION_FORMAT, $classname = 'plugins.metadata.xmdp22.schema.Xmdp22Schema') {
		// Configure the meta-data schema.
		parent::__construct(
				'xmdp-2.2',
				'xmdp',
				$classname,
				$appSpecificAssocType
		);

		$this->addProperty('dc:title[@xsi:type="ddb:titleISO639-2"]', METADATA_PROPERTY_TYPE_STRING, true, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('dc:creator[@xsi:type="pc:MetaPers"]', array(METADATA_PROPERTY_TYPE_COMPOSITE => ASSOC_TYPE_AUTHOR), false, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('dc:subject[@xsi:type="xMetaDiss:noScheme"]', METADATA_PROPERTY_TYPE_STRING, true, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('dcterms:tableOfContents', METADATA_PROPERTY_TYPE_STRING, true, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('dcterms:abstract[@xsi:type="ddb:contentISO639-2"]', METADATA_PROPERTY_TYPE_STRING, true, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('dc:publisher[@xsi:type="cc:Publisher"]', array(METADATA_PROPERTY_TYPE_COMPOSITE => ASSOC_TYPE_PRESS), false, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('dc:contributor', METADATA_PROPERTY_TYPE_STRING, true, METADATA_PROPERTY_CARDINALITY_MANY);
//		$this->addProperty('dcterms:dateSubmitted', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('dcterms:issued[@xsi:type="dcterms:W3CDTF"]', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_ONE, null, null, true);
		$this->addProperty('dc:type[@xsi:type="dini:PublType"]', METADATA_PROPERTY_TYPE_STRING, true, METADATA_PROPERTY_CARDINALITY_MANY, null, null, true);
//		$this->addProperty('dc:format', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('dc:identifier', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_ONE, null, null, true);
		$this->addProperty('dc:source', METADATA_PROPERTY_TYPE_STRING, true, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('dc:language[@xsi:type="dcterms:ISO639-2"]', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_MANY, null, null, true);
//		$this->addProperty('dc:relation', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('dc:coverage[@xsi:type="ddb:encoding" @ddb:Scheme="None"]', METADATA_PROPERTY_TYPE_STRING, true, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('dcterms:hasPart[@xsi:type="dcterms:URI"]', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('ddb:contact', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('ddb:fileNumber', METADATA_PROPERTY_TYPE_INTEGER, false, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('ddb:fileProperties', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('ddb:transfer[@ddb:type="dcterms:URI"]', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_MANY, null, null, true);
		$this->addProperty('ddb:identifier', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('ddb:rights', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_MANY, null, null, true);
	}
}
?>
