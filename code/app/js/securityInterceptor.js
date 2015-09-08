medicineApp.config(function($httpProvider) {
  var interceptor =
    function($q, $rootScope, $location) {
        return {
            'response': function(response) {
                return response;
             },
            'responseError': function(rejection) {
                console.log("response error detected");
                if (rejection.status==401) {
                    console.log("error was 401")
                    // Modify this part to suit your needs.
                    // In my case I broadcast a message which is
                    // picked up elsewhere to show the login screen.
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
