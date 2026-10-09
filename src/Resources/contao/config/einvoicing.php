<?php
declare(strict_types=1);

use LeadingSystems\MerconisBundle\EInvoicing\Contao\EInvoicingDcaHook;
use LeadingSystems\MerconisBundle\EInvoicing\Contao\EInvoicingCheckoutSnapshotHook;

$GLOBALS['TL_HOOKS']['loadDataContainer'][] = [EInvoicingDcaHook::class, 'onLoadDataContainer'];
$GLOBALS['MERCONIS_HOOKS']['afterCheckout'][] = [EInvoicingCheckoutSnapshotHook::class, 'afterCheckout'];
$GLOBALS['MERCONIS_HOOKS']['storeCartItemInOrder'][] = [EInvoicingCheckoutSnapshotHook::class, 'storeCartItemInOrder'];
