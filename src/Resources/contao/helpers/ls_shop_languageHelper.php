<?php
namespace Merconis\Core;

use Contao\PageModel;
use Contao\StringUtil;

class ls_shop_languageHelper {
	public static function getFallbackLanguage() {
		self::determineExistingLanguages();
		return $GLOBALS['merconis_globals']['arr_cache']['arr_languages']['str_fallbackLanguage'];
	}

	public static function getAllLanguages() {
		self::determineExistingLanguages();
		return $GLOBALS['merconis_globals']['arr_cache']['arr_languages']['arr_allLanguages'];
	}

	public static function determineExistingLanguages($bln_uncached = false) {
		if (!$bln_uncached && isset($GLOBALS['merconis_globals']['arr_cache']['arr_languages'])) {
			return;
		}

		/** @var \PageModel $objPage */
		global $objPage;
		$str_domain = is_object($objPage) ? $objPage->domain : '%';

		$arr_languages = array();
		$str_fallbackLanguage = '';
		$obj_dbres_rootPages = \Database::getInstance()
			->prepare("
			SELECT			`language`,
							`fallback`
			FROM			`tl_page`
			WHERE			`type` = ?
				AND			`dns` LIKE ?
			ORDER BY		`fallback` DESC,
							`language` ASC
		")
			->execute(
				'root',
				$str_domain
			);

		while ($obj_dbres_rootPages->next()) {
			$arr_languages[$obj_dbres_rootPages->language] = $obj_dbres_rootPages->language;
			if ($obj_dbres_rootPages->fallback) {
				$str_fallbackLanguage = $obj_dbres_rootPages->language;
			}
		}

		// use english as the fallback language if no fallback language could be determined
		$str_fallbackLanguage = $str_fallbackLanguage ? $str_fallbackLanguage : 'en';

		$GLOBALS['merconis_globals']['arr_cache']['arr_languages']['arr_allLanguages'] = $arr_languages;
		$GLOBALS['merconis_globals']['arr_cache']['arr_languages']['str_fallbackLanguage'] = $str_fallbackLanguage;
	}

	/*
	 * This function takes a field name and a data row (array) as arguments
	 * and checks whether or not the field is a multilanguage field
	 */
	public static function checkIfFieldIsMultilanguageField($str_fieldName = null, $arr_row = null) {
		if (!$str_fieldName || !is_array($arr_row)) {
			return false;
		}

		/*
		 * If a field is a multilanguage field, there is at least one language specific
		 * field named with the fieldname of the master field plus the fallback language
		 * key as a suffix.
		 */
		return key_exists($str_fieldName.'_'.self::getFallbackLanguage(), $arr_row);
	}

	public static function checkIfFieldIsMultilanguageSubfield($str_fieldName = null, $arr_row = null) {
		if (!$str_fieldName || !is_array($arr_row)) {
			return false;
		}

		$str_masterFieldNameIfRequestedFieldWasInFactASubfield = preg_replace('/_[a-zA-Z]*$/', '', $str_fieldName);
		if ($str_masterFieldNameIfRequestedFieldWasInFactASubfield != $str_fieldName && key_exists($str_masterFieldNameIfRequestedFieldWasInFactASubfield, $arr_row)) {
			/*
			 * If the requested field name has a suffix that matches the multilanguage syntax (true if the fieldName before and after the preg_replace differs)
			 * and if the name without this suffix actually exists as a fieldName (key) in the given $arr_row, it must be a subfield.
			 */
			return true;
		}

		return false;
	}

	/*
	 * This function is being called whenever a DCA configuration gets loaded and then creates
	 * the multilanguage field definitions where needed.
	 */
	public static function createMultiLanguageDCAFields($str_dcaName) {
		/*
		 * Do not process this function if the DCA name is on the list of DCAs not to process
		 */
		if ($GLOBALS['TL_CONFIG']['ls_shop_dcaNamesWithoutMultilanguageSupport'] ?? null) {
            $arr_dcaNamesNotToProcess = explode(',', $GLOBALS['TL_CONFIG']['ls_shop_dcaNamesWithoutMultilanguageSupport']);
            if (in_array($str_dcaName, $arr_dcaNamesNotToProcess)) {
                return;
            }
        }

		self::determineExistingLanguages(true);

		$arr_multiLanguageFields = array();

		/*
		 * Walk through each field definition in order to find fields that need to be handled
		 * as multilanguage fields
		 */
		if (is_array($GLOBALS['TL_DCA'][$str_dcaName]['fields'])) {
			foreach ($GLOBALS['TL_DCA'][$str_dcaName]['fields'] as $str_fieldKey => $arr_fieldDefinition) {
				/*
				 * --------------------------
				 * Kill references, because later we assign the field definition array to the different
				 * language fields and modify some parameters. If a parameter is a reference in the original
				 * dca definition of the field, changing this parameter for one of the newly created languge
				 * fields must not affect the others.
				 */
				$arr_tmpFieldDefinition = array();
				foreach ($arr_fieldDefinition as $k => $v) {
					$arr_tmpFieldDefinition[$k] = $v;
				}
				$arr_fieldDefinition = $arr_tmpFieldDefinition;
				/*
				 * --------------------------
				 */

				/*
				 * Check if the current field is marked as a merconis_multilanguage field and
				 * create the multilanguage fields for all languages
				 */
				if (isset($arr_fieldDefinition['eval']['merconis_multilanguage']) && $arr_fieldDefinition['eval']['merconis_multilanguage']) {
					/*
					 * Remove the original field definition from the dca array, because only the generated
					 * multilanguage fields are needed.
					 */
					//unset($GLOBALS['TL_DCA'][$str_dcaName]['fields'][$str_fieldKey]);

					/*
					 * The original fieldKey is used to group the newly created multilanguage field definitions
					 */
					$arr_multiLanguageFields[$str_fieldKey] = array();

					$arr_tmpFieldLabel = $arr_fieldDefinition['label'];

					/*
					 * Only keep the title part of the label array
					 */
					$arr_fieldDefinition['label'] = array(isset($arr_fieldDefinition['label'][0]) ? $arr_fieldDefinition['label'][0] : '');

					$str_tmpFieldClass = isset($arr_fieldDefinition['eval']['tl_class']) && $arr_fieldDefinition['eval']['tl_class'] ? $arr_fieldDefinition['eval']['tl_class'] : '';
					if (isset($arr_fieldDefinition['eval']['tl_class'])) {
						unset($arr_fieldDefinition['eval']['tl_class']);
					}

					$bln_tmp_fieldIsMandatory = isset($arr_fieldDefinition['eval']['mandatory']) && $arr_fieldDefinition['eval']['mandatory'];

					/*
					 * The dca fields which are only needed for styling purposes must not be inserted into the dca definition
					 * when we're in editAll/overrideAll mode because otherwise these fields would also get their own checkboxes in the checkbox
					 * menu where the fields to edit are selected.
					 */
					if (
							TL_MODE == 'BE'
						&&	\Input::get('act') != 'editAll'
						&&	\Input::get('act') != 'overrideAll'
					) {
						$arr_multiLanguageFields[$str_fieldKey][$str_fieldKey.'_htmlOutputBeforeCompleteField'] = array(
							'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'rawOutputForBackendDCA'),
							'eval'					  => array('output' => '<div class="ls_shop_multilanguage_fieldsWrapper clr'.(!($arr_fieldDefinition['eval']['merconis_multilanguage_noTopLinedGroup'] ?? null) ? ' topLinedGroup' : '').(($arr_fieldDefinition['eval']['merconis_multilanguage_wrapperClass'] ?? null) ? ' '.$arr_fieldDefinition['eval']['merconis_multilanguage_wrapperClass'] : '').'"><h3><label'.(($bln_tmp_fieldIsMandatory ?? null) ? ' class="mandatory"' : '').'>'.($arr_tmpFieldLabel[0] ?? '').($bln_tmp_fieldIsMandatory ? ' <span class="mandatory">*</span>' : '').'</label></h3><div class="ls_shop_multi_language">')
						);
					}

					if (
							TL_MODE == 'BE'
						&&	\Input::get('act') != 'editAll'
						&&	\Input::get('act') != 'overrideAll'
					) {
						$arr_multiLanguageFields[$str_fieldKey][$str_fieldKey.'_htmlOutputBeforeMainLanguageField'] = array(
							'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'rawOutputForBackendDCA'),
							'eval'					  => array('output' => '<div class="languageWidgetContainer mainLanguage '.self::getFallbackLanguage().($str_tmpFieldClass ? ' '.$str_tmpFieldClass : '').'"><div class="languageIcon '.self::getFallbackLanguage().' mainLanguage" style="background-image: url(bundles/leadingsystemsmerconis/images/languages/'.self::getFallbackLanguage().'.gif);"></div>')
						);
					}

					/*
					 * The original field definition is used with the fallback language
					 */
					$arr_multiLanguageFields[$str_fieldKey][$str_fieldKey.'_'.self::getFallbackLanguage()] = $arr_fieldDefinition;

					if (
							TL_MODE == 'BE'
						&&	\Input::get('act') != 'editAll'
						&&	\Input::get('act') != 'overrideAll'
					) {
						$arr_multiLanguageFields[$str_fieldKey][$str_fieldKey.'_htmlOutputAfterMainLanguageField'] = array(
							'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'rawOutputForBackendDCA'),
							'eval'					  => array('output' => '</div>')
						);
					}

					/*
					 * Walk through each language and create a field definition for the language
					 */
					foreach (self::getAllLanguages() as $str_language) {
						if ($str_language == self::getFallbackLanguage()) {
							continue;
						}

						if (
								TL_MODE == 'BE'
							&&	\Input::get('act') != 'editAll'
							&&	\Input::get('act') != 'overrideAll'
						) {
							$arr_multiLanguageFields[$str_fieldKey][$str_fieldKey.'_htmlOutputBeforeForeignLanguageField-'.$str_language] = array(
								'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'rawOutputForBackendDCA'),
								'eval'					  => array('output' => '<div class="languageWidgetContainer '.$str_language.($str_tmpFieldClass ? ' '.$str_tmpFieldClass : '').'"><div class="languageIcon '.$str_language.'" style="background-image: url(bundles/leadingsystemsmerconis/images/languages/'.$str_language.'.gif);"></div>')
							);
						}

						/*
						 * The original field definition is assigned and will later be changed somehow
						 */
						$arr_multiLanguageFields[$str_fieldKey][$str_fieldKey.'_'.$str_language] = $arr_fieldDefinition;

						if (
								TL_MODE == 'BE'
							&&	\Input::get('act') != 'editAll'
							&&	\Input::get('act') != 'overrideAll'
						) {
							$arr_multiLanguageFields[$str_fieldKey][$str_fieldKey.'_htmlOutputAfterForeignLanguageField-'.$str_language] = array(
								'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'rawOutputForBackendDCA'),
								'eval'					  => array('output' => '</div>')
							);
						}
					}

					if (
							TL_MODE == 'BE'
						&&	\Input::get('act') != 'editAll'
						&&	\Input::get('act') != 'overrideAll'
					) {
						$arr_multiLanguageFields[$str_fieldKey][$str_fieldKey.'_htmlOutputAfterCompleteField'] = array(
							'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'rawOutputForBackendDCA'),
							'eval'					  => array('output' => '</div>'.(($arr_tmpFieldLabel[1] ?? null) ? '<p class="tl_help tl_tip">'.$arr_tmpFieldLabel[1].'</p>' : '').'</div>')
						);
					}
				}
			}
		}

		/*
		 * Modify the field definitions for each single language field
		 */
		foreach ($arr_multiLanguageFields as $str_fieldKey => $arr_newFieldDefinitions) {
			foreach ($arr_newFieldDefinitions as $str_newFieldKey => $arr_newFieldDefinition) {
				if (
						isset($arr_newFieldDefinition['input_field_callback'])
					&&	$arr_newFieldDefinition['input_field_callback'][1] == 'rawOutputForBackendDCA'
				) {
					/*
					 * Skip raw output fields
					 */
					continue;
				}

				$str_fieldLanguage = self::getLanguageFromMultilanguageFieldKey($str_newFieldKey);

				// Adding classes to mark this field as multilanguage and to set the specific language
                if (!($arr_newFieldDefinition['eval']['tl_class'] ?? null)) {
                    $arr_newFieldDefinition['eval']['tl_class'] = '';
                }
				$arr_newFieldDefinition['eval']['tl_class'] .= ' merconis_multilanguage '.$str_fieldLanguage;

				/*
				 * Adding the field's language to the field title, which is just a quick solution for a problem
				 * that hopefully can be fixed a lot better
				 */
				$arr_newFieldDefinition['label'][0] .= ' ('.$str_fieldLanguage.')';

				/*
				 * Removing the mandatory flag if the field is not the fallback language
				 * because fields defined as mandatory still only require the fallback language
				 * field to be filled.
				 */
				if ($str_fieldLanguage != self::getFallbackLanguage()) {
					$arr_newFieldDefinition['eval']['mandatory'] = false;
				}

				/*
				 * Adding the save callbacks
				 */
				if (!isset($arr_newFieldDefinition['save_callback'])) {
					$arr_newFieldDefinition['save_callback'] = array();
				}
				$arr_newFieldDefinition['save_callback'][] = array('Merconis\Core\ls_shop_languageHelper', 'saveMultilanguageValue_new');

				/*
				 * Creating the sql statement for the field because otherwise install.php would suggest to
				 * delete the dynamically added fields because they are not defined in the database.sql
				 */
				if (\Database::getInstance()->tableExists($str_dcaName)) {
					$arr_newFieldDefinition['sql'] = ls_shop_generalHelper::getSQLFieldAttributes($str_dcaName, $str_fieldKey);
				}

				$arr_newFieldDefinitions[$str_newFieldKey] = $arr_newFieldDefinition;
			}
			$arr_multiLanguageFields[$str_fieldKey] = $arr_newFieldDefinitions;
		}

		/*
		 * Place all newly created fields in the dca fields array and check whether or not
		 * a database field has to be created because it doesn't exist yet.
		 */
		foreach ($arr_multiLanguageFields as $str_fieldKey => $arr_newFieldDefinitions) {
			foreach ($arr_newFieldDefinitions as $str_newFieldKey => $arr_newFieldDefinition) {
				$GLOBALS['TL_DCA'][$str_dcaName]['fields'][$str_newFieldKey] = $arr_newFieldDefinition;

				/*
				 * Don't try to add a database field if the table doesn't exist yet.
				 */
				if (!\Database::getInstance()->tableExists($str_dcaName)) {
					continue;
				}

				/*
				 * Don't try to add a database field if we are in the install tool
				 * or in the repository manager
				 */
				if (
						\Input::get('do') == 'repository_manager'
					||	strpos(\Environment::get('request'), 'install.php') !== false
				) {
					continue;
				}

				/*
				 * Don't add database fields for raw output fields
				 */
				if (
						!isset($arr_newFieldDefinition['input_field_callback'])
					||	$arr_newFieldDefinition['input_field_callback'][1] != 'rawOutputForBackendDCA'
				) {
					self::createMultilanguageDatabaseFieldIfNotExists($str_dcaName, $str_newFieldKey, $str_fieldKey);
				}
			}
		}

		foreach ($arr_multiLanguageFields as $str_fieldKey => $arr_fieldDefinitions) {
			/*
			 * Replace the fieldKey in the dca palettes or subpalettes with all newly created fieldKeys
			 */

			/*
			 * Creating the palette string for the new fieldKeys
			 */
			$str_paletteRepresentationOfNewFields = implode(',', array_keys($arr_fieldDefinitions));

			if (is_array($GLOBALS['TL_DCA'][$str_dcaName]['palettes'])) {
				foreach ($GLOBALS['TL_DCA'][$str_dcaName]['palettes'] as $str_paletteKey => $str_palette) {
					/*
					 * Skip the __selector__ palette because multilanguge fields as __selector__ fields are not supported and
					 * it would not make sense to support them because it would be unclear which field value to use for
					 * __selector__ purposes.
					 */
					if ($str_paletteKey == '__selector__') {
						continue;
					}

					$GLOBALS['TL_DCA'][$str_dcaName]['palettes'][$str_paletteKey] = preg_replace('/\b'.preg_quote($str_fieldKey).'\b/', $str_paletteRepresentationOfNewFields, $str_palette);
				}
			}

			if (is_array($GLOBALS['TL_DCA'][$str_dcaName]['subpalettes'] ?? null)) {
				foreach ($GLOBALS['TL_DCA'][$str_dcaName]['subpalettes'] as $str_paletteKey => $str_palette) {
					$GLOBALS['TL_DCA'][$str_dcaName]['subpalettes'][$str_paletteKey] = preg_replace('/\b'.preg_quote($str_fieldKey).'\b/', $str_paletteRepresentationOfNewFields, $str_palette);
				}
			}
		}
	}

	public static function createMultilanguageDatabaseFieldIfNotExists($str_tableName, $str_fieldName, $str_fieldToUseAsTemplate) {
		if (!\Database::getInstance()->tableExists($str_tableName)) {
			return array();
		}

		if (!isset($GLOBALS['merconis_globals']['multilanguageDatabaseFieldAlreadyChecked'])) {
			$GLOBALS['merconis_globals']['multilanguageDatabaseFieldAlreadyChecked'] = array();
		}

		if (!isset($GLOBALS['merconis_globals']['multilanguageDatabaseFieldAlreadyChecked'][$str_tableName])) {
			$GLOBALS['merconis_globals']['multilanguageDatabaseFieldAlreadyChecked'][$str_tableName] = array();
		}

		if (
				!isset($GLOBALS['merconis_globals']['multilanguageDatabaseFieldAlreadyChecked'][$str_tableName][$str_fieldName])
			||	!$GLOBALS['merconis_globals']['multilanguageDatabaseFieldAlreadyChecked'][$str_tableName][$str_fieldName]
		) {
			/*
			 * Check if field already exists in DB
			 */
			$obj_dbres_fields = \Database::getInstance()
			->prepare("
				SHOW COLUMNS FROM	`".$str_tableName."`
			")
			->execute();

			if (!$obj_dbres_fields->numRows) {
				return false;
			}

			$arr_fields = $obj_dbres_fields->fetchAllAssoc();
			$tmp_arrFields = array();
			foreach ($arr_fields as $arr_fieldInfo) {
				$tmp_arrFields[$arr_fieldInfo['Field']] = $arr_fieldInfo;
			}
			$arr_fields = $tmp_arrFields;

			$str_fieldAttributes = ls_shop_generalHelper::getSQLFieldAttributes($arr_fields, $str_fieldToUseAsTemplate);

			/*
			 * If the field to check does not exist but the field to use as a template for the
			 * field that should be created now does, the new field will be created now.
			 */
			if (
					!key_exists($str_fieldName, $arr_fields)
				&&	key_exists($str_fieldToUseAsTemplate, $arr_fields)
			) {
				\Database::getInstance()
				->prepare("
					ALTER TABLE	`".$str_tableName."`
					ADD			`".$str_fieldName."` ".$str_fieldAttributes."
				")
				->execute();
			}
		}

		// Store the information that the field has already been checked to prevent it from being checked again
		$GLOBALS['merconis_globals']['multilanguageDatabaseFieldAlreadyChecked'][$str_tableName][$str_fieldName] = true;
	}

	public static function getLanguageFromMultilanguageFieldKey($str_fieldKey) {
		preg_match('/_([a-zA-Z]*)$/', $str_fieldKey, $arr_matches);
		return isset($arr_matches) && isset($arr_matches[1]) ? $arr_matches[1] : false;
	}

	/*
	 * This save callback function is required to make sure that the value
	 * of a language field for the fallback language is also saved as the
	 * value of language independent master field
	 */
	public static function saveMultilanguageValue_new($var_value, \DataContainer $dc) {
		$str_tableName = $dc->table;
		$str_fieldName = $dc->field;
		$str_masterFieldName = preg_replace('/_[a-zA-Z]*$/', '', $str_fieldName);

		$str_languageKey = self::getLanguageFromMultilanguageFieldKey($str_fieldName);

		/*
		 * If this field has the fallback language, its value
		 * will be saved in the master field
		 */
		if ($str_languageKey == self::getFallbackLanguage()) {
			\Database::getInstance()
			->prepare("
				UPDATE	`".$str_tableName."`
				SET		`".$str_masterFieldName."` = ?
				WHERE	`id` = ?
			")
			->execute($var_value, $dc->id);
		}

		return $var_value;
	}

	public static function getMultilanguageDataRowInSpecificLanguage($arr_row = false, $str_languageKey = '') {
		if (!$arr_row || !is_array($arr_row) || !$str_languageKey) {
			return $arr_row;
		}

		$arr_rowTranslated = array();
		foreach ($arr_row as $str_fieldName => $str_fieldValue) {
			$arr_rowTranslated[$str_fieldName] = key_exists($str_fieldName.'_'.$str_languageKey, $arr_row) ? $arr_row[$str_fieldName.'_'.$str_languageKey] : $arr_row[$str_fieldName];
		}

		return $arr_rowTranslated;
	}

	public static function getAllMultilanguageFields($str_tableName = null) {
		if (!\Database::getInstance()->tableExists($str_tableName)) {
			return array();
		}

		if (!isset($GLOBALS['merconis_globals']['allMultilanguageFields'][$str_tableName])) {
			if (!$str_tableName) {
				return array();
			}

			$GLOBALS['merconis_globals']['allMultilanguageFields'][$str_tableName] = array();

			$arr_multilanguageFields = array();


			$obj_dbres_fields = \Database::getInstance()
			->prepare("
				SHOW COLUMNS FROM	`".$str_tableName."`
			")
			->execute();

			if (!$obj_dbres_fields->numRows) {
				return $GLOBALS['merconis_globals']['allMultilanguageFields'][$str_tableName];
			}

			$arr_fields = $obj_dbres_fields->fetchAllAssoc();
			$arr_tmp_fields = array();
			foreach ($arr_fields as $arr_fieldInfo) {
				$arr_tmp_fields[$arr_fieldInfo['Field']] = $arr_fieldInfo;
			}
			$arr_fields = $arr_tmp_fields;

			foreach ($arr_fields as $str_fieldName => $arr_fieldInfo) {
				if (!self::checkIfFieldIsMultilanguageField($str_fieldName, $arr_fields)) {
					continue;
				}
				$arr_multilanguageFields[] = $str_fieldName;
			}

			$GLOBALS['merconis_globals']['allMultilanguageFields'][$str_tableName] = $arr_multilanguageFields;
		}

		return $GLOBALS['merconis_globals']['allMultilanguageFields'][$str_tableName];
	}


	/*
	 * This function is used to load all MERCONIS DCA configurations in order
	 * to create the required language fields because the creation of language
	 * fields is part of the loadDataContainer callback!
	 */
	public static function multilanguageInitialization($bln_onlyIfDbFieldsNotOkay = true, $arr_excludeDCA = null) {
		if ($bln_onlyIfDbFieldsNotOkay) {
			/*
			 * Check whether or not database fields for all currently existing languages exist.
			 * If they exist, return from this function without doing anything. The title field
			 * in tl_ls_shop_product is checked because if it exists for all languages it
			 * definitely means that all language specific fields in all tables exist in all
			 * languages.
			 */
			$arr_fields = \Database::getInstance()->listFields('tl_ls_shop_product');
			$arr_tmp_fields = array();
			foreach ($arr_fields as $arr_fieldInfo) {
				$arr_tmp_fields[] = $arr_fieldInfo['name'];
			}
			$arr_fields = $arr_tmp_fields;

			$bln_fieldsOkay = true;
			foreach (self::getAllLanguages() as $str_language) {
				if (!in_array('title_'.$str_language, $arr_fields)) {
					$bln_fieldsOkay = false;
				}
			}

			if ($bln_fieldsOkay) {
				/*
				 * Do nothing if fields are already okay.
				 */
				return;
			}
		}

		if ($arr_excludeDCA === null) {
			/*
			 * If no DCA to exclude is given, and if not even
			 * an empty array indicating that no DCA should
			 * be skipped is given, exclude at least 'tl_page'
			 * by default.
			 */
			$arr_excludeDCA = array('tl_page');
		}

		/*
		 * Load all DCA definitions
		 */
		$str_pathToMerconisDCA = TL_ROOT.'/vendor/leadingsystems/contao-merconis/src/Resources/contao/dca';
		$arr_dcaFiles = scandir($str_pathToMerconisDCA);
		foreach ($arr_dcaFiles as $str_filename) {
			if ($str_filename == '.' || $str_filename == '..') {
				continue;
			}
			$str_dcaName = substr($str_filename, 0, -4);
			if (in_array($str_dcaName, $arr_excludeDCA)) {
				continue;
			}
			\Controller::loadDataContainer($str_dcaName);
		}
	}









	/*
	 * Diese Funktion erwartet als Parameter das Sprachkürzel, den Tabellennamen der languages-Tabelle, die ID des korrespondierenden
	 * Datensatzes aus der Parent-Tabelle als Parent-ID sowie den einzutragenden Wert. $var_fieldName und $var_value können Arrays sein, womit
	 * mehrere Werte für mehrere Felder gleichzeitig eingetragen werden können. Ist $var_fieldName ein Array, so muss $var_value auf jeden
	 * Fall auch ein Array sein.
	 *
	 * FIXME: New function description!
	 */
	public static function saveMultilanguageValue($int_rowId = false, $str_language = '', $str_tableName = '', $var_fieldName = '', $var_value = '') {
		if (
			!$str_language
			||	!$str_tableName
			||	!$var_fieldName
			||	!$int_rowId
		) {
			return false;
		}

		/*
		 * Don't try to insert anything if the language that should be inserted doesn't exist in the languages array
		 */
		if (!in_array($str_language, ls_shop_languageHelper::getAllLanguages())) {
			return false;
		}

		/*
		 * Deal with language table names which don't exist anymore but could possibly be given as an argument
		 * because old code (before the big multilanguage recronstruction) might call this function the
		 * old-fashioned way
		 */
		$str_tableName = preg_replace('/_languages/', '', $str_tableName);

		$str_set = "";
		if (is_array($var_fieldName)) {
			if (!is_array($var_value)) {
				throw new \Exception('Value must be an array if fieldName is an array.');
			}
			foreach ($var_fieldName as $str_singleFieldName) {
				if ($str_set) {
					$str_set .= ", ";
				}
				$str_set .= "`".$str_singleFieldName."_".$str_language."` = ?";
			}
			$arr_values = $var_value;
		} else {
			$str_set = "`".$var_fieldName."_".$str_language."` = ?";
			$arr_values = array($var_value);
		}

		$arr_values[] = $int_rowId;
		\Database::getInstance()
			->prepare("
			UPDATE		`".$str_tableName."`
			SET			".$str_set."
			WHERE		`id` = ?
		")
			->limit(1)
			->execute($arr_values);
	}

	/*
	 * Diese Funktion löscht Spracheinträge und erwartet als Parameter die Parent-ID, den Tabellennamen
	 * sowie ein Array, das die Sprachkürzel der zu löschenden Sprachen enthält oder einen String
	 * mit dem Keyword "all", um alle Spracheinträge zur Parent-ID in dieser Tabelle zu löschen.
	 */
	public static function deleteEntry($int_rowId = false, $str_tableName = '', $var_languages = 'all') {
		if (
			!$int_rowId
			||	!$str_tableName
			||	(
				$var_languages != 'all'
				&&	!is_array($var_languages)
			)
		) {
			throw new \Exception('insufficient parameters given');
		}

		/*
		 * Deal with language table names which don't exist anymore but could possibly be given as an argument
		 * because old code (before the big multilanguage recronstruction) might call this function the
		 * old-fashioned way
		 */
		$str_tableName = preg_replace('/_languages/', '', $str_tableName);

		/*
		 * "Deleting" the language fields means filling them with nothing. We use
		 * the saveMultilanguageValue function for that.
		 */
		$arr_multilanguageFields = ls_shop_languageHelper::getAllMultilanguageFields($str_tableName);
		$arr_values = array_fill(0, count($arr_multilanguageFields), '');

		if (!is_array($var_languages) && $var_languages == 'all') {
			$var_languages = ls_shop_languageHelper::getAllLanguages();
		}

		foreach ($var_languages as $str_language) {
			self::saveMultilanguageValue($int_rowId, $str_language, $str_tableName, $arr_multilanguageFields, $arr_values);
		}
	}

	/*
	 * Diese Funktion erwartet als Parameter eine Parent-ID, einen Sprach-Tabellennamen und Angaben zu den
	 * gewünschten Feldern und Sprachen und gibt dann ein Array mit den entsprechenden Feldwerten in den
	 * unterschiedlichen Sprachen zurück
	 */
	public static function getMultiLanguage($int_rowId = false, $str_tableName = '', $var_fields = 'all', $var_languages = 'all', $bln_allFieldsIncludingMonolanguageFields = false, $bln_returnNullIfLanguageNotDefined = true, $arr_dbRecord = null) {
		$str_cacheKey = md5(serialize(func_get_args()));

		if (isset($GLOBALS['merconis_globals']['getMultiLanguage'][$str_cacheKey])) {
			return $GLOBALS['merconis_globals']['getMultiLanguage'][$str_cacheKey];
		}

		if (
				(!$int_rowId && !is_array($arr_dbRecord))
			|| 	!$str_tableName
			|| 	($var_fields != 'all' && (!is_array($var_fields) || !count($var_fields)))
			|| 	($var_languages != 'all' && (!is_array($var_languages) || !count($var_languages)))
		) {
			throw new \Exception('insufficient parameters given');
		}

		/*
		 * Deal with language table names which don't exist anymore but could possibly be given as an argument
		 * because old code (before the big multilanguage recronstruction) might call this function the
		 * old-fashioned way
		 */
		$str_tableName = preg_replace('/_languages/', '', $str_tableName);


		$var_return = null;

        if (is_array($arr_dbRecord)) {
            /*
             * If the data array has already been passed as an argument,
             * no db request is necessary to retrieve the data ...
             */
            $arr_data = $arr_dbRecord;
        } else {
            /*
             * ... otherwise get the complete row for the requested id
             */
            $obj_dbres_row = \Database::getInstance()
                ->prepare("
                SELECT		*
                FROM		`".$str_tableName."`
                WHERE		`id` = ?
            ");

            $obj_dbres_row = $obj_dbres_row->execute($int_rowId);

            if (!$obj_dbres_row->numRows) {
                $GLOBALS['merconis_globals']['getMultiLanguage'][$str_cacheKey] = $var_return;
                return $GLOBALS['merconis_globals']['getMultiLanguage'][$str_cacheKey];
            }

            $arr_data = $obj_dbres_row->fetchAssoc();
        }

		if (is_array($var_languages) && count($var_languages) == 1) {
			$var_languages = $var_languages[0];
		}

		if (is_array($var_fields) && count($var_fields) == 1) {
			$var_fields = $var_fields[0];
		}

		/*
		 * Translating $varValues == 'all' in an array holding all existing languages
		 */
		if (!is_array($var_languages) && $var_languages == 'all') {
			$var_languages = ls_shop_languageHelper::getAllLanguages();
		}

		/*
		 * Translating $var_fields == 'all' in an array holding all multilanguage fields.
		 *
		 * A multilanguage field can be identified as a field that has at least one corresponding
		 * language specific field with the language key of the fallback language
		 */
		if (!is_array($var_fields) && $var_fields == 'all') {
			$var_fields = array();

			/*
			 * Checking if a field exists that has the same name as the current field but with
			 * the fallback language key as a suffix. If that's true, the field name of the current
			 * field will be added to $var_fields because it is a multilanguage field.
			 */
			foreach ($arr_data as $str_fieldName => $fieldValue) {
				if ($bln_allFieldsIncludingMonolanguageFields) {
					/*
					 * If the resulting data set should contain fields that aren't multilanguage
					 * fields, fields that are multilanguage subfields should still be skipped
					 */
					if (ls_shop_languageHelper::checkIfFieldIsMultilanguageSubfield($str_fieldName, $arr_data)) {
						continue;
					}
					$var_fields[] = $str_fieldName;
				} else {
					if (ls_shop_languageHelper::checkIfFieldIsMultilanguageField($str_fieldName, $arr_data)) {
						$var_fields[] = $str_fieldName;
					}
				}
			}
		}

		/*
		 * If one specific field in one specific language is requested
		 */
		if (!is_array($var_fields) && !is_array($var_languages)) {
			$var_return = isset($arr_data[$var_fields.'_'.$var_languages]) ? $arr_data[$var_fields.'_'.$var_languages] : ($bln_returnNullIfLanguageNotDefined ? null : $arr_data[$var_fields]);
			$GLOBALS['merconis_globals']['getMultiLanguage'][$str_cacheKey] = $var_return;
			return $GLOBALS['merconis_globals']['getMultiLanguage'][$str_cacheKey];
		}

		/*
		 * If multiple fields in one specific language are requested
		 */
		if (is_array($var_fields) && !is_array($var_languages)) {
			$var_return = array();
			foreach ($var_fields as $str_fieldName) {
				$var_return[$str_fieldName] = isset($arr_data[$str_fieldName.'_'.$var_languages]) ? $arr_data[$str_fieldName.'_'.$var_languages] : ($bln_returnNullIfLanguageNotDefined ? null : $arr_data[$str_fieldName]);
			}
			$GLOBALS['merconis_globals']['getMultiLanguage'][$str_cacheKey] = $var_return;
			return $GLOBALS['merconis_globals']['getMultiLanguage'][$str_cacheKey];
		}

		/*
		 * If one specific field in multiple languages is requested
		 */
		if (!is_array($var_fields) && is_array($var_languages)) {
			$var_return = array();
			foreach ($var_languages as $str_languageKey) {
				$var_return[$str_languageKey] = isset($arr_data[$var_fields.'_'.$str_languageKey]) ? $arr_data[$var_fields.'_'.$str_languageKey] : ($bln_returnNullIfLanguageNotDefined ? null : $arr_data[$var_fields]);
			}
			$GLOBALS['merconis_globals']['getMultiLanguage'][$str_cacheKey] = $var_return;
			return $GLOBALS['merconis_globals']['getMultiLanguage'][$str_cacheKey];
		}

		/*
		 * If multiple fields in multiple languages are requested
		 */
		if (is_array($var_fields) && is_array($var_languages)) {
			$var_return = array();
			foreach ($var_languages as $str_languageKey) {
				$var_return[$str_languageKey] = array();
				foreach ($var_fields as $str_fieldName) {
					$var_return[$str_languageKey][$str_fieldName] = isset($arr_data[$str_fieldName.'_'.$str_languageKey]) ? $arr_data[$str_fieldName.'_'.$str_languageKey] : ($bln_returnNullIfLanguageNotDefined ? null : $arr_data[$str_fieldName]);
				}
			}
			$GLOBALS['merconis_globals']['getMultiLanguage'][$str_cacheKey] = $var_return;
			return $GLOBALS['merconis_globals']['getMultiLanguage'][$str_cacheKey];
		}
	}

	/*
	 * Diese Funktion liefert zu einer pageID die pageID der korrespondierenden Hauptsprachseite
	 * bzw. gibt die pageID wieder zurück, sofern es sich dabei bereits um die Hauptsprachseite handelt.
	 */
	public static function getMainlanguagePageIDForPageID($pageID = false) {
		$objLanguageSelectorController = new \LeadingSystems\LanguageSelector\LsController();
		return $objLanguageSelectorController->getMainlanguagePageIDForPageID($pageID);
	}

	/*
	 * Diese Funktion bereitet das Array, welches die IDs der Sprach-Seiten enthält, auf,
	 * sodass es von der Funktion getLanguagePage verarbeitet werden kann.
	 */
	public static function processLanguagePageArray($key) {
		/** @var \PageModel $objPage */
		global $objPage;

		if (!is_array($GLOBALS['TL_CONFIG'][$key])) {
			$languagePages = StringUtil::deserialize($GLOBALS['TL_CONFIG'][$key]);
			$GLOBALS['TL_CONFIG'][$key] = array();
			if (is_array($languagePages)) {
				foreach ($languagePages as $languagePageID) {
					/*
					 * Zu jeder Seite wird eingetragen, welche Sprache sie hat.
					 */
					$pageInfo = \PageModel::findWithDetails($languagePageID);

					/*
					 * Skip pages with non-matching domains
					 */
					if (is_object($objPage) && $pageInfo->domain != $objPage->domain) {
						continue;
					}
					$GLOBALS['TL_CONFIG'][$key][$pageInfo->language] = $languagePageID;
				}
			}
		}
	}

	/*
	 * Diese Funktion ermittelt die URL der zur Sprache passenden Seite aus einem Sprach-Seiten-Auswahl-Array.
	 * Dies ist nötig, da in tl_lsShopSettings Seiten (z. B. für Versandkosteninfo usw.) ausgewählt werden
	 * und es hierbei möglich/nötig ist, für jede Sprache eine andere Seite auszuwählen. Um aus dieser
	 * Sprach-Seiten-Auswahl die für die aktuell aktive Sprache richtige Seite auszugeben
	 * (bzw. die URL zu dieser Seite), wird diese Funktion genutzt.
	 *
	 * Diese Funktion erwartet als Parameter den Key für das entsprechende Sprach-Seiten-Auswahl-Array in tl_lsShopSettings,
	 * also in $GLOBALS['TL_CONFIG'].
	 *
	 * Optional kann direkt ein serialisiertes Array übergeben werden, welches die Sprach-Seiten-Auswahl enthält, so können
	 * auch Sprach-Seiten ermittelt werden, bei denen die Auswahl nicht in $GLOBALS['TL_CONFIG'] gespeichert ist.
	 * In diesem Fall ist als erster Parameter weiterhin ein Key anzugeben, der in $GLOBALS['TL_CONFIG'] noch nicht
	 * existieren darf, da ansonsten der zweite übergebene Parameter ignoriert wird.
	 */
	public static function getLanguagePage($key, $languagePageSelection = false, $returnMode = 'url') {
		if (!isset($GLOBALS['merconis_globals'][$key.'Url']) || !isset($GLOBALS['merconis_globals'][$key.'ID']) || !isset($GLOBALS['merconis_globals'][$key.'Array'])) {
			if (!isset($GLOBALS['TL_CONFIG'][$key]) && $languagePageSelection) {
				$GLOBALS['TL_CONFIG'][$key] = $languagePageSelection;
			}

			/** @var \PageModel $objPage */
			global $objPage;
			ls_shop_languageHelper::processLanguagePageArray($key);
			$GLOBALS['merconis_globals'][$key.'Url'] = '';
			$GLOBALS['merconis_globals'][$key.'ID'] = 0;
			/*
			 * Prüfen, ob das Sprach-Seiten-Auswahl-Array mindestens ein Element enthält. Wenn ja,
			 * so wird das erste Element des Arrays als Fallback-ID verwendet. Falls nicht,
			 * so wird die Root-Page als Fallback verwendet
			 */
			if (count($GLOBALS['TL_CONFIG'][$key])) {
				foreach ($GLOBALS['TL_CONFIG'][$key] as $pageFallbackID) {
					break;
				}
			} else {
				$pageFallbackID = $objPage->rootId;
			}

			/*
			 * Prüfen, ob es eine zur Sprache passende Seite gibt. Wenn ja,
			 * die ID dieser Seite verwenden, falls nicht, Fallback-ID verwenden.
			 */
			if ($GLOBALS['TL_CONFIG'][$key][$objPage->language ?? ''] ?? null) {
				$pageID = $GLOBALS['TL_CONFIG'][$key][$objPage->language];
			} else {
				$pageID = $pageFallbackID;
			}

			$objLanguagePage = \Database::getInstance()
				->prepare("SELECT id, alias FROM tl_page WHERE id = ?")
				->limit(1)
				->execute($pageID);

			if ($objLanguagePage->numRows) {
				$GLOBALS['merconis_globals'][$key.'Array'] = $objLanguagePage->row();
                $pageModel = PageModel::findWithDetails($GLOBALS['merconis_globals'][$key.'Array']['id']);
                $GLOBALS['merconis_globals'][$key.'Url'] = $pageModel->getFrontendUrl();
			}

			$GLOBALS['merconis_globals'][$key.'ID'] = $pageID;
		}
		switch ($returnMode) {
			case 'id':
				return $GLOBALS['merconis_globals'][$key.'ID'];
				break;

			case 'array':
				return $GLOBALS['merconis_globals'][$key.'Array'];
				break;

			case 'url':
			default:
				return $GLOBALS['merconis_globals'][$key.'Url'];
				break;
		}
	}

	/*
	 * Diese Funktion dient dazu, zu einer Hauptsprachseite die Fremdsprach-Pendants zu ermitteln. Wird als
	 * Argument die ID einer Fremdsprachseite übergeben, so wird zu dieser zunächst automatisch die Hauptsprachseite
	 * ermittelt.
	 */
	public static function getLanguagePages($pageID = false) {
		if (!$pageID) {
			return array();
		}
		$objLanguageSelectorController = new \LeadingSystems\LanguageSelector\LsController();
		$arrLanguagePages = $objLanguageSelectorController->getCorrespondingLanguagesForCurrentRootPage($pageID);
		return $arrLanguagePages;
	}

	public static function translateProductAliasLanguage($str_alias, $str_sourceLanguage, $str_targetLanguage) {
		if (!$str_alias || !$str_sourceLanguage || !$str_targetLanguage) {
			return $str_alias;
		}

		try {
			$obj_dbres_recordForAlias = \Database::getInstance()->prepare("
					SELECT		`alias_".$str_targetLanguage."`
					FROM		`tl_ls_shop_product`
					WHERE		`alias_".$str_sourceLanguage."` = ?
				")
				->execute(
					urldecode($str_alias)
				);
		} catch (\Exception $e) {
			return $str_alias;
		}

		if ($obj_dbres_recordForAlias->numRows < 1) {
			return $str_alias;
		}

		return $obj_dbres_recordForAlias->first()->{'alias_'.$str_targetLanguage};
	}

	public static function translateVariantAliasLanguage($str_alias, $str_sourceLanguage, $str_targetLanguage) {
		if (!$str_alias || !$str_sourceLanguage || !$str_targetLanguage) {
			return $str_alias;
		}

		try {
			$obj_dbres_recordForAlias = \Database::getInstance()->prepare("
					SELECT		`alias_".$str_targetLanguage."`
					FROM		`tl_ls_shop_variant`
					WHERE		`alias_".$str_sourceLanguage."` = ?
				")
				->execute(
					urldecode($str_alias)
				);
		} catch (\Exception $e) {
			return $str_alias;
		}

		if ($obj_dbres_recordForAlias->numRows < 1) {
			return $str_alias;
		}

		return $obj_dbres_recordForAlias->first()->{'alias_'.$str_targetLanguage};
	}

	/*
	 * This function looks for a product alias and translates it
	 * considering the target language
	 */
	public static function translateProductAliasInLanguageSelectorLink($arr_languageLink, $str_sourceLanguage, $str_targetLanguage) {
		if (!$arr_languageLink || !is_array($arr_languageLink) || !$str_sourceLanguage || !$str_targetLanguage) {
			return $arr_languageLink;
		}

		$arr_matches = array();
		if (!preg_match('/(product\/)(.*?)(\/|\.|$)/', $arr_languageLink['href'], $arr_matches)) {
			return $arr_languageLink;
		}

		/*
		 * Take the alias from the matches array and translate it
		 */
		$str_productAlias = $arr_matches[2];

		/*
		 * If the value for the product parameter in the url is not actually
		 * an alias but a numeric id, we don't translate anything
		 */
		if (ctype_digit($str_productAlias)) {
			return $arr_languageLink;
		}

		$str_productAlias = ls_shop_languageHelper::translateProductAliasLanguage($str_productAlias, $str_sourceLanguage, $str_targetLanguage);

		$arr_languageLink['href'] = preg_replace('/(product\/)(.*?)(\/|\.|$)/', $arr_matches[1].$str_productAlias.$arr_matches[3], $arr_languageLink['href']);

		return $arr_languageLink;
	}

	/*
	 * This function looks for a product alias and translates it
	 * considering the target language
	 */
	public static function translateVariantAliasInLanguageSelectorLink($arr_languageLink, $str_sourceLanguage, $str_targetLanguage) {
		if (!$arr_languageLink || !is_array($arr_languageLink) || !$str_sourceLanguage || !$str_targetLanguage) {
			return $arr_languageLink;
		}

		$arr_matches = array();
		if (!preg_match('/(selectVariant\/)(.*?)(\/|\.|$)/', $arr_languageLink['href'], $arr_matches)) {
			return $arr_languageLink;
		}

		/*
		 * Take the alias from the matches array and translate it
		 */
		$str_variantAlias = $arr_matches[2];

		/*
		 * If the value for the selectVariant parameter in the url is not actually
		 * an alias but a numeric id, we don't translate anything
		 */
		if (ctype_digit($str_variantAlias)) {
			return $arr_languageLink;
		}

		$str_variantAlias = ls_shop_languageHelper::translateVariantAliasLanguage($str_variantAlias, $str_sourceLanguage, $str_targetLanguage);

		$arr_languageLink['href'] = preg_replace('/(selectVariant\/)(.*?)(\/|\.|$)/', $arr_matches[1].$str_variantAlias.$arr_matches[3], $arr_languageLink['href']);

		return $arr_languageLink;
	}

	public static function modifyLanguageSelectorLinks($arr_languagesLinks, $str_sourceLanguage) {
		foreach ($arr_languagesLinks as $str_targetLanguage => $arr_languageLink) {
			$arr_languageLink = ls_shop_languageHelper::translateProductAliasInLanguageSelectorLink($arr_languageLink, $str_sourceLanguage, $str_targetLanguage);
			$arr_languageLink = ls_shop_languageHelper::translateVariantAliasInLanguageSelectorLink($arr_languageLink, $str_sourceLanguage, $str_targetLanguage);
			$arr_languagesLinks[$str_targetLanguage] = $arr_languageLink;
		}

		return $arr_languagesLinks;
	}

}
