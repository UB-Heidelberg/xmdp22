<?php
/**
 * @defgroup plugins_metadata_xmdp22_filter XMDP 2.2 Filter Plugin
 */

/**
 * @file plugins/metadata/xmdp22/filter/Xmdp22DescriptionXmlFilter.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Xmdp22DescriptionXmlFilter
 * @ingroup plugins_metadata_xmdp22_filter
 *
 * @brief Class that converts a meta-data description to an XMDP 2.2 XML record.
 */


import('lib.pkp.classes.filter.PersistableFilter');
import('lib.pkp.classes.xml.XMLCustomWriter');

class Xmdp22DescriptionXmlFilter extends PersistableFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		$this->setDisplayName('XMDP 2.2');
		parent::__construct($filterGroup);
	}


	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'plugins.metadata.xmdp22.filter.Xmdp22DescriptionXmlFilter';
	}


	//
	// Implement template methods from Filter
	//
	/**
	 * @copydoc Filter::process()
	 * @param $input MetadataDescription
	 */
	function &process(&$input) {
		// Start the XML document.
		$doc =& XMLCustomWriter::createDocument();

		// Create the root element.
		$root =& XMLCustomWriter::createElement($doc, 'xMetaDiss:xMetaDiss');

		// Add the XML namespace and schema.
		XMLCustomWriter::setAttribute($root, 'xmlns:xMetaDiss', 'http://www.d-nb.de/standards/xmetadissplus/');
		XMLCustomWriter::setAttribute($root, 'xmlns:cc', 'http://www.d-nb.de/standards/cc/');
		XMLCustomWriter::setAttribute($root, 'xmlns:dc', 'http://purl.org/dc/elements/1.1/');
		XMLCustomWriter::setAttribute($root, 'xmlns:dcmitype', 'http://purl.org/dc/dcmitype');
		XMLCustomWriter::setAttribute($root, 'xmlns:dcterms', 'http://purl.org/dc/terms/');
		XMLCustomWriter::setAttribute($root, 'xmlns:ddb', 'http://www.d-nb.de/standards/ddb/');
		XMLCustomWriter::setAttribute($root, 'xmlns:doi', 'http://www.d-nb.de/standards/doi/');
		XMLCustomWriter::setAttribute($root, 'xmlns:hdl', 'http://www.d-nb.de/standards/hdl/');
		XMLCustomWriter::setAttribute($root, 'xmlns:pc', 'http://www.d-nb.de/standards/pc/');
		XMLCustomWriter::setAttribute($root, 'xmlns', 'http://www.d-nb.de/standards/subject/');
		XMLCustomWriter::setAttribute($root, 'xmlns:thesis', 'http://www.ndltd.org/standards/metadata/etdms/1.0/');
		XMLCustomWriter::setAttribute($root, 'xmlns:urn', 'http://www.d-nb.de/standards/urn/');
		XMLCustomWriter::setAttribute($root, 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		XMLCustomWriter::setAttribute($root, 'xsi:schemaLocation', 'http://www.d-nb.de/standards/xmetadissplus/ http://www.d-nb.de/standards/xmetadissplus/xmetadissplus.xsd');
		
		// Prepare the XMDP document hierarchy from the XMDP MetadataDescription instance.
		$documentHierarchy =& $this->_buildDocumentHierarchy($doc, $root, $input);

		// Recursively join the document hierarchy into a single document.
		$root =& $this->_joinNodes($documentHierarchy);
		XMLCustomWriter::appendChild($doc, $root);

		// Retrieve the XML from the DOM.
		$output = XMLCustomWriter::getXml($doc);
		return $output;
	}

	//
	// Private helper methods
	//
	/**
	 * Process an XMDP composite property into an XML node.
	 * @param $doc XMLNode|DOMDocument the XMDP document node.
	 * @param $elementName string the name of the root element of the composite
	 * @param $metadataDescription MetadataDescription
	 * @return XMLNode|DOMDocument
	 */
	function &_processCompositeProperty(&$doc, $nodePath, &$metadataDescription) {
		// Create the root element.
		$root =& $this->_createNode($doc, $nodePath);

		// Prepare the XMDP hierarchy from the XMDP name MetadataDescription instance.
		$documentHierarchy =& $this->_buildDocumentHierarchy($doc, $root, $metadataDescription);

		// Recursively join the name document hierarchy into a single element.
		$root =& $this->_joinNodes($documentHierarchy);
		return $root;
	}

	/**
	 * Create a hierarchical array that represents the XMDP DOM
	 * from the meta-data description.
	 *
	 * @param $doc XMLNode|DOMDocument the XMDP document node.
	 * @param $root XMLNode|DOMDocument the root node of the
	 *  XMDP document.
	 * @param $description MetadataDescription
	 * @return array a hierarchical array of XMLNode|DOMDocument objects
	 *  representing the XMDP document.
	 */
	function &_buildDocumentHierarchy(&$doc, &$root, &$description) {
		// Get the schema.
		$xmdp22Schema =& $description->getMetadataSchema();
		$catalogingLanguage = 'undefined';

		// Initialize the document hierarchy with the root node.
		$documentHierarchy = array(
			'@branch' => &$root
		);

		// Find the translations required for top-level elements.
		// We need this array later because we'll have to repeat non-translated
		// values for every translated top-level element.
		$properties = $description->getProperties();
		$translations = array();
		foreach ($properties as $propertyName => $property) { /* @var $property MetadataProperty */
			if ($description->hasStatement($propertyName)) {
				$nodes = explode('/', $propertyName);
				$topLevelNode = array_shift($nodes);
				if (!isset($translations[$topLevelNode])) $translations[$topLevelNode] = array();
				if ($property->getTranslated()) {
					foreach ($description->getStatementTranslations($propertyName) as $locale => $value) {
						$isoLanguage = AppLocale::get3LetterIsoFromLocale($locale);
						if (!in_array($isoLanguage, $translations[$topLevelNode])) {
							$translations[$topLevelNode][] = $isoLanguage;
						}
					}
				} else {
					if (!in_array($catalogingLanguage, $translations[$topLevelNode])) {
						$translations[$topLevelNode][] = $catalogingLanguage;
					}
				}
			}
		}

		// Build the document hierarchy.
		foreach ($properties as $propertyName => $property) { /* @var $property MetadataProperty */
			if ($description->hasStatement($propertyName)) {
				// Get relevant property attributes.
				$translated = $property->getTranslated();
				$cardinality = $property->getCardinality();

				// Get the XML element hierarchy.
				$nodes = explode('/', $propertyName);
				$hierarchyDepth = count($nodes) - 1;

				// Normalize property values to an array of translated strings.
				if ($translated) {
					// Only the main XMDP schema can contain translated values.
					assert(is_a($xmdp22Schema, 'Xmdp22Schema'));

					// Retrieve the translated values of the statement.
					$localizedValues =& $description->getStatementTranslations($propertyName);

					// Translate the PKP locale into ISO639-2b 3-letter codes.
					$translatedValues = array();
					foreach($localizedValues as $locale => $translatedValue) {
						$isoLanguage = AppLocale::get3LetterIsoFromLocale($locale);
						assert(!is_null($isoLanguage));
						$translatedValues[$isoLanguage] = $translatedValue;
					}
				} else {
					// Untranslated statements will be repeated for all languages
					// present in the top-level element.
					$untranslatedValue =& $description->getStatement($propertyName);
					$translatedValues = array();
					assert(isset($translations[$nodes[0]]));
					foreach($translations[$nodes[0]] as $isoLanguage) {
						$translatedValues[$isoLanguage] = $untranslatedValue;
					}
				}

				// Normalize all values to arrays so that we can
				// handle them uniformly.
				$translatedValueArrays = array();
				foreach($translatedValues as $isoLanguage => $translatedValue) {
					if ($cardinality == METADATA_PROPERTY_CARDINALITY_ONE) {
						assert(is_scalar($translatedValue));
						$translatedValueArrays[$isoLanguage] = array(&$translatedValue);
					} else {
						assert(is_array($translatedValue));
						$translatedValueArrays[$isoLanguage] =& $translatedValue;
					}
					unset($translatedValue);
				}

				// Add the translated values one by one to the element hierarchy.
				foreach($translatedValueArrays as $isoLanguage => $translatedValueArray) {
					foreach($translatedValueArray as $translatedValue) {
						// Add a language attribute to the top-level element if
						// it differs from the cataloging language.
						$translatedNodes = $nodes;
						if ($isoLanguage != $catalogingLanguage) {
							if (strpos($translatedNodes[0], '[') === false) {
								$translatedNodes[0] .= '[@lang="'.$isoLanguage.'"]';
							} else {
								$translatedNodes[0] = substr($translatedNodes[0], 0, -1) . ' @lang="' . $isoLanguage . '"]';
							}
						}

						// Create the node hierarchy for the statement.
						$currentNodeList =& $documentHierarchy;
						foreach($translatedNodes as $nodeDepth => $nodeName) {
							// Are we at a leaf node?
							if($nodeDepth == $hierarchyDepth) {
								// Is this a top-level attribute?
								if (substr($nodeName, 0, 1) == '[') {
									assert($nodeDepth == 0);
									assert($translated == false);
									assert($cardinality == METADATA_PROPERTY_CARDINALITY_ONE);
									assert(!is_object($translatedValue));
									$attributeName = trim($nodeName, '[@"]');
									
									XMLCustomWriter::setAttribute($root, $attributeName, (string)$translatedValue);
									continue;
								}

								// This is a sub-element.
								if (isset($currentNodeList[$nodeName])) {
									// Only properties with cardinality "many" can
									// have more than one leaf node.
									assert($cardinality == METADATA_PROPERTY_CARDINALITY_MANY);

									// Check that the leaf list is actually there.
									assert(isset($currentNodeList[$nodeName]['@leaves']));

									// We should never find any branch in a leaves node.
									assert(!isset($currentNodeList[$nodeName]['@branch']));
								} else {
									// Create the leaf list in the hierarchy.
									$currentNodeList[$nodeName]['@leaves'] = array();
								}

								if (is_a($translatedValue, 'MetadataDescription')) {
									// Recursively process composite properties.
									$leafNode =& $this->_processCompositeProperty($doc, $propertyName, $translatedValue);
								} else {
									// Cast scalar values to string types for XML binding.
									$translatedValue = (string)$translatedValue;

									// Create the leaf element.
									$leafNode =& $this->_createNode($doc, $nodeName, $translatedValue);
								}

								// Add the leaf element to the leaves list.
								$currentNodeList[$nodeName]['@leaves'][] =& $leafNode;
								unset($leafNode);
							} else {
								// This is a branch node.

								// Has the branch already been created? If not: create it.
								if (isset($currentNodeList[$nodeName])) {
									// Check that the branch node is actually there.
									assert(isset($currentNodeList[$nodeName]['@branch']));

									// We should never find any leaves in a branch node.
									assert(!isset($currentNodeList[$nodeName]['@leaves']));
								} else {
									// Create the branch node.
									$branchNode =& $this->_createNode($doc, $nodeName);

									// Add the branch node list and add the new node as it's root element.
									$currentNodeList[$nodeName] = array(
										'@branch' => &$branchNode
									);
									unset($branchNode);
								}
							}

							// Set the node list pointer to the sub-element
							$currentNodeList =& $currentNodeList[$nodeName];
						}
					}
				}
			}
		}

		return $documentHierarchy;
	}

	/**
	 * Create a new XML node.
	 * @param $doc XMLNode|DOMImplementation
	 * @param $nodePath string an XPath-like string that describes the
	 *  node to be created.
	 * @param $value string the value to be added as a text node (if any)
	 * @return XMLNode|DOMDocument
	 */
	function &_createNode($doc, $nodePath, $value = null) {
		// Separate the element name from the attributes.
		$elementPlusAttributes = explode('[', $nodePath);
		$element = $elementPlusAttributes[0];
		assert(!empty($element));

		// Create the element.
		$newNode =& XMLCustomWriter::createElement($doc, $element);
		
		// Check for configurable attributes in element value, remove them from value
		// and add them to regular attributes
		$attributeOffset = strpos($value, '[@');
		if ($attributeOffset !== false) {
			// no configurable attributes
			if (count($elementPlusAttributes) < 2) {
				$elementPlusAttributes[] = '';
			}
			
			if ($attributeOffset !== 0) {
				$elementPlusAttributes[1] = rtrim($elementPlusAttributes[1], ']') . ltrim(substr($value, $attributeOffset), '[');
				$value = substr($value, 0, $attributeOffset);
			}
			else {
				$elementPlusAttributes[1] = rtrim($elementPlusAttributes[1], ']') . ltrim(substr($value, $attributeOffset), '[');
				$value = "";
			}
		}

		// Add attributes.
		if (count($elementPlusAttributes) == 2) {
			// Separate the attribute key/value pairs.
			$unparsedAttributes = explode('@', rtrim(ltrim($elementPlusAttributes[1], '@'), ']'));
			foreach($unparsedAttributes as $unparsedAttribute) {
				// Split attribute expressions into key and value.
				list($attributeName, $attributeValue) = explode('=', rtrim($unparsedAttribute, ' '));
				$attributeValue = trim($attributeValue, '"');
				XMLCustomWriter::setAttribute($newNode, $attributeName, $attributeValue);
			}
		}

		// Insert a text node if we got a value for it.
		if (!is_null($value)) {
			$textNode =& XMLCustomWriter::createTextNode($doc, $value);
			XMLCustomWriter::appendChild($newNode, $textNode);
		}

		return $newNode;
	}

	/**
	 * Recursively join the document hierarchy into a single document.
	 * @param $documentHierarchy
	 * @return array an array of joined nodes
	 */
	function &_joinNodes(&$documentHierarchy) {
		// Get the root node of the hierarchy.
		$root = $documentHierarchy['@branch'];
		unset($documentHierarchy['@branch']);

		// Add the sub-hierarchies to the root element.
		foreach($documentHierarchy as $subHierarchy) {
			// Is this a leaf node?
			if (isset($subHierarchy['@leaves'])) {
				// Make sure that there's no rubbish in this node.
				assert(count($subHierarchy) == 1);

				foreach($subHierarchy['@leaves'] as $leafNode) {
					XMLCustomWriter::appendChild($root, $leafNode);
				}
			} else {
				// This is a branch node.
				$subNode =& $this->_joinNodes($subHierarchy);
				XMLCustomWriter::appendChild($root, $subNode);
			}
		}

		// Return the node list.
		return $root;
	}
}
?>
