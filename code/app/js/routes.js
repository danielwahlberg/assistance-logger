medicineApp.config(['$routeProvider',
  function($routeProvider) {
    $routeProvider.
      when('/start', {
        templateUrl: 'app/partials/start.html'
      }).      
      when('/createLog', {
        templateUrl: 'app/partials/create-log.html',
        controller: 'CreateLogCtrl'
      }).
      when('/viewLog', {
        templateUrl: 'app/partials/view-log-list.html',
        controller: 'ViewLogListCtrl'
      }).
      when('/viewLog/:logId', {
        templateUrl: 'app/partials/view-log.html',
        controller: 'ViewLogCtrl'
      }).
      otherwise({
        redirectTo: '/start'
      });
  }]);