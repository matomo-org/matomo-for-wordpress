/**
 * Matomo - free/libre analytics platform
 *
 * Visitor profile popup control.
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

(function ($, require) {

    var piwik = require('piwik'),
        exports = require('piwik/UI'),
        UIControl = exports.UIControl;

    /**
     * Sets up and handles events for the visitor profile popup.
     *
     * @param {Element} element The HTML element returned by the Live.getVisitorLog controller
     *                          action. Should have the CSS class 'visitor-profile'.
     * @constructor
     */
    var VisitorProfileControl = function (element) {
        UIControl.call(this, element);
        this._setupControl();
        this._bindEventCallbacks();
    };

    /**
     * Initializes all elements w/ the .visitor-profile CSS class as visitor profile popups,
     * if the element has not already been initialized.
     */
    VisitorProfileControl.initElements = function () {
        UIControl.initElements(this, '.visitor-profile');
    };

    /**
     * Shows the visitor profile popover for a visitor ID. This should not be called directly.
     * Instead broadcast.propagateNewPopoverParameter('visitorProfile', visitorId) should be
     * called. This would make sure the popover would be opened if the URL is copied and pasted
     * in a new tab/window.
     *
     * @param {String} visitorId The string visitor ID.
     * @param {String} idSite The ID of the site.
     */
    VisitorProfileControl.showPopover = function (visitorId, idSite) {

        if (!piwik.visitorProfileEnabled) {
            console.error('Visitor Profile was disabled in website settings');
            return;
        }

        var url = 'module=Live&action=getVisitorProfilePopup&visitorId=' + encodeURIComponent(visitorId);
        if (idSite) {
            url += '&idSite=' + idSite;
        }

        // if there is already a map shown on the screen, do not show the map in the popup. kartograph seems
        // to only support showing one map at a time.
        if ($('.RealTimeMap').length > 0) {
            url += '&showMap=0';
        }

        var ajaxRequest = new ajaxHelper();
        ajaxRequest.removeDefaultParameter('segment');

        Piwik_Popover.createPopupAndLoadUrl(url, _pk_translate('Live_VisitorProfile'), 'visitor-profile-popup', ajaxRequest);
    };

    $.extend(VisitorProfileControl.prototype, UIControl.prototype, {

        _setupControl: function () {
            // focus the popup so it will accept key events
            this.$element.focus();
        },

        _bindEventCallbacks: function () {
            var self = this,
                $element = this.$element;

            // if there are no popover params in stack, simply close the popover
            if (!broadcast.popoverParamStack.length) {
                $element.on('click', '.visitor-profile-close', function (e) {
                    e.preventDefault();
                    try {
                        $element.tooltip('destroy');
                    } catch (e) {
                    }
                    Piwik_Popover.close();
                });
            } else {
                $element.on('click', '.visitor-profile-close', function (e) {
                    e.preventDefault();
                    try {
                        $element.tooltip('destroy');
                    } catch (e) {
                    }
                    broadcast.propagateNewPopoverParameter(false);
                    return false;
                });
            }

            $element.on('click', '.visitor-profile-toggle-actions', function (e) {
                e.preventDefault();
                $(this).toggleClass('minimized');
                if ($(this).hasClass('minimized')) {
                    $('.visitor-profile-actions', $element).slideUp();
                } else {
                    $('.visitor-profile-actions', $element).slideDown();
                }
                return false;
            });

            $element.on('click', '.visitor-profile-more-info>a', function (e) {
                e.preventDefault();
                self._loadMoreVisits();
                return false;
            });

            $element.on('click', '.visitor-profile-visit-title', function () {
               $('.visitor-profile-visit-details-extended', $(this).parents('li')).slideToggle();
            });

            $element.on('click', '.visitor-profile-show-actions', function () {
                $('.visitor-profile-actions', $(this).parents('li')).slideToggle();
                return false;
            });

            $element.on('click', '.visitor-profile-prev-visitor', function (e) {
                e.preventDefault();
                self._loadPreviousVisitor();
                return false;
            });

            $element.on('click', '.visitor-profile-next-visitor', function (e) {
                e.preventDefault();
                self._loadNextVisitor();
                return false;
            });

            $element.on('keydown', function (e) {
                if (e.which == 37 && !e.altKey) { // on <- key press, load previous visitor
                    self._loadPreviousVisitor();
                } else if (e.which == 39 && !e.altKey) { // on -> key press, load next visitor
                    self._loadNextVisitor();
                }
            });

            $element.on('click', '.visitor-profile-show-map', function (e) {
                e.preventDefault();
                self.toggleMap();
                return false;
            });

            // append token_auth dynamically to export link
            $element.on('mousedown', '.visitor-profile-export', function (e) {
                var url = $(this).attr('href');
                if (url.indexOf('&token_auth=') == -1) {
                    if (!piwik.broadcast.isWidgetizeRequestWithoutSession()) {
                        url += '&force_api_session=1';
                    }
                    $(this).attr('href', url + '&token_auth=' + piwik.token_auth);
                }
            });

            initializeVisitorActions($element);
        },

        toggleMap: function () {
            var $element = this.$element,
                $map = $('.visitor-profile-map', $element);
            if (!$map.children().length) { // if the map hasn't been loaded, load it
                this._loadMap($map);
                return;
            }

            if ($map.is(':hidden')) { // show the map if it is hidden
                if ($map.height() < 1) {
                    $map.resize();
                }

                $map.slideDown('slow');
                var newLabel = 'Live_HideMap';

                piwikHelper.lazyScrollTo($('.visitor-profile-location', $element)[0], 400);
            } else { // hide the map if it is shown
                $map.slideUp('slow');
                var newLabel = 'Live_ShowMap';
            }

            newLabel = _pk_translate(newLabel).replace(' ', '\xA0');
            $('.visitor-profile-show-map', $element).text('(' + newLabel + ')');
        },

        _loadMap: function ($map) {
            var self = this;

            var ajax = new ajaxHelper();
            ajax.setUrl($map.attr('data-href'));
            ajax.setCallback(function (response) {
               $map.html(response);
               self.toggleMap();
            });
            ajax.setFormat('html');
            ajax.setLoadingElement($('.visitor-profile-location > p > .loadingPiwik', self.$element));
            ajax.send();
        },

        _loadMoreVisits: function () {
            var self = this,
                $element = this.$element;

            var loading = $('.visitor-profile-more-info > .loadingPiwik', $element);
            loading.show();

            var ajax = new ajaxHelper();
            ajax.removeDefaultParameter('segment');
            ajax.addParams({
                module: 'Live',
                action: 'getVisitList',
                period: '',
                date: '',
                visitorId: $element.attr('data-visitor-id'),
                filter_offset: $('.visitor-profile-visits>li', $element).length,
                start_number: $('.visitor-profile-visits>li:last', $element).data('number') - 1
            }, 'GET');
            ajax.setCallback(function (response) {
                if (response == "") { // no more visits left
                    self._showNoMoreVisitsSpan();
                } else {
                    response = $(response);
                    loading.hide();

                    $('.visitor-profile-visits', $element).append(response);
                    if (response.filter('li').length < 10) {
                        self._showNoMoreVisitsSpan();
                    }

                    var numbers = $('[data-number]', $element);
                    var max = $(numbers[0]).attr('data-number');

                    if (numbers.length > max) {
                        var counter = numbers.length;
                        numbers.each(function() {
                            $(this).attr('data-number', counter);
                            $('.counter', $(this)).text(counter);
                            counter--;
                        });
                    }

                    piwikHelper.lazyScrollTo($(response)[0], 400, true);
                }
            });
            ajax.setFormat('html');
            ajax.send();
        },

        _showNoMoreVisitsSpan: function () {
            var noMoreSpan = $('<span/>').text(_pk_translate('Live_NoMoreVisits')).addClass('visitor-profile-no-visits');
            $('.visitor-profile-more-info', this.$element).html(noMoreSpan);
        },

        _loadPreviousVisitor: function () {
            this._gotoAdjacentVisitor(this.$element.attr('data-prev-visitor'));
        },

        _loadNextVisitor: function () {
            this._gotoAdjacentVisitor(this.$element.attr('data-next-visitor'));
        },

        _gotoAdjacentVisitor: function (idVisitor) {
            if (!idVisitor) {
                return;
            }

            if (this._inPopover()) {
                broadcast.propagateNewPopoverParameter('visitorProfile', idVisitor);
            } else if (this._inWidget()) {
                this.$element.closest('[widgetid]').dashboardWidget('reload', false, true, {visitorId: idVisitor});
            }
        },

        _getFirstVisitId: function () {
            return $('.visitor-profile-visits>li:first-child>h2', this.$element).attr('data-idvisit');
        },

        _inPopover: function () {
            return !! this.$element.closest('#Piwik_Popover').length;
        },

        _inWidget: function () {
            return !! this.$element.closest('.widget').length;
        }
    });

    exports.VisitorProfileControl = VisitorProfileControl;

    // add the popup handler that creates a visitor profile
    broadcast.addPopoverHandler('visitorProfile', VisitorProfileControl.showPopover);

})(jQuery, require);
