medicineApp.config(function($httpProvider, AUTH_EVENTS) {
  var interceptor =
    function($q, $rootScope, $location, $injector) {
        return {
            'request': function(config) {
                var AuthService = $injector.get('AuthService');
                if (AuthService.getAuthToken() != null) {
                    // Add custom HTTP header "x-session-token" to request, if a token is stored locally
                    config.headers['x-session-token'] = AuthService.getAuthToken();
                }
                return config;
            },
            'response': function(response) {
                return response;
             },
            'responseError': function(rejection) {
                if (rejection.status==401) {
                    // Response from backend was 401 ("Unauthorized"); redirect to login page in case we're not already there
                    console.log('Tried to access a restricted resource');
                    if (!rejection.config.url.endsWith('/login'))
                    {
                        $rootScope.$broadcast('not-auth-event');
                        $location.path('/login');
                    }
                }

                return $q.reject(rejection)
             }
        }
    };
    $httpProvider.interceptors.push(interceptor);
    });
