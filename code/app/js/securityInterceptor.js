medicineApp.config(function($httpProvider) {
  var interceptor =
    function($q, $rootScope, $location) {
        return {
            'response': function(response) {
                return response;
             },
            'responseError': function(rejection) {
                if (rejection.status==401) {
                    // Response from backend was 401 ("Unauthorized"); redirect to login page in case we're not already there
                    if (!rejection.config.url.endsWith('/login'))
                    {
                        $location.path('/login');
                        $rootScope.$broadcast('auth:loginRequired');
                    }
                }

                return $q.reject(rejection)
             }
        }
    };
    $httpProvider.interceptors.push(interceptor);
    });
