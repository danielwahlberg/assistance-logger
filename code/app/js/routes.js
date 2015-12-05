medicineApp.config(['$routeProvider',
  function($routeProvider) {
    $routeProvider.
      when('/start', {
        templateUrl: 'app/partials/start.html'
      }).
      when('/logMedicine', {
        templateUrl: 'app/partials/medicine-log.html',
        controller: 'MedicineLogCtrl'
      }).
      when('/logFood', {
        templateUrl: 'app/partials/log-food.html',
        controller: 'FoodCtrl'
      }).
      when('/logEvent', {
        templateUrl: 'app/partials/log-event.html',
        controller: 'EventCtrl'
      }).
      when('/foodStatistics', {
        templateUrl: 'app/partials/food-statistics.html',
        controller: 'FoodStatisticsCtrl'
      }).
      when('/viewLog', {
        templateUrl: 'app/partials/view-log-list.html',
        controller: 'ViewLogListCtrl'
      }).
      when('/viewLog/:logId', {
        templateUrl: 'app/partials/view-log.html',
        controller: 'ViewLogCtrl'
      }).
      when('/admin/medicines', {
        templateUrl: 'app/partials/admin-medicines.html',
        controller: 'AdminMainCtrl'
      }).
      when('/sign-up', {
        templateUrl: 'app/partials/register-user.html',
        controller: 'SignUpCtrl'
      }).
      when('/login', {
        templateUrl: 'app/partials/login.html',
        controller: 'LoginCtrl'
      }).
      when('/logout', {
        templateUrl: 'app/partials/login.html',
        controller: 'LogoutCtrl'
      }).
      otherwise({
        redirectTo: '/start'
      });
  }]);
