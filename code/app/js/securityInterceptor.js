medicineApp.config(function($httpProvider, AUTH_EVENTS) {
  var interceptor =
    function($q, $rootScope, $location) {
        return {
            'request': function(config) {
                if (true) { // TODO Use local storage to check if session token exist (to put in auth service?)
                    config.headers['x-session-token'] = 'daniels-test-token';
                  }
                return config;
            },
            'response': function(response) {
                return response;
             },
            'responseError': function(rejection) {
                if (rejection.status==401) {
                    // Response from backend was 401 ("Unauthorized"); redirect to login page in case we're not already there
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
