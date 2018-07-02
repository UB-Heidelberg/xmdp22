<?php

/**
 * @file plugins/metadata/xmdp22/schema/Pc14NameSchema.inc.php
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

class CC21InstitutionSchema extends MetadataSchema {
	/**
	 * Constructor
	 * @param $appSpecificAssocType integer
	 */
	function __construct($appSpecificAssocType = ASSOC_TYPE_PRESS, $classname = 'plugins.metadata.xmdp22.schema.CC21InstitutionSchema') {
		// Configure the meta-data schema.
		parent::__construct(
				'cc-2.1',
				'cc',
				$classname,
				$appSpecificAssocType
		);

		$this->addProperty('cc:universityOrInstitution/cc:name', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_ONE);
		$this->addProperty('cc:universityOrInstitution/cc:place', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('cc:address', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_ONE);
	}
}
?>
