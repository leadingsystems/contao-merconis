<?php

namespace Merconis\Core;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\System;
use function LeadingSystems\Helpers\createMultidimensionalArray;

	class ls_shop_custom_regexp_fe
	{
		public function customRegexp($strRegexp, &$varValue, \Widget &$objWidget) {
			$decimalsSeparator = ($GLOBALS['merconis_globals']['ls_shop_decimalsSeparator'] ?? null) ?: '.';
			switch($strRegexp) {
				case 'merconisCheckVATID':
					/*
					 * Remove spaces from the input value because many people would enter the VATID with spaces grouping
					 * the digits although that is not the correct syntax
					 */
					$varValue = preg_replace('/\s/', '', $varValue);
					
					/*
					 * Check if the given VATID matches the general syntax pattern and if it does
					 * split it into the country code and the number itself
					 */
					if (!preg_match('/^([A-Za-z]{2})([0-9A-Za-z\+\*\.]{2,12})$/', $varValue, $arrMatches)) {
						// The syntax of the VATID is invalid
						$objWidget->addError($GLOBALS['TL_LANG']['MSC']['ls_shop']['VATValidationMessages']['VATIDInvalid']);
						return false;
					}
					$customerCountryCode = strtoupper($arrMatches[1]);
					$customerVATNumber = $arrMatches[2];
					
					$arrEUCountries = explode(',', strtoupper($GLOBALS['TL_CONFIG']['ls_shop_euCountrycodes']));
					
					/*
					 * Check if the country code extracted from the given VATID is listed in the defined EU countries list
					 */
					if (!in_array($customerCountryCode, $arrEUCountries)) {
						$objWidget->addError($GLOBALS['TL_LANG']['MSC']['ls_shop']['VATValidationMessages']['noEUCountry']);
						return false;
					}
					
					/*
					 * Check if the country code extracted from the given VATID matches the country stated in the customer's invoice address.
					 * If no country is given (which is the case when the validation takes place in the checkoutData and not during a regular
					 * form validation process) the country code is not matched against the country because this would always faild under
					 * these circumstances.
					 */
					if ($objWidget->Input->post('country') && $customerCountryCode != strtoupper($objWidget->Input->post('country'))) {
						$objWidget->addError($GLOBALS['TL_LANG']['MSC']['ls_shop']['VATValidationMessages']['countryDoesNotMatch']);
						return false;					
					}
					
					
					/*
					 * VATID online validation
					 */
					if (isset($GLOBALS['TL_CONFIG']['ls_shop_useVATIDValidation']) && $GLOBALS['TL_CONFIG']['ls_shop_useVATIDValidation']) {
						/*
						 * Only process this part of the VATID validation if the same VATID hasn't been validated yet.
						 * Each VATID will only be validated once to prevent unnecessary connection attempts to the SOAP service.
						 * If the VATID has been validated yet, the result that has been stored in the session before will be used.
						 */
						if (
								!isset($_SESSION['lsShop']['checkedVATID'][$varValue])
							||	!is_array($_SESSION['lsShop']['checkedVATID'][$varValue])
						) {
							$europeanServiceWsdlUrl = 'http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl';
							$blnTryAgain = false;
							$default_socket_timeout_before = ini_get( 'default_socket_timeout' );
							ini_set('default_socket_timeout', 3);
							try {
								/*
								 * Trying to establish a SOAP connection with the options array given in the localconfig settings...
								 */
								$options = createMultidimensionalArray(\LeadingSystems\Helpers\createOneDimensionalArrayFromTwoDimensionalArray(json_decode($GLOBALS['TL_CONFIG']['ls_shop_VATIDValidationSOAPOptions'])), 2, 1);
								$blnOptionsFilled = false;
								foreach ($options as $k => $v) {
									if ($k) {
										$blnOptionsFilled = true;
									}
								}
								
								if (!$blnOptionsFilled) {
									throw new \Exception('options array empty');
								}
								
								/*
								 * Replace all constants in the options array with the actual constants
								 */
								foreach ($options as $k => $v) {
									if (preg_match('/^constant:(.*)$/', $v, $arrMatches)) {
										$options[$k] = constant(trim($arrMatches[1]));
									} else if ($v === 'true') {
										// $options[$k] = true;
									} else if ($v === 'false') {
										// $options[$k] = false;
									}
								}
								
								$sc = @ new \SoapClient($europeanServiceWsdlUrl, $options);
								
								$result = @ $sc->checkVat(array(
									'countryCode' => $customerCountryCode,
									'vatNumber' => $customerVATNumber
								));
							} catch (\Exception $e) {
								$blnTryAgain = true;
							}

							/*
							 * ... and if the connection attempt using the options array fails, we try it again without the options array
							 * but we still use default options defining a connection_timeout
							 */
							if ($blnTryAgain) {
								try {
									$sc = @ new \SoapClient($europeanServiceWsdlUrl, array('connection_timeout' => 10));

									$result = @ $sc->checkVat(array(
										'countryCode' => $customerCountryCode,
										'vatNumber' => $customerVATNumber
									));
								} catch (\Exception $e) {
                                    System::getContainer()->get('monolog.logger.contao')->info('MERCONIS: Checking a VATID failed. SOAP exception faultcode: "'.$e->faultcode.'", SOAP exception message: "'.$e->faultstring.'"', ['contao' => new ContaoContext('MERCONIS MESSAGES', TL_MERCONIS_ERROR)]);
									$result = null; // result null indicates that the validation was not possible which means that the given VATID could be valid but that is unknown
								}
							}
							
							ini_set('default_socket_timeout', $default_socket_timeout_before);
							
							/*
							 * If the result is not definitely true/1 or false/0, the result null will be stored.
							 */
							if ($result === null || !isset($result->valid) || ($result->valid !== true && $result->valid !== false && $result->valid !== 1 && $result->valid !== 0)) {
								$_SESSION['lsShop']['checkedVATID'][$varValue] = array(
									'valid' => null
								);
							} else {
								$_SESSION['lsShop']['checkedVATID'][$varValue] = array(
									'valid' => $result->valid ? true : false,
									'arrDetails' => $result
								);
							}
						}

						/*
						 * Using the online validation result that has been stored in the session regardless of when the online validation request has been performed.
						 */
						if ($_SESSION['lsShop']['checkedVATID'][$varValue]['valid'] === null || $_SESSION['lsShop']['checkedVATID'][$varValue]['valid']) {
							/*
							 * If $_SESSION['lsShop']['checkedVATID'][$varValue]['valid'] is null (no result could be retrieved for whatever reason, e. g. soap service down)
							 * this validation actually validates the given VATID positively to make sure that a VATID whose validation status is unclear will not be blocked.
							 * 
							 * If we have a result and it's valid, we'll validate the widget positively as well.
							 */
							return true;
						} else {
							/*
							 * If we have a clear result from the eu service and 'valid' is not true, we don't validate
							 * the widget, making it impossible to place the order using the given VATID.
							 */
							$objWidget->addError($GLOBALS['TL_LANG']['MSC']['ls_shop']['VATValidationMessages']['notValidAccordingToEUService']);
							return false;
						}
					}
					

					return true;
					break;
					
				case 'numberWithDecimalsFE':
					if (
							preg_match('/[^-0-9\\'.$decimalsSeparator.($GLOBALS['merconis_globals']['ls_shop_thousandsSeparator'] ? '\\'.$GLOBALS['merconis_globals']['ls_shop_thousandsSeparator'] : '').']/siU', $varValue)
						||	preg_replace('/\\'.$GLOBALS['merconis_globals']['ls_shop_decimalsSeparator'].'/siU', '.', $varValue) <= 0
					) {
						$objWidget->addError(sprintf($GLOBALS['TL_LANG']['MOD']['ls_shop']['rgxpErrorMessages']['numberWithDecimalsFE'], $objWidget->label));
					}
					
					return true;
					break;
			}
			return false;
		}
	}
?>