Leading Systems Contao Merconis bundle
=================================

Withdrawal entry points
-----------------------

- Global entry point: add a visible link with the label `Vertrag widerrufen`
  to the page configured via `ls_shop_withdrawalPages`.
- Order communication (mail templates): use
  `{{shop_link::withdrawalPage}}?wid=##orderWithdrawalIdentifier##` for the direct link
  and `##orderWithdrawalIdentifier##` for the plain identifier text.
- Customer account integration:
  - `ls_shop_myOrders` shows a direct link per order.
  - `ls_shop_myOrderDetails` shows the identifier and the direct link.
