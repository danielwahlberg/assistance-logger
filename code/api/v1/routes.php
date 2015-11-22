<?php
/**
 * Definitions of routes used in the application
 */

  $app->foodService = new FoodService();
  $app->medicineService = new MedicineService();
  $app->assistantService = new AssistantService();
  $app->eventService = new EventService();

  $app->get('/hello/:name', function ($name) {
      echo "Hello, $name";
  });


  //
  // Security
  //
  $app->post('/login/', function () use ($app) {
    $arrInput = $app->request->getBody();
    $loginResult = SecurityService::login($arrInput['username'], $arrInput['password']);
    echo json_encode($loginResult);
  });

  $app->post('/logout/', function ()  {
      SecurityService::logout();
    });

  $app->get('/login/generatePassword/:password', function ($password) {
    echo SecurityService::getPasswordHash($password);
  });

  $app->post('/sign-up/', function () use ($app) {
    $arrInput = $app->request->getBody();
    $security = new SecurityService();
    $signupResult = $security->signUp($arrInput);
    echo json_encode($signupResult);
  });

  //
  // Medication and assistant
  //

  $app->get('/assistants/', $authenticateForRole('assistant'), function() use ($app){
    $data = $app->assistantService->getActiveAssistants();
    echo json_encode($data);
  });

  $app->get('/medicines/all/', $authenticateForRole('assistant'), function() use ($app){
    $data = $app->medicineService->getAllMedicines();
    echo json_encode($data);
  });

  $app->get('/medicines/:forDate', $authenticateForRole('assistant'), function($forDate) use ($app){
    $data = $app->medicineService->getMedicineList($forDate);
    echo json_encode($data);
  });

  $app->post('/medication/',  $authenticateForRole('assistant'), function() use ($app){
    $arrInput = $app->request()->getBody();
    $app->medicineService->storeMedication($arrInput);
  });

  $app->post('/medicines',  $authenticateForRole('patientAdmin'), function() use ($app){
    $arrInput = $app->request()->getBody();
    $app->medicineService->storeMedicines($arrInput);
  });

  $app->delete('/medicines/:medicineId',  $authenticateForRole('patientAdmin'), function($medicineId) use ($app){
    $app->medicineService->inactivateMedicine($medicineId);
  });

  $app->get('/medicines/whenNeededMedicines/:forDate', $authenticateForRole('assistant'), function($forDate) use ($app){
    $medicationList = $app->medicineService->getWhenNeededMedicationList($forDate);
    echo json_encode($medicationList);
  });

  /** Retrieve logged medication given because it was needed (not given regularly) */
  $app->get('/medicines/whenNeededLog/:forDate', $authenticateForRole('assistant'), function($forDate) use ($app){
  	$data = $app->medicineService->getLoggedWhenNeededMedication($forDate);
    echo json_encode($data);
  });

  $app->post('/medicines/storeDoses', $authenticateForRole('patientAdmin'), function() use ($app) {
    $arrInput = $app->request()->getBody();
    $createdDoseId = $app->medicineService->storeDoseChange($arrInput);
    echo json_encode($createdDoseId);
  });


  //
  // Food
  //
  $app->get('/food/foodTypes/', $authenticateForRole('assistant'), function() use ($app){
    $data = $app->foodService->getFoodTypes();
    echo json_encode($data);
  });

  $app->get('/food/feeding/', $authenticateForRole('assistant'),  function() use ($app) {
      $data = $app->foodService->getLoggedFeeding();
      echo json_encode($data);
  });

  $app->get('/food/feeding/statistics/', $authenticateForRole('assistant'),  function() use ($app) {
      $data = $app->foodService->getFeedingStatistics();
      echo json_encode($data);
  });

  $app->post('/food/feeding/', $authenticateForRole('assistant'), function() use ($app){
    $arrInput = $app->request()->getBody();
    $createdId = $app->foodService->storeFeeding($arrInput);
    echo json_encode($createdId);
  });

  $app->delete('/food/feeding/:id', $authenticateForRole('assistant'), function($id) use ($app){
    $app->foodService->deleteFeeding($id);
  });

  //
  // Events
  //
  $app->get('/event/:forDate', $authenticateForRole('assistant'), function($givenDate) use ($app){
  	$data = $app->eventService->getEventsFor($givenDate);
    echo json_encode($data);
  });

  $app->post('/event/', $authenticateForRole('assistant'), function() use ($app){
    $arrInput = $app->request()->getBody(); // Note temporary simple function below which use the same $arrInput structure
  	$createdId = $app->eventService->storeEvent($arrInput);
    echo json_encode($createdId);
  });

  /** Temporary, unsecured, service for registering a hard coded event */
  $app->post('/event/createHardCoded', function() use ($app){
    // Hard code input, to use from embryo event registerer app

    $arrInput =
      array(
        'eventType' => array('id'=>2), // Large epilepsy seizure
        'assistant' => array('id'=>13), // "Assistant at home"
        'duration' => null,
        'description' => 'Anfall registrerat med knapptryckning'
      );
    $createdId = $app->eventService->storeEvent($arrInput);
    echo json_encode($createdId);
  });

  $app->get('/event/types/', $authenticateForRole('assistant'), function() use ($app){
  	$data = $app->eventService->getEventTypes();
    echo json_encode($data);
  });

?>
