/*!
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

(function () {
    angular.module('piwikApp.service').factory('piwikApi', piwikApiService);

    piwikApiService.$inject = ['$http', '$q', '$rootScope', 'piwik', '$window', 'piwikUrl'];

    /**
     * @deprecated use AjaxHelper's promise API instead
     */
    function piwikApiService ($http, $q, $rootScope, piwik, $window, piwikUrl) {

        var url = 'index.php';
        var format = 'json';
        var getParams  = {};
        var postParams = {};
        var allRequests = [];

        /**
         * Adds params to the request.
         * If params are given more then once, the latest given value is used for the request
         *
         * @param {object}  params
         * @return {void}
         */
        function addParams (params) {
            if (typeof params == 'string') {
                params = piwik.broadcast.getValuesFromUrl(params);
            }

            var arrayParams = ['compareSegments', 'comparePeriods', 'compareDates'];

            for (var key in params) {
                if (arrayParams.indexOf(key) !== -1
                    && !params[key]
                ) {
                    continue;
                }

                getParams[key] = params[key];
            }
        }

        function withTokenInUrl()
        {
            postParams['token_auth'] = piwik.token_auth;
            // When viewing a widgetized report there won't be any session that can be used, so don't force session usage
            postParams['force_api_session'] = piwik.broadcast.isWidgetizeRequestWithoutSession() ? 0 : 1;
        }

        function isRequestToApiMethod() {
            return getParams && getParams['module'] === 'API' && getParams['method'];
        }

        function reset () {
            getParams  = {};
            postParams = {};
        }

        function isErrorResponse(response) {
            return response && angular.isObject(response) && response.result == 'error';
        }

        function createResponseErrorNotification(response, options) {
            if (response.message
                && options.createErrorNotification
            ) {
                var UI = require('piwik/UI');
                var notification = new UI.Notification();
                notification.show(response.message, {
                    context: 'error',
                    type: 'toast',
                    id: 'ajaxHelper',
                    placeat: options.placeat
                });
                setTimeout(function () {
                    // give some time for angular to render it
                    notification.scrollToNotification();
                }, 100);
            }
        }

        /**
         * Send the request
         * @return $promise
         */
        function send (options) {
            if (!options) {
                options = {};
            }

            if (options.createErrorNotification === undefined) {
                options.createErrorNotification = true;
            }

            function onSuccess(response)
            {
                var headers = response.headers;
                response = response.data;

                if (!angular.isDefined(response) || response === null) {
                    return $q.reject(null);
                } else if (isErrorResponse(response)) {
                    createResponseErrorNotification(response, options);

                    return $q.reject(response.message || null);
                } else {
                    return options.includeHeaders ? { headers: headers, response: response } : response;
                }
            }

            function onError(response)
            {
                var message = 'Something went wrong';
                if (response && (response.status === 0 || response.status === -1)) {
                    message = 'Request was possibly aborted';
                }

                return $q.reject(message);
            }

            var deferred = $q.defer(),
                requestPromise = deferred.promise;

            var headers = {
                'Content-Type': 'application/x-www-form-urlencoded',
                // ie 8,9,10 caches ajax requests, prevent this
                'cache-control': 'no-cache'
            };

            var requestFormat = format;
            if (getParams.format && getParams.format.toLowerCase() !== 'json' && getParams.format.toLowerCase() !== 'json') {
                requestFormat = getParams.format;
            }

            var ajaxCall = {
                method: 'POST',
                url: url + '?' + $.param(mixinDefaultGetParams(getParams)),
                responseType: requestFormat,
                data: $.param(getPostParams(postParams)),
                timeout: requestPromise,
                headers: headers
            };

            var promise = $http(ajaxCall).then(onSuccess, onError);

            // we can't modify requestPromise directly and add an abort method since for some reason it gets
            // removed after then/finally/catch is called.
            var addAbortMethod = function (to, deferred) {
                return {
                    then: function () {
                        return addAbortMethod(to.then.apply(to, arguments), deferred);
                    },

                    'finally': function () {
                        return addAbortMethod(to.finally.apply(to, arguments), deferred);
                    },

                    'catch': function () {
                        return addAbortMethod(to.catch.apply(to, arguments), deferred);
                    },

                    abort: function () {
                        deferred.resolve();
                        return this;
                    }
                };
            };

            var request = addAbortMethod(promise, deferred);

            allRequests.push(request);
            return request.finally(function() {
                var index = allRequests.indexOf(request);
                if (index !== -1) {
                    allRequests.splice(index, 1);
                }
            });
        }

        /**
         * Get the parameters to send as POST
         *
         * @param {object}   params   parameter object
         * @return {object}
         * @private
         */
        function getPostParams (params) {
            if (isRequestToApiMethod() || piwik.shouldPropagateTokenAuth) {
                params.token_auth = piwik.token_auth;
                // When viewing a widgetized report there won't be any session that can be used, so don't force session usage
                params.force_api_session = piwik.broadcast.isWidgetizeRequestWithoutSession() ? 0 : 1;
            }

            return params;
        }

        /**
         * Mixin the default parameters to send as GET
         *
         * @param {object}   getParamsToMixin   parameter object
         * @return {object}
         * @private
         */
        function mixinDefaultGetParams (getParamsToMixin) {
            // we have to decode the value manually because broadcast will not decode anything itself. if we don't,
            // angular will encode it again before sending the value in an HTTP request.
            var segment = piwikUrl.getSearchParam('segment');
            if (segment) {
                segment = decodeURIComponent(segment);
            }

            var defaultParams = {
                idSite:  piwik.idSite || piwikUrl.getSearchParam('idSite'),
                period:  piwik.period || piwikUrl.getSearchParam('period'),
                segment: segment
            };

            // never append token_auth to url
            if (getParamsToMixin.token_auth) {
                getParamsToMixin.token_auth = null;
                delete getParamsToMixin.token_auth;
            }

            for (var key in defaultParams) {
                if (!(key in getParamsToMixin) && !(key in postParams) && defaultParams[key]) {
                    getParamsToMixin[key] = defaultParams[key];
                }
            }

            // handle default date & period if not already set
            if (!getParamsToMixin.date && !postParams.date) {
                getParamsToMixin.date = piwik.currentDateString;
            }

            return getParamsToMixin;
        }

        function abortAll() {
            reset();

            allRequests.forEach(function (request) {
                request.abort();
            });

            allRequests = [];
        }

        function abort () {
            abortAll();
        }

        /**
         * Perform a reading API request.
         * @param getParams
         */
        function fetch (getParams, options) {

            getParams.module = getParams.module || 'API';

            if (!getParams.format) {
                getParams.format = 'JSON';
            }

            addParams(getParams);

            var promise = send(options);

            reset();

            return promise;
        }

        function post(getParams, _postParams_, options) {
            if (_postParams_) {
                if (postParams && postParams.token_auth && !_postParams_.token_auth) {
                    _postParams_.token_auth = postParams.token_auth;
                    // When viewing a widgetized report there won't be any session that can be used, so don't force session usage
                    _postParams_.force_api_session = piwik.broadcast.isWidgetizeRequestWithoutSession() ? 0 : 1;
                }
                postParams = _postParams_;
            }

            return fetch(getParams, options);
        }

        function addPostParams(_postParams_) {
            if (_postParams_) {
                angular.merge(postParams, _postParams_);
            }
        }

        /**
         * Convenience method that will perform a bulk request using Piwik's API.getBulkRequest method.
         * Bulk requests allow you to execute multiple Piwik requests with one HTTP request.
         *
         * @param {object[]} requests
         * @param {object} options
         * @return {HttpPromise} a promise that is resolved when the request finishes. The argument passed
         *                       to the .then(...) callback will be an array with one element per request
         *                       made.
         */
        function bulkFetch(requests, options) {
            var bulkApiRequestParams = {
                urls: requests.map(function (requestObj) { return '?' + $.param(requestObj); })
            };

            var deferred = $q.defer(),
                requestPromise = post({method: "API.getBulkRequest"}, bulkApiRequestParams, options).then(function (response) {
                    if (!(response instanceof Array)) {
                        response = [response];
                    }

                    // check for errors
                    for (var i = 0; i != response.length; ++i) {
                        var specificResponse = response[i];

                        if (isErrorResponse(specificResponse)) {
                            deferred.reject(specificResponse.message || null);

                            createResponseErrorNotification(specificResponse, options || {});

                            return;
                        }
                    }

                    deferred.resolve(response);
                }).catch(function () {
                    deferred.reject.apply(deferred, arguments);
                });

            return deferred.promise;
        }

        return {
            withTokenInUrl: withTokenInUrl, // technically should probably be called withTokenInPost
            bulkFetch: bulkFetch,
            post: post,
            fetch: fetch,
            addPostParams: addPostParams,
            abort: abort,
            abortAll: abortAll,
            mixinDefaultGetParams: mixinDefaultGetParams
        };
    }
})();
