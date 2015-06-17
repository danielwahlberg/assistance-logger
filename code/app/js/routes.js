medicineApp.config(['$routeProvider',
  function($routeProvider) {
    $routeProvider.
      when('/start', {
        templateUrl: 'app/partials/start.html'
      }).      
      when('/logMedicine', {
        templateUrl: 'app/partials/create-log.html',
        controller: 'CreateLogCtrl'
      }).
      when('/logFood', {
        templateUrl: 'app/partials/log-food.html',
        controller: 'FoodCtrl'
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