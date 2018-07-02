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

class Pc14NameSchema extends MetadataSchema {
	/**
	 * Constructor
	 * @param $appSpecificAssocType integer
	 */
	function __construct($appSpecificAssocType = ASSOC_TYPE_AUTHOR, $classname = 'plugins.metadata.xmdp22.schema.Pc14NameSchema') {
		// Configure the meta-data schema.
		parent::__construct(
				'pc-1.4',
				'pc',
				$classname,
				$appSpecificAssocType
		);

		$this->addProperty('pc:person/pc:name[@type="nameUsedByThePerson"]/pc:foreName', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_MANY);
		$this->addProperty('pc:person/pc:name[@type="nameUsedByThePerson"]/pc:surName', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_MANY);
	}
}
?>
