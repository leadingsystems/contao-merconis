Leading Systems Contao Merconis bundle changelog
===========================================

### 5.1.17 (2026-06-26)
 * improve product filter (performance)
 * feature cart item comment
 * improve old price

### 5.1.16 (2026-06-12)
 * fix feature 'Widerruf': Configurator/Customizer Referenz
 * fix branding
 * fix withdrawal honeypot autofill

### 5.1.15 (2026-05-08)
 * improve SitemapListener
 * fix default product image livehits

### 5.1.14 (2026-04-21)
 * fix default product image CS
 * improve sendMessagesOnStatusChange
 * fix inserttag 'shopProductOutput'
 * fix redirect validation for PayPalCheckout
 * add feature 'Widerruf'

### 5.1.13 (2026-02-24)
* revision of the payment process for PayPalCheckout
* new license key
* update branding

### 5.1.12 (2025-12-16)
 * improve export-download backend
 * disable payment options (backend)

### 5.1.11 (2025-11-07)
 * add hook 'manipulateDeliveryTimeDays'

### 5.1.10 (2025-10-10)
 * fix sendRestockInfo

### 5.1.9 (2025-10-09)
 * improve product import: add event trigger for external listener

### 5.1.8 (2025-09-15)
 * improve product import: priceType & weightType
 * fix cache warmup after theme setup

### 5.1.7 (2025-08-01)
 * fix scroll to top-pagination
 * fix sitemap output
 * fix default Product Page
 * rename dynamicAttachmentPdfPaths to dynamicAttachmentPaths

### 5.1.6 (2025-07-18)
 * improve customizer public storage
 * improve UX 'Shopping cart button'
 * improve performance getPageDetailsCached
 * extend customizer DB fields

### 5.1.5 (2025-06-26)
 * improve user input for 'Steuersätze'
 * improve hook 'getImagesFromProductFolder' if empty image array
 * add new fields for product importer
 * fix crossSeller inserttag
 * add a data proxy because of the Customizer

### 5.1.4 (2025-05-16)
 * fix delete button 'Mehrere bearbeiten'
 * fix price sorting product list

### 5.1.3 (2025-05-09)
 * add hook 'validateCoupon'
 * improve product images identification
 * fix customizer instanz
 * fix darkmode backend
 * fix insertAttributeValueAllocationsInAllocationTable()

### 5.1.2 (2025-04-11)
 * add indices
 * fix LiveHits
 * add quantity comparison output for productoverview and CS

### 5.1.1 (2025-04-08)
 * fix session handling

### 5.1.0 (2025-02-04)
 * add feature productCode and alias to inserttag {{shopProductProperty::*}}, {{shopProductOutput::*}}
 * add Mailer Transport for Shopmessages
