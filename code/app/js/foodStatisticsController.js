medicineApp.controller('FoodStatisticsCtrl', function ($scope, $http, $modal, $log) {
/*
  $scope.days = [
    {"date": "2015-10-06",
     "sumAmount": "222",
     "sond": "111",
     "mat": "222",
     "dryck": "333"
   },
   {"date": "2015-10-07",
    "sumAmount": "223",
    "sond": "111",
    "mat": "222",
    "dryck": "333"
  },
  {"date": "2015-10-08",
   "sumAmount": "224",
   "sond": "111",
   "mat": "222",
   "dryck": "333"
  }
  ];
*/
  // Retrieve statistical data
  $http.get('/api/v1/food/feeding/statistics/').success(function(data) {
    $scope.days = data;

  });

});
