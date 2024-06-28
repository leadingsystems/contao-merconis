(function() {
    var classdef_app = {
        obj_config: {},

        obj_references: {},

        initialize: function() {
        },

        start: function() {
            lsjs.obj_preferences.bln_activateUrlModificationInRequestCajax = true;

            new Fx.SmoothScroll({
                duration: 750
            },window);

            lsjs.__moduleHelpers.touchDetector.start({});

            lsjs.__moduleHelpers.scrollAssistant.start();

            lsjs.__moduleHelpers.stickyHeader.start({
                int_minScrollSpeedToShowSticky: 1,
                int_minScrollSpeedToHideSticky: 1,
                /*
                 * This time in ms should correspond with transition times e.g. for opening sub-navigations
                 */
                int_timeToWaitForRecalculationsAfterHeaderClickInMs: 800,
                bln_alwaysShowStickyHeader: true,
                bln_untouchEverythingInHeaderAfterHidingSticky: true,
                int_stickyStartEndDistance: 150,
                bln_debug: false
            });

            lsjs.apiInterface.str_apiUrl = lsjs.__appHelpers.merconisApp.obj_config.str_ajaxUrl;

            lsjs.__moduleHelpers.ocFlex.start({
                // el_domReference: el_domReference,
                str_ocTogglerSelector: '#off-canvas-navi-toggler',
                str_ocContainerSelector: '#off-canvas-navi-container',
                str_uniqueInstanceName: 'off-canvas-navi',
                bln_debug: true
            });

            lsjs.__moduleHelpers.ocFlex.start({
                // el_domReference: el_domReference,
                str_ocTogglerSelector: '.off-canvas-search-toggler',
                str_ocContainerSelector: '#off-canvas-search-container',
                str_uniqueInstanceName: 'off-canvas-search',
                bln_debug: true
            });

            lsjs.__moduleHelpers.ocFlex.start({
                // el_domReference: el_domReference,
                str_ocTogglerSelector: '.off-canvas-language-selector-toggler',
                str_ocContainerSelector: '#off-canvas-language-selector-container',
                str_uniqueInstanceName: 'off-canvas-language-selector',
                bln_debug: true
            });

            /*
             * The cart preview container will not exist in the DOM if we're on the cart page,
             * so we deactivate the debug output because we expect missing elements in this situation.
             */
            lsjs.__moduleHelpers.ocFlex.start({
                // el_domReference: el_domReference,
                str_ocTogglerSelector: '.off-canvas-cart-preview-toggler',
                str_ocContainerSelector: '#off-canvas-cart-preview-container',
                str_uniqueInstanceName: 'off-canvas-cart-preview',
                bln_debug: false
            });

            lsjs.__moduleHelpers.ocFlex.start({
                // el_domReference: el_domReference,
                str_ocTogglerSelector: '.off-canvas-filter-form-toggler',
                str_ocContainerSelector: '#off-canvas-filter-form-container',

                /*
                 * If the filterFormManager should be able to toggle this ocFlex, make sure to use this unique instance name
                 * in filterFormManager.start()
                 */
                str_uniqueInstanceName: 'off-canvas-filter-form',
                bln_debug: false
            });

            /*
             * Things that need to happen in the beginning (domready) and also in
             * case of a cajax_domUpdate are registered with the event and then the
             * event is fired instantly. When a cajax_domUpdate event occurs, it will
             * automatically be fired again.
             */
            window.addEvent('cajax_domUpdate', function(el_domReference) {
                lsjs.__moduleHelpers.sliderInputManager.start({
                    el_domReference: el_domReference
                });

                lsjs.__moduleHelpers.switchGallery.start({
                    el_domReference: el_domReference,
                    bln_debug: true
                });

                lsjs.__moduleHelpers.ocFlex.start({
                    el_domReference: el_domReference,
                    str_ocTogglerSelector: '.off-canvas-added-to-cart-info-toggler',
                    str_ocContainerSelector: '[id^="off-canvas-added-to-cart-info-container"]',
                    str_uniqueInstanceName: 'off-canvas-added-to-cart-info',
                    bln_debug: true
                });

                this.showAddedToCartInfoIfNecessary(el_domReference);

                this.setBottomPaginationScrollBehaviour(el_domReference);

                lsjs.__moduleHelpers.touchNaviManager.start({
                    el_domReference: el_domReference,
                    str_selector: 'nav.lscss-standard-navigation:not([class*="touch-settings"])',
                    obj_instanceOptions: {
                        bln_useTouchBehaviourOnNonTouchDevices: true
                    }
                });

                lsjs.__moduleHelpers.touchNaviManager.start({
                    el_domReference: el_domReference,
                    str_selector: 'nav.lscss-standard-navigation.touch-settings-main-navigation',
                    obj_instanceOptions: {
                        bln_useTouchBehaviourOnNonTouchDevices: true,
                        bln_allowMultipleParallelTouches: false,
                        bln_preTouchActiveAndTrailOnStart: false,
                        bln_untouchOnOutsideClick: true
                    }
                });

                lsjs.__moduleHelpers.sliderManager.start({
                    el_domReference: el_domReference,
                    str_containerSelector: '.switch-gallery-slider',
                    bln_lastSlideFilled: false,
                    bln_autoplayActive: true,
                    bln_autoplayStartInstantly: false,
                    bln_mouseDragOnNonTouchDeviceActive: false,
                    bln_dotNavigationUseImagesIfPossible: false,
                    bln_showConsoleWarnings: false,
                    int_dotNavigationMaxNumberOfSlides: 10
                });

                lsjs.__moduleHelpers.imageZoomerManager.start({
                    el_domReference: el_domReference,
                    str_containerSelector: 'a[data-lightbox]',
                    float_maxZoomFactor: 1,
                    float_zoomFactorStep: 0.1,
                    str_attributeToIdentifyGallerySets: 'data-lightbox'
                });

                lsjs.__moduleHelpers.imageZoomerManager.start({
                    el_domReference: el_domReference,
                    str_containerSelector: '.galleryContainer .lsjs-image-zoomer',
                    float_maxZoomFactor: 1,
                    float_zoomFactorStep: 0.1,
                    str_attributeToIdentifyGallerySets: 'class'
                });


                lsjs.__moduleHelpers.imageZoomerManager.start({
                    el_domReference: el_domReference,
                    float_maxZoomFactor: 1,
                    float_zoomFactorStep: 0.1
                });

                lsjs.__moduleHelpers.sliderManager.start({
                    el_domReference: el_domReference,
                    str_containerSelector: '.galleryContainer',
                    bln_lastSlideFilled: false,
                    bln_autoplayActive: false,
                    bln_autoplayStartInstantly: true,
                    bln_mouseDragOnNonTouchDeviceActive: false,
                    bln_showConsoleWarnings: false,
                    int_dotNavigationMaxNumberOfSlides: 10
                });

                lsjs.__moduleHelpers.sliderManager.start({
                    el_domReference: el_domReference,
                    str_containerSelector: '.crossSeller.slider .product-list',
                    bln_mouseDragOnNonTouchDeviceActive: false,
                    bln_showConsoleWarnings: false,
                    int_dotNavigationMaxNumberOfSlides: 5,
                    bln_dotNavigationUseImagesIfPossible: false,
                    bln_autoplayActive: false
                });


                lsjs.__moduleHelpers.sliderManager.start({
                    el_domReference: el_domReference,
                    str_containerSelector: '.ls_infoLineBox_slider',
                    bln_mouseDragOnNonTouchDeviceActive: false,
                    bln_showConsoleWarnings: false,
                    int_dotNavigationMaxNumberOfSlides: 5,
                    bln_dotNavigationUseImagesIfPossible: false,
                    bln_autoplayActive: false
                });

                /*
                 * new feature, requires LSJS ~v3.0.0-beta21
                 */
                lsjs.__moduleHelpers.submitOnChangeManager.start({
                    el_domReference: el_domReference,
                    str_selector: 'select#userSortingSelection'
                });

                lsjs.__moduleHelpers.submitOnChangeManager.start({
                    el_domReference: el_domReference,
                    str_selector: '.template_myOrders_default .sortingForm select'
                });

                /* ->
                 * reinitialize mediabox
                 */
                el_domReference.getElements('a[data-lightbox]').mediabox({
                        // Put custom options here
                    },
                    function(el) {
                        return [el.href, el.title, el.getAttribute('data-lightbox')];
                    },
                    function(el) {
                        var data = this.getAttribute('data-lightbox').split(' ');
                        return (this == el) || (data[0] && el.getAttribute('data-lightbox').match(data[0]));
                    });
                /*
                 * <-
                 */

                //auskommentiert
                //lsjs.__moduleHelpers.customerDataFormManager.start({el_domReference: el_domReference});
                //new anfang
                lsjs.__moduleHelpers.conditionalFormManager.start({el_domReference: el_domReference});
                //new ende
                lsjs.__moduleHelpers.formReviewerManager.start({el_domReference: el_domReference});

                lsjs.__moduleHelpers.statusTogglerManager.start({el_domReference: el_domReference});

                lsjs.__moduleHelpers.cajaxCallerManager.start({el_domReference: el_domReference});

                lsjs.__moduleHelpers.elementFolderManager.start({el_domReference: el_domReference});

                lsjs.__moduleHelpers.configuratorManager.start();
                lsjs.__moduleHelpers.putInCartFormManager.start();
                lsjs.__moduleHelpers.putInWatchlistFormManager.start();
                lsjs.__moduleHelpers.variantLinkerManager.start();
                this.prefillContactProductForm(el_domReference);

            }.bind(this));

            window.fireEvent('cajax_domUpdate', $$('body')[0]);

            lsjs.__moduleHelpers.filterFormManager.start({
                str_containerSelector: '.template_filterForm_new',
                bln_doNotUseAjax: false,

                /*
                 * make sure that this corresponds with the actual ocFlex instance name used in ocFlex.start()
                 */
                str_ocFlexInstanceName: 'off-canvas-filter-form'
            });

            lsjs.__moduleHelpers.liveHits.start({
                var_inputField: '#off-canvas .liveHits input[name="merconis_searchWord"]'
            });

            lsjs.__moduleHelpers.variantSelectorManager.start();

            lsjs.__moduleHelpers.productManagementApiTestManager.start();

            /*
             * TESTS ->
             */
            // lsjs.__moduleHelpers.ajaxTest.start();

            /*
             lsjs.__moduleHelpers.templateTest.start({
             str_containerSelector: '#templateTest'
             });
             */
            /*
             * <- TESTS
             */
        },

        prefillContactProductForm: function() {
            var els_formFieldsToPrefill = $$('.contact-product form input[name="code"]');
            Array.each(
                els_formFieldsToPrefill,
                function(el_formFieldToPrefill) {
                    var el_product = el_formFieldToPrefill.getParent('.shop-product');
                    if (typeOf(el_product) !== 'element') {
                        return;
                    }
                    var el_productCode = el_product.getElement('.code');
                    var el_productTitle = el_product.getElement('.productTitle');
                    var el_variantTitle = el_product.getElement('.variantTitle');
                    var str_productCode = typeOf(el_productCode) === 'element' ? el_productCode.getProperty('html') : '';
                    var str_productTitle = typeOf(el_productTitle) === 'element' ? el_productTitle.getProperty('html') : '';
                    var str_variantTitle = typeOf(el_variantTitle) === 'element' ? el_variantTitle.getProperty('html') : '';

                    var str_valueToPrefill = str_productCode + ' (' + str_productTitle + (str_variantTitle ? ', ' + str_variantTitle : '') + ')';
                    el_formFieldToPrefill.setProperty('value', str_valueToPrefill);
                }
            );
        },

        showAddedToCartInfoIfNecessary: function(el_domReference) {
            if (el_domReference.getElement('[id^="off-canvas-added-to-cart-info-container"]') !== null) {
                lsjs.__moduleHelpers.ocFlex.self['off-canvas-added-to-cart-info'].__view.toggle();
            }
        },

        /*
         * If a pagination is used with ajax, the next page is loaded and the scroll position remains unchanged.
         * This is okay for the top pagination but if the bottom pagination is used, the page should scroll to
         * the top of content that has been changed.
         */
        setBottomPaginationScrollBehaviour: function(el_domReference) {
            var el_bottomPagination = el_domReference.getElement('.bottom-pagination');
            if (typeOf(el_bottomPagination) !== 'element') {
                return;
            }

            var el_topPagination = el_domReference.getElement('.top-pagination');
            if (typeOf(el_topPagination) !== 'element') {
                return;
            }

            var obj_scroll = new Fx.Scroll($$('body')[0]);

            var int_scrollTargetPositionY = el_topPagination.getPosition().y - (window.innerHeight / 2);
            if (int_scrollTargetPositionY < 0) {
                int_scrollTargetPositionY = 0;
            }

            el_bottomPagination.getElements('a').addEvent(
                'click',
                function() {
                    if (window.scrollY < int_scrollTargetPositionY) {
                        /*
                         * If the current scroll position is already closer to the top than the target scroll position,
                         * we don't scroll.
                         */
                        return;
                    }
                    obj_scroll.start(0, int_scrollTargetPositionY);
                }
            )
        }

    };

    var class_app = new Class(classdef_app);

    window.addEvent('domready', function() {
        lsjs.__appHelpers.merconisApp = new class_app();
    });
})();