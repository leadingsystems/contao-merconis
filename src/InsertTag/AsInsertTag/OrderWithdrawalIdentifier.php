<?php

namespace LeadingSystems\MerconisBundle\InsertTag\AsInsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\Database;
use Contao\Input;
use Merconis\Core\ls_shop_generalHelper;

#[AsInsertTag('shop_order_withdrawal_identifier')]
#[AsInsertTag('shoporderwithdrawalidentifier')]
class OrderWithdrawalIdentifier extends InsertTag
{
	public function customInserttags($strTag, $params) {
		$arrOrder = $this->resolveCurrentOrder();
		if (!is_array($arrOrder) || !count($arrOrder)) {
			return '';
		}

		return (string) ($arrOrder['withdrawalIdentifier'] ?? '');
	}

	protected function resolveCurrentOrder(): ?array
	{
		$orderIdentificationHash = trim((string) (Input::get('oih') ?: Input::post('oih')));
		if ($orderIdentificationHash !== '') {
			return $this->resolveOrderByOrderIdentificationHash($orderIdentificationHash);
		}

		$withdrawalIdentifier = trim((string) (Input::get('wid') ?: Input::post('wid')));
		if ($withdrawalIdentifier !== '') {
			return $this->resolveOrderByWithdrawalIdentifier($withdrawalIdentifier);
		}

		$encodedOrderId = trim((string) (Input::get('oix') ?: Input::post('oix')));
		if ($encodedOrderId !== '') {
			return $this->resolveOrderByEncodedOrderId($encodedOrderId);
		}

		return null;
	}

	protected function resolveOrderByOrderIdentificationHash(string $orderIdentificationHash): ?array
	{
		$arrOrder = ls_shop_generalHelper::getOrder($orderIdentificationHash, 'orderIdentificationHash');

		return is_array($arrOrder) && count($arrOrder) ? $arrOrder : null;
	}

	protected function resolveOrderByWithdrawalIdentifier(string $withdrawalIdentifier): ?array
	{
		$normalizedWithdrawalIdentifier = $this->normalizeWithdrawalIdentifier($withdrawalIdentifier);
		if ($normalizedWithdrawalIdentifier === '') {
			return null;
		}

		$objResult = Database::getInstance()
			->prepare(
				"SELECT `id`
				 FROM `tl_ls_shop_orders`
				 WHERE REPLACE(UPPER(`withdrawalIdentifier`), '-', '') = ?"
			)
			->limit(1)
			->execute($normalizedWithdrawalIdentifier);

		if (!$objResult->numRows) {
			return null;
		}

		$orderId = (int) $objResult->id;
		$arrOrder = ls_shop_generalHelper::getOrder($orderId);

		return is_array($arrOrder) && count($arrOrder) ? $arrOrder : null;
	}

	protected function resolveOrderByEncodedOrderId(string $encodedOrderId): ?array
	{
		$orderId = (int) ls_shop_generalHelper::decodeOix($encodedOrderId);
		if ($orderId <= 0) {
			return null;
		}

		$arrOrder = ls_shop_generalHelper::getOrder($orderId);

		return is_array($arrOrder) && count($arrOrder) ? $arrOrder : null;
	}

	protected function normalizeWithdrawalIdentifier(string $withdrawalIdentifier): string
	{
		return strtoupper(str_replace('-', '', trim($withdrawalIdentifier)));
	}
}
